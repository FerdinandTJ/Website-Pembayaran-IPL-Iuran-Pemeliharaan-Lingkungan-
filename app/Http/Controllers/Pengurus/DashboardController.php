<?php

namespace App\Http\Controllers\Pengurus;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Cashflow;
use App\Models\House;
use App\Models\Invoice;
use App\Models\User;
use App\Services\DashboardMetrics;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $housingId = $request->user()->housing_id;
        $cashflows = Cashflow::forHousing($housingId);
        $income = (float) (clone $cashflows)->where('type', 'income')->sum('total_cashflow');
        $expense = (float) (clone $cashflows)->where('type', 'expense')->sum('total_cashflow');
        $overdue = app(ReportService::class)->overdue($housingId);

        return view('pengurus.dashboard', [
            'cashflowSeries' => app(DashboardMetrics::class)->cashflow($housingId),
            'announcements' => Announcement::where('housing_id', $housingId)->where('status', 'published')->latest('published_at')->latest('id')->limit(3)->get(),
            'houseCounts' => House::where('housing_id', $housingId)->selectRaw('occupancy, COUNT(*) AS total')->groupBy('occupancy')->pluck('total', 'occupancy'),
            'unassignedHouses' => House::where('housing_id', $housingId)->whereNull('responsible_user_id')->count(),
            'overdueTotal' => (clone $overdue)->sum('total_amount'),
            'overdueResidents' => (clone $overdue)->distinct()->count('user_id'),
            'overdueCount' => (clone $overdue)->count(),
            'pendingTotal' => Invoice::forHousing($housingId)->where('status', InvoiceStatus::Pending)->sum('total_amount'),
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'residentCount' => User::where('housing_id', $housingId)->where('role', 'warga')->where('is_active', true)->count(),
            'pendingCount' => Invoice::forHousing($housingId)->where('status', InvoiceStatus::Pending)->count(),
            'unpaidCount' => Invoice::forHousing($housingId)->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Rejected])->count(),
            'pendingInvoices' => Invoice::with('resident', 'paymentBatch')
                ->forHousing($housingId)
                ->where('status', InvoiceStatus::Pending)
                ->paymentRepresentatives()
                ->oldest('submitted_at')
                ->limit(5)
                ->get(),
            'recentCashflows' => Cashflow::forHousing($housingId)->latest()->limit(7)->get(),
        ]);
    }
}
