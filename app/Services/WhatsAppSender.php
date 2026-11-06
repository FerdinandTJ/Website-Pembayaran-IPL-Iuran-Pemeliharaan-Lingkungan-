<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\WhatsAppDelivery;
use Illuminate\Support\Facades\DB;

class WhatsAppSender
{
    public function send(int $id): void
    {
        $client = app(FonnteClient::class);
        if (! $client->configured()) {
            return;
        }

        $delivery = DB::transaction(function () use ($id) {
            $row = WhatsAppDelivery::lockForUpdate()->find($id);
            if (! $row || $row->status !== 'pending' || $row->available_at->isFuture()) {
                return null;
            }
            $row->update(['status' => 'processing', 'attempts' => $row->attempts + 1, 'claimed_until' => now()->addMinutes(5)]);

            return $row;
        });
        if (! $delivery) {
            return;
        }

        $invoice = $delivery->invoice()->with('resident', 'housing')->first();
        $user = $delivery->user;
        $receipt = $delivery->receipt;
        $isReceipt = in_array($delivery->event, ['verified', 'cash_paid', 'batch_verified', 'batch_cash_paid'], true);
        $reason = null;
        if (! $invoice || ! $user || ! $user->isResident() || ! $user->is_active || ! $invoice->housing?->is_active
            || $user->housing_id !== $delivery->housing_id || $invoice->housing_id !== $delivery->housing_id
            || $invoice->user_id !== $user->id) {
            $reason = 'recipient_inactive_or_moved';
        } elseif ($isReceipt && (! $receipt || $invoice->status !== InvoiceStatus::Verified
            || ($receipt->invoice_id ? $receipt->invoice_id !== $invoice->id
                : (! $receipt->payment_batch_id || $receipt->payment_batch_id !== $invoice->payment_batch_id)))) {
            $reason = 'receipt_not_valid';
        } elseif ($isReceipt && $receipt->payment_batch_id && (! $invoice->paymentBatch
            || $invoice->paymentBatch->status !== InvoiceStatus::Verified
            || $invoice->paymentBatch->housing_id !== $delivery->housing_id
            || $invoice->paymentBatch->user_id !== $user->id
            || $invoice->paymentBatch->invoices()->where(fn ($q) => $q->where('invoices.status', '!=', InvoiceStatus::Verified->value)
                ->orWhere('invoices.housing_id', '!=', $delivery->housing_id)->orWhere('invoices.user_id', '!=', $user->id)
                ->orWhereNull('invoices.payment_batch_id')->orWhere('invoices.payment_batch_id', '!=', $receipt->payment_batch_id))->exists()
            || $invoice->paymentBatch->invoices()->count() !== count($receipt->snapshot['invoices'] ?? []))) {
            $reason = 'receipt_not_valid';
        } elseif (! $isReceipt && ! $invoice->canReceiveProof()) {
            $reason = 'invoice_not_payable';
        } elseif ($delivery->created_at->lt(now()->subDay()) || (! $isReceipt && ! $invoice->due_date->isSameDay($delivery->due_date))) {
            $reason = 'reminder_expired_or_changed';
        }
        $phone = $client->normalizePhone($user?->phone_number);
        if ($reason || ! $phone) {
            $delivery->update(['status' => 'skipped', 'last_error' => $reason ?? 'invalid_phone', 'claimed_until' => null]);

            return;
        }

        $intro = match ($delivery->event) {
            'issued' => 'Tagihan IPL baru telah diterbitkan.',
            'due_soon' => 'Pengingat: tagihan IPL jatuh tempo dalam 3 hari.',
            default => 'Pengingat: tagihan IPL telah melewati jatuh tempo.',
        };
        // Generate from current data, using APP_URL rather than an incoming Host header.
        $url = rtrim(config('app.url'), '/').route('warga.invoices.show', $invoice, false);
        $message = 'Halo Bapak/Ibu '.$user->name.",\n\n".$intro."\n"
            .'Perumahan: '.$invoice->housing->name."\n"
            .'Tagihan: '.$invoice->title."\n"
            .'Periode: '.$invoice->billing_period->translatedFormat('F Y')."\n"
            .'Nominal: Rp '.number_format($invoice->total_amount, 0, ',', '.')."\n"
            .'Jatuh tempo: '.$invoice->due_date->format('d/m/Y')."\n\n"
            .'Lihat rincian dan unggah bukti pembayaran: '.$url."\n\nTerima kasih.\nEnviroPay";
        if ($isReceipt) {
            $receiptUrl = rtrim(config('app.url'), '/').route('receipts.show', $invoice, false);
            $message = app(WhatsAppReceiptMessage::class)->render($receipt, $receiptUrl);
        }
        $result = $client->send($phone, $message);
        $status = $result['status'];
        if ($status === 'pending' && $delivery->attempts >= 5) {
            $status = 'failed';
        }
        $delivery->update([
            'status' => $status, 'last_error' => $result['error'], 'claimed_until' => null,
            'accepted_at' => $status === 'accepted' ? now() : null,
            'available_at' => now()->addMinutes(2 ** $delivery->attempts),
        ]);
    }
}
