<?php

namespace App\Http\Controllers\Pengurus;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Cashflow;
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
        $invoices = Invoice::with('resident')
            ->forHousing($request->user()->housing_id)
            ->where('status', InvoiceStatus::Pending)
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

        DB::transaction(function () use ($request, $invoice) {
            $lockedInvoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->authorize('verify', $lockedInvoice);

            if ($lockedInvoice->status !== InvoiceStatus::Pending) {
                throw ValidationException::withMessages(['invoice' => 'Tagihan tidak lagi menunggu verifikasi.']);
            }

            $lockedInvoice->update([
                'status' => InvoiceStatus::Verified,
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
                'rejection_reason' => null,
            ]);

            Cashflow::create([
                'housing_id' => $lockedInvoice->housing_id,
                'invoice_id' => $lockedInvoice->id,
                'created_by' => $request->user()->id,
                'name' => 'Pembayaran IPL '.$lockedInvoice->billing_period->translatedFormat('F Y').' - '.$lockedInvoice->resident->name,
                'type' => 'income',
                'source' => 'invoice',
                'total_cashflow' => $lockedInvoice->total_amount,
            ]);
        });

        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reject(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('reject', $invoice);
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'min:5', 'max:1000']]);

        if ($invoice->status !== InvoiceStatus::Pending) {
            throw ValidationException::withMessages(['invoice' => 'Tagihan tidak lagi menunggu verifikasi.']);
        }

        $invoice->update([
            'status' => InvoiceStatus::Rejected,
            'rejection_reason' => $data['rejection_reason'],
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return back()->with('success', 'Bukti pembayaran ditolak dan warga dapat mengunggah ulang.');
    }
}
