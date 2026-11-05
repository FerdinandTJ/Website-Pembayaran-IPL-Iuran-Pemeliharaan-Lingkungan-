<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\PaymentBatch;
use App\Services\BatchPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class PaymentBatchController extends Controller
{
    public function select(Request $request)
    {
        $invoices = Invoice::forHousing($request->user()->housing_id)->where('user_id', $request->user()->id)
            ->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Rejected])->orderBy('due_date')->orderBy('id')->limit(100)->get();

        return view('payments.select', compact('invoices'));
    }

    public function transferPreview(Request $request, BatchPaymentService $payments)
    {
        return $this->preview($request, $payments, 'transfer');
    }

    public function cashPreview(Request $request, BatchPaymentService $payments)
    {
        return $this->preview($request, $payments, 'cash');
    }

    private function preview(Request $request, BatchPaymentService $payments, string $method)
    {
        $invoices = $payments->selection($request->user(), $this->ids($request));
        $invoices->load('components', 'resident', 'housing');
        $total = $payments->total($invoices);
        $accounts = $invoices->first()->housing->paymentAccounts()->where('is_active', true)->orderBy('bank_name')->get();

        return view('payments.confirm', compact('invoices', 'total', 'method', 'accounts'));
    }

    public function transferStore(Request $request, BatchPaymentService $payments)
    {
        $ids = $this->ids($request);
        $request->validate([
            'expected_total' => ['required', 'string', 'regex:/^\d{1,13}(?:\.\d{1,2})?$/D'],
            'proof' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5 * 1024), 'dimensions:max_width=6000,max_height=6000'],
        ]);
        // Do not store uploads from a selection the resident cannot pay.
        $payments->selection($request->user(), $ids);
        $path = $request->file('proof')->store('payment-proofs', 'local');
        if (! $path) {
            throw ValidationException::withMessages(['proof' => 'Bukti pembayaran gagal disimpan. Silakan coba lagi.']);
        }
        try {
            $payment = $payments->create($request->user(), $ids, 'transfer', $request->input('expected_total'), $path);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        if ($payment instanceof Invoice) {
            return redirect()->route('warga.invoices.show', $payment)
                ->with('success', 'Bukti pembayaran tagihan berhasil dikirim dan menunggu verifikasi pengurus.');
        }

        return redirect()->route('payment-batches.show', $payment)->with('success', 'Satu bukti pembayaran dikirim untuk seluruh tagihan pilihan. Menunggu verifikasi pengurus.');
    }

    public function cashStore(Request $request, BatchPaymentService $payments)
    {
        $ids = $this->ids($request);
        $data = $request->validate([
            'expected_total' => ['required', 'string', 'regex:/^\d{1,13}(?:\.\d{1,2})?$/D'],
            'received' => ['accepted'], 'payment_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $payment = $payments->create($request->user(), $ids, 'cash', $data['expected_total'], null, $data['payment_note'] ?? null);

        if ($payment instanceof Invoice) {
            return redirect()->route('receipts.show', $payment)->with('success', 'Pembayaran cash tagihan berhasil dicatat.');
        }

        return redirect()->route('payment-batches.show', $payment)->with('success', 'Pembayaran cash dicatat sekali dan seluruh tagihan pilihan sudah lunas.');
    }

    public function show(PaymentBatch $paymentBatch)
    {
        $this->authorize('view', $paymentBatch);
        $paymentBatch->load('invoices', 'receipt');

        return view('payments.show', ['batch' => $paymentBatch]);
    }

    public function proof(PaymentBatch $paymentBatch)
    {
        $this->authorize('view', $paymentBatch);
        abort_unless($paymentBatch->proof_path && Storage::disk('local')->exists($paymentBatch->proof_path), 404);

        return Storage::disk('local')->response($paymentBatch->proof_path,
            'bukti-gabungan-'.$paymentBatch->id.'.'.pathinfo($paymentBatch->proof_path, PATHINFO_EXTENSION),
            ['Content-Disposition' => 'inline', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => "default-src 'none'; sandbox"]);
    }

    public function verify(Request $request, PaymentBatch $paymentBatch, BatchPaymentService $payments)
    {
        $this->authorize('verify', $paymentBatch);
        $payments->decide($paymentBatch, $request->user());

        return redirect()->route('payment-batches.show', $paymentBatch)->with('success', 'Pembayaran terverifikasi. Semua tagihan dalam pembayaran ini lunas.');
    }

    public function reject(Request $request, PaymentBatch $paymentBatch, BatchPaymentService $payments)
    {
        $this->authorize('verify', $paymentBatch);
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $payments->decide($paymentBatch, $request->user(), $data['rejection_reason']);

        return redirect()->route('payment-batches.show', $paymentBatch)->with('success', 'Bukti ditolak untuk seluruh tagihan dalam pembayaran ini. Warga dapat memilih ulang tagihannya.');
    }

    private function ids(Request $request): array
    {
        return $request->validate([
            'invoice_ids' => ['required', 'array', 'min:1', 'max:100'],
            'invoice_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ])['invoice_ids'];
    }
}
