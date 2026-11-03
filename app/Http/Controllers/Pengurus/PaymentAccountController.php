<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentAccountController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = PaymentAccount::where('housing_id', $request->user()->housing_id)
            ->orderByDesc('is_active')
            ->orderBy('bank_name')
            ->paginate(10);

        return view('pengurus.payment-accounts.index', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        PaymentAccount::create([
            ...$this->validated($request),
            'housing_id' => $request->user()->housing_id,
        ]);

        return back()->with('success', 'Rekening pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, PaymentAccount $paymentAccount): RedirectResponse
    {
        $this->authorize('update', $paymentAccount);
        $paymentAccount->update($this->validated($request, $paymentAccount));

        return back()->with('success', 'Rekening pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentAccount $paymentAccount): RedirectResponse
    {
        $this->authorize('delete', $paymentAccount);
        $paymentAccount->update(['is_active' => false]);

        return back()->with('success', 'Rekening dinonaktifkan. Histori tagihan tetap tersimpan.');
    }

    private function validated(Request $request, ?PaymentAccount $paymentAccount = null): array
    {
        return $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => [
                'required', 'string', 'max:100',
                Rule::unique('payment_accounts')->where(fn ($query) => $query
                    ->where('housing_id', $request->user()->housing_id)
                    ->where('bank_name', $request->input('bank_name')))->ignore($paymentAccount),
            ],
            'account_holder' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
