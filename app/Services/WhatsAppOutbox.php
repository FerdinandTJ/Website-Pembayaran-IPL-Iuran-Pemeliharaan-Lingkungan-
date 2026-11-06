<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\PaymentBatch;
use App\Models\User;
use App\Models\WhatsAppDelivery;
use Illuminate\Notifications\DatabaseNotification;

class WhatsAppOutbox
{
    public function enqueue(User $user, DatabaseNotification $notification): void
    {
        $data = $notification->data;
        if (! app(FonnteClient::class)->configured() || ! $user->isResident()
            || ! $user->is_active || ! $user->housing?->is_active
            || ! in_array($data['event'] ?? null, ['issued', 'due_soon', 'overdue', 'verified', 'cash_paid', 'batch_verified', 'batch_cash_paid'], true)) {
            return;
        }

        $isReceipt = in_array($data['event'], ['verified', 'cash_paid', 'batch_verified', 'batch_cash_paid'], true);
        if (str_starts_with($data['event'], 'batch_')) {
            $batch = PaymentBatch::where('housing_id', $user->housing_id)->where('user_id', $user->id)
                ->find($data['payment_batch_id'] ?? null);
            $invoice = $batch?->invoices()->where('housing_id', $user->housing_id)->where('user_id', $user->id)->first();
            $receipt = $batch?->receipt;
        } else {
            $invoice = Invoice::where('housing_id', $user->housing_id)->where('user_id', $user->id)->find($data['invoice_id'] ?? null);
            $receipt = $isReceipt ? $invoice?->receipt : null;
        }
        if (! $invoice || ($isReceipt ? (! $receipt || $invoice->status !== InvoiceStatus::Verified) : ! $invoice->canReceiveProof())) {
            return;
        }

        WhatsAppDelivery::firstOrCreate($isReceipt ? ['receipt_id' => $receipt->id] : ['notification_id' => $notification->id], [
            'notification_id' => $notification->id,
            'housing_id' => $user->housing_id, 'user_id' => $user->id,
            'invoice_id' => $invoice->id, 'event' => $data['event'],
            'receipt_id' => $receipt?->id,
            'due_date' => $invoice->due_date, 'available_at' => now(),
        ]);
    }
}
