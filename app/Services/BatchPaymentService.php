<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Cashflow;
use App\Models\Invoice;
use App\Models\PaymentBatch;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BatchPaymentService
{
    public function selection(User $actor, array $ids, bool $lock = false): Collection
    {
        abort_unless($actor->isManager() || $actor->isResident(), 403);
        if (count($ids) < 1 || count($ids) > 100 || count(array_unique($ids)) !== count($ids)) {
            $this->invalid('Pilih 1–100 tagihan berbeda dalam satu pembayaran.');
        }
        $query = Invoice::forHousing($actor->housing_id)->whereIn('id', $ids)
            ->when($actor->isResident(), fn ($q) => $q->where('user_id', $actor->id))->orderBy('id');
        $invoices = ($lock ? $query->lockForUpdate() : $query)->get();
        if ($invoices->count() !== count($ids) || $invoices->pluck('user_id')->unique()->count() !== 1) {
            $this->invalid('Semua tagihan harus milik satu warga dalam perumahan Anda.');
        }
        foreach ($invoices as $invoice) {
            if (! $invoice->canReceiveProof()) {
                $this->invalid('Ada tagihan yang sudah lunas atau menunggu verifikasi. Pilih ulang tagihan.');
            }
        }
        $this->total($invoices);

        return $invoices;
    }

    public function total(Collection $invoices): string
    {
        $cents = $invoices->sum(fn ($invoice) => $this->cents($invoice->total_amount));
        if ($cents < 1 || $cents > 999999999999999) {
            $this->invalid('Total pembayaran melebihi batas. Kurangi jumlah tagihan yang dipilih.');
        }

        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public function create(User $actor, array $ids, string $method, string $expectedTotal, ?string $proofPath = null, ?string $note = null): Invoice|PaymentBatch
    {
        abort_unless(($method === 'cash' && $actor->isManager()) || ($method === 'transfer' && $actor->isResident()), 403);
        if ($method === 'transfer' && ! $proofPath) {
            $this->invalid('Bukti transfer wajib disertakan.');
        }

        return DB::transaction(function () use ($actor, $ids, $method, $expectedTotal, $proofPath, $note) {
            $invoices = $this->selection($actor, $ids, true);
            $total = $this->total($invoices);
            if ($this->cents($expectedTotal) !== $this->cents($total)) {
                $this->invalid('Total tagihan berubah. Tinjau kembali rincian sebelum melanjutkan pembayaran.');
            }
            // The validated selection determines the record type, never a client-supplied mode.
            if ($invoices->count() === 1) {
                $invoice = $invoices->first();
                $payments = app(PaymentService::class);
                if ($method === 'cash') {
                    $payments->settle($invoice, $actor, 'cash', $note);
                } else {
                    $payments->submitProof($invoice, $actor, $proofPath);
                }

                return $invoice->fresh();
            }
            $invoices->load('components', 'resident', 'housing');
            $first = $invoices->first();
            $batch = PaymentBatch::create([
                'housing_id' => $first->housing_id, 'user_id' => $first->user_id, 'created_by' => $actor->id,
                'method' => $method, 'status' => InvoiceStatus::Pending, 'total_amount' => $total,
                'proof_path' => $proofPath, 'note' => $note, 'submitted_at' => now(),
                'snapshot' => [
                    'housing' => $first->housing->name, 'resident' => $first->resident->name,
                    'invoices' => $invoices->map(fn ($invoice) => [
                        'id' => $invoice->id, 'title' => $invoice->title,
                        'period' => $invoice->billing_period->translatedFormat('F Y'),
                        'address' => $invoice->house_label ?: $invoice->resident->house_address,
                        'amount' => $invoice->total_amount,
                        'components' => $invoice->components->map(fn ($component) => ['name' => $component->name, 'amount' => $component->amount])->all(),
                    ])->all(),
                ],
            ]);
            $batch->invoices()->attach($invoices->modelKeys());
            foreach ($invoices as $invoice) {
                $invoice->update([
                    'payment_batch_id' => $batch->id, 'status' => InvoiceStatus::Pending,
                    'payment_method' => $method, 'payment_proof_path' => $proofPath,
                    'submitted_at' => $batch->submitted_at, 'paid_at' => null, 'verified_at' => null,
                    'verified_by' => null, 'rejection_reason' => null, 'payment_note' => $note,
                ]);
            }
            if ($method === 'cash') {
                $this->complete($batch, $invoices, $actor);
            } else {
                app(BatchPaymentNotifier::class)->send($batch, 'submitted');
            }

            return $batch;
        }, 3);
    }

    public function decide(PaymentBatch $batch, User $actor, ?string $rejectionReason = null): PaymentBatch
    {
        abort_unless($actor->isManager() && $actor->housing_id === $batch->housing_id, 403);

        return DB::transaction(function () use ($batch, $actor, $rejectionReason) {
            // Keep a single lock order across grouped and individual payment flows.
            $ids = $batch->invoices()->pluck('invoices.id')->all();
            $invoices = Invoice::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $batch = PaymentBatch::lockForUpdate()->findOrFail($batch->id);
            if ($batch->status !== InvoiceStatus::Pending || $batch->method !== 'transfer') {
                $this->invalid('Pembayaran gabungan sudah diproses atau statusnya berubah.');
            }
            if (! $invoices->count() || $this->total($invoices) !== $batch->total_amount) {
                $this->invalid('Rincian tagihan tidak lagi sesuai dengan pembayaran gabungan.');
            }
            $snapshots = collect($batch->snapshot['invoices'])->keyBy('id');
            foreach ($invoices as $invoice) {
                if ($invoice->payment_batch_id !== $batch->id || $invoice->status !== InvoiceStatus::Pending
                    || $invoice->housing_id !== $batch->housing_id || $invoice->user_id !== $batch->user_id
                    || ! isset($snapshots[$invoice->id]) || $this->cents($invoice->total_amount) !== $this->cents($snapshots[$invoice->id]['amount'])) {
                    $this->invalid('Keanggotaan atau nominal tagihan berubah. Pembayaran tidak diproses sebagian.');
                }
            }
            if ($rejectionReason !== null) {
                $batch->update(['status' => InvoiceStatus::Rejected, 'rejection_reason' => $rejectionReason]);
                foreach ($invoices as $invoice) {
                    $invoice->update(['status' => InvoiceStatus::Rejected, 'rejection_reason' => $rejectionReason,
                        'verified_at' => null, 'verified_by' => null]);
                }
                app(BatchPaymentNotifier::class)->send($batch, 'rejected');
            } else {
                $this->complete($batch, $invoices, $actor);
            }

            return $batch;
        }, 3);
    }

    private function complete(PaymentBatch $batch, Collection $invoices, User $actor): void
    {
        $confirmed = now();
        $batch->update(['status' => InvoiceStatus::Verified, 'verified_at' => $confirmed, 'verified_by' => $actor->id]);
        foreach ($invoices as $invoice) {
            $invoice->update(['status' => InvoiceStatus::Verified, 'paid_at' => $confirmed,
                'verified_at' => $confirmed, 'verified_by' => $actor->id, 'rejection_reason' => null]);
        }
        Cashflow::create([
            'housing_id' => $batch->housing_id, 'payment_batch_id' => $batch->id, 'created_by' => $actor->id,
            'name' => 'Pembayaran gabungan #'.$batch->id.' · '.$invoices->count().' tagihan · '.mb_substr($batch->snapshot['resident'], 0, 100),
            'type' => 'income', 'source' => 'invoice', 'total_cashflow' => $batch->total_amount,
        ]);
        Receipt::create(['payment_batch_id' => $batch->id, 'number' => 'KWG-'.$batch->housing_id.'-'.$batch->id,
            'snapshot' => [
                'housing' => $batch->snapshot['housing'], 'resident' => $batch->snapshot['resident'],
                'address' => collect($batch->snapshot['invoices'])->pluck('address')->filter()->unique()->implode('; '),
                'title' => 'Pembayaran gabungan '.$invoices->count().' tagihan', 'period' => 'Sesuai rincian tiap tagihan',
                'invoices' => $batch->snapshot['invoices'], 'components' => [],
                'amount' => $batch->total_amount, 'method' => $batch->method, 'confirmed_at' => $confirmed->toIso8601String(),
                'officer' => $actor->name, 'note' => $batch->note, 'legacy' => false,
            ],
        ]);
        app(BatchPaymentNotifier::class)->send($batch, $batch->method === 'cash' ? 'cash_paid' : 'verified');
    }

    private function cents(string $amount): int
    {
        if (! preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $amount)) {
            $this->invalid('Nominal pembayaran tidak valid.');
        }
        [$whole, $decimal] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['invoice_ids' => $message]);
    }
}
