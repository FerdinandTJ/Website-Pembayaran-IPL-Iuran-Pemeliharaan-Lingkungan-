<?php

namespace App\Http\Controllers\Pengurus;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Cashflow;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $housingId = $request->user()->housing_id;
        $cashflows = Cashflow::forHousing($housingId)->get();
        $income = (float) $cashflows->where('type', 'income')->sum('total_cashflow');
        $expense = (float) $cashflows->where('type', 'expense')->sum('total_cashflow');

        return view('pengurus.dashboard', [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'residentCount' => User::where('housing_id', $housingId)->where('role', 'warga')->where('is_active', true)->count(),
            'pendingCount' => Invoice::forHousing($housingId)->where('status', InvoiceStatus::Pending)->count(),
            'unpaidCount' => Invoice::forHousing($housingId)->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Rejected])->count(),
            'pendingInvoices' => Invoice::with('resident')
                ->forHousing($housingId)
                ->where('status', InvoiceStatus::Pending)
                ->oldest('submitted_at')
                ->limit(5)
                ->get(),
            'recentCashflows' => Cashflow::forHousing($housingId)->latest()->limit(7)->get(),
        ]);
    }
}
