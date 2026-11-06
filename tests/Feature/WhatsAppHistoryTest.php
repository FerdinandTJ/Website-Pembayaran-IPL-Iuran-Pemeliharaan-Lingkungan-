<?php

namespace Tests\Feature;

use App\Models\Housing;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\User;
use App\Models\WhatsAppDelivery;
use App\Notifications\HousingNotification;
use App\Services\BatchPaymentService;
use App\Services\WhatsAppSender;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsAppHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-06 10:00:00', 'Asia/Jakarta'));
        Http::preventStrayRequests();
        $housing = Housing::create(['name' => 'Riwayat A', 'address' => 'A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->resident = User::factory()->create(['housing_id' => $housing->id, 'name' => 'Budi Utama', 'phone_number' => '081234567890']);
    }

    private function delivery(array $attributes = [], ?User $resident = null): WhatsAppDelivery
    {
        $resident ??= $this->resident;
        $invoice = Invoice::create(['housing_id' => $resident->housing_id, 'user_id' => $resident->id,
            'created_by' => $this->manager->id, 'title' => 'IPL untuk riwayat', 'house_label' => 'Rumah A-SATU',
            'billing_period' => '2026-11-01', 'due_date' => '2026-11-10', 'total_amount' => '150000', 'status' => 'unpaid']);
        $notification = $resident->notifications()->create(['id' => (string) Str::uuid(), 'type' => HousingNotification::class,
            'housing_id' => $resident->housing_id, 'data' => ['event' => 'issued', 'invoice_id' => $invoice->id]]);

        return WhatsAppDelivery::create($attributes + ['notification_id' => $notification->id,
            'housing_id' => $resident->housing_id, 'user_id' => $resident->id, 'invoice_id' => $invoice->id,
            'event' => 'issued', 'status' => 'pending', 'due_date' => '2026-11-10', 'available_at' => now()]);
    }

    public function test_summary_covers_all_states_and_respects_the_selected_filters(): void
    {
        foreach (array_keys(WhatsAppDelivery::STATUSES) as $status) {
            $this->delivery(['status' => $status]);
        }
        $this->actingAs($this->manager)->get(route('pengurus.whatsapp-history.index'))->assertOk()
            ->assertSee('Pengiriman WhatsApp belum aktif')
            ->assertViewHas('deliveries', fn ($rows) => $rows->total() === 6)
            ->assertViewHas('summary', fn ($summary) => $summary->sum() === 6 && $summary->every(fn ($count) => $count === 1));
        $this->get(route('pengurus.whatsapp-history.index', ['status' => 'failed']))->assertOk()
            ->assertViewHas('deliveries', fn ($rows) => $rows->total() === 1 && $rows->first()->status === 'failed')
            ->assertViewHas('summary', fn ($summary) => $summary['failed'] === 1 && $summary->sum() === 1);
    }

    public function test_date_range_includes_end_of_day_and_defaults_to_current_server_month(): void
    {
        $this->delivery(['created_at' => '2026-10-31 23:59:59']);
        $this->delivery(['created_at' => '2026-11-01 00:00:00']);
        $this->delivery(['created_at' => '2026-11-06 23:59:59']);
        $this->delivery(['created_at' => '2026-11-07 00:00:00']);
        $this->actingAs($this->manager)->get(route('pengurus.whatsapp-history.index'))->assertOk()
            ->assertViewHas('deliveries', fn ($rows) => $rows->total() === 2);
        $this->get(route('pengurus.whatsapp-history.index', ['date_from' => '2026-10-31', 'date_to' => '2026-10-31']))->assertOk()
            ->assertViewHas('deliveries', fn ($rows) => $rows->total() === 1);
        $this->get(route('pengurus.whatsapp-history.index', ['date_from' => '', 'date_to' => '']))->assertOk()
            ->assertViewHas('deliveries', fn ($rows) => $rows->total() === 4);
    }

    public function test_search_event_pagination_and_detail_back_link_keep_filters(): void
    {
        for ($i = 0; $i < 23; $i++) {
            $this->delivery(['status' => 'failed', 'event' => 'due_soon', 'recipient_name' => 'Warga 0', 'recipient_house' => 'Blok Z', 'created_at' => '2026-10-10 10:00:00']);
        }
        $this->delivery(['event' => 'issued', 'recipient_name' => 'Warga Lain']);
        $filters = ['date_from' => '', 'date_to' => '', 'status' => 'failed', 'event' => 'due_soon', 'search' => '0'];
        $page = $this->actingAs($this->manager)->get(route('pengurus.whatsapp-history.index', $filters))->assertOk()
            ->assertViewHas('deliveries', fn ($rows) => $rows->count() === 20 && $rows->total() === 23)
            ->assertViewHas('summary', fn ($summary) => $summary['failed'] === 23);
        $next = $page->viewData('deliveries')->nextPageUrl();
        $this->assertStringContainsString('date_from=', $next);
        $this->assertStringContainsString('date_to=', $next);
        $this->get($next)->assertOk()->assertViewHas('deliveries', fn ($rows) => $rows->count() === 3 && $rows->total() === 23);
        $delivery = $page->viewData('deliveries')->first();
        $detail = $this->get(route('pengurus.whatsapp-history.show', ['whatsAppDelivery' => $delivery->id, ...$filters, 'page' => 2]))->assertOk();
        $detail->assertSee('search=0', false)->assertSee('page=2', false)->assertSee('date_from=', false);
    }

    public function test_unknown_errors_are_safely_presented_and_reads_never_send_or_mutate_records(): void
    {
        config(['services.fonnte.enabled' => true, 'services.fonnte.token' => 'private-fonnte-token']);
        $delivery = $this->delivery(['status' => 'uncertain', 'last_error' => 'private-fonnte-token', 'attempts' => 1]);
        $before = (array) DB::table('whatsapp_deliveries')->where('id', $delivery->id)->first();
        $this->actingAs($this->manager)->get(route('pengurus.whatsapp-history.index'))->assertOk()->assertDontSee('private-fonnte-token');
        $this->get(route('pengurus.whatsapp-history.show', $delivery))->assertOk()->assertSee('Permintaan mungkin sudah diterima Fonnte')->assertDontSee('private-fonnte-token');
        $this->post(route('pengurus.whatsapp-history.show', $delivery))->assertMethodNotAllowed();
        $this->post(route('pengurus.whatsapp-history.show', $delivery).'/retry')->assertNotFound();
        $this->assertSame($before, (array) DB::table('whatsapp_deliveries')->where('id', $delivery->id)->first());
        Http::assertNothingSent();
    }

    public function test_actual_last_destination_is_encrypted_and_distinct_from_changed_current_phone(): void
    {
        config(['services.fonnte.enabled' => true, 'services.fonnte.token' => 'test-secret']);
        Http::fake(['api.fonnte.com/send' => Http::response(['status' => true])]);
        $delivery = $this->delivery();
        app(WhatsAppSender::class)->send($delivery->id);
        $delivery->refresh();
        $this->assertSame('accepted', $delivery->status);
        $this->assertSame('6281234567890', $delivery->recipient_phone);
        $this->assertSame('Budi Utama', $delivery->recipient_name);
        $this->assertNotNull($delivery->last_attempt_at);
        $this->assertNotSame('6281234567890', DB::table('whatsapp_deliveries')->where('id', $delivery->id)->value('recipient_phone'));
        $this->resident->update(['phone_number' => '089876543210', 'name' => 'Nama Baru']);
        $this->actingAs($this->manager)->get(route('pengurus.whatsapp-history.show', $delivery))->assertOk()
            ->assertSee('6281234567890')->assertSee('089876543210')->assertSee('Budi Utama')->assertDontSee('test-secret');
        Http::assertSentCount(1);
    }

    public function test_legacy_delivery_does_not_fabricate_a_historical_destination(): void
    {
        $delivery = $this->delivery(['status' => 'accepted', 'attempts' => 1, 'accepted_at' => now()]);
        $response = $this->actingAs($this->manager)->get(route('pengurus.whatsapp-history.show', $delivery))->assertOk()
            ->assertSee('Belum tercatat')->assertSee('nomor akun saat ini tidak digunakan sebagai pengganti catatan pengiriman lama');
        $this->assertNull($response->viewData('delivery')->recipient_phone);
        $this->assertNull($response->viewData('delivery')->last_attempt_at);
    }

    public function test_other_housings_cannot_view_records_or_leak_moved_account_data(): void
    {
        $delivery = $this->delivery(['recipient_name' => 'Nama Saat Dikirim', 'recipient_phone' => '6281234567890']);
        $housing = Housing::create(['name' => 'Riwayat B', 'address' => 'B']);
        $other = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->actingAs($other)->get(route('pengurus.whatsapp-history.index'))->assertOk()->assertViewHas('deliveries', fn ($rows) => $rows->total() === 0);
        $this->get(route('pengurus.whatsapp-history.show', $delivery))->assertNotFound();
        $this->resident->update(['housing_id' => $housing->id, 'name' => 'Rahasia Rumah B', 'phone_number' => '089999999999']);
        $this->actingAs($this->manager)->get(route('pengurus.whatsapp-history.show', $delivery))->assertOk()
            ->assertSee('Nama Saat Dikirim')->assertDontSee('Rahasia Rumah B')->assertDontSee('089999999999');
        $this->get(route('pengurus.whatsapp-history.index', ['search' => 'Rahasia Rumah B']))->assertOk()
            ->assertViewHas('deliveries', fn ($rows) => $rows->total() === 0);
    }

    public function test_foreign_receipt_reference_cannot_expose_receipt_content_or_links(): void
    {
        $delivery = $this->delivery();
        $housing = Housing::create(['name' => 'Other', 'address' => 'B']);
        $foreign = User::factory()->create(['housing_id' => $housing->id]);
        $foreignDelivery = $this->delivery([], $foreign);
        $receipt = Receipt::create(['invoice_id' => $foreignDelivery->invoice_id, 'number' => 'SECRET-OTHER-RECEIPT', 'snapshot' => ['resident' => 'SECRET-OTHER-NAME']]);
        $delivery->update(['receipt_id' => $receipt->id]);
        $this->actingAs($this->manager)->get(route('pengurus.whatsapp-history.show', $delivery))->assertOk()
            ->assertDontSee('SECRET-OTHER-RECEIPT')->assertDontSee('SECRET-OTHER-NAME')->assertViewHas('matchingReceipt', false);
    }

    public function test_residents_admins_and_inactive_accounts_or_housings_are_blocked(): void
    {
        $delivery = $this->delivery();
        $admin = User::factory()->admin()->create();
        foreach ([$this->resident, $admin] as $user) {
            $this->actingAs($user)->get(route('pengurus.whatsapp-history.index'))->assertForbidden();
            $this->get(route('pengurus.whatsapp-history.show', $delivery))->assertForbidden();
        }
        $this->manager->update(['is_active' => false]);
        $this->actingAs($this->manager)->get(route('pengurus.whatsapp-history.index'))->assertForbidden();
        $this->manager->update(['is_active' => true]);
        $this->manager->housing->update(['is_active' => false]);
        $this->actingAs($this->manager->fresh())->get(route('pengurus.whatsapp-history.show', $delivery))->assertForbidden();
    }

    public function test_filters_reject_invalid_dates_status_events_and_arrays(): void
    {
        $this->actingAs($this->manager);
        foreach (['date_from' => 'bad', 'date_to' => ['array'], 'status' => 'unknown', 'event' => 'unknown', 'search' => ['array'], 'page' => -1] as $key => $value) {
            $this->getJson(route('pengurus.whatsapp-history.index', [$key => $value]))->assertUnprocessable()->assertJsonValidationErrors($key);
        }
        $this->getJson(route('pengurus.whatsapp-history.index', ['date_from' => '2026-11-06', 'date_to' => '2026-11-01']))->assertUnprocessable()->assertJsonValidationErrors('date_to');
        Http::assertNothingSent();
    }

    public function test_grouped_receipt_history_links_to_the_correct_authorized_receipt(): void
    {
        $first = $this->delivery()->invoice;
        $second = $first->replicate();
        $second->save();
        $batch = app(BatchPaymentService::class)->create($this->manager, [$first->id, $second->id], 'cash', '300000.00');
        $notification = $this->resident->notifications()->create(['id' => (string) Str::uuid(), 'type' => HousingNotification::class,
            'housing_id' => $this->manager->housing_id, 'data' => ['event' => 'batch_cash_paid', 'payment_batch_id' => $batch->id]]);
        $delivery = WhatsAppDelivery::create(['housing_id' => $this->manager->housing_id, 'user_id' => $this->resident->id,
            'invoice_id' => $first->id, 'receipt_id' => $batch->receipt->id, 'notification_id' => $notification->id,
            'event' => 'batch_cash_paid', 'status' => 'accepted', 'due_date' => '2026-11-10', 'available_at' => now(), 'accepted_at' => now()]);
        $this->actingAs($this->manager)->get(route('pengurus.whatsapp-history.show', $delivery))->assertOk()
            ->assertSee('Kuitansi Gabungan Tunai')->assertSee($batch->receipt->number)->assertSee('Lihat Kuitansi')
            ->assertViewHas('matchingReceipt', true)->assertSee(route('receipts.show', $first), false);
        Http::assertNothingSent();
    }
}
