<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentsAnnouncementsAndPasswordTest extends TestCase
{
    use RefreshDatabase;

    private Housing $housing;

    private User $manager;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->housing = Housing::create(['name' => 'Pembayaran A', 'address' => 'Jl A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $this->housing->id]);
        $this->resident = User::factory()->create(['housing_id' => $this->housing->id]);
    }

    private function invoice(string $status = 'unpaid'): Invoice
    {
        $invoice = Invoice::create(['housing_id' => $this->housing->id, 'created_by' => $this->manager->id, 'user_id' => $this->resident->id,
            'house_label' => 'A-1 · Jalan Lama', 'title' => 'IPL November', 'billing_period' => '2026-11-01', 'due_date' => '2026-11-10', 'total_amount' => 125000, 'status' => $status]);
        $invoice->components()->create(['name' => 'IPL', 'amount' => 125000]);

        return $invoice;
    }

    public function test_cash_payment_receipt_pdf_snapshot_and_duplicate_protection(): void
    {
        $invoice = $this->invoice();
        $this->actingAs($this->manager)->get(route('pengurus.cash-payments.index', ['resident_id' => $this->resident->id]))->assertOk()->assertSee('IPL November');
        $this->post(route('pengurus.cash-payments.store', $invoice), ['received' => 1, 'payment_note' => 'Diterima di pos'])->assertRedirect(route('receipts.show', $invoice));
        $invoice->refresh();
        $this->assertSame('cash', $invoice->payment_method);
        $this->assertSame('verified', $invoice->status->value);
        $this->assertNotNull($invoice->paid_at);
        $this->assertDatabaseCount('cashflows', 1);
        $this->assertDatabaseCount('receipts', 1);
        $this->assertSame('cash_paid', $this->resident->notifications()->first()->data['event']);
        $original = Receipt::first()->snapshot;
        $this->resident->update(['name' => 'Nama baru', 'house_address' => 'Alamat baru']);
        $invoice->components()->first()->update(['name' => 'Komponen berubah']);
        $this->get(route('receipts.show', $invoice))->assertOk()->assertSee('A-1 · Jalan Lama')->assertSee($original['resident'])->assertDontSee('Komponen berubah');
        $this->assertSame($original, Receipt::first()->snapshot);
        $pdf = $this->get(route('receipts.pdf', $invoice))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->post(route('pengurus.cash-payments.store', $invoice), ['received' => 1])->assertSessionHasErrors('invoice');
        $this->assertDatabaseCount('cashflows', 1);
        $this->assertDatabaseCount('receipts', 1);
        $this->actingAs($this->resident)->get(route('receipts.show', $invoice))->assertOk();
        $item = $this->resident->notifications()->first();
        $this->patchJson(route('notifications.read', $item->id))->assertJsonPath('url', route('receipts.show', $invoice));
    }

    public function test_transfer_verification_also_creates_receipt_and_legacy_receipts_are_lazy(): void
    {
        $invoice = $this->invoice('pending');
        $this->actingAs($this->manager)->post(route('pengurus.invoices.verify', $invoice))->assertRedirect();
        $this->assertSame('transfer', $invoice->refresh()->payment_method);
        $this->assertDatabaseCount('receipts', 1);
        $legacy = $this->invoice('verified');
        $legacy->update(['verified_at' => now()->subMonth(), 'paid_at' => now()->subMonth(), 'verified_by' => $this->manager->id]);
        $this->get(route('receipts.show', $legacy))->assertOk()->assertSee('Kuitansi historis');
        $this->get(route('receipts.show', $legacy))->assertOk();
        $this->assertDatabaseCount('receipts', 2);
    }

    public function test_cash_status_validation_permission_and_atomic_rollback(): void
    {
        $pending = $this->invoice('pending');
        $this->actingAs($this->manager)->post(route('pengurus.cash-payments.store', $pending), ['received' => 1])->assertSessionHasErrors('invoice');
        $invoice = $this->invoice();
        $this->post(route('pengurus.cash-payments.store', $invoice), [])->assertSessionHasErrors('received');
        $other = User::factory()->create(['housing_id' => $this->housing->id]);
        $this->actingAs($other)->get(route('receipts.show', $invoice))->assertForbidden();
        $this->post(route('pengurus.cash-payments.store', $invoice), ['received' => 1])->assertForbidden();
        $housingB = Housing::create(['name' => 'B', 'address' => 'B']);
        $foreign = User::factory()->manager()->create(['housing_id' => $housingB->id]);
        $this->actingAs($foreign)->get(route('receipts.pdf', $invoice))->assertForbidden();
        $this->post(route('pengurus.cash-payments.store', $invoice), ['received' => 1])->assertForbidden();
        $this->mock(\App\Services\InvoiceNotifier::class)->shouldReceive('send')->andThrow(new \RuntimeException('Failure'));
        try {
            app(PaymentService::class)->settle($invoice, $this->manager, 'cash');
            $this->fail('Must rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('Failure', $e->getMessage());
        }
        $this->assertSame('unpaid', $invoice->refresh()->status->value);
        $this->assertDatabaseCount('cashflows', 0);
        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_announcement_drafts_publishing_notification_and_archived_links(): void
    {
        $inactive = User::factory()->inactive()->create(['housing_id' => $this->housing->id]);
        $payload = ['title' => 'Pemeliharaan lingkungan', 'body' => "Jadwal baru\n<script>alert('x')</script>"];
        $this->actingAs($this->manager)->post(route('pengurus.announcements.store'), $payload)->assertRedirect();
        $announcement = Announcement::firstOrFail();
        $this->get(route('pengurus.announcements.edit', $announcement))->assertOk();
        $this->assertDatabaseCount('notifications', 0);
        $this->actingAs($this->resident)->get(route('announcements.show', $announcement))->assertNotFound();
        $this->get(route('announcements.index'))->assertDontSee($payload['title']);
        $this->post(route('pengurus.announcements.publish', $announcement))->assertForbidden();
        $this->actingAs($this->manager)->post(route('pengurus.announcements.publish', $announcement))->assertRedirect();
        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame(0, $inactive->notifications()->count());
        $this->post(route('pengurus.announcements.publish', $announcement))->assertSessionHasErrors('announcement');
        $this->put(route('pengurus.announcements.update', $announcement), $payload)->assertSessionHasErrors('announcement');
        $this->assertDatabaseCount('notifications', 1);
        $this->actingAs($this->resident)->get(route('announcements.show', $announcement))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false);
        $this->get(route('warga.dashboard'))->assertOk()->assertSee($payload['title']);
        $item = $this->resident->notifications()->first();
        $this->patchJson(route('notifications.read', $item->id))->assertJsonPath('url', route('announcements.show', $announcement));
        $this->actingAs($this->manager)->post(route('pengurus.announcements.archive', $announcement))->assertRedirect();
        $this->actingAs($this->resident)->get(route('announcements.index'))->assertDontSee($payload['title']);
        $this->get(route('announcements.show', $announcement))->assertOk()->assertSee('sudah diarsipkan');
    }

    public function test_announcement_cross_housing_access_is_forbidden(): void
    {
        $announcement = Announcement::create(['housing_id' => $this->housing->id, 'created_by' => $this->manager->id, 'title' => 'Rahasia A', 'body' => 'Isi', 'status' => 'published', 'published_at' => now()]);
        $b = Housing::create(['name' => 'B', 'address' => 'B']);
        $manager = User::factory()->manager()->create(['housing_id' => $b->id]);
        $this->actingAs($manager)->get(route('announcements.show', $announcement))->assertForbidden();
        $this->get(route('announcements.index'))->assertOk()->assertDontSee('Rahasia A');
        $this->post(route('pengurus.announcements.archive', $announcement))->assertForbidden();
    }

    public function test_self_password_change_checks_old_password_ends_other_sessions_and_preserves_login(): void
    {
        $this->actingAs($this->resident)->get(route('profile.show'))->assertOk()->assertSee('Ubah Password');
        $this->patch(route('profile.password'), ['current_password' => 'wrong', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasErrors('current_password');
        $this->patch(route('profile.password'), ['current_password' => 'password', 'password' => 'NewPassword123', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
        DB::table('sessions')->insert(['id' => 'another-device', 'user_id' => $this->resident->id, 'payload' => '', 'last_activity' => time()]);
        $oldToken = $this->resident->remember_token;
        $this->patch(route('profile.password'), ['current_password' => 'password', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertRedirect();
        $this->assertTrue(Hash::check('NewPassword123', $this->resident->refresh()->password));
        $this->assertNotEquals($oldToken, $this->resident->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'another-device']);
        $this->assertAuthenticatedAs($this->resident);
        $this->assertFalse(Hash::check('password', $this->resident->password));
        $this->patch(route('profile.password'), ['current_password' => 'NewPassword123', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasErrors('password');
    }

    public function test_new_pages_render_and_inactive_accounts_cannot_mutate(): void
    {
        foreach (['pengurus.houses.index', 'pengurus.houses.create', 'pengurus.imports.index', 'pengurus.imports.template', 'pengurus.cash-payments.index', 'pengurus.announcements.create', 'announcements.index'] as $route) {
            $this->actingAs($this->manager)->get(route($route))->assertOk();
        }
        $this->resident->update(['is_active' => false]);
        $this->actingAs($this->resident)->patch(route('profile.password'), ['current_password' => 'password', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertForbidden();
    }
}
