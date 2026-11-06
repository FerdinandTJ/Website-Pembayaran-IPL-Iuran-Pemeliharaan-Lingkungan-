<?php

namespace App\Http\Controllers\Pengurus;

use App\Enums\CashflowCategory;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Services\ReportPdf;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'bookPeriod' => $report === 'cashflow' ? AccountingPeriod::with('closer')->where('housing_id', $request->user()->housing_id)->whereDate('period', $month)->first() : null,
            'categories' => CashflowCategory::cases(), 'categorySummary' => $data['category_summary'] ?? collect(),
        ]);
    }

    public function export(Request $request, string $report, ReportService $service)
    {
        [$data, $month, $filters] = $this->data($request, $report, $service);
        $headers = match ($report) {
            'arrears' => ['Warga', 'Alamat', 'Status Warga', 'Jumlah Tagihan', 'Total Tunggakan', '1–30 Hari', '31–60 Hari', '>60 Hari', 'Jatuh Tempo Tertua'],
            'billing' => ['Warga', 'Alamat', 'Tagihan', 'Periode', 'Jatuh Tempo', 'Nominal', 'Status', 'Rumah', 'Metode'],
            'cashflow' => ['Tanggal Transaksi', 'Tanggal Pencatatan', 'Transaksi', 'Jenis', 'Kategori', 'Sumber', 'Nominal', 'Metode'],
        };
        $label = ['arrears' => 'tunggakan', 'billing' => 'tagihan', 'cashflow' => 'kas'][$report];
        if ($report === 'cashflow') {
            $label .= ! empty($filters['type']) ? '-'.($filters['type'] === 'income' ? 'pemasukan' : 'pengeluaran') : '';
            $label .= ! empty($filters['category']) ? '-'.$filters['category'] : '';
        }
        $stamp = $report === 'arrears' ? today('Asia/Jakarta')->format('Y-m-d') : $month->format('Y-m');

        return response()->streamDownload(function () use ($data, $headers, $report, $month, $filters) {
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
                'cashflow' => ['opening' => 'Saldo Awal Seluruh Kas', 'period_income' => 'Pemasukan Seluruh Kas Bulan Ini', 'period_expense' => 'Pengeluaran Seluruh Kas Bulan Ini', 'closing' => 'Saldo Akhir Seluruh Kas', 'income' => 'Pemasukan Sesuai Filter', 'expense' => 'Pengeluaran Sesuai Filter', 'net' => 'Selisih Sesuai Filter', 'count' => 'Jumlah Transaksi Sesuai Filter', 'manual' => 'Pemasukan Manual Sesuai Filter', 'invoice' => 'Pemasukan Tagihan Sesuai Filter'],
            };
            foreach ($names as $key => $label) {
                $write([$label, $data['summary'][$key]]);
            }
            if ($report === 'billing') {
                foreach (InvoiceStatus::cases() as $status) {
                    $statusSummary = $data['summary']['statuses'][$status->value];
                    $write([$status->label(), $statusSummary->count, $statusSummary->amount]);
                }
            }
            if ($report === 'cashflow') {
                $write(['Jenis transaksi', ['income' => 'Pemasukan', 'expense' => 'Pengeluaran'][$filters['type'] ?? ''] ?? 'Semua transaksi']);
                $write(['Kategori', ! empty($filters['category']) ? CashflowCategory::from($filters['category'])->label() : 'Semua kategori']);
                $write(['Sumber', ['manual' => 'Manual', 'invoice' => 'Tagihan'][$filters['source'] ?? ''] ?? 'Semua sumber']);
                $write(['Pencarian', $filters['search'] ?? '']);
                $write([]);
                $write(['Rekap per Kategori', 'Jumlah Transaksi', 'Pemasukan', 'Pengeluaran', 'Selisih']);
                foreach ($data['category_summary'] as $category) {
                    $write([$category->category->label(), $category->count, $category->income, $category->expense, (float) $category->income - (float) $category->expense]);
                }
                $write([]);
            }
            $write($headers);
            foreach ($data['query']->lazy(200) as $row) {
                $write(match ($report) {
                    'arrears' => [$row->name, $row->house_address, $row->is_active ? 'Aktif' : 'Nonaktif', $row->invoice_count, $row->total, $row->age_30, $row->age_60, $row->age_over_60, $row->oldest_due],
                    'billing' => [$row->resident->name, $row->house_label ?: $row->resident->house_address, $row->title, $row->billing_period->format('Y-m'), $row->due_date->toDateString(), $row->total_amount, $row->status->label(), $row->house_label, $row->payment_label],
                    'cashflow' => [$row->accounting_date?->toDateString(), $row->created_at->format('Y-m-d H:i:s'), $row->name, $row->type === 'income' ? 'Pemasukan' : 'Pengeluaran', $row->category->label(), $row->isManual() ? 'Manual' : 'Tagihan', $row->total_cashflow, $row->method_label],
                });
            }
            fclose($output);
        }, "laporan-{$label}-{$stamp}.csv", ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    public function pdf(Request $request, string $report, ReportService $service, ReportPdf $pdf)
    {
        [$data, $month, $filters] = $this->data($request, $report, $service);
        if ((clone $data['query'])->toBase()->getCountForPagination() > 2000) {
            throw ValidationException::withMessages(['report' => 'PDF dibatasi 2.000 baris. Persempit filter atau gunakan ekspor CSV untuk seluruh data.']);
        }
        $title = match ($report) {
            'arrears' => 'Laporan Tunggakan',
            'billing' => 'Laporan Tagihan Bulanan',
            'cashflow' => ['income' => 'Laporan Pemasukan', 'expense' => 'Laporan Pengeluaran'][$filters['type'] ?? ''] ?? 'Laporan Kas Gabungan',
        };
        $filterLabels = [];
        if ($report === 'cashflow') {
            $filterLabels['Jenis'] = ['income' => 'Pemasukan', 'expense' => 'Pengeluaran'][$filters['type'] ?? ''] ?? 'Semua transaksi';
            $filterLabels['Kategori'] = ! empty($filters['category']) ? CashflowCategory::from($filters['category'])->label() : 'Semua kategori';
            $filterLabels['Sumber'] = ['invoice' => 'Pembayaran tagihan', 'manual' => 'Transaksi manual'][$filters['source'] ?? ''] ?? 'Semua sumber';
        }
        if ($report === 'arrears' && ! empty($filters['age'])) {
            $filterLabels['Umur tunggakan'] = ['1-30' => '1-30 hari', '31-60' => '31-60 hari', '61+' => 'Lebih dari 60 hari'][$filters['age']];
        }
        if ($report === 'arrears' && ! empty($filters['resident_id'])) {
            $filterLabels['Warga'] = $request->user()->housing->users()->findOrFail($filters['resident_id'])->name;
        }
        if (isset($filters['search']) && $filters['search'] !== '') {
            $filterLabels['Pencarian'] = $filters['search'];
        }
        $label = ['arrears' => 'tunggakan', 'billing' => 'tagihan', 'cashflow' => 'kas'][$report];
        if ($report === 'cashflow') {
            $label .= ! empty($filters['type']) ? '-'.($filters['type'] === 'income' ? 'pemasukan' : 'pengeluaran') : '';
            $label .= ! empty($filters['category']) ? '-'.$filters['category'] : '';
        }
        $stamp = $report === 'arrears' ? today('Asia/Jakarta')->format('Y-m-d') : $month->format('Y-m');
        $content = $pdf->render([
            'report' => $report, 'title' => $title, 'month' => $month,
            'housing' => $request->user()->housing, 'preparedBy' => $request->user()->name,
            'generatedAt' => now('Asia/Jakarta'), 'filterLabels' => $filterLabels,
            'bookPeriod' => $report === 'cashflow' ? AccountingPeriod::with('closer')->where('housing_id', $request->user()->housing_id)->whereDate('period', $month)->first() : null,
            'summary' => $data['summary'], 'categorySummary' => $data['category_summary'] ?? collect(),
            'rows' => $data['query']->get(),
        ]);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-'.$label.'-'.$stamp.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function data(Request $request, string $report, ReportService $service): array
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'], 'search' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', Rule::in(['1-30', '31-60', '61+'])],
            'type' => ['nullable', Rule::in(['income', 'expense'])],
            'category' => ['nullable', Rule::enum(CashflowCategory::class)],
            'source' => ['nullable', Rule::in(['manual', 'invoice'])],
            'resident_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('housing_id', $request->user()->housing_id)->where('role', 'warga')],
        ]);
        $month = Carbon::createFromFormat('!Y-m', $filters['month'] ?? today('Asia/Jakarta')->format('Y-m'), 'Asia/Jakarta');
        $housing = $request->user()->housing_id;
        $data = match ($report) {
            'arrears' => $service->arrears($housing, $filters),
            'billing' => $service->billing($housing, $month),
            'cashflow' => $service->cashflow($housing, $month, $filters),
        };

        return [$data, $month, $filters];
    }
}
