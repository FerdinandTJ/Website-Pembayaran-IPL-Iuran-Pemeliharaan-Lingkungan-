<?php

namespace App\Services;

use App\Models\PaymentBatch;
use App\Models\User;
use App\Notifications\HousingNotification;

class BatchPaymentNotifier
{
    public function send(PaymentBatch $batch, string $event): void
    {
        if (! $batch->housing?->is_active) {
            return;
        }
        $title = match ($event) {
            'submitted' => 'Bukti pembayaran gabungan baru',
            'verified' => 'Pembayaran gabungan terverifikasi',
            'cash_paid' => 'Pembayaran cash gabungan diterima',
            'rejected' => 'Bukti pembayaran gabungan ditolak',
        };
        $recipients = User::where('housing_id', $batch->housing_id)->where('is_active', true)
            ->when($event === 'submitted', fn ($q) => $q->where('role', 'pengurus'),
                fn ($q) => $q->where('role', 'warga')->whereKey($batch->user_id));
        foreach ($recipients->cursor() as $recipient) {
            $recipient->notify(new HousingNotification($batch->housing_id, 'payment-batch:'.$batch->id.':'.$event, [
                'event' => 'batch_'.$event, 'payment_batch_id' => $batch->id, 'title' => $title,
                'message' => count($batch->snapshot['invoices']).' tagihan · Rp '.number_format($batch->total_amount, 0, ',', '.')
                    .($event === 'submitted' ? ' · '.$batch->snapshot['resident'] : '')
                    .($event === 'rejected' ? '. Periksa alasan penolakan dan kirim pembayaran baru.' : ''),
            ]));
        }
    }
}
