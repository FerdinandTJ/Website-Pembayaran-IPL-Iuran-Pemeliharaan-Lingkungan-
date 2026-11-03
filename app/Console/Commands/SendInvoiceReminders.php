<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendInvoiceReminders extends Command
{
    protected $signature = 'app:send-invoice-reminders';

    protected $description = 'Kirim pengingat H-3 dan H+1 jatuh tempo (tanpa mengulang pengingat lama).';

    public function handle(InvoiceNotifier $notifier): int
    {
        $today = today('Asia/Jakarta');
        foreach (['due_soon' => $today->copy()->addDays(3), 'overdue' => $today->copy()->subDay()] as $event => $date) {
            Invoice::whereDate('due_date', $date)->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Rejected])
                ->whereHas('housing', fn ($q) => $q->where('is_active', true))
                ->whereHas('resident', fn ($q) => $q->where('is_active', true))
                ->select('id')->chunkById(100, function ($invoices) use ($notifier, $event, $date) {
                    foreach ($invoices as $invoice) {
                        DB::transaction(function () use ($invoice, $notifier, $event, $date) {
                            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
                            if ($locked->canReceiveProof() && $locked->due_date->isSameDay($date)) {
                                $notifier->send($locked, $event);
                            }
                        });
                    }
                });
        }
        $this->info('Pemeriksaan pengingat selesai.');

        return self::SUCCESS;
    }
}
