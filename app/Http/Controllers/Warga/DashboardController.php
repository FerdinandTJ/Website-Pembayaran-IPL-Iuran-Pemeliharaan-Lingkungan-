<?php

namespace App\Http\Controllers\Warga;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Cashflow;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $invoiceQuery = Invoice::where('user_id', $user->id);
        $cashflows = Cashflow::forHousing($user->housing_id)->get();
        $income = (float) $cashflows->where('type', 'income')->sum('total_cashflow');
        $expense = (float) $cashflows->where('type', 'expense')->sum('total_cashflow');

        return view('warga.dashboard', [
            'activeCount' => (clone $invoiceQuery)->whereIn('status', [
                InvoiceStatus::Unpaid, InvoiceStatus::Rejected, InvoiceStatus::Pending,
            ])->count(),
            'verifiedCount' => (clone $invoiceQuery)->where('status', InvoiceStatus::Verified)->count(),
            'outstandingTotal' => (clone $invoiceQuery)->whereIn('status', [
                InvoiceStatus::Unpaid, InvoiceStatus::Rejected,
            ])->sum('total_amount'),
            'housingBalance' => $income - $expense,
            'recentInvoices' => (clone $invoiceQuery)->latest('billing_period')->limit(5)->get(),
            'recentCashflows' => Cashflow::forHousing($user->housing_id)->latest()->limit(5)->get(),
        ]);
    }
}
