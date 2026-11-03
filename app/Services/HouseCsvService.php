<?php

namespace App\Services;

use App\Models\House;
use App\Models\HouseImport;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HouseCsvService
{
    public const COLUMNS = ['code', 'address', 'occupancy', 'owner_name', 'occupant_name', 'vacant_billing', 'vacant_amount', 'notes'];

    public const MAX_ROWS = 1000;

    private function normalize(array $raw, ?House $existing): array
    {
        $row = array_fill_keys(self::COLUMNS, '');
        if ($existing) {
            foreach (self::COLUMNS as $key) {
                $row[$key] = (string) ($existing->$key ?? '');
            }
            $row['vacant_amount'] = $existing->vacant_amount !== null ? (string) (int) $existing->vacant_amount : '';
        }
        foreach (array_intersect_key($raw, $row) as $key => $value) {
            $row[$key] = trim((string) $value);
        }
        $row['code'] = mb_strtoupper($row['code']);
        $occupancies = array_change_key_case(array_flip(House::OCCUPANCIES), CASE_LOWER);
        $billing = array_change_key_case(array_flip(House::BILLING), CASE_LOWER);
        $row['occupancy'] = $occupancies[mb_strtolower($row['occupancy'])] ?? mb_strtolower($row['occupancy']);
        $row['vacant_billing'] = $row['vacant_billing'] === '' ? 'full' : ($billing[mb_strtolower($row['vacant_billing'])] ?? mb_strtolower($row['vacant_billing']));
        foreach (['owner_name', 'occupant_name', 'vacant_amount', 'notes'] as $key) {
            if ($row[$key] === '') {
                $row[$key] = null;
            }
        }

        $row['_vacancy_provided'] = array_key_exists('vacant_billing', $raw) || array_key_exists('vacant_amount', $raw);

        return $row;
    }

    public function errors(array $row, User $actor): array
    {
        $messages = Validator::make($row, [
            'code' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:1000'],
            'occupancy' => ['required', Rule::in(array_keys(House::OCCUPANCIES))],
            'owner_name' => ['nullable', 'string', 'max:255'], 'occupant_name' => ['nullable', 'string', 'max:255'],
            'vacant_billing' => ['required', Rule::in(array_keys(House::BILLING))],
            'vacant_amount' => ['nullable', 'required_if:vacant_billing,custom', 'integer', 'min:1', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'occupancy.in' => 'Kondisi harus Dihuni, Dikontrakkan, Kosong, atau Renovasi.',
            'vacant_billing.in' => 'Aturan harus Tarif penuh, Nominal khusus, atau Bebas iuran baru.',
            'vacant_amount.integer' => 'Nominal harus angka rupiah bulat tanpa Rp, titik, atau koma.',
            'vacant_amount.required_if' => 'Nominal wajib diisi untuk aturan Nominal khusus.',
        ])->errors()->all();
        if ($row['occupancy'] !== 'vacant' && ($row['_vacancy_provided'] ?? true) && ($row['vacant_billing'] !== 'full' || $row['vacant_amount'] !== null)) {
            $messages[] = 'Nominal khusus/bebas iuran hanya berlaku pada kondisi Kosong. Gunakan Tarif penuh dan kosongkan nominal untuk kondisi lain.';
        }
        if ($row['vacant_billing'] !== 'custom' && $row['vacant_amount'] !== null) {
            $messages[] = 'Kolom vacant_amount harus kosong kecuali aturan Nominal khusus dipilih.';
        }

        return $messages;
    }

    public function preview(string $path, User $actor): array
    {
        $stream = fopen($path, 'r');
        try {
            $first = fgets($stream);
            if ($first === false || ! mb_check_encoding($first, 'UTF-8')) {
                throw ValidationException::withMessages(['csv' => 'CSV harus berisi header UTF-8.']);
            }
            $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
            $separator = count(str_getcsv($first, ';', '"', '')) > count(str_getcsv($first, ',', '"', '')) ? ';' : ',';
            $header = array_map(fn ($value) => mb_strtolower(trim((string) $value)), str_getcsv($first, $separator, '"', ''));
            if (count($header) !== count(array_unique($header)) || array_diff(['code', 'address', 'occupancy'], $header) || array_diff($header, self::COLUMNS)) {
                throw ValidationException::withMessages(['csv' => 'Header wajib: code, address, occupancy. Kolom lain hanya boleh mengikuti template.']);
            }
            $rows = [];
            $preview = [];
            $importErrors = [];
            $seen = [];
            $line = 1;
            while (($values = fgetcsv($stream, 0, $separator, '"', '')) !== false) {
                $line++;
                if ($values === [null] || count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }
                if (count($rows) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages(['csv' => 'Maksimal '.self::MAX_ROWS.' rumah per file.']);
                }
                if (count($values) !== count($header) || ! mb_check_encoding(implode('', $values), 'UTF-8')) {
                    throw ValidationException::withMessages(['csv' => "Jumlah kolom atau encoding tidak valid pada baris {$line}."]);
                }
                $raw = array_combine($header, $values);
                $existing = House::where('housing_id', $actor->housing_id)->where('code', mb_strtoupper(trim((string) $raw['code'])))->first();
                $row = $this->normalize($raw, $existing);
                $row['_target_id'] = $existing?->id;
                $row['_version'] = $this->fingerprint($existing);
                $errors = $this->errors($row, $actor);
                if (isset($seen[$row['code']])) {
                    $errors[] = 'Kode rumah berulang pada file (huruf besar/kecil dianggap sama).';
                }
                $seen[$row['code']] = true;
                if ($errors) {
                    $importErrors[$line] = $errors;
                }
                $preview[] = ['line' => $line, 'action' => $existing ? 'Perbarui' : 'Baru'] + array_intersect_key($row, array_flip(self::COLUMNS));
                $rows[] = $row;
            }
            if (! $rows) {
                throw ValidationException::withMessages(['csv' => 'CSV tidak berisi data rumah.']);
            }
            $batch = null;
            if (! $importErrors) {
                $batch = HouseImport::create([
                    'id' => (string) Str::uuid(), 'housing_id' => $actor->housing_id, 'user_id' => $actor->id, 'rows' => $rows, 'expires_at' => now()->addMinutes(30),
                ]);
            }

            return compact('preview', 'importErrors', 'batch');
        } finally {
            fclose($stream);
        }
    }

    private function fingerprint(?House $house): ?string
    {
        return $house ? hash('sha256', json_encode($house->only($house->getFillable()), JSON_THROW_ON_ERROR)) : null;
    }

    public function confirm(string $id, User $actor): array
    {
        try {
            return DB::transaction(function () use ($id, $actor) {
                $batch = HouseImport::where('housing_id', $actor->housing_id)->where('user_id', $actor->id)->lockForUpdate()->findOrFail($id);
                if ($batch->completed_at) {
                    return ['created' => 0, 'updated' => 0, 'replayed' => true];
                }
                if ($batch->expires_at->isPast()) {
                    throw ValidationException::withMessages(['csv' => 'Pratinjau kedaluwarsa. Unggah ulang CSV rumah.']);
                }
                $rows = $batch->rows;
                $result = ['created' => 0, 'updated' => 0, 'replayed' => false];
                foreach ($rows as $index => $row) {
                    if (! array_key_exists('_target_id', $row) || ! array_key_exists('_version', $row)) {
                        throw ValidationException::withMessages(['csv' => 'Format pratinjau sudah berubah. Unggah ulang CSV rumah.']);
                    }
                    if ($errors = $this->errors($row, $actor)) {
                        throw ValidationException::withMessages(['csv' => 'Baris data '.($index + 1).': '.implode(' ', $errors)]);
                    }
                    $existing = House::where('housing_id', $actor->housing_id)->where('code', $row['code'])->lockForUpdate()->first();
                    if ($existing?->id !== ($row['_target_id'] ?? null) || $this->fingerprint($existing) !== ($row['_version'] ?? null)) {
                        throw ValidationException::withMessages(['csv' => 'Data rumah berubah sejak pratinjau. Tidak ada perubahan yang disimpan. Unggah ulang CSV.']);
                    }
                    $data = array_intersect_key($row, array_flip(self::COLUMNS));
                    app(HouseService::class)->save($existing ?? new House, $data, $actor);
                    $result[$existing ? 'updated' : 'created']++;

                }
                $batch->update(['completed_at' => now(), 'rows' => []]);

                return $result;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['csv' => 'Kode rumah baru saja dipakai proses lain. Seluruh perubahan batch dibatalkan. Periksa dan unggah ulang CSV.']);
        }
    }
}
