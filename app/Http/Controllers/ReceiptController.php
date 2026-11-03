<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\PaymentService;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReceiptController extends Controller
{
    public function show(Invoice $invoice, PaymentService $payments)
    {
        $this->authorize('view', $invoice);

        return view('receipts.show', ['receipt' => $payments->receipt($invoice), 'invoice' => $invoice, 'pdf' => false]);
    }

    public function pdf(Invoice $invoice, PaymentService $payments)
    {
        $this->authorize('view', $invoice);
        $receipt = $payments->receipt($invoice);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('receipts.show', ['receipt' => $receipt, 'invoice' => $invoice, 'pdf' => true])->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$receipt->number.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
