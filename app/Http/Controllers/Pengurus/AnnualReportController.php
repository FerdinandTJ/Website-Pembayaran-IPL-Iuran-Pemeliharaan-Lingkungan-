<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Services\AnnualReportService;
use App\Services\ReportPdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AnnualReportController extends Controller
{
    public function index(Request $request, AnnualReportService $service)
    {
        [$year, $filters, $query] = $this->data($request, $service);
        $groups = (clone $query)->orderBy('group_type')->orderBy('code')->orderBy('resident')->orderBy('group_id')->paginate(20)->withQueryString();
        $details = ! empty($filters['detail_house_id']) || ! empty($filters['detail_resident_id'])
            ? $service->details($request->user()->housing_id, $year, $filters)->paginate(15, ['*'], 'details_page')->withQueryString() : null;

        return view('pengurus.reports.annual', [
            'year' => $year, 'month' => Carbon::create($year, 1, 1), 'filters' => $filters,
            'yearOptions' => $service->yearOptions($request->user()->housing_id, $year),
            'groups' => $groups, 'rows' => $service->matrix($request->user()->housing_id, $year, $groups->getCollection()),
            'summary' => $service->summary($request->user()->housing_id, $year, $query), 'details' => $details,
        ]);
    }

    public function export(Request $request, AnnualReportService $service)
    {
        [$year, $filters, $query] = $this->data($request, $service);
        $housingId = $request->user()->housing_id;
        $summary = $service->summary($housingId, $year, $query);

        return response()->streamDownload(function () use ($query, $housingId, $year, $filters, $summary, $service) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            $write = function (array $row) use ($stream) {
                fputcsv($stream, array_map(fn ($value) => is_string($value) && preg_match('/^[\s\x{FEFF}]*[=+@-]/u', $value) ? "'".$value : $value, $row), ',', '"', '');
            };
            $write(['Rekap Tahunan per Rumah', $year]);
            $write(['Status', AnnualReportService::STATUSES[$filters['payment_status'] ?? ''] ?? 'Semua status']);
            $write(['Pencarian', $filters['search'] ?? '']);
            foreach (['groups' => 'Jumlah Baris', 'invoice_count' => 'Jumlah Tagihan', 'billed' => 'Total Tagihan', 'paid' => 'Lunas', 'pending' => 'Menunggu Verifikasi', 'unpaid' => 'Belum Lunas', 'overdue' => 'Sudah Jatuh Tempo'] as $key => $label) {
                $write([$label, $summary[$key]]);
            }
            $write(['Keterangan', 'Periode tagihan dengan status saat ini. Tanpa tagihan bukan berarti lunas. Belum lunas tidak termasuk menunggu verifikasi.']);
            $months = array_map(fn ($month) => Carbon::create($year, $month, 1)->translatedFormat('M'), range(1, 12));
            $write(['Rumah', 'Alamat', 'Penanggung Jawab Saat Ini / Warga Tanpa Rumah', ...$months, 'Jumlah Tagihan', 'Total Tagihan', 'Lunas', 'Menunggu Verifikasi', 'Belum Lunas']);
            (clone $query)->orderBy('group_type')->orderBy('code')->orderBy('resident')->orderBy('group_id')->chunk(100, function ($groups) use ($service, $housingId, $year, $write) {
                foreach ($service->matrix($housingId, $year, $groups) as $row) {
                    $cells = array_map(fn ($cell) => AnnualReportService::STATUSES[$cell['status']].($cell['status'] === 'unpaid' && $cell['pending_count'] > 0 ? ' + Verifikasi' : ''), $row->months);
                    $write([$row->group_type === 'house' ? $row->code : 'Tanpa Rumah Tercatat', $row->address, $row->resident, ...$cells,
                        $row->totals['invoice_count'], $row->totals['billed'], $row->totals['paid'], $row->totals['pending'], $row->totals['unpaid']]);
                }
            });
            fclose($stream);
        }, 'rekap-tahunan-rumah-'.$year.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function pdf(Request $request, AnnualReportService $service, ReportPdf $pdf)
    {
        [$year, $filters, $query] = $this->data($request, $service);
        if ((clone $query)->count() > 500) {
            throw ValidationException::withMessages(['report' => 'PDF rekap tahunan dibatasi 500 baris. Persempit filter atau gunakan CSV untuk seluruh data.']);
        }
        $groups = (clone $query)->orderBy('group_type')->orderBy('code')->orderBy('resident')->orderBy('group_id')->get();
        $content = $pdf->render([
            'report' => 'annual', 'year' => $year, 'filters' => $filters, 'housing' => $request->user()->housing,
            'preparedBy' => $request->user()->name, 'generatedAt' => now('Asia/Jakarta'),
            'summary' => $service->summary($request->user()->housing_id, $year, $query),
            'rows' => $service->matrix($request->user()->housing_id, $year, $groups),
        ]);

        return response($content, 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rekap-tahunan-rumah-'.$year.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }

    private function data(Request $request, AnnualReportService $service): array
    {
        $housingId = $request->user()->housing_id;
        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'search' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['nullable', Rule::in(array_keys(AnnualReportService::STATUSES))],
            'detail_house_id' => ['nullable', 'integer', 'prohibits:detail_resident_id', Rule::exists('houses', 'id')->where('housing_id', $housingId)],
            'detail_resident_id' => ['nullable', 'integer', 'prohibits:detail_house_id', Rule::exists('users', 'id')->where('housing_id', $housingId)->where('role', 'warga')],
            'detail_month' => ['required_with:detail_house_id,detail_resident_id', 'nullable', 'integer', 'between:1,12'],
        ]);
        $year = (int) ($filters['year'] ?? today('Asia/Jakarta')->year);

        return [$year, $filters, $service->groups($housingId, $year, $filters)];
    }
}
