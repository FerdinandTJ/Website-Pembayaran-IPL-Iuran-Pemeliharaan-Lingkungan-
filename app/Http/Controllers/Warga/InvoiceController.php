<?php

namespace App\Http\Controllers\Warga;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('period'), fn ($query) => $query->whereDate('billing_period', $request->string('period')->toString().'-01'))
            ->latest('billing_period')
            ->paginate(10)
            ->withQueryString();

        return view('warga.invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::cases(),
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('warga.invoices.show', [
            'invoice' => $invoice->load([
                'components',
                'housing.paymentAccounts' => fn ($query) => $query->where('is_active', true)->orderBy('bank_name'),
            ]),
        ]);
    }
}
