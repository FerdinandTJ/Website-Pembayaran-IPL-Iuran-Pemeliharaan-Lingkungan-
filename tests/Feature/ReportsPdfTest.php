<?php

namespace Tests\Feature;

use App\Models\Cashflow;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ReportPdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-06 10:30:00', 'Asia/Jakarta'));
        $housing = Housing::create(['name' => 'PDF Perumahan A', 'address' => 'Jalan Laporan']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->resident = User::factory()->create(['housing_id' => $housing->id]);
    }

    private function url(string $report = 'cashflow', array $filters = []): string
    {
        return route('pengurus.reports.pdf', ['report' => $report, 'month' => '2026-11'] + $filters);
    }

    private function invoice(int $days, string $status = 'unpaid'): Invoice
    {
        return Invoice::create([
            'housing_id' => $this->manager->housing_id, 'user_id' => $this->resident->id, 'created_by' => $this->manager->id,
            'title' => 'IPL November', 'billing_period' => '2026-11-01', 'due_date' => today()->subDays($days),
            'total_amount' => '150000.50', 'status' => $status,
        ]);
    }

    public function test_all_three_report_types_generate_real_private_pdf_downloads(): void
    {
        $this->invoice(31);
        Cashflow::create(['housing_id' => $this->manager->housing_id, 'name' => 'Gaji satpam',
            'type' => 'expense', 'source' => 'manual', 'category' => 'security', 'total_cashflow' => '150000.50']);
        foreach (['cashflow', 'billing', 'arrears'] as $report) {
            $response = $this->actingAs($this->manager)->get($this->url($report));
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertDownload();
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertGreaterThan(10000, strlen($response->getContent()));
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        }
    }

    public function test_pdf_keeps_all_filtered_rows_and_correct_totals_across_ui_pages(): void
    {
        for ($i = 0; $i < 19; $i++) {
            Cashflow::create(['housing_id' => $this->manager->housing_id, 'name' => 'Gaji satpam '.$i,
                'type' => 'expense', 'source' => 'manual', 'category' => 'security', 'total_cashflow' => '1000.25']);
        }
        Cashflow::create(['housing_id' => $this->manager->housing_id, 'name' => 'Sampah',
            'type' => 'expense', 'source' => 'manual', 'category' => 'cleanliness', 'total_cashflow' => '9000']);
        $this->mock(ReportPdf::class)->shouldReceive('render')->once()->withArgs(function (array $data) {
            return $data['rows']->count() === 19 && $data['rows']->last()->name === 'Gaji satpam 18'
                && $data['summary']['expense'] === 19004.75 && $data['summary']['closing'] === -28004.75
                && $data['categorySummary']->count() === 1
                && $data['filterLabels'] === ['Jenis' => 'Pengeluaran', 'Kategori' => 'Keamanan', 'Sumber' => 'Transaksi manual', 'Pencarian' => 'satpam']
                && $data['housing']->id === $this->manager->housing_id && $data['title'] === 'Laporan Pengeluaran';
        })->andReturn('%PDF-1.7 fixture');
        $this->actingAs($this->manager)->get($this->url('cashflow', ['type' => 'expense', 'category' => 'security', 'source' => 'manual', 'search' => 'satpam']))
            ->assertOk()->assertDownload('laporan-kas-pengeluaran-security-2026-11.pdf');
    }

    public function test_aging_filter_and_resident_filter_match_the_onscreen_report(): void
    {
        $this->invoice(10);
        $this->invoice(65);
        $this->invoice(65, 'pending');
        $this->mock(ReportPdf::class)->shouldReceive('render')->once()->withArgs(function (array $data) {
            return $data['rows']->count() === 1 && (float) $data['summary']['total'] === 300001.0
                && $data['filterLabels']['Umur tunggakan'] === 'Lebih dari 60 hari'
                && $data['filterLabels']['Warga'] === $this->resident->name;
        })->andReturn('%PDF-1.7 fixture');
        $this->actingAs($this->manager)->get($this->url('arrears', ['resident_id' => $this->resident->id, 'age' => '61+']))->assertOk();
    }

    public function test_pdf_is_scoped_to_housing_and_blocks_residents_admin_and_inactive_accounts(): void
    {
        $this->invoice(1);
        $otherHousing = Housing::create(['name' => 'PDF Perumahan B', 'address' => 'B']);
        $other = User::factory()->manager()->create(['housing_id' => $otherHousing->id]);
        $this->mock(ReportPdf::class)->shouldReceive('render')->times(3)->withArgs(fn (array $data) => $data['housing']->id === $otherHousing->id && $data['rows']->isEmpty())->andReturn('%PDF-1.7 fixture');
        $admin = User::factory()->admin()->create();
        foreach (['cashflow', 'billing', 'arrears'] as $report) {
            $this->actingAs($other)->get($this->url($report))->assertOk();
            $this->actingAs($this->resident)->get($this->url($report))->assertForbidden();
            $this->actingAs($admin)->get($this->url($report))->assertForbidden();
        }
        $this->actingAs($other)->getJson($this->url('arrears', ['resident_id' => $this->resident->id]))->assertUnprocessable();
        $this->manager->update(['is_active' => false]);
        $this->actingAs($this->manager)->get($this->url())->assertForbidden();
        $otherHousing->update(['is_active' => false]);
        $this->actingAs($other->fresh())->get($this->url())->assertForbidden();
    }

    public function test_pdf_validates_filters_before_rendering(): void
    {
        $this->mock(ReportPdf::class)->shouldNotReceive('render');
        foreach (['month' => '2026-13', 'type' => 'bad', 'category' => 'bad', 'source' => 'bad', 'search' => ['array']] as $field => $value) {
            $params = ['report' => 'cashflow', 'month' => '2026-11', $field => $value];
            $this->actingAs($this->manager)->getJson(route('pengurus.reports.pdf', $params))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
    }

    public function test_empty_reports_render_without_layout_or_data_errors(): void
    {
        foreach (['cashflow', 'billing', 'arrears'] as $report) {
            $this->actingAs($this->manager)->get($this->url($report))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
    }

    public function test_large_reports_offer_csv_instead_of_silently_truncating_the_pdf(): void
    {
        $record = ['housing_id' => $this->manager->housing_id, 'name' => 'Kas', 'type' => 'expense', 'source' => 'manual',
            'category' => 'security', 'total_cashflow' => 1000, 'created_at' => '2026-11-06 10:00:00'];
        foreach (array_chunk(array_fill(0, 2001, $record), 500) as $chunk) {
            DB::table('cashflows')->insert($chunk);
        }
        $this->mock(ReportPdf::class)->shouldNotReceive('render');
        $this->actingAs($this->manager)->getJson($this->url())->assertUnprocessable()->assertJsonValidationErrors('report');
    }
}
