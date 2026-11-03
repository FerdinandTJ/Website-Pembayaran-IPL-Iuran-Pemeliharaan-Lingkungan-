<?php

namespace App\Http\Controllers\Warga;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
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
        $cashflows = Cashflow::forHousing($user->housing_id);
        $income = (float) (clone $cashflows)->where('type', 'income')->sum('total_cashflow');
        $expense = (float) (clone $cashflows)->where('type', 'expense')->sum('total_cashflow');

        return view('warga.dashboard', [
            'pendingCount' => (clone $invoiceQuery)->where('status', InvoiceStatus::Pending)->count(),
            'nextInvoice' => (clone $invoiceQuery)->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Rejected])->orderBy('due_date')->orderBy('id')->first(),
            'announcements' => Announcement::where('housing_id', $user->housing_id)->where('status', 'published')->latest('published_at')->latest('id')->limit(3)->get(),
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
