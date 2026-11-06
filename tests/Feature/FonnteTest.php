<?php

namespace Tests\Feature;

use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WhatsAppDelivery;
use App\Services\BatchPaymentNotifier;
use App\Services\BatchPaymentService;
use App\Services\FonnteClient;
use App\Services\InvoiceNotifier;
use App\Services\PaymentService;
use App\Services\WhatsAppReceiptMessage;
use App\Services\WhatsAppSender;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteTest extends TestCase
{
    use RefreshDatabase;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-03 08:00:00', 'Asia/Jakarta'));
        config(['services.fonnte.enabled' => true, 'services.fonnte.token' => 'test-secret', 'app.url' => 'https://ipl.example.com']);
        Http::preventStrayRequests();
        $this->fakeResponse(['status' => true, 'id' => ['123']]);
        $housing = Housing::create(['name' => 'Perumahan A', 'address' => 'A']);
        $manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $resident = User::factory()->create(['housing_id' => $housing->id, 'phone_number' => '0812-3456-7890']);
        $this->invoice = Invoice::create([
            'housing_id' => $housing->id, 'created_by' => $manager->id, 'user_id' => $resident->id,
            'title' => 'IPL November', 'billing_period' => '2026-11-01', 'due_date' => '2026-11-06',
            'total_amount' => 150000, 'status' => 'unpaid',
        ]);
    }

    private function fakeResponse(array $body, int $code = 200): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.fonnte.com/send' => Http::response($body, $code)]);
    }

    private function enqueue(string $event = 'issued'): WhatsAppDelivery
    {
        app(InvoiceNotifier::class)->send($this->invoice, $event);

        return WhatsAppDelivery::firstOrFail();
    }

    public function test_enqueues_once_and_sends_form_request_with_current_invoice_and_trusted_url(): void
    {
        $delivery = $this->enqueue();
        $this->enqueue();
        Http::assertNothingSent();
        $this->assertDatabaseCount('whatsapp_deliveries', 1);
        $this->artisan('app:send-whatsapp')->assertSuccessful();
        $this->artisan('app:send-whatsapp')->assertSuccessful();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send'
            && $request->hasHeader('Authorization', 'test-secret')
            && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
            && $request['target'] === '6281234567890'
            && $request['countryCode'] === '0'
            && str_contains($request['message'], 'Rp 150.000')
            && str_contains($request['message'], '06/11/2026')
            && str_contains($request['message'], 'https://ipl.example.com/warga/invoices/'.$this->invoice->id));
        $this->assertSame('accepted', $delivery->refresh()->status);
        $this->assertNotNull($delivery->accepted_at);
    }

    public function test_outbox_rolls_back_with_invoice_transaction(): void
    {
        DB::beginTransaction();
        try {
            $this->enqueue();
            $this->assertDatabaseCount('whatsapp_deliveries', 1);
        } finally {
            DB::rollBack();
        }
        $this->assertDatabaseCount('whatsapp_deliveries', 0);
        $this->assertDatabaseCount('notifications', 0);
        Http::assertNothingSent();
    }

    public function test_daily_reminders_only_queue_eligible_dates_once(): void
    {
        $this->invoice->replicate()->fill(['due_date' => '2026-11-02', 'status' => 'rejected'])->save();
        $this->invoice->replicate()->fill(['due_date' => '2026-11-01'])->save();
        $this->invoice->replicate()->fill(['status' => 'pending'])->save();
        $this->invoice->replicate()->fill(['status' => 'verified'])->save();
        $this->artisan('app:send-invoice-reminders')->assertSuccessful();
        $this->artisan('app:send-invoice-reminders')->assertSuccessful();
        $this->assertDatabaseCount('whatsapp_deliveries', 2);
        $this->assertEqualsCanonicalizing(['due_soon', 'overdue'], WhatsAppDelivery::pluck('event')->all());
        $this->artisan('app:send-whatsapp')->assertSuccessful();
        Http::assertSentCount(2);
    }

    public function test_disabled_or_missing_token_keeps_in_app_notifications_without_wa(): void
    {
        config(['services.fonnte.enabled' => false]);
        app(InvoiceNotifier::class)->send($this->invoice, 'issued');
        config(['services.fonnte.enabled' => true, 'services.fonnte.token' => '']);
        app(InvoiceNotifier::class)->send($this->invoice, 'due_soon');
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseCount('whatsapp_deliveries', 0);
        $this->artisan('app:send-whatsapp')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_other_events_are_not_sent_to_whatsapp(): void
    {
        foreach (['submitted', 'rejected', 'verified', 'cash_paid'] as $event) {
            app(InvoiceNotifier::class)->send($this->invoice, $event);
        }
        $this->assertDatabaseCount('whatsapp_deliveries', 0);
    }

    public function test_paid_pending_changed_expired_and_invalid_recipients_are_skipped(): void
    {
        foreach (['verified', 'pending', 'due_date', 'expired', 'inactive', 'housing', 'moved', 'owner', 'phone'] as $scenario) {
            $invoice = $this->invoice->replicate();
            $invoice->save();
            app(InvoiceNotifier::class)->send($invoice, 'issued');
            $delivery = WhatsAppDelivery::where('invoice_id', $invoice->id)->firstOrFail();
            $user = $invoice->resident;
            switch ($scenario) {
                case 'verified': case 'pending': $invoice->update(['status' => $scenario]);
                    break;
                case 'due_date': $invoice->update(['due_date' => '2026-11-07']);
                    break;
                case 'expired': $delivery->update(['created_at' => now()->subDays(2)]);
                    break;
                case 'inactive': $user->update(['is_active' => false]);
                    break;
                case 'housing': $invoice->housing->update(['is_active' => false]);
                    break;
                case 'moved': $user->update(['housing_id' => Housing::create(['name' => 'B', 'address' => 'B'])->id]);
                    break;
                case 'owner': $invoice->update(['user_id' => User::factory()->create(['housing_id' => $invoice->housing_id])->id]);
                    break;
                case 'phone': $user->update(['phone_number' => '081234567890,089999999999']);
                    break;
            }
            app(WhatsAppSender::class)->send($delivery->id);
            $this->assertSame('skipped', $delivery->refresh()->status, $scenario);
            $user->update(['is_active' => true, 'housing_id' => $this->invoice->housing_id, 'phone_number' => '081234567890']);
            $this->invoice->housing->update(['is_active' => true]);
        }
        Http::assertNothingSent();
    }

    public function test_provider_rejection_retries_with_backoff_and_stops_after_five_attempts(): void
    {
        $this->fakeResponse(['status' => false, 'reason' => 'test-secret']);
        $delivery = $this->enqueue();
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->artisan('app:send-whatsapp')->assertSuccessful();
            $delivery->refresh();
            $this->assertSame($attempt, $delivery->attempts);
            $this->assertSame($attempt === 5 ? 'failed' : 'pending', $delivery->status);
            $this->assertSame('provider_rejected', $delivery->last_error);
            $this->artisan('app:send-whatsapp')->assertSuccessful();
            $this->travelTo($delivery->available_at->copy()->addSecond());
        }
        Http::assertSentCount(5);
        $this->assertStringNotContainsString('test-secret', $delivery->toJson());
    }

    public function test_timeouts_and_stale_claims_are_not_automatically_resent(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.fonnte.com/send' => Http::failedConnection()]);
        $delivery = $this->enqueue();
        $this->artisan('app:send-whatsapp')->assertSuccessful();
        $this->assertSame('uncertain', $delivery->refresh()->status);
        $this->travel(10)->minutes();
        $this->artisan('app:send-whatsapp')->assertSuccessful();
        $this->assertSame(1, $delivery->refresh()->attempts);
        $delivery->update(['status' => 'processing', 'claimed_until' => now()->subMinute()]);
        $this->artisan('app:send-whatsapp')->assertSuccessful();
        $this->assertSame('uncertain', $delivery->refresh()->status);
        $this->assertSame('worker_interrupted', $delivery->last_error);
    }

    public function test_http_errors_and_malformed_success_are_never_marked_accepted(): void
    {
        foreach ([401 => 'failed', 429 => 'pending', 500 => 'uncertain', 200 => 'uncertain'] as $code => $status) {
            $this->fakeResponse(['reason' => 'error'], $code);
            $delivery = $this->enqueue();
            $delivery->update(['status' => 'pending', 'available_at' => now()]);
            app(WhatsAppSender::class)->send($delivery->id);
            $this->assertSame($status, $delivery->refresh()->status);
        }
    }

    public function test_phone_normalization_rejects_multi_target_and_group_injection(): void
    {
        $client = app(FonnteClient::class);
        foreach (['081234567890', '+62 812 3456 7890', '6281234567890'] as $phone) {
            $this->assertSame('6281234567890', $client->normalizePhone($phone));
        }
        foreach ([null, '', '123', '081234567890,089999999999', '081234567890|Nama', '123@g.us', 'abc081234567890'] as $phone) {
            $this->assertNull($client->normalizePhone($phone));
        }
    }

    public function test_profile_and_notifications_no_longer_offer_browser_push(): void
    {
        $this->actingAs($this->invoice->resident)->get(route('profile.show'))->assertOk()->assertDontSee('data-web-push', false)->assertDontSee('Aktifkan Notifikasi Perangkat');
        $this->get(route('warga.dashboard'))->assertOk()->assertDontSee('notifikasi-perangkat')->assertDontSee('Aktifkan notifikasi layar HP');
        $this->get('/push/config')->assertNotFound();
        $this->post('/push/subscriptions')->assertNotFound();
    }

    public function test_transfer_and_cash_send_a_text_receipt_with_the_issued_snapshot(): void
    {
        foreach (['transfer', 'cash'] as $method) {
            $invoice = $this->invoice->replicate();
            $invoice->fill(['house_label' => 'A-01'])->save();
            $invoice->components()->create(['name' => 'Keamanan', 'amount' => 100000]);
            $invoice->components()->create(['name' => 'Kebersihan', 'amount' => 50000]);
            $service = app(PaymentService::class);
            if ($method === 'transfer') {
                $service->submitProof($invoice, $invoice->resident, 'proof/test.jpg');
                $this->assertDatabaseCount('whatsapp_deliveries', $method === 'transfer' ? 0 : 1);
            }
            $receipt = $service->settle($invoice, $invoice->creator, $method, 'Pembayaran November');
            $delivery = WhatsAppDelivery::where('receipt_id', $receipt->id)->firstOrFail();
            app(InvoiceNotifier::class)->send($invoice->fresh(), $method === 'cash' ? 'cash_paid' : 'verified');
            $this->assertSame(1, WhatsAppDelivery::where('receipt_id', $receipt->id)->count());
            $invoice->update(['title' => 'Judul diganti', 'total_amount' => 1]);
            app(WhatsAppSender::class)->send($delivery->id);
            $this->assertSame('accepted', $delivery->refresh()->status);
            Http::assertSent(fn ($request) => str_contains($request['message'], 'No. kuitansi: '.$receipt->number)
                && str_contains($request['message'], '*KUITANSI PEMBAYARAN IPL*')
                && str_contains($request['message'], 'Status: *LUNAS*')
                && str_contains($request['message'], 'Rumah: A-01')
                && str_contains($request['message'], '• Keamanan: Rp 100.000')
                && str_contains($request['message'], '• Kebersihan: Rp 50.000')
                && str_contains($request['message'], '*Rp 150.000*')
                && str_contains($request['message'], '03/11/2026 08:00 WIB')
                && str_contains($request['message'], 'Metode: '.($method === 'cash' ? 'Tunai / Cash' : 'Transfer Bank'))
                && str_contains($request['message'], 'Dikonfirmasi oleh: '.$invoice->creator->name)
                && str_contains($request['message'], 'https://ipl.example.com/invoices/'.$invoice->id.'/receipt')
                && ! str_contains($request['message'], 'Judul diganti')
                && ! isset($request['file']) && ! isset($request['url']));
        }
        Http::assertSentCount(2);
    }

    public function test_batch_transfer_and_cash_send_one_receipt_for_all_invoices(): void
    {
        foreach (['transfer', 'cash'] as $method) {
            $first = $this->invoice->replicate();
            $first->save();
            $second = $this->invoice->replicate();
            $second->fill(['title' => 'IPL Desember', 'billing_period' => '2026-12-01'])->save();
            $service = app(BatchPaymentService::class);
            $batch = $service->create($method === 'cash' ? $first->creator : $first->resident,
                [$first->id, $second->id], $method, '300000.00', $method === 'transfer' ? 'proof/batch.jpg' : null);
            if ($method === 'transfer') {
                $this->assertSame(0, WhatsAppDelivery::whereIn('invoice_id', [$first->id, $second->id])->count());
                $batch = $service->decide($batch, $first->creator);
            }
            app(BatchPaymentNotifier::class)->send($batch, $method === 'cash' ? 'cash_paid' : 'verified');
            $this->assertSame(1, WhatsAppDelivery::whereIn('invoice_id', [$first->id, $second->id])->count());
            $delivery = WhatsAppDelivery::where('receipt_id', $batch->receipt->id)->firstOrFail();
            app(WhatsAppSender::class)->send($delivery->id);
            $this->assertSame('accepted', $delivery->refresh()->status);
            Http::assertSent(fn ($request) => str_contains($request['message'], $batch->receipt->number)
                && str_contains($request['message'], '1. IPL November')
                && str_contains($request['message'], '2. IPL Desember')
                && str_contains($request['message'], '*Rp 300.000*'));
        }
        Http::assertSentCount(2);
    }

    public function test_receipt_is_not_queued_when_payment_rolls_back(): void
    {
        DB::beginTransaction();
        try {
            app(PaymentService::class)->settle($this->invoice, $this->invoice->creator, 'cash');
            $this->assertDatabaseCount('whatsapp_deliveries', 1);
        } finally {
            DB::rollBack();
        }
        $this->assertDatabaseCount('whatsapp_deliveries', 0);
        $this->assertDatabaseCount('receipts', 0);
        $this->assertSame('unpaid', $this->invoice->fresh()->status->value);
        Http::assertNothingSent();
    }

    public function test_payment_cancels_queued_reminder_but_sends_receipt(): void
    {
        $reminder = $this->enqueue();
        $receipt = app(PaymentService::class)->settle($this->invoice, $this->invoice->creator, 'cash');
        $this->artisan('app:send-whatsapp')->assertSuccessful();
        $this->assertSame('skipped', $reminder->refresh()->status);
        $this->assertDatabaseHas('whatsapp_deliveries', ['receipt_id' => $receipt->id, 'status' => 'accepted']);
        Http::assertSentCount(1);
    }

    public function test_receipt_checks_current_recipient_and_payment_status(): void
    {
        foreach (['inactive', 'moved', 'pending', 'receipt_mismatch'] as $scenario) {
            $invoice = $this->invoice->replicate();
            $invoice->save();
            $receipt = app(PaymentService::class)->settle($invoice, $invoice->creator, 'cash');
            $delivery = WhatsAppDelivery::where('receipt_id', $receipt->id)->firstOrFail();
            $user = $invoice->resident;
            if ($scenario === 'inactive') {
                $user->update(['is_active' => false]);
            }
            if ($scenario === 'moved') {
                $user->update(['housing_id' => Housing::create(['name' => 'B', 'address' => 'B'])->id]);
            }
            if ($scenario === 'pending') {
                $invoice->update(['status' => 'pending']);
            }
            if ($scenario === 'receipt_mismatch') {
                $receipt->update(['invoice_id' => $this->invoice->id]);
            }
            app(WhatsAppSender::class)->send($delivery->id);
            $this->assertSame('skipped', $delivery->refresh()->status, $scenario);
            $user->update(['is_active' => true, 'housing_id' => $this->invoice->housing_id]);
        }
        Http::assertNothingSent();
    }

    public function test_receipt_formatter_keeps_layout_safe_and_decimal_amounts_exact(): void
    {
        $this->invoice->update(['total_amount' => '150000.50']);
        $receipt = app(PaymentService::class)->settle($this->invoice, $this->invoice->creator, 'cash', "Baris satu\n*Status palsu*");
        $originalName = $receipt->snapshot['resident'];
        $this->invoice->resident->update(['name' => 'Nama diubah']);
        $message = app(WhatsAppReceiptMessage::class)->render($receipt, 'https://ipl.example.com/receipt');
        $this->assertStringContainsString($originalName, $message);
        $this->assertStringNotContainsString('Nama diubah', $message);
        $this->assertStringContainsString('*Rp 150.000,50*', $message);
        $this->assertStringContainsString('Catatan: Baris satu Status palsu', $message);
        $this->assertStringNotContainsString('*Status palsu*', $message);
    }
}
