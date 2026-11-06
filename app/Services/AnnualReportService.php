<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnnualReportService
{
    public const STATUSES = ['paid' => 'Lunas', 'pending' => 'Menunggu Verifikasi', 'unpaid' => 'Belum Lunas', 'not_issued' => 'Belum Ada Tagihan'];

    public function yearOptions(int $housingId, int $selectedYear): array
    {
        $currentYear = today('Asia/Jakarta')->year;
        $periods = Invoice::forHousing($housingId)
            ->selectRaw('MIN(billing_period) as earliest, MAX(billing_period) as latest')->first();
        $earliest = min($currentYear - 5, $selectedYear, $periods->earliest ? Carbon::parse($periods->earliest)->year : $currentYear);
        $latest = max($currentYear + 1, $selectedYear, $periods->latest ? Carbon::parse($periods->latest)->year : $currentYear);

        return range(min(2100, $latest), max(2000, $earliest));
    }

    public function groups(int $housingId, int $year, array $filters = []): Builder
    {
        [$start, $end] = $this->bounds($year);
        $houses = DB::table('houses')->leftJoin('users as payer', function ($join) use ($housingId) {
            $join->on('payer.id', '=', 'houses.responsible_user_id')->where('payer.housing_id', $housingId);
        })->where('houses.housing_id', $housingId)->selectRaw("'house' as group_type, houses.id as group_id, houses.code, houses.address, COALESCE(payer.name, '') as resident");
        // A historical invoice without house_id cannot safely be assigned using
        // the resident's current home. Keep a separate row for that account.
        $unlinked = DB::table('users')->where('users.housing_id', $housingId)->whereExists(function ($q) use ($housingId, $start, $end) {
            $q->selectRaw('1')->from('invoices')->where('housing_id', $housingId)->whereNull('house_id')
                ->whereColumn('user_id', 'users.id')->where('billing_period', '>=', $start)->where('billing_period', '<', $end);
        })->selectRaw("'resident' as group_type, users.id as group_id, '' as code, '' as address, users.name as resident");
        $query = DB::query()->fromSub($houses->unionAll($unlinked), 'annual_groups');
        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn ($q) => $q->where('code', 'like', $search)->orWhere('address', 'like', $search)->orWhere('resident', 'like', $search));
        }
        $status = $filters['payment_status'] ?? '';
        if ($status !== '') {
            $exists = function ($q) use ($housingId, $start, $end, $status) {
                $q->selectRaw('1')->from('invoices as annual_invoices')->where('annual_invoices.housing_id', $housingId)
                    ->where('annual_invoices.billing_period', '>=', $start)->where('annual_invoices.billing_period', '<', $end);
                $this->matchGroup($q, 'annual_invoices', 'annual_groups');
                if ($status === 'unpaid') {
                    $q->whereIn('annual_invoices.status', ['unpaid', 'rejected']);
                } elseif ($status === 'pending') {
                    $q->where('annual_invoices.status', 'pending');
                }
            };
            if ($status === 'not_issued') {
                $query->whereNotExists($exists);
            } else {
                $query->whereExists($exists);
                if ($status === 'paid') {
                    $query->whereNotExists(function ($q) use ($housingId, $start, $end) {
                        $q->selectRaw('1')->from('invoices as annual_invoices')->where('annual_invoices.housing_id', $housingId)
                            ->where('annual_invoices.billing_period', '>=', $start)->where('annual_invoices.billing_period', '<', $end)
                            ->where('annual_invoices.status', '!=', 'verified');
                        $this->matchGroup($q, 'annual_invoices', 'annual_groups');
                    });
                }
            }
        }

        return $query;
    }

    public function summary(int $housingId, int $year, Builder $groups): array
    {
        [$start, $end] = $this->bounds($year);
        $query = DB::table('invoices')->where('housing_id', $housingId)->where('billing_period', '>=', $start)->where('billing_period', '<', $end)
            ->whereExists(function ($q) use ($groups) {
                $q->selectRaw('1')->fromSub(clone $groups, 'selected_groups');
                $this->matchGroup($q, 'invoices', 'selected_groups');
            });
        $totals = $query->selectRaw('COUNT(*) as invoice_count, COALESCE(SUM(total_amount), 0) as billed')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'verified' THEN total_amount ELSE 0 END), 0) as paid")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END), 0) as pending")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('unpaid', 'rejected') THEN total_amount ELSE 0 END), 0) as unpaid")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('unpaid', 'rejected') AND due_date < ? THEN total_amount ELSE 0 END), 0) as overdue", [today('Asia/Jakarta')->toDateString()])->first();

        return ['groups' => (clone $groups)->count(), 'unlinked' => (clone $groups)->where('group_type', 'resident')->count(),
            'invoice_count' => (int) $totals->invoice_count, 'billed' => (float) $totals->billed,
            'paid' => (float) $totals->paid, 'pending' => (float) $totals->pending,
            'unpaid' => (float) $totals->unpaid, 'overdue' => (float) $totals->overdue];
    }

    public function matrix(int $housingId, int $year, Collection $groups): Collection
    {
        if ($groups->isEmpty()) {
            return collect();
        }
        [$start, $end] = $this->bounds($year);
        $houseIds = $groups->where('group_type', 'house')->pluck('group_id');
        $userIds = $groups->where('group_type', 'resident')->pluck('group_id');
        $months = DB::table('invoices')->where('housing_id', $housingId)->where('billing_period', '>=', $start)->where('billing_period', '<', $end)
            ->where(fn ($q) => $q->whereIn('house_id', $houseIds)->orWhere(fn ($q) => $q->whereNull('house_id')->whereIn('user_id', $userIds)))
            ->select('house_id', 'user_id', 'billing_period')->selectRaw('COUNT(*) as invoice_count, SUM(total_amount) as billed')
            ->selectRaw("SUM(CASE WHEN status = 'verified' THEN total_amount ELSE 0 END) as paid")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status IN ('unpaid', 'rejected') THEN total_amount ELSE 0 END) as unpaid")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN status IN ('unpaid', 'rejected') THEN 1 ELSE 0 END) as unpaid_count")
            ->groupBy('house_id', 'user_id', 'billing_period')->get();
        $indexed = $months->groupBy(fn ($row) => ($row->house_id ? 'house:'.$row->house_id : 'resident:'.$row->user_id).':'.Carbon::parse($row->billing_period)->month);

        return $groups->map(function ($group) use ($indexed) {
            $cells = [];
            $totals = ['invoice_count' => 0, 'billed' => 0.0, 'paid' => 0.0, 'pending' => 0.0, 'unpaid' => 0.0];
            for ($month = 1; $month <= 12; $month++) {
                $entries = $indexed->get($group->group_type.':'.$group->group_id.':'.$month, collect());
                $cell = [];
                foreach (['invoice_count', 'billed', 'paid', 'pending', 'unpaid', 'pending_count', 'unpaid_count'] as $key) {
                    $cell[$key] = (float) $entries->sum($key);
                    if (array_key_exists($key, $totals)) {
                        $totals[$key] += $cell[$key];
                    }
                }
                $cell['status'] = $cell['invoice_count'] === 0.0 ? 'not_issued' : ($cell['unpaid_count'] > 0 ? 'unpaid' : ($cell['pending_count'] > 0 ? 'pending' : 'paid'));
                $cells[$month] = $cell;
            }

            return (object) array_merge((array) $group, ['months' => $cells, 'totals' => $totals]);
        });
    }

    public function details(int $housingId, int $year, array $filters)
    {
        [$start, $end] = $this->bounds($year);

        return Invoice::with('resident')->forHousing($housingId)->where('billing_period', '>=', $start)->where('billing_period', '<', $end)
            ->when(! empty($filters['detail_house_id']), fn ($q) => $q->where('house_id', $filters['detail_house_id']),
                fn ($q) => $q->whereNull('house_id')->where('user_id', $filters['detail_resident_id']))
            ->whereDate('billing_period', Carbon::create($year, (int) $filters['detail_month'], 1)->toDateString())->orderBy('id');
    }

    private function matchGroup(Builder $query, string $invoices, string $groups): void
    {
        $query->where(function ($q) use ($invoices, $groups) {
            $q->where(fn ($q) => $q->where($groups.'.group_type', 'house')->whereColumn($invoices.'.house_id', $groups.'.group_id'))
                ->orWhere(fn ($q) => $q->where($groups.'.group_type', 'resident')->whereNull($invoices.'.house_id')->whereColumn($invoices.'.user_id', $groups.'.group_id'));
        });
    }

    private function bounds(int $year): array
    {
        return [sprintf('%04d-01-01', $year), sprintf('%04d-01-01', $year + 1)];
    }
}
