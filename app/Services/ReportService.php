<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Cashflow;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function overdue(int $housingId): Builder
    {
        return Invoice::where('invoices.housing_id', $housingId)
            ->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Rejected])
            ->whereDate('due_date', '<', today('Asia/Jakarta')->toDateString());
    }

    public function arrears(int $housingId, array $filters): array
    {
        $day30 = today('Asia/Jakarta')->subDays(30)->toDateString();
        $day60 = today('Asia/Jakarta')->subDays(60)->toDateString();
        $base = $this->overdue($housingId)->join('users', 'users.id', '=', 'invoices.user_id')
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q
                ->where('users.name', 'like', "%{$value}%")->orWhere('users.house_address', 'like', "%{$value}%")))
            ->when($filters['resident_id'] ?? null, fn ($q, $id) => $q->where('users.id', $id));
        $query = (clone $base)->select('users.id', 'users.name', 'users.house_address', 'users.is_active')
            ->selectRaw('COUNT(*) AS invoice_count, SUM(total_amount) AS total, MIN(due_date) AS oldest_due')
            ->selectRaw('SUM(CASE WHEN due_date >= ? THEN total_amount ELSE 0 END) AS age_30', [$day30])
            ->selectRaw('SUM(CASE WHEN due_date < ? AND due_date >= ? THEN total_amount ELSE 0 END) AS age_60', [$day30, $day60])
            ->selectRaw('SUM(CASE WHEN due_date < ? THEN total_amount ELSE 0 END) AS age_over_60', [$day60])
            ->groupBy('users.id', 'users.name', 'users.house_address', 'users.is_active');
        $bucket = ['1-30' => 'age_30', '31-60' => 'age_60', '61+' => 'age_over_60'][$filters['age'] ?? ''] ?? null;
        if ($bucket) {
            $query->having($bucket, '>', 0);
        }
        $summary = (array) DB::query()->fromSub((clone $query)->toBase(), 'arrears')->selectRaw(
            'COUNT(*) AS residents, COALESCE(SUM(invoice_count), 0) AS invoices, COALESCE(SUM(total), 0) AS total,
             COALESCE(SUM(age_30), 0) AS age_30, COALESCE(SUM(age_60), 0) AS age_60,
             COALESCE(SUM(age_over_60), 0) AS age_over_60'
        )->first();

        return ['query' => $query->orderBy('oldest_due')->orderBy('users.id'), 'summary' => $summary];
    }

    public function billing(int $housingId, Carbon $month): array
    {
        $query = Invoice::with('resident')->forHousing($housingId)->whereDate('billing_period', $month->toDateString());
        $statuses = [];
        foreach (InvoiceStatus::cases() as $status) {
            $statuses[$status->value] = (clone $query)->where('status', $status)->selectRaw('COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS amount')->first();
        }
        $total = array_sum(array_map(fn ($row) => (float) $row->amount, $statuses));

        return [
            'query' => $query->orderBy('id'),
            'summary' => [
                'statuses' => $statuses, 'total' => $total,
                'percentage' => $total > 0 ? round($statuses['verified']->amount / $total * 100, 1) : 0,
            ],
        ];
    }

    public function cashflow(int $housingId, Carbon $month, array $filters = []): array
    {
        $base = Cashflow::forHousing($housingId);
        $before = (clone $base)->accountingBefore($month);
        $opening = (float) (clone $before)->where('type', 'income')->sum('total_cashflow')
            - (float) (clone $before)->where('type', 'expense')->sum('total_cashflow');
        $period = (clone $base)->accountingRange($month, $month->copy()->addMonth());
        $periodIncome = (float) (clone $period)->where('type', 'income')->sum('total_cashflow');
        $periodExpense = (float) (clone $period)->where('type', 'expense')->sum('total_cashflow');
        $query = (clone $period)->matchingFilters($filters);
        $income = (float) (clone $query)->where('type', 'income')->sum('total_cashflow');
        $expense = (float) (clone $query)->where('type', 'expense')->sum('total_cashflow');
        $categories = (clone $query)->select('category')->selectRaw('COUNT(*) as count')
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN total_cashflow ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN total_cashflow ELSE 0 END) as expense")
            ->groupBy('category')->orderBy('category')->get();

        return [
            'query' => $query->with('invoice', 'paymentBatch')->orderByRaw(Cashflow::ACCOUNTING_DATE_SQL)->orderBy('id'),
            'category_summary' => $categories,
            'summary' => [
                // Actual housing balances remain independent of the detail filters.
                'opening' => $opening, 'closing' => $opening + $periodIncome - $periodExpense,
                'period_income' => $periodIncome, 'period_expense' => $periodExpense,
                'income' => $income, 'expense' => $expense, 'net' => $income - $expense,
                'count' => (clone $query)->count(),
                'filtered' => (bool) array_filter(array_intersect_key($filters, array_flip(['type', 'category', 'source', 'search'])), fn ($v) => $v !== null && $v !== ''),
                'manual' => (float) (clone $query)->where('type', 'income')->where('source', 'manual')->sum('total_cashflow'),
                'invoice' => (float) (clone $query)->where('type', 'income')->where('source', 'invoice')->sum('total_cashflow'),
            ],
        ];
    }
}
