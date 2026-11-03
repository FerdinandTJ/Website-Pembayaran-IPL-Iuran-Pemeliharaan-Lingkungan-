<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Cashflow;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $resident;

    private Housing $housing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-03 08:00:00', 'Asia/Jakarta'));
        $this->housing = Housing::create(['name' => 'Laporan A', 'address' => 'Jl. A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $this->housing->id]);
        $this->resident = User::factory()->create(['housing_id' => $this->housing->id]);
    }

    private function invoice(int $days, string $status = 'unpaid', ?User $resident = null): Invoice
    {
        return Invoice::create([
            'housing_id' => $this->housing->id, 'created_by' => $this->manager->id,
            'user_id' => ($resident ?? $this->resident)->id, 'title' => 'IPL '.$days,
            'billing_period' => '2026-11-01', 'due_date' => today()->subDays($days),
            'total_amount' => 10000, 'status' => $status,
        ]);
    }

    public function test_aging_boundaries_pending_exclusion_and_inactive_residents(): void
    {
        foreach ([1, 30, 31, 60, 61] as $days) {
            $this->invoice($days);
        }
        $this->invoice(0);
        $this->invoice(-1);
        $this->invoice(2, 'pending');
        $this->invoice(2, 'verified');
        $inactive = User::factory()->inactive()->create(['housing_id' => $this->housing->id]);
        $this->invoice(10, 'rejected', $inactive);
        $summary = app(ReportService::class)->arrears($this->housing->id, [])['summary'];
        $this->assertEquals(60000, $summary['total']);
        $this->assertEquals(30000, $summary['age_30']);
        $this->assertEquals(20000, $summary['age_60']);
        $this->assertEquals(10000, $summary['age_over_60']);
        $this->assertEquals(2, $summary['residents']);
        $this->assertEquals(6, $summary['invoices']);
        $this->actingAs($this->manager)->get(route('pengurus.reports.index', 'arrears'))
            ->assertOk()->assertSee($inactive->name)->assertSee('Nonaktif');
        $this->get(route('pengurus.dashboard'))->assertOk()->assertViewHas('overdueTotal', 60000);
        $this->get(route('pengurus.reports.index', ['report' => 'arrears', 'resident_id' => $this->resident->id]))
            ->assertOk()->assertSee('Rincian Tagihan Tertunggak');
    }

    public function test_aging_search_and_bucket_filters_keep_entire_resident_balance(): void
    {
        $this->resident->update(['name' => 'Warga Istimewa', 'house_address' => 'Blok Zebra']);
        $this->invoice(5);
        $this->invoice(70);
        $other = User::factory()->create(['housing_id' => $this->housing->id]);
        $this->invoice(5, 'unpaid', $other);
        $summary = app(ReportService::class)->arrears($this->housing->id, ['age' => '61+', 'search' => 'Zebra'])['summary'];
        $this->assertEquals(20000, $summary['total']);
        $this->assertEquals(1, $summary['residents']);
        $this->actingAs($this->manager)->get(route('pengurus.reports.index', ['report' => 'arrears', 'age' => '61+']))
            ->assertOk()->assertSee('Warga Istimewa')->assertDontSee($other->name);
    }

    public function test_billing_status_summary_multiple_invoices_and_empty_month(): void
    {
        foreach (InvoiceStatus::cases() as $status) {
            $this->invoice(1, $status->value);
        }
        $response = $this->actingAs($this->manager)->get(route('pengurus.reports.index', ['report' => 'billing', 'month' => '2026-11']));
        $response->assertOk()->assertViewHas('summary', fn ($s) => $s['total'] === 40000.0 && $s['percentage'] === 25.0);
        $this->get(route('pengurus.reports.index', ['report' => 'billing', 'month' => '2027-01']))
            ->assertOk()->assertViewHas('summary', fn ($s) => $s['total'] == 0 && $s['percentage'] === 0);
        $this->get(route('pengurus.reports.index', ['report' => 'billing', 'month' => '2026-13']))->assertSessionHasErrors('month');
    }

    public function test_monthly_cashflow_opening_closing_and_date_boundaries(): void
    {
        foreach ([['2026-10-31 23:59:59', 'income', 100000], ['2026-10-31 12:00:00', 'expense', 20000],
            ['2026-11-01 00:00:00', 'income', 50000], ['2026-11-30 23:59:59', 'expense', 10000],
            ['2026-12-01 00:00:00', 'income', 999999]] as [$date, $type, $amount]) {
            Cashflow::forceCreate(['housing_id' => $this->housing->id, 'name' => 'Kas', 'type' => $type,
                'source' => 'manual', 'total_cashflow' => $amount, 'created_at' => $date]);
        }
        $this->actingAs($this->manager)->get(route('pengurus.reports.index', ['report' => 'cashflow', 'month' => '2026-11']))
            ->assertOk()->assertViewHas('summary', fn ($s) => $s['opening'] === 80000.0 && $s['closing'] === 120000.0 && $s['manual'] === 50000.0);
        $this->get(route('pengurus.reports.index', ['report' => 'cashflow', 'month' => '2027-01']))
            ->assertOk()->assertViewHas('summary', fn ($s) => $s['income'] === 0.0 && $s['closing'] === $s['opening']);
    }

    public function test_csv_contains_all_pages_respects_filters_and_escapes_formulas(): void
    {
        $this->resident->update(['name' => '=HYPERLINK("bad")']);
        for ($i = 0; $i < 18; $i++) {
            $this->invoice(1)->update(['title' => 'CSV unique '.$i]);
        }
        $this->actingAs($this->manager)->get(route('pengurus.reports.index', ['report' => 'billing', 'month' => '2026-11']))
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 15 && $rows->total() === 18);
        foreach (['arrears', 'billing', 'cashflow'] as $report) {
            $response = $this->get(route('pengurus.reports.export', ['report' => $report, 'month' => '2026-11']));
            $response->assertOk()->assertDownload();
            $content = $response->streamedContent();
            if ($report === 'billing') {
                $this->assertStringContainsString('CSV unique 17', $content);
                $this->assertStringContainsString("'=HYPERLINK", $content);
            }
        }
        $filtered = $this->get(route('pengurus.reports.export', ['report' => 'arrears', 'search' => 'not found']))->streamedContent();
        $this->assertStringNotContainsString('HYPERLINK', $filtered);
    }

    public function test_reports_are_isolated_and_require_active_manager(): void
    {
        $this->invoice(1);
        $housingB = Housing::create(['name' => 'B', 'address' => 'B']);
        $managerB = User::factory()->manager()->create(['housing_id' => $housingB->id]);
        foreach (['arrears', 'billing', 'cashflow'] as $report) {
            $this->actingAs($managerB)->get(route('pengurus.reports.index', $report))->assertOk()->assertDontSee($this->resident->name);
            $this->get(route('pengurus.reports.export', $report))->assertOk();
            $this->actingAs($this->resident)->get(route('pengurus.reports.index', $report))->assertForbidden();
            $this->get(route('pengurus.reports.export', $report))->assertForbidden();
        }
        $this->actingAs($managerB)->get(route('pengurus.reports.index', ['report' => 'arrears', 'resident_id' => $this->resident->id]))
            ->assertSessionHasErrors('resident_id');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('pengurus.reports.index', 'arrears'))->assertForbidden();
        $this->manager->update(['is_active' => false]);
        $this->actingAs($this->manager)->get(route('pengurus.reports.index', 'arrears'))->assertForbidden();
    }
}
