<?php

namespace App\Services;

use App\Models\Cashflow;

class DashboardMetrics
{
    public function cashflow(int $housingId): array
    {
        $start = today('Asia/Jakarta')->startOfMonth()->subMonths(5);
        $query = Cashflow::forHousing($housingId)->accountingRange($start, $start->copy()->addMonths(6));
        $accountingDate = Cashflow::ACCOUNTING_DATE_SQL;
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $next = $month->copy()->addMonth();
            foreach (['income', 'expense'] as $type) {
                $query->selectRaw("COALESCE(SUM(CASE WHEN {$accountingDate} >= ? AND {$accountingDate} < ? AND type = ? THEN total_cashflow ELSE 0 END), 0) AS {$type}_{$i}", [$month->toDateString(), $next->toDateString(), $type]);
            }
            $months[] = ['label' => $month->translatedFormat('M'), 'period' => $month->translatedFormat('F Y')];
        }
        $totals = $query->first();
        foreach ($months as $i => &$month) {
            $month['income'] = (float) $totals->{'income_'.$i};
            $month['expense'] = (float) $totals->{'expense_'.$i};
        }

        return $months;
    }
}
