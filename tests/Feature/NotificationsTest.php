<?php

namespace Tests\Feature;

use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceNotifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Housing $housing;

    private User $manager;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-03 08:00:00', 'Asia/Jakarta'));
        $this->housing = Housing::create(['name' => 'Notif A', 'address' => 'A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $this->housing->id]);
        $this->resident = User::factory()->create(['housing_id' => $this->housing->id]);
        Storage::fake('local');
    }

    private function invoice(string $due = '2026-11-06', string $status = 'unpaid', ?User $resident = null): Invoice
    {
        return Invoice::create([
            'housing_id' => $this->housing->id, 'created_by' => $this->manager->id,
            'user_id' => ($resident ?? $this->resident)->id, 'title' => 'IPL November',
            'billing_period' => '2026-11-01', 'due_date' => $due, 'total_amount' => 10000, 'status' => $status,
        ]);
    }

    public function test_issued_notification_only_goes_to_selected_recipients(): void
    {
        $other = User::factory()->create(['housing_id' => $this->housing->id]);
        $this->actingAs($this->manager)->post(route('pengurus.invoices.store'), [
            'title' => 'Tagihan terpilih', 'billing_period' => '2026-11', 'due_date' => '2026-11-10',
            'recipient_mode' => 'selected', 'resident_ids' => [$this->resident->id],
            'components' => [['name' => 'IPL', 'amount' => 10000]],
        ])->assertRedirect(route('pengurus.invoices.index'));
        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame('issued', $this->resident->notifications()->first()->data['event']);
        $this->assertSame(0, $other->notifications()->count());
        $this->assertSame(0, $this->manager->notifications()->count());
    }

    public function test_payment_events_notify_active_managers_and_resident_and_allow_resubmission(): void
    {
        $secondManager = User::factory()->manager()->create(['housing_id' => $this->housing->id]);
        $inactiveManager = User::factory()->manager()->inactive()->create(['housing_id' => $this->housing->id]);
        $housingB = Housing::create(['name' => 'B', 'address' => 'B']);
        $foreignManager = User::factory()->manager()->create(['housing_id' => $housingB->id]);
        $invoice = $this->invoice();
        $this->actingAs($this->resident)->post(route('warga.invoices.payment', $invoice), ['proof' => UploadedFile::fake()->image('bukti.jpg')])->assertRedirect();
        $this->assertSame(1, $this->manager->notifications()->count());
        $this->assertSame(1, $secondManager->notifications()->count());
        $this->assertSame(0, $inactiveManager->notifications()->count());
        $this->assertSame(0, $foreignManager->notifications()->count());
        $item = $this->manager->notifications()->first();
        $this->actingAs($this->manager)->get(route('pengurus.verifications.index', ['invoice_id' => $invoice->id]))->assertOk()->assertSee('IPL November');
        $this->patch(route('notifications.read', $item->id))->assertRedirect(route('pengurus.verifications.index', ['invoice_id' => $invoice->id]));
        $this->post(route('pengurus.invoices.reject', $invoice), ['rejection_reason' => 'Bukti belum jelas.'])->assertRedirect();
        $this->assertSame('rejected', $this->resident->notifications()->first()->data['event']);
        $oldPath = $invoice->refresh()->payment_proof_path;
        $this->actingAs($this->resident)->post(route('warga.invoices.payment', $invoice), ['proof' => UploadedFile::fake()->image('ulang.jpg')])->assertRedirect();
        Storage::disk('local')->assertMissing($oldPath);
        $this->assertSame(2, $this->manager->notifications()->count());
        $this->actingAs($this->manager)->post(route('pengurus.invoices.verify', $invoice))->assertRedirect();
        $this->assertSame(2, $this->resident->notifications()->count());
        $this->post(route('pengurus.invoices.verify', $invoice))->assertSessionHasErrors('invoice');
        $this->post(route('pengurus.invoices.reject', $invoice), ['rejection_reason' => 'Bukti belum jelas.'])->assertSessionHasErrors('invoice');
        $this->assertSame(2, $this->resident->notifications()->count());
    }

    public function test_notifications_and_new_proof_rollback_if_storage_notification_transaction_fails(): void
    {
        $invoice = $this->invoice();
        DB::beginTransaction();
        app(InvoiceNotifier::class)->send($invoice, 'issued');
        $this->assertDatabaseCount('notifications', 1);
        DB::rollBack();
        $this->assertDatabaseCount('notifications', 0);

        $this->mock(InvoiceNotifier::class)->shouldReceive('send')->once()->andThrow(new \RuntimeException('Simulated failure'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->resident)->post(route('warga.invoices.payment', $invoice), ['proof' => UploadedFile::fake()->image('bukti.jpg')]);
            $this->fail('Upload should fail.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated failure', $e->getMessage());
        }
        $this->assertSame('unpaid', $invoice->refresh()->status->value);
        $this->assertNull($invoice->payment_proof_path);
        $this->assertSame([], Storage::disk('local')->allFiles('payment-proofs'));
    }

    public function test_reminders_are_date_specific_deduplicated_and_stop_after_payment(): void
    {
        $soon = $this->invoice();
        $this->invoice('2026-11-02', 'rejected');
        $this->invoice('2026-11-01'); // No catch-up.
        $this->invoice('2026-11-03');
        $this->invoice('2026-11-06', 'pending');
        $this->invoice('2026-11-06', 'verified');
        $inactive = User::factory()->inactive()->create(['housing_id' => $this->housing->id]);
        $this->invoice('2026-11-06', 'unpaid', $inactive);
        $this->artisan('app:send-invoice-reminders')->assertSuccessful();
        $this->artisan('app:send-invoice-reminders')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 2);
        $this->assertEqualsCanonicalizing(['due_soon', 'overdue'], $this->resident->notifications()->get()->pluck('data.event')->all());
        $soon->update(['status' => 'verified']);
        $this->travelTo(Carbon::parse('2026-11-07 08:00:00', 'Asia/Jakarta'));
        $this->artisan('app:send-invoice-reminders')->assertSuccessful();
        // Other unpaid invoice with that due date does not exist; pending and verified stay excluded.
        $this->assertDatabaseCount('notifications', 2);
        $this->housing->update(['is_active' => false]);
        app(InvoiceNotifier::class)->send($soon, 'issued');
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_inbox_read_state_isolation_and_moved_manager_context(): void
    {
        $invoice = $this->invoice();
        app(InvoiceNotifier::class)->send($invoice, 'issued');
        app(InvoiceNotifier::class)->send($invoice, 'issued');
        $this->assertDatabaseCount('notifications', 1);
        $item = $this->resident->notifications()->first();
        $this->actingAs($this->resident)->get(route('notifications.index'))->assertOk()->assertSee('Tagihan baru');
        $this->getJson(route('notifications.index'))->assertOk()->assertJsonPath('unread_count', 1)->assertJsonPath('items.0.title', 'Tagihan baru');
        $this->assertNull($item->refresh()->read_at);
        $this->actingAs($this->manager)->patchJson(route('notifications.read', $item->id))->assertNotFound();
        $this->patchJson(route('notifications.read-all'))->assertOk();
        $this->assertNull($item->refresh()->read_at);
        $this->actingAs($this->resident)->patchJson(route('notifications.read', $item->id))
            ->assertOk()->assertJsonPath('url', route('warga.invoices.show', $invoice));
        $this->assertNotNull($item->refresh()->read_at);
        $this->getJson(route('notifications.count'))->assertJsonPath('unread_count', 0);
        $this->get(route('notifications.index', ['filter' => 'unread']))->assertOk()->assertDontSee('Tagihan baru');
        app(InvoiceNotifier::class)->send($invoice, 'submitted');
        $managerItem = $this->manager->notifications()->first();
        $housingB = Housing::create(['name' => 'B', 'address' => 'B']);
        $this->manager->update(['housing_id' => $housingB->id]);
        $this->actingAs($this->manager)->getJson(route('notifications.index'))->assertJsonPath('unread_count', 0)->assertJsonCount(0, 'items');
        $this->patchJson(route('notifications.read', $managerItem->id))->assertNotFound();
        $this->patchJson(route('notifications.read-all'))->assertOk();
        $this->assertNull($managerItem->refresh()->read_at);
    }

    public function test_all_inbox_endpoints_block_inactive_users_housing_and_admin(): void
    {
        $invoice = $this->invoice();
        app(InvoiceNotifier::class)->send($invoice, 'issued');
        $id = $this->resident->notifications()->first()->id;
        $admin = User::factory()->admin()->create();
        foreach ([$admin, $this->resident] as $user) {
            if ($user->isResident()) {
                $user->update(['is_active' => false]);
            }
            $this->actingAs($user)->getJson(route('notifications.index'))->assertForbidden();
            $this->getJson(route('notifications.count'))->assertForbidden();
            $this->patchJson(route('notifications.read', $id))->assertForbidden();
            $this->patchJson(route('notifications.read-all'))->assertForbidden();
        }
        $this->resident->update(['is_active' => true]);
        $this->housing->update(['is_active' => false]);
        $this->actingAs($this->resident)->getJson(route('notifications.index'))->assertForbidden();
    }
}
