<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AnnualReportService;
use App\Services\HouseService;
use App\Services\ReportPdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnnualReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $resident;

    private House $house;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-06 10:00:00', 'Asia/Jakarta'));
        $housing = Housing::create(['name' => 'Rekap Tahunan A', 'address' => 'A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->resident = User::factory()->create(['housing_id' => $housing->id, 'name' => 'Budi Tahunan']);
        $this->house = House::create(['housing_id' => $housing->id, 'code' => 'A-01', 'address' => 'Jalan Melati 1', 'occupancy' => 'occupied', 'responsible_user_id' => $this->resident->id, 'vacant_billing' => 'full']);
        $this->resident->update(['house_id' => $this->house->id]);
    }

    private function invoice(int $month, string $status = 'unpaid', ?House $house = null, ?User $resident = null, string $amount = '10000', int $year = 2026, bool $unlinked = false): Invoice
    {
        return Invoice::create(['housing_id' => $this->manager->housing_id, 'house_id' => $unlinked ? null : ($house ?? $this->house)->id,
            'house_label' => $unlinked ? null : ($house ?? $this->house)->label,
            'user_id' => ($resident ?? $this->resident)->id, 'created_by' => $this->manager->id, 'title' => 'Tagihan '.$month,
            'billing_period' => sprintf('%04d-%02d-01', $year, $month), 'due_date' => sprintf('%04d-%02d-10', $year, $month),
            'total_amount' => $amount, 'status' => $status]);
    }

    private function matrix(array $filters = [])
    {
        $service = app(AnnualReportService::class);

        return $service->matrix($this->manager->housing_id, 2026, $service->groups($this->manager->housing_id, 2026, $filters)->get());
    }

    public function test_months_combine_all_invoices_and_separate_pending_from_unpaid(): void
    {
        $this->invoice(1, 'verified');
        $this->invoice(1, 'verified', amount: '2500.50');
        $this->invoice(2, 'pending');
        $this->invoice(3, 'verified');
        $this->invoice(3, 'unpaid');
        $this->invoice(3, 'pending');
        $this->invoice(4, 'rejected');
        $this->invoice(12, 'verified', year: 2025);
        $this->invoice(1, 'verified', year: 2027);
        $row = $this->matrix()->first();
        $this->assertSame('paid', $row->months[1]['status']);
        $this->assertSame(12500.5, $row->months[1]['paid']);
        $this->assertSame('pending', $row->months[2]['status']);
        $this->assertSame('unpaid', $row->months[3]['status']);
        $this->assertSame(1.0, $row->months[3]['pending_count']);
        $this->assertSame('unpaid', $row->months[4]['status']);
        $this->assertSame('not_issued', $row->months[5]['status']);
        $this->assertSame('not_issued', $row->months[12]['status']);
        $this->assertSame(62500.5, $row->totals['billed']);
        $this->assertSame(22500.5, $row->totals['paid']);
        $this->assertSame(20000.0, $row->totals['pending']);
        $this->assertSame(20000.0, $row->totals['unpaid']);
        $this->actingAs($this->manager)->get(route('pengurus.annual-report.index', ['year' => 2026]))->assertOk()
            ->assertSee('Rekap Tahunan Rumah')->assertSee('Rp 22.500,50')
            ->assertViewHas('summary', fn ($summary) => $summary['invoice_count'] === 7 && $summary['billed'] === 62500.5 && $summary['overdue'] === 20000.0);
    }

    public function test_moving_resident_never_moves_historical_invoices_to_the_new_house(): void
    {
        $this->invoice(1, 'unpaid');
        $newHouse = House::create(['housing_id' => $this->manager->housing_id, 'code' => 'B-02', 'address' => 'Baru', 'occupancy' => 'occupied', 'vacant_billing' => 'full']);
        DB::transaction(fn () => app(HouseService::class)->link($this->resident, $newHouse->id, true, $this->manager));
        $this->invoice(2, 'verified', $newHouse);
        $replacement = User::factory()->create(['housing_id' => $this->manager->housing_id]);
        $this->invoice(1, 'pending', $this->house, $replacement);
        $rows = $this->matrix()->keyBy('code');
        $this->assertSame(20000.0, $rows['A-01']->totals['billed']);
        $this->assertSame('unpaid', $rows['A-01']->months[1]['status']);
        $this->assertSame(10000.0, $rows['A-01']->months[1]['pending']);
        $this->assertSame(10000.0, $rows['B-02']->totals['paid']);
        $this->assertSame('not_issued', $rows['B-02']->months[1]['status']);
    }

    public function test_legacy_invoices_without_house_remain_a_separate_account_row(): void
    {
        $this->invoice(1, 'unpaid', unlinked: true);
        $this->invoice(2, 'verified');
        $rows = $this->matrix();
        $this->assertCount(2, $rows);
        $this->assertSame(10000.0, $rows->firstWhere('group_type', 'house')->totals['paid']);
        $legacy = $rows->firstWhere('group_type', 'resident');
        $this->assertSame('Budi Tahunan', $legacy->resident);
        $this->assertSame(10000.0, $legacy->totals['unpaid']);
        $this->actingAs($this->manager)->get(route('pengurus.annual-report.index', ['year' => 2026]))
            ->assertOk()->assertSee('Tanpa Rumah Tercatat')->assertViewHas('summary', fn ($s) => $s['unlinked'] === 1 && $s['billed'] === 20000.0);
    }

    public function test_status_filters_include_empty_houses_and_do_not_treat_unissued_months_as_paid(): void
    {
        $this->invoice(1, 'verified');
        $empty = House::create(['housing_id' => $this->manager->housing_id, 'code' => 'KOSONG-0', 'address' => 'Kosong', 'occupancy' => 'vacant', 'vacant_billing' => 'exempt']);
        $this->assertCount(1, $this->matrix(['payment_status' => 'paid']));
        $this->assertCount(0, $this->matrix(['payment_status' => 'pending']));
        $this->assertSame($empty->id, $this->matrix(['payment_status' => 'not_issued'])->first()->group_id);
        $this->assertSame($empty->id, $this->matrix(['search' => '0', 'payment_status' => 'not_issued'])->first()->group_id);
        $this->invoice(2, 'pending');
        $this->assertCount(0, $this->matrix(['payment_status' => 'paid']));
        $this->assertCount(1, $this->matrix(['payment_status' => 'pending']));
        $this->invoice(3, 'rejected');
        $this->assertCount(1, $this->matrix(['payment_status' => 'unpaid']));
        $this->actingAs($this->manager)->get(route('pengurus.annual-report.index', ['year' => 2026, 'search' => 'Kosong']))->assertOk()
            ->assertViewHas('summary', fn ($s) => $s['groups'] === 1 && $s['billed'] === 0.0 && $s['invoice_count'] === 0);
    }

    public function test_monthly_detail_preserves_status_and_shows_all_owners_of_house_invoices(): void
    {
        $other = User::factory()->create(['housing_id' => $this->manager->housing_id, 'name' => 'Penghuni Sebelumnya']);
        $this->invoice(1, 'unpaid', resident: $other);
        $this->invoice(1, 'pending');
        $this->invoice(2, 'unpaid');
        $this->invoice(1, 'unpaid', unlinked: true);
        $this->actingAs($this->manager)->get(route('pengurus.annual-report.index', ['year' => 2026, 'detail_month' => 1, 'detail_house_id' => $this->house->id]))
            ->assertOk()->assertSee('Penghuni Sebelumnya')->assertViewHas('details', fn ($rows) => $rows->total() === 2);
        $this->get(route('pengurus.annual-report.index', ['year' => 2026, 'detail_month' => 1, 'detail_resident_id' => $this->resident->id]))
            ->assertOk()->assertViewHas('details', fn ($rows) => $rows->total() === 1 && $rows->first()->house_id === null);
    }

    public function test_csv_exports_all_pages_obeys_filters_and_escapes_formulas(): void
    {
        for ($i = 2; $i <= 25; $i++) {
            $house = House::create(['housing_id' => $this->manager->housing_id, 'code' => sprintf('A-%02d', $i), 'address' => $i === 25 ? '=HYPERLINK("bad")' : 'Rumah '.$i, 'occupancy' => 'occupied', 'vacant_billing' => 'full']);
            $this->invoice(1, 'unpaid', $house);
        }
        $this->invoice(1, 'verified');
        $this->actingAs($this->manager)->get(route('pengurus.annual-report.index', ['year' => 2026]))
            ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 20)->assertViewHas('summary', fn ($s) => $s['groups'] === 25);
        $csv = $this->get(route('pengurus.annual-report.export', ['year' => 2026, 'payment_status' => 'unpaid']))->assertOk()->assertDownload('rekap-tahunan-rumah-2026.csv')->streamedContent();
        $this->assertStringContainsString('A-25', $csv);
        $this->assertStringNotContainsString('A-01', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('240000', $csv);
        $this->assertStringContainsString('Belum Ada Tagihan', $csv);
    }

    public function test_pdf_generates_real_matrix_and_uses_the_same_filtered_dataset(): void
    {
        $this->invoice(1, 'unpaid');
        $response = $this->actingAs($this->manager)->get(route('pengurus.annual-report.pdf', ['year' => 2026]))->assertOk()->assertDownload('rekap-tahunan-rumah-2026.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->mock(ReportPdf::class)->shouldReceive('render')->once()->withArgs(fn ($data) => $data['report'] === 'annual' && $data['rows']->count() === 0 && $data['summary']['billed'] === 0.0)->andReturn('%PDF-1.7 test');
        $this->get(route('pengurus.annual-report.pdf', ['year' => 2026, 'search' => 'not found']))->assertOk();
    }

    public function test_report_exports_and_details_are_isolated_by_housing_and_manager_role(): void
    {
        $this->invoice(1);
        $housing = Housing::create(['name' => 'Other', 'address' => 'B']);
        $other = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->actingAs($other)->get(route('pengurus.annual-report.index', ['year' => 2026]))->assertOk()->assertDontSee('A-01')
            ->assertViewHas('summary', fn ($s) => $s['groups'] === 0 && $s['billed'] === 0.0);
        $csv = $this->get(route('pengurus.annual-report.export', ['year' => 2026]))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('A-01', $csv);
        $this->getJson(route('pengurus.annual-report.index', ['year' => 2026, 'detail_month' => 1, 'detail_house_id' => $this->house->id]))->assertUnprocessable();
        $this->getJson(route('pengurus.annual-report.index', ['year' => 2026, 'detail_month' => 1, 'detail_resident_id' => $this->resident->id]))->assertUnprocessable();
        $this->mock(ReportPdf::class)->shouldReceive('render')->once()->withArgs(fn ($data) => $data['housing']->id === $housing->id && $data['rows']->isEmpty())->andReturn('%PDF-1.7 test');
        $this->get(route('pengurus.annual-report.pdf', ['year' => 2026]))->assertOk();
        foreach (['index', 'export', 'pdf'] as $action) {
            $this->actingAs($this->resident)->get(route('pengurus.annual-report.'.$action))->assertForbidden();
        }
    }

    public function test_filters_are_validated_and_empty_year_defaults_to_server_year(): void
    {
        $this->invoice(1, year: 2018);
        $this->actingAs($this->manager)->get(route('pengurus.annual-report.index'))->assertOk()->assertViewHas('year', 2026)
            ->assertViewHas('yearOptions', fn ($years) => in_array(2018, $years, true) && in_array(2026, $years, true) && in_array(2027, $years, true))
            ->assertSee('<select id="annual-year"', false)->assertDontSee('type="number"', false);
        $this->get(route('pengurus.annual-report.index', ['year' => 2010]))->assertOk()
            ->assertViewHas('yearOptions', fn ($years) => in_array(2010, $years, true));
        foreach (['year' => 1999, 'payment_status' => 'bad', 'search' => ['array'], 'detail_month' => 13] as $field => $value) {
            foreach (['index', 'export', 'pdf'] as $action) {
                $this->getJson(route('pengurus.annual-report.'.$action, [$field => $value]))->assertUnprocessable()->assertJsonValidationErrors($field);
            }
        }
        $this->getJson(route('pengurus.annual-report.index', ['detail_house_id' => $this->house->id]))->assertUnprocessable()->assertJsonValidationErrors('detail_month');
    }

    public function test_inactive_residents_and_future_due_dates_keep_accurate_unpaid_and_overdue_totals(): void
    {
        $this->resident->update(['is_active' => false]);
        $this->invoice(1, 'unpaid');
        $this->invoice(12, 'unpaid');
        $this->actingAs($this->manager)->get(route('pengurus.annual-report.index', ['year' => 2026]))->assertOk()
            ->assertViewHas('summary', fn ($s) => $s['unpaid'] === 20000.0 && $s['overdue'] === 10000.0 && $s['invoice_count'] === 2);
    }

    public function test_annual_pdf_row_limit_never_truncates_the_selected_houses(): void
    {
        $records = [];
        for ($i = 0; $i < 500; $i++) {
            $records[] = ['housing_id' => $this->manager->housing_id, 'code' => 'LIMIT-'.$i,
                'address' => 'Rumah uji', 'occupancy' => 'occupied', 'vacant_billing' => 'full'];
        }
        foreach (array_chunk($records, 100) as $chunk) {
            DB::table('houses')->insert($chunk);
        }
        $this->mock(ReportPdf::class)->shouldNotReceive('render');
        $this->actingAs($this->manager)->getJson(route('pengurus.annual-report.pdf', ['year' => 2026]))
            ->assertUnprocessable()->assertJsonValidationErrors('report');
    }
}
