<?php

namespace App\Http\Controllers\Pengurus;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['invoice_id' => ['nullable', 'integer']]);
        $invoices = Invoice::with('resident')
            ->forHousing($request->user()->housing_id)
            ->where('status', InvoiceStatus::Pending)
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

        app(\App\Services\PaymentService::class)->settle($invoice, $request->user(), 'transfer');

        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reject(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('reject', $invoice);
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'min:5', 'max:1000']]);

        DB::transaction(function () use ($invoice, $data) {
            $lockedInvoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->authorize('reject', $lockedInvoice);
            if ($lockedInvoice->status !== InvoiceStatus::Pending) {
                throw ValidationException::withMessages(['invoice' => 'Tagihan tidak lagi menunggu verifikasi.']);
            }
            $lockedInvoice->update([
                'status' => InvoiceStatus::Rejected,
                'rejection_reason' => $data['rejection_reason'],
                'verified_at' => null,
                'verified_by' => null,
            ]);
            app(\App\Services\InvoiceNotifier::class)->send($lockedInvoice, 'rejected');
        });

        return back()->with('success', 'Bukti pembayaran ditolak dan warga dapat mengunggah ulang.');
    }
}
