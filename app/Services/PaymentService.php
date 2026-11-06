<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Cashflow;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function submitProof(Invoice $invoice, User $actor, string $path): ?string
    {
        return DB::transaction(function () use ($invoice, $actor, $path) {
            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
            abort_unless($actor->isResident() && $actor->id === $locked->user_id
                && $actor->housing_id === $locked->housing_id, 403);
            if (! $locked->canReceiveProof()) {
                throw ValidationException::withMessages(['proof' => 'Tagihan tidak dapat menerima bukti pembayaran saat ini.']);
            }
            $oldPath = $locked->payment_proof_path;
            $locked->update([
                'payment_batch_id' => null, 'status' => InvoiceStatus::Pending,
                'payment_method' => 'transfer', 'payment_proof_path' => $path,
                'submitted_at' => now(), 'paid_at' => null, 'verified_at' => null,
                'verified_by' => null, 'rejection_reason' => null, 'payment_note' => null,
            ]);
            app(InvoiceNotifier::class)->send($locked, 'submitted');

            return $oldPath;
        });
    }

    public function settle(Invoice $invoice, User $actor, string $method, ?string $note = null): Receipt
    {
        return DB::transaction(function () use ($invoice, $actor, $method, $note) {
            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
            abort_unless($actor->isManager() && $actor->housing_id === $locked->housing_id, 403);
            if ($method !== 'cash' && $locked->payment_batch_id) {
                throw ValidationException::withMessages(['invoice' => 'Tagihan tergabung dalam pembayaran. Verifikasi seluruh pembayaran gabungan.']);
            }
            $allowed = $method === 'cash' ? $locked->canReceiveProof() : $locked->status === InvoiceStatus::Pending;
            if (! $allowed) {
                throw ValidationException::withMessages(['invoice' => 'Tagihan tidak dapat diproses: sudah lunas atau statusnya berubah.']);
            }
            $locked->update(['payment_batch_id' => null, 'status' => InvoiceStatus::Verified, 'payment_method' => $method, 'paid_at' => now(),
                'verified_at' => now(), 'verified_by' => $actor->id, 'payment_note' => $note, 'rejection_reason' => null]);
            Cashflow::create(['housing_id' => $locked->housing_id, 'invoice_id' => $locked->id, 'created_by' => $actor->id,
                'name' => $locked->title.' - '.$locked->resident->name, 'type' => 'income', 'source' => 'invoice', 'category' => 'ipl', 'total_cashflow' => $locked->total_amount]);
            $receipt = $this->receipt($locked, false);
            app(InvoiceNotifier::class)->send($locked, $method === 'cash' ? 'cash_paid' : 'verified');

            return $receipt;
        });
    }

    public function receipt(Invoice $invoice, bool $historical = true): Receipt
    {
        return DB::transaction(function () use ($invoice, $historical) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            abort_unless($invoice->status === InvoiceStatus::Verified, 404);
            if ($invoice->payment_batch_id) {
                return $invoice->paymentBatch->receipt()->firstOrFail();
            }
            if ($existing = $invoice->receipt) {
                return $existing;
            }

            return Receipt::create(['invoice_id' => $invoice->id, 'number' => 'KW-'.$invoice->housing_id.'-'.$invoice->id,
                'snapshot' => [
                    'housing' => $invoice->housing->name, 'resident' => $invoice->resident->name,
                    'address' => $invoice->house_label ?: $invoice->resident->house_address,
                    'title' => $invoice->title, 'period' => $invoice->billing_period->translatedFormat('F Y'),
                    'components' => $invoice->components->map(fn ($c) => ['name' => $c->name, 'amount' => $c->amount])->all(),
                    'amount' => $invoice->total_amount, 'method' => $invoice->payment_method ?: 'transfer',
                    'confirmed_at' => $invoice->paid_at?->toIso8601String() ?? $invoice->verified_at?->toIso8601String(),
                    'officer' => $invoice->verifier?->name ?: 'Tidak tercatat pada data lama', 'note' => $invoice->payment_note,
                    'legacy' => $historical,
                ]]);
        });
    }
}
