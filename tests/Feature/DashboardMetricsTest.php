<?php

namespace Tests\Feature;

use App\Models\Cashflow;
use App\Models\Housing;
use App\Services\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_groups_six_calendar_months_and_excludes_other_housings(): void
    {
        $this->travelTo(Carbon::parse('2026-11-03 12:00:00', 'Asia/Jakarta'));
        $housing = Housing::create(['name' => 'Griya Asri', 'address' => 'Jl. Hijau']);
        $other = Housing::create(['name' => 'Griya Lain', 'address' => 'Jl. Biru']);

        foreach ([
            [$housing->id, '2026-05-31 23:59:59', 'income', 999999],
            [$housing->id, '2026-06-01 00:00:00', 'income', 100000],
            [$housing->id, '2026-06-30 23:59:59', 'income', 50000],
            [$housing->id, '2026-07-01 00:00:00', 'expense', 25000],
            [$housing->id, '2026-11-02 12:00:00', 'income', 200000],
            [$housing->id, '2026-12-01 00:00:00', 'income', 999999],
            [$other->id, '2026-11-02 12:00:00', 'income', 999999],
        ] as [$housingId, $date, $type, $amount]) {
            Cashflow::forceCreate([
                'housing_id' => $housingId, 'name' => 'Transaksi kas', 'type' => $type,
                'source' => 'manual', 'total_cashflow' => $amount,
                'created_at' => $date, 'updated_at' => $date,
            ]);
        }

        $series = app(DashboardMetrics::class)->cashflow($housing->id);

        $this->assertCount(6, $series);
        $this->assertSame([150000.0, 0.0, 0.0, 0.0, 0.0, 200000.0], array_column($series, 'income'));
        $this->assertSame([0.0, 25000.0, 0.0, 0.0, 0.0, 0.0], array_column($series, 'expense'));
        $this->assertSame(today('Asia/Jakarta')->subMonths(5)->translatedFormat('F Y'), $series[0]['period']);
        $this->assertSame(today('Asia/Jakarta')->translatedFormat('F Y'), $series[5]['period']);
    }
}
