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

    public function cashflow(int $housingId, Carbon $month): array
    {
        $base = Cashflow::forHousing($housingId);
        $before = (clone $base)->where('created_at', '<', $month);
        $opening = (float) (clone $before)->where('type', 'income')->sum('total_cashflow')
            - (float) (clone $before)->where('type', 'expense')->sum('total_cashflow');
        $query = $base->where('created_at', '>=', $month)->where('created_at', '<', $month->copy()->addMonth());
        $income = (float) (clone $query)->where('type', 'income')->sum('total_cashflow');
        $expense = (float) (clone $query)->where('type', 'expense')->sum('total_cashflow');

        return [
            'query' => $query->with('invoice')->orderBy('created_at')->orderBy('id'),
            'summary' => [
                'opening' => $opening, 'income' => $income, 'expense' => $expense, 'closing' => $opening + $income - $expense,
                'manual' => (float) (clone $query)->where('type', 'income')->where('source', 'manual')->sum('total_cashflow'),
                'invoice' => (float) (clone $query)->where('type', 'income')->where('source', 'invoice')->sum('total_cashflow'),
            ],
        ];
    }
}
