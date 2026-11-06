<?php

namespace Tests\Feature;

use App\Enums\CashflowCategory;
use App\Models\Cashflow;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use App\Services\BatchPaymentService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CashflowCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-06 10:00:00', 'Asia/Jakarta'));
        $housing = Housing::create(['name' => 'Laporan Kategori', 'address' => 'Jl. A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->resident = User::factory()->create(['housing_id' => $housing->id]);
    }

    private function cash(string $name, string $type, string $category, string $amount, string $date = '2026-11-06 10:00:00', string $source = 'manual', ?int $housingId = null): Cashflow
    {
        return Cashflow::forceCreate([
            'housing_id' => $housingId ?? $this->manager->housing_id, 'name' => $name, 'type' => $type,
            'category' => $category, 'total_cashflow' => $amount, 'source' => $source, 'created_at' => $date,
        ]);
    }

    private function url(array $filters = [], bool $export = false): string
    {
        return route($export ? 'pengurus.reports.export' : 'pengurus.reports.index', ['report' => 'cashflow', 'month' => '2026-11'] + $filters);
    }

    public function test_combined_income_and_expense_reports_keep_actual_balances_independent(): void
    {
        $this->cash('Saldo sebelumnya', 'income', 'donation', '100000', '2026-10-31 23:59:59');
        $this->cash('Iuran bulan ini', 'income', 'ipl', '300000', source: 'invoice');
        $this->cash('Donasi warga', 'income', 'donation', '50000');
        $this->cash('Gaji satpam', 'expense', 'security', '100000');
        $this->cash('Pengangkutan sampah', 'expense', 'cleanliness', '20000');
        $this->cash('Bulan depan', 'income', 'donation', '999999', '2026-12-01 00:00:00');
        foreach (['' => [350000.0, 120000.0, 4], 'income' => [350000.0, 0.0, 2], 'expense' => [0.0, 120000.0, 2]] as $type => [$income, $expense, $count]) {
            $this->actingAs($this->manager)->get($this->url(['type' => $type]))->assertOk()
                ->assertViewHas('summary', fn ($s) => $s['income'] === $income && $s['expense'] === $expense
                    && $s['net'] === $income - $expense && $s['count'] === $count
                    && $s['opening'] === 100000.0 && $s['closing'] === 330000.0
                    && $s['period_income'] === 350000.0 && $s['period_expense'] === 120000.0)
                ->assertViewHas('rows', fn ($rows) => $rows->total() === $count);
        }
    }

    public function test_category_source_and_search_apply_to_rows_totals_and_category_summary(): void
    {
        $this->cash('Gaji satpam malam', 'expense', 'security', '100000.50');
        $this->cash('Gaji satpam siang', 'expense', 'security', '50000.25');
        $this->cash('Perbaikan pos', 'expense', 'security', '20000');
        $this->cash('Gaji petugas sampah', 'expense', 'cleanliness', '30000');
        $this->cash('Gaji titipan', 'income', 'security', '99999');
        $this->actingAs($this->manager)->get($this->url(['type' => 'expense', 'category' => 'security', 'source' => 'manual', 'search' => 'Gaji satpam']))
            ->assertOk()->assertSee('Rp 150.000,75')->assertSee('Gaji satpam malam')->assertDontSee('Perbaikan pos')
            ->assertDontSee('Gaji petugas sampah')->assertDontSee('Gaji titipan')
            ->assertViewHas('summary', fn ($s) => $s['expense'] === 150000.75 && $s['income'] === 0.0 && $s['count'] === 2)
            ->assertViewHas('categorySummary', fn ($rows) => $rows->count() === 1 && $rows->first()->category === CashflowCategory::Security
                && (float) $rows->first()->expense === 150000.75 && $rows->first()->count == 2);
        $this->get($this->url(['source' => 'invoice']))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 0);
    }

    public function test_filtered_csv_has_all_pages_category_metadata_and_formula_protection(): void
    {
        for ($i = 0; $i < 18; $i++) {
            $this->cash('Gaji satpam '.$i, 'expense', 'security', '1000.25');
        }
        $this->cash('=HYPERLINK("bad")', 'expense', 'security', '100');
        $this->cash('Jangan ekspor kebersihan', 'expense', 'cleanliness', '999999');
        $this->cash('Jangan ekspor pemasukan', 'income', 'security', '999999');
        $filters = ['type' => 'expense', 'category' => 'security', 'source' => 'manual'];
        $page = $this->actingAs($this->manager)->get($this->url($filters))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 15 && $rows->total() === 19);
        $this->assertStringContainsString('category=security', $page->viewData('rows')->nextPageUrl());
        $this->assertStringContainsString('source=manual', $page->viewData('rows')->nextPageUrl());
        $page->assertSee('type=expense', false)->assertSee('category=security', false);
        $csv = $this->get($this->url($filters, true))->assertOk()->assertDownload('laporan-kas-pengeluaran-security-2026-11.csv')->streamedContent();
        $this->assertStringContainsString('Gaji satpam 17', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Jangan ekspor', $csv);
        $this->assertStringContainsString('Keamanan', $csv);
        $this->assertStringContainsString('18104.5', $csv);
        $this->assertStringContainsString('Saldo Akhir Seluruh Kas', $csv);
        $this->assertStringContainsString('Pengeluaran Sesuai Filter', $csv);
        $this->assertStringContainsString('Rekap per Kategori', $csv);
    }

    public function test_manual_categories_can_be_saved_edited_and_filtered_without_changing_ownership(): void
    {
        $data = ['name' => 'Perbaikan lampu', 'type' => 'expense', 'category' => 'maintenance', 'total_cashflow' => '25000.50'];
        $this->actingAs($this->manager)->post(route('pengurus.cashflows.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $cash = Cashflow::firstOrFail();
        $this->assertSame(CashflowCategory::Maintenance, $cash->category);
        $this->assertSame($this->manager->housing_id, $cash->housing_id);
        $this->put(route('pengurus.cashflows.update', $cash), [...$data, 'category' => 'utilities', 'housing_id' => 999])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(CashflowCategory::Utilities, $cash->refresh()->category);
        $this->assertSame($this->manager->housing_id, $cash->housing_id);
        $this->get(route('pengurus.cashflows.index', ['category' => 'utilities']))->assertOk()->assertViewHas('cashflows', fn ($rows) => $rows->total() === 1);
        $this->get(route('pengurus.cashflows.index', ['category' => 'maintenance']))->assertOk()->assertViewHas('cashflows', fn ($rows) => $rows->total() === 0);
        $this->put(route('pengurus.cashflows.update', $cash), array_diff_key($data, ['category' => true]))->assertSessionHasNoErrors();
        $this->assertSame(CashflowCategory::Utilities, $cash->refresh()->category);
    }

    public function test_invalid_filter_and_manual_category_values_are_rejected(): void
    {
        $this->actingAs($this->manager);
        foreach (['type' => 'unknown', 'category' => 'hacked', 'source' => 'unknown', 'search' => ['array']] as $field => $value) {
            $this->getJson($this->url([$field => $value]))->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->getJson($this->url([$field => $value], true))->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->getJson(route('pengurus.cashflows.index', [$field => $value]))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        foreach (['hacked', 'ipl', ''] as $category) {
            $this->postJson(route('pengurus.cashflows.store'), ['name' => 'Kas', 'type' => 'expense', 'total_cashflow' => '100', 'category' => $category])
                ->assertUnprocessable()->assertJsonValidationErrors('category');
        }
        $this->assertDatabaseCount('cashflows', 0);
    }

    public function test_automatic_single_and_batch_payments_are_categorized_and_remain_locked(): void
    {
        $make = fn () => Invoice::create([
            'housing_id' => $this->manager->housing_id, 'user_id' => $this->resident->id, 'created_by' => $this->manager->id,
            'title' => 'Tagihan IPL', 'billing_period' => '2026-11-01', 'due_date' => '2026-11-10', 'total_amount' => '10000', 'status' => 'unpaid',
        ]);
        $single = $make();
        app(PaymentService::class)->settle($single, $this->manager, 'cash');
        $first = $make();
        $second = $make();
        app(BatchPaymentService::class)->create($this->manager, [$first->id, $second->id], 'cash', '20000.00');
        $this->assertDatabaseCount('cashflows', 2);
        $this->assertSame(2, Cashflow::where('category', 'ipl')->count());
        $this->actingAs($this->manager)->putJson(route('pengurus.cashflows.update', Cashflow::first()), [
            'name' => 'Ubah otomatis', 'type' => 'income', 'category' => 'other', 'total_cashflow' => 1,
        ])->assertForbidden();
        $csv = $this->get($this->url(['category' => 'ipl'], true))->assertOk()->streamedContent();
        $this->assertSame(2, substr_count($csv, 'Tunai / Cash'));
        $this->assertStringNotContainsString(',Manual', $csv);
    }

    public function test_category_reports_and_edits_are_isolated_between_housings(): void
    {
        $cash = $this->cash('Kas privat A', 'expense', 'security', '123456');
        $otherHousing = Housing::create(['name' => 'Lain', 'address' => 'B']);
        $other = User::factory()->manager()->create(['housing_id' => $otherHousing->id]);
        $this->cash('Kas privat B', 'expense', 'security', '999999', housingId: $otherHousing->id);
        $this->actingAs($this->manager)->get($this->url(['category' => 'security']))->assertOk()->assertSee('Kas privat A')->assertDontSee('Kas privat B');
        $this->actingAs($other)->get($this->url(['category' => 'security']))->assertOk()->assertDontSee('Kas privat A')
            ->assertViewHas('summary', fn ($s) => $s['expense'] === 999999.0);
        $this->assertStringNotContainsString('Kas privat A', $this->get($this->url(['category' => 'security'], true))->streamedContent());
        $this->putJson(route('pengurus.cashflows.update', $cash), ['name' => 'Pindah', 'type' => 'expense', 'category' => 'other', 'total_cashflow' => 1])->assertForbidden();
        $this->actingAs($this->resident)->get($this->url(['category' => 'security']))->assertForbidden();
        $this->get($this->url(['category' => 'security'], true))->assertForbidden();
    }

    public function test_category_migration_preserves_legacy_amounts_and_marks_manual_entries_for_review(): void
    {
        $migration = require database_path('migrations/2026_11_06_000003_add_category_to_cashflows.php');
        $migration->down();
        DB::table('cashflows')->insert([
            ['housing_id' => $this->manager->housing_id, 'name' => 'Data lama manual', 'type' => 'expense', 'source' => 'manual', 'total_cashflow' => '12500.50'],
            ['housing_id' => $this->manager->housing_id, 'name' => 'Data lama tagihan', 'type' => 'income', 'source' => 'invoice', 'total_cashflow' => '25000.25'],
        ]);
        $migration->up();
        $this->assertDatabaseHas('cashflows', ['name' => 'Data lama manual', 'category' => 'uncategorized', 'total_cashflow' => '12500.50']);
        $this->assertDatabaseHas('cashflows', ['name' => 'Data lama tagihan', 'category' => 'ipl', 'total_cashflow' => '25000.25']);
        $this->assertDatabaseCount('cashflows', 2);
    }

    public function test_empty_filters_and_empty_results_have_clear_zero_totals(): void
    {
        $this->cash('Data tersedia', 'expense', 'security', '12345');
        $this->actingAs($this->manager)->get($this->url(['category' => 'maintenance']))->assertOk()
            ->assertSee('Tidak ada transaksi yang sesuai filter')
            ->assertViewHas('summary', fn ($s) => $s['income'] === 0.0 && $s['expense'] === 0.0 && $s['count'] === 0 && $s['closing'] === -12345.0)
            ->assertViewHas('categorySummary', fn ($rows) => $rows->isEmpty());
        $this->get($this->url(['type' => '', 'category' => '', 'source' => '', 'search' => '']))->assertOk()
            ->assertViewHas('summary', fn ($s) => ! $s['filtered'] && $s['count'] === 1);
    }

    public function test_zero_search_is_applied_in_both_reports_and_cashflow_list(): void
    {
        $this->cash('Nomor 0', 'expense', 'security', '100');
        $this->cash('Nomor satu', 'expense', 'security', '999');
        $this->actingAs($this->manager)->get($this->url(['search' => '0']))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->total() === 1)
            ->assertViewHas('summary', fn ($s) => $s['expense'] === 100.0);
        $this->get(route('pengurus.cashflows.index', ['search' => '0']))->assertOk()
            ->assertViewHas('cashflows', fn ($rows) => $rows->total() === 1);
    }
}
