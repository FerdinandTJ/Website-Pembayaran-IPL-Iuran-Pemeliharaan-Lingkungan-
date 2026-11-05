<?php

namespace App\Http\Controllers\Warga;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentBatch;
use App\Services\PaymentService;
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
            'proof' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5 * 1024), 'dimensions:max_width=6000,max_height=6000'],
        ]);

        $newPath = $request->file('proof')->store('payment-proofs', 'local');
        if (! $newPath) {
            throw ValidationException::withMessages(['proof' => 'Bukti pembayaran gagal disimpan. Silakan coba lagi.']);
        }
        try {
            $oldPath = app(PaymentService::class)->submitProof($invoice, $request->user(), $newPath);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath
            && ! Invoice::where('payment_proof_path', $oldPath)->exists()
            && ! PaymentBatch::where('proof_path', $oldPath)->exists()) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()->route('warga.invoices.show', $invoice)
            ->with('success', 'Bukti pembayaran berhasil dikirim dan menunggu verifikasi pengurus.');
    }
}
