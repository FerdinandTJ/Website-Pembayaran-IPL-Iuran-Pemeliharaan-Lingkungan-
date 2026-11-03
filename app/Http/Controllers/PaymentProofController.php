<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofController extends Controller
{
    public function __invoke(Invoice $invoice): StreamedResponse
    {
        $this->authorize('view', $invoice);
        abort_unless($invoice->payment_proof_path && Storage::disk('local')->exists($invoice->payment_proof_path), 404);

        return Storage::disk('local')->response(
            $invoice->payment_proof_path,
            'bukti-pembayaran-'.$invoice->id.'.'.pathinfo($invoice->payment_proof_path, PATHINFO_EXTENSION),
            ['Content-Disposition' => 'inline', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => "default-src 'none'; sandbox"]
        );
    }
}
