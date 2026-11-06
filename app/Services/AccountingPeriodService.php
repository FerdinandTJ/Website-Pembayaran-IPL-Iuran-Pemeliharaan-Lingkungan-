<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\Housing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingPeriodService
{
    public function assertOpen(int $housingId, string $date): void
    {
        Housing::whereKey($housingId)->lockForUpdate()->firstOrFail();
        $blocking = AccountingPeriod::where('housing_id', $housingId)->whereDate('period', '>=', Carbon::parse($date)->startOfMonth())->where('status', 'closed')->latest('period')->first();
        if ($blocking) {
            throw ValidationException::withMessages(['transaction_date' => 'Transaksi ini memengaruhi buku kas '.$blocking->period->translatedFormat('F Y').' yang sudah ditutup. Buka kembali periode tertutup yang terdampak sebelum mengubah transaksi.']);
        }
    }

    public function close(User $actor, string $period): AccountingPeriod
    {
        abort_unless($actor->isManager() && $actor->housing_id, 403);
        $month = Carbon::createFromFormat('!Y-m', $period, 'Asia/Jakarta');
        if ($month->gte(today('Asia/Jakarta')->startOfMonth())) {
            throw ValidationException::withMessages(['period' => 'Hanya bulan yang sudah berakhir yang dapat ditutup.']);
        }

        return DB::transaction(function () use ($actor, $month) {
            Housing::whereKey($actor->housing_id)->lockForUpdate()->firstOrFail();
            $record = AccountingPeriod::where('housing_id', $actor->housing_id)->whereDate('period', $month)->first()
                ?? AccountingPeriod::create(['housing_id' => $actor->housing_id, 'period' => $month->toDateString(), 'status' => 'open']);
            if ($record->status === 'closed') {
                throw ValidationException::withMessages(['period' => 'Bulan ini sudah ditutup.']);
            }
            $summary = app(ReportService::class)->cashflow($actor->housing_id, $month)['summary'];
            $record->update(['status' => 'closed', 'snapshot' => $summary, 'closed_by' => $actor->id, 'closed_at' => now(), 'reopen_reason' => null]);

            return $record;
        });
    }

    public function reopen(User $actor, AccountingPeriod $period, string $reason): void
    {
        abort_unless($period->housing_id === $actor->housing_id && $actor->isManager(), 403);
        DB::transaction(function () use ($actor, $period, $reason) {
            Housing::whereKey($actor->housing_id)->lockForUpdate()->firstOrFail();
            $record = AccountingPeriod::lockForUpdate()->findOrFail($period->id);
            if ($record->status !== 'closed') {
                throw ValidationException::withMessages(['period' => 'Bulan ini sudah terbuka.']);
            }
            if (AccountingPeriod::where('housing_id', $actor->housing_id)->where('status', 'closed')->whereDate('period', '>', $record->period->toDateString())->exists()) {
                throw ValidationException::withMessages(['period' => 'Buka kembali periode tertutup paling akhir terlebih dahulu agar saldo tetap konsisten.']);
            }
            $record->update(['status' => 'open', 'reopen_reason' => $reason]);
        });
    }
}
