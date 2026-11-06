<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\ActivityLog;
use App\Models\Cashflow;
use App\Models\House;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\User;
use App\Services\AccountingPeriodService;
use App\Services\InvoiceTemplateService;
use App\Services\PaymentService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManagerWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $resident;

    private House $house;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-06 10:00:00', 'Asia/Jakarta'));
        Storage::fake('local');
        $housing = Housing::create(['name' => 'Workflow A', 'address' => 'A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->resident = User::factory()->create(['housing_id' => $housing->id]);
        $this->house = House::create(['housing_id' => $housing->id, 'code' => 'A-01', 'address' => 'Jalan A',
            'occupancy' => 'occupied', 'vacant_billing' => 'full', 'responsible_user_id' => $this->resident->id]);
        $this->resident->update(['house_id' => $this->house->id]);
    }

    private function template(): InvoiceTemplate
    {
        return InvoiceTemplate::create($this->templateData() + ['housing_id' => $this->manager->housing_id, 'created_by' => $this->manager->id]);
    }

    private function templateData(): array
    {
        return ['name' => 'IPL rutin', 'title' => 'IPL lingkungan', 'components' => [['name' => 'Keamanan', 'amount' => 100000], ['name' => 'Kebersihan', 'amount' => 50000]],
            'recipient_mode' => 'all', 'resident_ids' => null, 'due_day' => 31, 'due_month_offset' => 1, 'apply_house_tariff' => true, 'is_active' => true];
    }

    private function invoice(): Invoice
    {
        return Invoice::create(['housing_id' => $this->manager->housing_id, 'user_id' => $this->resident->id, 'created_by' => $this->manager->id,
            'title' => 'Tagihan lama', 'billing_period' => '2026-10-01', 'due_date' => '2026-10-10', 'total_amount' => 150000, 'status' => 'unpaid']);
    }

    private function cash(string $date = '2026-10-15'): Cashflow
    {
        return Cashflow::create(['housing_id' => $this->manager->housing_id, 'created_by' => $this->manager->id, 'name' => 'Biaya kebersihan',
            'type' => 'expense', 'source' => 'manual', 'category' => 'cleanliness', 'total_cashflow' => 10000, 'transaction_date' => $date]);
    }

    private function cashData(string $date = '2026-10-15'): array
    {
        return ['name' => 'Biaya kebersihan', 'type' => 'expense', 'category' => 'cleanliness', 'total_cashflow' => 12000, 'transaction_date' => $date];
    }

    public function test_template_preview_has_no_side_effect_then_issue_clamps_due_day_and_is_deduplicated(): void
    {
        $this->actingAs($this->manager)->post(route('pengurus.invoice-templates.store'), $this->templateData())->assertSessionHasNoErrors();
        $template = InvoiceTemplate::firstOrFail();
        $preview = $this->get(route('pengurus.invoice-templates.preview', [$template, 'period' => '2026-01']))->assertOk();
        $this->assertSame('2026-02-28', $preview->viewData('plan')['data']['due_date']);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_template_runs', 0);
        $data = ['period' => '2026-01', 'preview_token' => $preview->viewData('token'), 'confirmed' => 1];
        $this->post(route('pengurus.invoice-templates.issue', $template), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', ['user_id' => $this->resident->id, 'total_amount' => 150000]);
        $this->assertSame('2026-02-28', Invoice::first()->due_date->toDateString());
        $this->assertDatabaseCount('invoice_template_runs', 1);
        $this->post(route('pengurus.invoice-templates.issue', $template), $data)->assertSessionHasErrors('period');
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_components', 2);
    }

    public function test_template_stale_preview_cannot_issue_changed_tariffs_or_recipients(): void
    {
        $template = $this->template();
        $service = app(InvoiceTemplateService::class);
        $plan = $service->preview($this->manager, $template, '2026-11');
        $token = $service->token($template, $plan);
        $this->house->update(['occupancy' => 'vacant', 'vacant_billing' => 'custom', 'vacant_amount' => 25000]);
        $this->actingAs($this->manager)->post(route('pengurus.invoice-templates.issue', $template), ['period' => '2026-11', 'preview_token' => $token, 'confirmed' => 1])->assertSessionHasErrors('preview_token');
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_template_runs', 0);
        $newPlan = $service->preview($this->manager, $template->fresh(), '2026-11');
        $this->assertSame('25000.00', $newPlan['rows'][0]['amount']);
        $this->resident->update(['is_active' => false]);
        $this->post(route('pengurus.invoice-templates.issue', $template), ['period' => '2026-11', 'preview_token' => $service->token($template, $newPlan), 'confirmed' => 1])->assertSessionHasErrors('preview_token');
    }

    public function test_template_respects_exempt_house_and_inactive_template_and_total_limit(): void
    {
        $template = $this->template();
        $this->house->update(['occupancy' => 'vacant', 'vacant_billing' => 'exempt']);
        $service = app(InvoiceTemplateService::class);
        $plan = $service->preview($this->manager, $template, '2026-11');
        $this->assertCount(0, $plan['rows']);
        $this->assertSame(1, $plan['skipped']);
        $this->actingAs($this->manager)->post(route('pengurus.invoice-templates.issue', $template), ['period' => '2026-11', 'preview_token' => $service->token($template, $plan), 'confirmed' => 1])->assertSessionHasErrors('template');
        $template->update(['is_active' => false]);
        $this->get(route('pengurus.invoice-templates.preview', [$template, 'period' => '2026-11']))->assertSessionHasErrors('template');
        $this->post(route('pengurus.invoice-templates.store'), [...$this->templateData(), 'components' => array_fill(0, 11, ['name' => 'Terlalu besar', 'amount' => 999999999999])])->assertSessionHasErrors('components');
    }

    public function test_template_and_new_pages_are_scoped_to_managers_and_their_housing(): void
    {
        $template = $this->template();
        $housing = Housing::create(['name' => 'B', 'address' => 'B']);
        $other = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->actingAs($other)->get(route('pengurus.invoice-templates.edit', $template))->assertNotFound();
        $this->get(route('pengurus.invoice-templates.preview', [$template, 'period' => '2026-11']))->assertNotFound();
        $this->get(route('pengurus.follow-ups.show', $this->resident))->assertNotFound();
        foreach (['invoice-templates.index', 'follow-ups.index', 'activity-logs.index', 'accounting-periods.index'] as $route) {
            $this->get(route('pengurus.'.$route))->assertOk();
            $this->actingAs($this->resident)->get(route('pengurus.'.$route))->assertForbidden();
            $this->actingAs($other);
        }
        $this->post(route('pengurus.invoice-templates.store'), [...$this->templateData(), 'recipient_mode' => 'selected', 'resident_ids' => [$this->resident->id]])->assertSessionHasErrors('resident_ids.0');
    }

    public function test_follow_up_keeps_payment_state_and_validates_assignee_and_promise_date(): void
    {
        $invoice = $this->invoice();
        $data = ['status' => 'promised', 'note' => 'Warga berjanji membayar minggu depan', 'next_action_date' => '2026-11-10', 'assigned_to' => $this->manager->id];
        $this->actingAs($this->manager)->post(route('pengurus.follow-ups.store', $this->resident), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('arrears_follow_ups', ['resident_id' => $this->resident->id, 'status' => 'promised']);
        $this->assertSame('unpaid', $invoice->refresh()->status->value);
        $this->get(route('pengurus.follow-ups.index'))->assertOk()->assertSee('Janji Bayar');
        $this->get(route('pengurus.follow-ups.show', $this->resident))->assertOk()->assertSee($data['note']);
        $this->post(route('pengurus.follow-ups.store', $this->resident), [...$data, 'assigned_to' => $this->resident->id])->assertSessionHasErrors('assigned_to');
        $this->post(route('pengurus.follow-ups.store', $this->resident), [...$data, 'next_action_date' => null])->assertSessionHasErrors('next_action_date');
    }

    public function test_private_attachment_is_downloadable_only_by_own_manager_and_is_cleaned_on_replacement(): void
    {
        $this->actingAs($this->manager)->post(route('pengurus.cashflows.store'), [...$this->cashData(), 'attachment' => UploadedFile::fake()->image('nota.jpg')])->assertSessionHasNoErrors();
        $cash = Cashflow::firstOrFail();
        $oldPath = $cash->attachment_path;
        Storage::disk('local')->assertExists($oldPath);
        $this->get(route('pengurus.cashflows.attachment', $cash))->assertOk()->assertDownload();
        $this->actingAs($this->resident)->get(route('pengurus.cashflows.attachment', $cash))->assertForbidden();
        $housing = Housing::create(['name' => 'B', 'address' => 'B']);
        $other = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->actingAs($other)->get(route('pengurus.cashflows.attachment', $cash))->assertForbidden();
        $this->actingAs($this->manager)->post(route('pengurus.cashflows.update', $cash), [...$this->cashData(), '_method' => 'PUT', 'attachment' => UploadedFile::fake()->image('baru.png')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($cash->refresh()->attachment_path);
        $this->delete(route('pengurus.cashflows.destroy', $cash))->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($cash->attachment_path);
    }

    public function test_invalid_attachments_and_future_transaction_dates_are_rejected(): void
    {
        $this->actingAs($this->manager)->post(route('pengurus.cashflows.store'), [...$this->cashData(), 'attachment' => UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('attachment');
        $this->post(route('pengurus.cashflows.store'), [...$this->cashData(), 'transaction_date' => '2026-11-07'])->assertSessionHasErrors('transaction_date');
        $this->post(route('pengurus.cashflows.store'), [...$this->cashData(), 'attachment' => UploadedFile::fake()->create('large.pdf', 6000, 'application/pdf')])->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('cashflows', 0);
    }

    public function test_transaction_date_controls_reports_but_creation_time_is_server_owned(): void
    {
        $this->actingAs($this->manager)->post(route('pengurus.cashflows.store'), [...$this->cashData('2026-10-20'), 'created_at' => '2000-01-01', 'housing_id' => 999])->assertSessionHasNoErrors();
        $cash = Cashflow::firstOrFail();
        $this->assertSame('2026-10-20', $cash->transaction_date->toDateString());
        $this->assertSame('2026-11-06', $cash->created_at->toDateString());
        $summary = app(ReportService::class)->cashflow($this->manager->housing_id, Carbon::parse('2026-10-01'))['summary'];
        $this->assertSame(12000.0, $summary['expense']);
        $invoice = $this->invoice();
        $this->post(route('pengurus.cash-payments.store', $invoice), ['received' => 1, 'transaction_date' => '2020-01-01', 'paid_at' => '2000-01-01'])->assertSessionHasNoErrors();
        $automatic = Cashflow::where('source', 'invoice')->firstOrFail();
        $this->assertSame('2026-11-06', $automatic->transaction_date->toDateString());
        $this->assertSame('2026-11-06', $invoice->refresh()->paid_at->toDateString());
    }

    public function test_closed_books_block_creates_updates_deletes_and_earlier_changes_affecting_opening_balance(): void
    {
        $cash = $this->cash();
        $earlier = $this->cash('2026-09-15');
        $this->actingAs($this->manager)->post(route('pengurus.accounting-periods.close'), ['period' => '2026-10', 'confirmed' => 1])->assertSessionHasNoErrors();
        $record = AccountingPeriod::firstOrFail();
        $this->assertSame(-20000.0, (float) $record->snapshot['closing']);
        $this->post(route('pengurus.cashflows.store'), $this->cashData())->assertSessionHasErrors('transaction_date');
        $this->put(route('pengurus.cashflows.update', $cash), $this->cashData('2026-11-01'))->assertSessionHasErrors('transaction_date');
        $this->delete(route('pengurus.cashflows.destroy', $cash))->assertSessionHasErrors('transaction_date');
        $this->delete(route('pengurus.cashflows.destroy', $earlier))->assertSessionHasErrors('transaction_date');
        $this->assertDatabaseCount('cashflows', 2);
        $this->post(route('pengurus.cashflows.store'), $this->cashData('2026-11-01'))->assertSessionHasNoErrors();
        $this->get(route('pengurus.reports.index', ['report' => 'cashflow', 'month' => '2026-10']))->assertOk()->assertSee('sudah ditutup');
    }

    public function test_reopening_needs_reason_and_closed_month_attachment_failure_cleans_new_file(): void
    {
        $this->actingAs($this->manager)->post(route('pengurus.cashflows.store'), [...$this->cashData(), 'attachment' => UploadedFile::fake()->image('nota.jpg')])->assertSessionHasNoErrors();
        $cash = Cashflow::firstOrFail();
        $period = app(AccountingPeriodService::class)->close($this->manager, '2026-10');
        $this->post(route('pengurus.cashflows.update', $cash), [...$this->cashData(), '_method' => 'PUT', 'attachment' => UploadedFile::fake()->image('baru.jpg')])->assertSessionHasErrors('transaction_date');
        $this->assertCount(1, Storage::disk('local')->allFiles('cashflow-attachments'));
        $this->post(route('pengurus.accounting-periods.reopen', $period), ['reason' => 'short'])->assertSessionHasErrors('reason');
        $this->post(route('pengurus.accounting-periods.reopen', $period), ['reason' => 'Perbaikan nota dan nominal yang salah'])->assertSessionHasNoErrors();
        $this->put(route('pengurus.cashflows.update', $cash), [...$this->cashData(), 'total_cashflow' => 5000])->assertSessionHasNoErrors();
        $this->assertSame('5000.00', $cash->refresh()->total_cashflow);
        $this->assertSame('open', $period->refresh()->status);
        app(AccountingPeriodService::class)->close($this->manager, '2026-10');
        $this->assertDatabaseCount('accounting_periods', 1);
    }

    public function test_current_month_cannot_close_and_reopening_must_start_with_latest_closed_period(): void
    {
        $this->actingAs($this->manager)->post(route('pengurus.accounting-periods.close'), ['period' => '2026-11', 'confirmed' => 1])->assertSessionHasErrors('period');
        $service = app(AccountingPeriodService::class);
        $september = $service->close($this->manager, '2026-09');
        $october = $service->close($this->manager, '2026-10');
        $this->post(route('pengurus.accounting-periods.reopen', $september), ['reason' => 'Koreksi laporan lama yang belum benar'])->assertSessionHasErrors('period');
        $this->post(route('pengurus.accounting-periods.reopen', $october), ['reason' => 'Koreksi laporan terakhir terlebih dahulu'])->assertSessionHasNoErrors();
    }

    public function test_activity_log_keeps_before_after_actor_and_rollback_without_leaking_paths(): void
    {
        $this->actingAs($this->manager);
        $cash = $this->cash();
        $this->put(route('pengurus.cashflows.update', $cash), [...$this->cashData(), 'total_cashflow' => 5000])->assertSessionHasNoErrors();
        $log = ActivityLog::where('entity_type', 'Cashflow')->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertSame($this->manager->id, $log->actor_id);
        $this->assertSame(10000.0, (float) $log->before['total_cashflow']);
        $this->assertSame(5000.0, (float) $log->after['total_cashflow']);
        $this->assertArrayNotHasKey('attachment_path', $log->after);
        $count = ActivityLog::count();
        DB::beginTransaction();
        try {
            $this->cash('2026-11-01');
        } finally {
            DB::rollBack();
        }
        $this->assertSame($count, ActivityLog::count());
        $this->get(route('pengurus.activity-logs.index', ['entity' => 'Cashflow', 'date_to' => '2026-11-06']))->assertOk()->assertSee('Lihat nilai sebelum / sesudah');
        $housing = Housing::create(['name' => 'B', 'address' => 'B']);
        $other = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->actingAs($other)->get(route('pengurus.activity-logs.index'))->assertOk()->assertDontSee($this->manager->name);
        $this->post(route('pengurus.activity-logs.index'))->assertMethodNotAllowed();
    }

    public function test_template_edit_preserves_old_invoices_and_renders_existing_components(): void
    {
        $template = $this->template();
        $this->actingAs($this->manager)->get(route('pengurus.invoice-templates.create'))->assertOk();
        $this->get(route('pengurus.invoice-templates.edit', $template))->assertOk()->assertSee('data-initial-components', false)->assertSee('Keamanan');
        $service = app(InvoiceTemplateService::class);
        $plan = $service->preview($this->manager, $template, '2026-10');
        $service->issue($this->manager, $template, '2026-10', $service->token($template, $plan));
        $data = [...$this->templateData(), 'components' => [['name' => 'Tarif baru', 'amount' => 175000]]];
        $this->put(route('pengurus.invoice-templates.update', $template), $data)->assertSessionHasNoErrors();
        $this->assertSame('150000.00', Invoice::first()->total_amount);
        $newPlan = $service->preview($this->manager, $template->fresh(), '2026-11');
        $this->assertSame('175000', $newPlan['rows'][0]['amount']);
    }

    public function test_old_invoice_can_be_paid_in_current_month_after_old_books_close(): void
    {
        $invoice = $this->invoice();
        $this->cash();
        $period = app(AccountingPeriodService::class)->close($this->manager, '2026-10');
        $before = $period->snapshot;
        app(PaymentService::class)->settle($invoice, $this->manager, 'cash');
        $this->assertSame('verified', $invoice->refresh()->status->value);
        $this->assertSame('2026-11-06', Cashflow::where('invoice_id', $invoice->id)->first()->transaction_date->toDateString());
        $actual = app(ReportService::class)->cashflow($this->manager->housing_id, Carbon::parse('2026-10-01'))['summary'];
        $this->assertEquals($before['closing'], $actual['closing']);
    }

    public function test_audit_storage_failure_rolls_back_payment_cash_and_receipt(): void
    {
        $invoice = $this->invoice();
        DB::statement("CREATE TRIGGER reject_audit BEFORE INSERT ON activity_logs BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");
        try {
            app(PaymentService::class)->settle($invoice, $this->manager, 'cash');
            $this->fail('Audit storage failure must abort the transaction.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('audit unavailable', $e->getMessage());
        } finally {
            DB::statement('DROP TRIGGER reject_audit');
        }
        $this->assertSame('unpaid', $invoice->refresh()->status->value);
        $this->assertDatabaseCount('cashflows', 0);
        $this->assertDatabaseCount('receipts', 0);
    }
}
