<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Services\AccountingPeriodService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountingPeriodController extends Controller
{
    public function index(Request $request, ReportService $reports)
    {
        $data = $request->validate(['period' => ['nullable', 'date_format:Y-m']]);
        $period = $data['period'] ?? today('Asia/Jakarta')->subMonthNoOverflow()->format('Y-m');
        $month = Carbon::createFromFormat('!Y-m', $period);

        return view('pengurus.accounting-periods.index', ['period' => $period,
            'summary' => $reports->cashflow($request->user()->housing_id, $month)['summary'],
            'selected' => AccountingPeriod::where('housing_id', $request->user()->housing_id)->whereDate('period', $month)->first(),
            'periods' => AccountingPeriod::with('closer')->where('housing_id', $request->user()->housing_id)->latest('period')->paginate(12)]);
    }

    public function close(Request $request, AccountingPeriodService $service)
    {
        $data = $request->validate(['period' => ['required', 'date_format:Y-m'], 'confirmed' => ['accepted']]);
        $service->close($request->user(), $data['period']);

        return redirect()->route('pengurus.accounting-periods.index', ['period' => $data['period']])->with('success', 'Buku kas ditutup. Transaksi pada bulan tersebut dikunci.');
    }

    public function reopen(Request $request, AccountingPeriod $accountingPeriod, AccountingPeriodService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $service->reopen($request->user(), $accountingPeriod, $data['reason']);

        return back()->with('success', 'Buku kas dibuka kembali. Alasan dan tindakan tercatat pada riwayat aktivitas.');
    }
}
