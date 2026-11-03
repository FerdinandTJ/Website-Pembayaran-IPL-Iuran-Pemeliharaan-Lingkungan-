<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request, string $report, ReportService $service)
    {
        [$data, $month, $filters] = $this->data($request, $report, $service);
        $details = $report === 'arrears' && ! empty($filters['resident_id'])
            ? $service->overdue($request->user()->housing_id)->where('user_id', $filters['resident_id'])
                ->orderBy('due_date')->paginate(15, ['*'], 'details_page')->withQueryString() : null;

        return view('pengurus.reports.index', [
            'report' => $report, 'month' => $month, 'summary' => $data['summary'],
            'rows' => $data['query']->paginate(15)->withQueryString(), 'details' => $details,
        ]);
    }

    public function export(Request $request, string $report, ReportService $service)
    {
        [$data, $month] = $this->data($request, $report, $service);
        $headers = match ($report) {
            'arrears' => ['Warga', 'Alamat', 'Status Warga', 'Jumlah Tagihan', 'Total Tunggakan', '1–30 Hari', '31–60 Hari', '>60 Hari', 'Jatuh Tempo Tertua'],
            'billing' => ['Warga', 'Alamat', 'Tagihan', 'Periode', 'Jatuh Tempo', 'Nominal', 'Status', 'Rumah', 'Metode'],
            'cashflow' => ['Tanggal Pencatatan', 'Transaksi', 'Jenis', 'Sumber', 'Nominal', 'Metode'],
        };
        $label = ['arrears' => 'tunggakan', 'billing' => 'tagihan', 'cashflow' => 'kas'][$report];
        $stamp = $report === 'arrears' ? today('Asia/Jakarta')->format('Y-m-d') : $month->format('Y-m');

        return response()->streamDownload(function () use ($data, $headers, $report, $month) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            $write = function (array $row) use ($output) {
                $safe = array_map(function ($value) {
                    if (is_string($value) && preg_match('/^[\s\x{FEFF}]*[=+@-]/u', $value)) {
                        return "'".$value;
                    }

                    return $value;
                }, $row);
                fputcsv($output, $safe, ',', '"', '');
            };
            $write([$report === 'arrears' ? 'Tunggakan per tanggal' : 'Periode laporan', $report === 'arrears' ? today('Asia/Jakarta')->toDateString() : $month->format('Y-m')]);
            $names = match ($report) {
                'arrears' => ['total' => 'Total Tunggakan', 'residents' => 'Warga Menunggak', 'invoices' => 'Tagihan Menunggak', 'age_30' => 'Umur 1–30 Hari', 'age_60' => 'Umur 31–60 Hari', 'age_over_60' => 'Umur >60 Hari'],
                'billing' => ['total' => 'Total Nominal Tagihan', 'percentage' => 'Persentase Nominal Terverifikasi'],
                'cashflow' => ['opening' => 'Saldo Awal', 'income' => 'Pemasukan', 'expense' => 'Pengeluaran', 'closing' => 'Saldo Akhir', 'manual' => 'Pemasukan Manual', 'invoice' => 'Pemasukan Tagihan'],
            };
            foreach ($names as $key => $label) {
                $write([$label, $data['summary'][$key]]);
            }
            if ($report === 'billing') {
                foreach (\App\Enums\InvoiceStatus::cases() as $status) {
                    $statusSummary = $data['summary']['statuses'][$status->value];
                    $write([$status->label(), $statusSummary->count, $statusSummary->amount]);
                }
            }
            $write($headers);
            foreach ($data['query']->lazy(200) as $row) {
                $write(match ($report) {
                    'arrears' => [$row->name, $row->house_address, $row->is_active ? 'Aktif' : 'Nonaktif', $row->invoice_count, $row->total, $row->age_30, $row->age_60, $row->age_over_60, $row->oldest_due],
                    'billing' => [$row->resident->name, $row->house_label ?: $row->resident->house_address, $row->title, $row->billing_period->format('Y-m'), $row->due_date->toDateString(), $row->total_amount, $row->status->label(), $row->house_label, $row->payment_label],
                    'cashflow' => [$row->created_at->format('Y-m-d H:i:s'), $row->name, $row->type === 'income' ? 'Pemasukan' : 'Pengeluaran', $row->isManual() ? 'Manual' : 'Tagihan', $row->total_cashflow, $row->invoice?->payment_label ?? 'Manual'],
                });
            }
            fclose($output);
        }, "laporan-{$label}-{$stamp}.csv", ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    private function data(Request $request, string $report, ReportService $service): array
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'], 'search' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', Rule::in(['1-30', '31-60', '61+'])],
            'resident_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('housing_id', $request->user()->housing_id)->where('role', 'warga')],
        ]);
        $month = Carbon::createFromFormat('!Y-m', $filters['month'] ?? today('Asia/Jakarta')->format('Y-m'), 'Asia/Jakarta');
        $housing = $request->user()->housing_id;
        $data = match ($report) {
            'arrears' => $service->arrears($housing, $filters),
            'billing' => $service->billing($housing, $month),
            'cashflow' => $service->cashflow($housing, $month),
        };

        return [$data, $month, $filters];
    }
}
