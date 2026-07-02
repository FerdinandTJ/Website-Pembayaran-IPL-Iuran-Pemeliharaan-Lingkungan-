<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Cashflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashflowController extends Controller
{
    public function index(Request $request): View
    {
        $base = Cashflow::forHousing($request->user()->housing_id);
        $income = (clone $base)->where('type', 'income')->sum('total_cashflow');
        $expense = (clone $base)->where('type', 'expense')->sum('total_cashflow');

        $cashflows = $base
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('source'), fn ($query) => $query->where('source', $request->string('source')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pengurus.cashflows.index', compact('cashflows', 'income', 'expense'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Cashflow::create([
            ...$data,
            'housing_id' => $request->user()->housing_id,
            'created_by' => $request->user()->id,
            'source' => 'manual',
        ]);

        return back()->with('success', 'Transaksi kas berhasil ditambahkan.');
    }

    public function update(Request $request, Cashflow $cashflow): RedirectResponse
    {
        $this->authorize('update', $cashflow);
        $cashflow->update($this->validated($request));

        return back()->with('success', 'Transaksi kas berhasil diperbarui.');
    }

    public function destroy(Cashflow $cashflow): RedirectResponse
    {
        $this->authorize('delete', $cashflow);
        $cashflow->delete();

        return back()->with('success', 'Transaksi kas berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense'],
            'total_cashflow' => ['required', 'numeric', 'min:1'],
        ]);
    }
}
