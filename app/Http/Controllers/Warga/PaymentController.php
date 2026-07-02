<?php

namespace App\Http\Controllers\Warga;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('submitPayment', $invoice);

        if (! $invoice->canReceiveProof()) {
            throw ValidationException::withMessages(['proof' => 'Tagihan tidak dapat menerima bukti pembayaran saat ini.']);
        }

        $request->validate([
            'proof' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5 * 1024)],
        ]);

        $oldPath = $invoice->payment_proof_path;
        $newPath = $request->file('proof')->store('payment-proofs', 'local');

        $invoice->update([
            'status' => InvoiceStatus::Pending,
            'payment_proof_path' => $newPath,
            'submitted_at' => now(),
            'verified_at' => null,
            'verified_by' => null,
            'rejection_reason' => null,
        ]);

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()->route('warga.invoices.show', $invoice)
            ->with('success', 'Bukti pembayaran berhasil dikirim dan menunggu verifikasi pengurus.');
    }
}
