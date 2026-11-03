<?php

namespace App\Services;

use App\Models\House;
use App\Models\ResidentImport;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResidentCsvService
{
    public const COLUMNS = ['name', 'username', 'email', 'phone_number', 'house_code', 'password', 'is_responsible'];

    public function validateRow(array $row, User $actor, bool $password = true): array
    {
        [$target,$identityErrors] = app(ImportIdentity::class)->resident($row, $actor);
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($target)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target)],
            'phone_number' => ['required', 'string', 'max:20'],
            'house_code' => ['required', 'string', 'max:100', Rule::exists('houses', 'code')->where('housing_id', $actor->housing_id)],
            'is_responsible' => ['required', Rule::in(['0', '1'])],
        ];
        if ($password) {
            $rules['password'] = [$target ? 'nullable' : 'required', 'string', 'min:8', 'max:128'];
        }

        return array_merge($identityErrors, Validator::make($row, $rules)->errors()->all());
    }

    private function houseVersion(House $house): string
    {
        return hash('sha256', json_encode($house->only(['id', 'code', 'address', 'responsible_user_id']), JSON_THROW_ON_ERROR));
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
            $header = array_map(fn ($v) => mb_strtolower(trim((string) $v)), str_getcsv($first, $separator, '"', ''));
            if (count($header) !== count(array_unique($header)) || array_diff(self::COLUMNS, $header) || array_diff($header, self::COLUMNS)) {
                throw ValidationException::withMessages(['csv' => 'Header wajib: '.implode(', ', self::COLUMNS).'.']);
            }
            $rows = [];
            $preview = [];
            $errors = [];
            $seen = [];
            $payers = [];
            $targets = [];
            $line = 1;
            while (($values = fgetcsv($stream, 0, $separator, '"', '')) !== false) {
                $line++;
                if ($values === [null] || count(array_filter($values, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                if (count($rows) >= 100) {
                    throw ValidationException::withMessages(['csv' => 'Maksimal 100 warga per file. Pisahkan menjadi beberapa file.']);
                }
                if (count($values) !== count($header) || ! mb_check_encoding(implode('', $values), 'UTF-8')) {
                    throw ValidationException::withMessages(['csv' => "Jumlah kolom atau encoding tidak valid pada baris {$line}."]);
                }
                $row = array_combine($header, $values);
                foreach ($row as $key => &$value) {
                    if ($key !== 'password') {
                        $value = trim((string) $value);
                    }
                }
                unset($value);
                $row['username'] = mb_strtolower($row['username']);
                $row['email'] = mb_strtolower($row['email']);
                $row['house_code'] = mb_strtoupper($row['house_code']);
                if ($row['password'] === '') {
                    $row['password'] = null;
                }
                [$target] = app(ImportIdentity::class)->resident($row, $actor);
                $rowErrors = $this->validateRow($row, $actor);
                foreach (['username', 'email'] as $key) {
                    if (isset($seen[$key][$row[$key]])) {
                        $rowErrors[] = "{$key} berulang di file.";
                    }
                    $seen[$key][$row[$key]] = true;
                }
                if ($target && isset($targets[$target->id])) {
                    $rowErrors[] = 'Akun yang sama muncul lebih dari satu kali dalam file.';
                }
                if ($target) {
                    $targets[$target->id] = true;
                }
                $house = House::where('housing_id', $actor->housing_id)->where('code', $row['house_code'])->first();
                if ($row['is_responsible'] === '1') {
                    if (isset($payers[$row['house_code']])) {
                        $rowErrors[] = 'Satu rumah hanya boleh memiliki satu penanggung jawab IPL pada file.';
                    }
                    $payers[$row['house_code']] = true;
                }
                if ($rowErrors) {
                    $errors[$line] = $rowErrors;
                }
                $preview[] = ['line' => $line, 'action' => $target ? 'Perbarui' : 'Baru', 'matched_account' => $target ? $target->name.' · '.$target->username.' · '.$target->email : null, 'password_action' => $row['password'] ? ($target ? 'Ganti password' : 'Password awal') : 'Tetap',
                    'payer_change' => $row['is_responsible'] === '1' && $house?->responsible_user_id && $house->responsible_user_id !== $target?->id ? 'Menggantikan '.($house->responsible?->name ?? 'penanggung jawab lama') : null,
                ] + array_diff_key($row, ['password' => true]);
                $row['_target_id'] = $target?->id;
                $row['_version'] = app(ImportIdentity::class)->fingerprint($target);
                $row['_house_id'] = $house?->id;
                $row['_house_version'] = $house ? $this->houseVersion($house) : null;
                $rows[] = $row;
            }
            if (! $rows) {
                throw ValidationException::withMessages(['csv' => 'CSV tidak berisi data warga.']);
            }
            $batch = null;
            if (! $errors) {
                foreach ($rows as &$row) {
                    if ($row['password'] !== null) {
                        $row['password'] = Hash::make($row['password']);
                    }
                }
                unset($row);
                $batch = ResidentImport::create(['id' => (string) Str::uuid(), 'housing_id' => $actor->housing_id, 'user_id' => $actor->id, 'rows' => $rows, 'expires_at' => now()->addMinutes(30)]);
            }

            return ['preview' => $preview, 'importErrors' => $errors, 'batch' => $batch];
        } finally {
            fclose($stream);
        }
    }

    public function confirm(string $id, User $actor): array
    {
        try {
            return DB::transaction(function () use ($id, $actor) {
                $batch = ResidentImport::where('housing_id', $actor->housing_id)->where('user_id', $actor->id)->lockForUpdate()->findOrFail($id);
                if ($batch->completed_at) {
                    return ['created' => 0, 'updated' => 0, 'replayed' => true];
                }
                if ($batch->expires_at->isPast()) {
                    throw ValidationException::withMessages(['csv' => 'Pratinjau kedaluwarsa. Unggah ulang CSV.']);
                }
                $rows = $batch->rows;
                $prepared = [];
                // Check the whole preview before applying changes to shared houses.
                foreach ($rows as $index => $row) {
                    if (! array_key_exists('_target_id', $row) || ! array_key_exists('_version', $row) || ! array_key_exists('_house_version', $row)) {
                        throw ValidationException::withMessages(['csv' => 'Format pratinjau sudah berubah. Unggah ulang CSV.']);
                    }
                    if ($errors = $this->validateRow($row, $actor, false)) {
                        throw ValidationException::withMessages(['csv' => 'Baris '.($index + 2).': '.implode(' ', $errors)]);
                    }
                    [$target] = app(ImportIdentity::class)->resident($row, $actor);
                    if ($target) {
                        $target = User::lockForUpdate()->findOrFail($target->id);
                    }
                    $house = House::where('housing_id', $actor->housing_id)->where('code', $row['house_code'])->lockForUpdate()->firstOrFail();
                    if ($target?->id !== ($row['_target_id'] ?? null) || app(ImportIdentity::class)->fingerprint($target) !== ($row['_version'] ?? null)
                        || $house->id !== ($row['_house_id'] ?? $house->id) || (isset($row['_house_version']) && $this->houseVersion($house) !== $row['_house_version'])) {
                        throw ValidationException::withMessages(['csv' => 'Data akun/rumah berubah sejak pratinjau. Unggah ulang CSV agar tidak salah memperbarui data.']);
                    }
                    $prepared[] = [$row, $target, $house];
                }
                $result = ['created' => 0, 'updated' => 0, 'replayed' => false];
                foreach ($prepared as [$row,$target,$house]) {
                    $data = array_intersect_key($row, array_flip(['name', 'username', 'email', 'phone_number']));
                    if (! empty($row['password'])) {
                        $data['password'] = $row['password'];
                    }
                    if ($target) {
                        $target->update($data);
                        if (! empty($row['password'])) {
                            app(SessionRevoker::class)->revoke($target);
                        }
                        $resident = $target;
                        $result['updated']++;
                    } else {
                        $resident = User::create($data + ['housing_id' => $actor->housing_id, 'house_address' => $house->address, 'role' => 'warga', 'is_active' => true]);
                        $result['created']++;
                    }
                    app(HouseService::class)->link($resident, $house->id, $row['is_responsible'] === '1', $actor);
                }
                $batch->update(['completed_at' => now(), 'rows' => []]);

                return $result;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['csv' => 'Identitas akun baru saja dipakai proses lain. Seluruh perubahan dibatalkan; unggah ulang CSV.']);
        }
    }
}
