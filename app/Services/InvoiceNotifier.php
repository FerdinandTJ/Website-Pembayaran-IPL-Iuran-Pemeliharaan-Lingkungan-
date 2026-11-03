<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceNotification;

class InvoiceNotifier
{
    public function send(Invoice $invoice, string $event): void
    {
        if (! $invoice->housing?->is_active) {
            return;
        }

        $titles = [
            'issued' => 'Tagihan baru',
            'submitted' => 'Bukti pembayaran baru',
            'verified' => 'Pembayaran terverifikasi',
            'cash_paid' => 'Pembayaran cash diterima',
            'rejected' => 'Bukti pembayaran ditolak',
            'due_soon' => 'Tagihan jatuh tempo dalam 3 hari',
            'overdue' => 'Tagihan lewat jatuh tempo',
        ];
        $title = $titles[$event];
        $attempt = in_array($event, ['submitted', 'verified', 'rejected'], true)
            ? ':'.$invoice->payment_proof_path : '';
        $key = $invoice->housing_id.':'.$invoice->id.':'.$event.$attempt;
        $recipients = User::where('housing_id', $invoice->housing_id)->where('is_active', true)
            ->when($event === 'submitted', fn ($q) => $q->where('role', 'pengurus'),
                fn ($q) => $q->where('role', 'warga')->whereKey($invoice->user_id));

        foreach ($recipients->cursor() as $recipient) {
            $recipient->notify(new InvoiceNotification($invoice->housing_id, $key, [
                'event' => $event,
                'invoice_id' => $invoice->id,
                'title' => $title,
                'message' => $invoice->title.' · '.$invoice->billing_period->translatedFormat('F Y')
                    .' · Rp '.number_format($invoice->total_amount, 0, ',', '.')
                    .($event === 'submitted' ? ' · '.$invoice->resident->name : '')
                    .($event === 'rejected' ? '. Silakan periksa alasan penolakan dan unggah ulang bukti.' : '')
                    .(in_array($event, ['due_soon', 'overdue'], true) ? ' · Jatuh tempo '.$invoice->due_date->format('d/m/Y') : ''),
            ]));
        }
    }
}
