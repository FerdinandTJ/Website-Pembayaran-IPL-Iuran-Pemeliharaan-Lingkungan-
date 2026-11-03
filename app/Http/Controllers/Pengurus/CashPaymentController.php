<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CashPaymentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['resident_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('housing_id', $request->user()->housing_id)->where('role', 'warga')], 'search' => ['nullable', 'string', 'max:255']]);
        $residents = User::with('house')->where('housing_id', $request->user()->housing_id)->where('role', 'warga')
            ->when($request->input('search'), fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('house_address', 'like', "%{$s}%")))
            ->orderBy('name')->paginate(15)->withQueryString();
        $resident = $request->filled('resident_id') ? User::where('housing_id', $request->user()->housing_id)->where('role', 'warga')->findOrFail($request->integer('resident_id')) : null;
        $invoices = $resident ? Invoice::where('housing_id', $request->user()->housing_id)->where('user_id', $resident->id)
            ->whereIn('status', ['unpaid', 'rejected'])->with('components')->orderBy('due_date')->paginate(10, ['*'], 'invoices_page')->withQueryString() : null;

        return view('pengurus.cash-payments.index', compact('residents', 'resident', 'invoices'));
    }

    public function store(Request $request, Invoice $invoice, PaymentService $payments)
    {
        $this->authorize('verify', $invoice);
        $data = $request->validate(['payment_note' => ['nullable', 'string', 'max:1000'], 'received' => ['accepted']]);
        $payments->settle($invoice, $request->user(), 'cash', $data['payment_note'] ?? null);

        return redirect()->route('receipts.show', $invoice)->with('success', 'Pembayaran cash berhasil dicatat.');
    }
}
