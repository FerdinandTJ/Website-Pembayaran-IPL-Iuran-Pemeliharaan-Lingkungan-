<?php

namespace App\Http\Controllers\Pengurus;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\BatchPaymentService;
use App\Services\InvoiceNotifier;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $request->validate(['invoice_id' => ['nullable', 'integer']]);
        if ($request->filled('invoice_id')) {
            $target = Invoice::forHousing($request->user()->housing_id)->find($request->integer('invoice_id'));
            if ($target?->payment_batch_id) {
                return redirect()->route('payment-batches.show', $target->payment_batch_id);
            }
        }
        $invoices = Invoice::with('resident', 'paymentBatch')
            ->forHousing($request->user()->housing_id)
            ->where('status', InvoiceStatus::Pending)
            ->paymentRepresentatives()
            ->when($request->filled('invoice_id'), fn ($q) => $q->whereKey($request->integer('invoice_id')))
            ->when($request->string('search')->toString(), fn ($query, $search) => $query
                ->whereHas('resident', fn ($query) => $query->where('name', 'like', "%{$search}%")))
            ->oldest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        return view('pengurus.verifications.index', compact('invoices'));
    }

    public function verify(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('verify', $invoice);

        if ($invoice->payment_batch_id) {
            app(BatchPaymentService::class)->decide($invoice->paymentBatch, $request->user());

            return redirect()->route('payment-batches.show', $invoice->payment_batch_id)->with('success', 'Seluruh pembayaran gabungan berhasil diverifikasi.');
        }

        app(PaymentService::class)->settle($invoice, $request->user(), 'transfer');

        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reject(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('reject', $invoice);
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'min:5', 'max:1000']]);

        if ($invoice->payment_batch_id) {
            app(BatchPaymentService::class)->decide($invoice->paymentBatch, $request->user(), $data['rejection_reason']);

            return redirect()->route('payment-batches.show', $invoice->payment_batch_id)->with('success', 'Seluruh bukti pembayaran gabungan ditolak.');
        }

        DB::transaction(function () use ($invoice, $data) {
            $lockedInvoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->authorize('reject', $lockedInvoice);
            if ($lockedInvoice->status !== InvoiceStatus::Pending || $lockedInvoice->payment_batch_id) {
                throw ValidationException::withMessages(['invoice' => 'Tagihan tidak lagi menunggu verifikasi.']);
            }
            $lockedInvoice->update([
                'status' => InvoiceStatus::Rejected,
                'rejection_reason' => $data['rejection_reason'],
                'verified_at' => null,
                'verified_by' => null,
            ]);
            app(InvoiceNotifier::class)->send($lockedInvoice, 'rejected');
        });

        return back()->with('success', 'Bukti pembayaran ditolak dan warga dapat mengunggah ulang.');
    }
}
