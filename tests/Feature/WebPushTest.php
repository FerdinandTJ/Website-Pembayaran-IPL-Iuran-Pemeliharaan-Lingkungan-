<?php

namespace Tests\Feature;

use App\Models\Housing;
use App\Models\PushDelivery;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\HousingNotification;
use App\Services\PushEndpoint;
use App\Services\PushSender;
use App\Services\WebPushTransport;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Housing $housing;

    private array $keys;

    private array $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->housing = Housing::create(['name' => 'Push A', 'address' => 'A']);
        $this->user = User::factory()->create(['housing_id' => $this->housing->id]);
        $this->keys = VAPID::createVapidKeys();
        config(['webpush.public_key' => $this->keys['publicKey'], 'webpush.private_key' => $this->keys['privateKey'], 'webpush.subject' => 'mailto:test@example.com']);
        $this->subscription = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/example-device', 'keys' => [
            'p256dh' => $this->keys['publicKey'], 'auth' => rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '='),
        ]];
    }

    private function register(): PushSubscription
    {
        $this->actingAs($this->user)->postJson(route('push.store'), $this->subscription)->assertCreated();

        return PushSubscription::firstOrFail();
    }

    private function notify(): void
    {
        $this->user->notify(new HousingNotification($this->housing->id, 'test:unique-event', [
            'event' => 'push_test', 'title' => 'Private test', 'message' => 'Nominal rahasia Rp 1.000.000',
        ]));
    }

    public function test_subscription_is_encrypted_and_public_config_never_returns_private_key(): void
    {
        $this->actingAs($this->user)->getJson(route('push.config'))->assertOk()->assertJsonPath('public_key', $this->keys['publicKey'])->assertDontSee($this->keys['privateKey']);
        $subscription = $this->register();
        $this->assertSame($this->subscription['endpoint'], $subscription->endpoint);
        $row = DB::table('push_subscriptions')->first();
        $this->assertStringNotContainsString('fcm.googleapis.com', $row->endpoint);
        $this->assertNotEquals($this->subscription['keys']['auth'], $row->auth_token);
        $this->postJson(route('push.status'), ['endpoint' => $this->subscription['endpoint']])->assertJsonPath('active', true);
    }

    public function test_endpoints_cannot_trigger_ssrf_or_take_over_another_users_device(): void
    {
        foreach (['http://fcm.googleapis.com/a', 'https://127.0.0.1/a', 'https://fcm.googleapis.com.evil.example/a', 'https://user@fcm.googleapis.com/a', 'https://fcm.googleapis.com:444/a', 'https://[::1]/a'] as $url) {
            $this->assertFalse(app(PushEndpoint::class)->allowed($url));
        }
        $this->actingAs($this->user)->postJson(route('push.store'), array_replace($this->subscription, ['endpoint' => 'https://127.0.0.1/a']))->assertUnprocessable();
        $this->register();
        $other = User::factory()->create(['housing_id' => $this->housing->id]);
        $this->actingAs($other)->postJson(route('push.store'), $this->subscription)->assertStatus(409);
        $this->deleteJson(route('push.destroy'), ['endpoint' => $this->subscription['endpoint']])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertSame($this->user->id, PushSubscription::first()->user_id);
    }

    public function test_notification_creates_one_outbox_row_and_rolls_back_atomically(): void
    {
        $this->register();
        DB::beginTransaction();
        $this->notify();
        $this->assertDatabaseCount('push_deliveries', 1);
        DB::rollBack();
        $this->assertDatabaseCount('push_deliveries', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->notify();
        $this->notify();
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('push_deliveries', 1);
    }

    public function test_sender_uses_generic_lockscreen_text_and_records_provider_acceptance(): void
    {
        $this->register();
        $this->notify();
        $this->mock(WebPushTransport::class)->shouldReceive('send')->once()->withArgs(fn ($subscription, $payload) => $payload['title'] === 'EnviroPay' && ! str_contains($payload['body'], '1.000.000') && $payload['url'] === '/notifications')->andReturn('sent');
        $this->artisan('app:send-web-push')->assertSuccessful();
        $this->assertSame('sent', PushDelivery::first()->status);
        app(PushSender::class)->send(PushDelivery::first()->id);
    }

    public function test_expired_subscriptions_are_removed_and_temporary_failures_retry(): void
    {
        $this->register();
        $this->notify();
        $this->mock(WebPushTransport::class)->shouldReceive('send')->once()->andReturn('retry');
        app(PushSender::class)->send(PushDelivery::first()->id);
        $delivery = PushDelivery::first();
        $this->assertSame('pending', $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertTrue($delivery->available_at->isFuture());
        $delivery->update(['available_at' => now()->subSecond()]);
        $this->mock(WebPushTransport::class)->shouldReceive('send')->once()->andReturn('expired');
        app(PushSender::class)->send($delivery->id);
        $this->assertDatabaseCount('push_subscriptions', 0);
        $this->assertDatabaseCount('push_deliveries', 0);
    }

    public function test_inactive_or_moved_accounts_are_not_sent_queued_pushes(): void
    {
        $this->register();
        $this->notify();
        $this->user->update(['is_active' => false]);
        $this->mock(WebPushTransport::class)->shouldNotReceive('send');
        app(PushSender::class)->send(PushDelivery::first()->id);
        $this->assertSame('canceled', PushDelivery::first()->status);
    }

    public function test_logout_and_device_opt_out_remove_subscriptions_and_pending_deliveries(): void
    {
        $this->register();
        $this->notify();
        $this->deleteJson(route('push.destroy'), ['endpoint' => $this->subscription['endpoint']])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 0);
        $this->assertDatabaseCount('push_deliveries', 0);
        $this->register();
        $this->withCookie(config('session.cookie'), session()->getId())->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_test_push_targets_only_current_device_and_inactive_routes_are_blocked(): void
    {
        $this->register();
        $this->subscription['endpoint'] = 'https://fcm.googleapis.com/fcm/send/second-device';
        $this->register();
        $this->postJson(route('push.test'), ['endpoint' => $this->subscription['endpoint']])->assertOk();
        $this->assertDatabaseCount('push_deliveries', 1);
        $this->assertSame(PushSubscription::latest('id')->first()->id, PushDelivery::first()->push_subscription_id);
        $this->get(route('profile.show'))->assertOk()->assertSee('Aktifkan Notifikasi Perangkat');
        $this->user->update(['is_active' => false]);
        $this->actingAs($this->user)->getJson(route('push.config'))->assertForbidden();
    }

    public function test_actual_web_push_library_encrypts_and_signs_without_contacting_provider(): void
    {
        $history = [];
        $handler = HandlerStack::create(new MockHandler([new Response(201)]));
        $handler->push(Middleware::history($history));
        $client = new WebPush(['VAPID' => $this->keys + ['subject' => 'mailto:test@example.com']], [], 15, ['handler' => $handler]);
        $payload = json_encode(['title' => 'EnviroPay', 'body' => 'Payload uji privat']);
        $report = $client->sendOneNotification(Subscription::create([
            'endpoint' => $this->subscription['endpoint'], 'publicKey' => $this->subscription['keys']['p256dh'],
            'authToken' => $this->subscription['keys']['auth'], 'contentEncoding' => 'aes128gcm',
        ]), $payload);
        $this->assertTrue($report->isSuccess());
        $request = $history[0]['request'];
        $this->assertSame('aes128gcm', $request->getHeaderLine('Content-Encoding'));
        $this->assertStringContainsString('vapid', $request->getHeaderLine('Authorization'));
        $this->assertStringNotContainsString('Payload uji privat', (string) $request->getBody());
    }

    public function test_key_generation_is_private_idempotent_and_never_prints_secrets(): void
    {
        config(['webpush.public_key' => null, 'webpush.private_key' => null]);
        $this->artisan('app:web-push-keys')->assertSuccessful();
        $first = Storage::disk('local')->get(config('webpush.key_file'));
        $this->assertArrayHasKey('privateKey', json_decode($first, true));
        $this->artisan('app:web-push-keys')->assertSuccessful();
        $this->assertSame($first,Storage::disk('local')->get(config('webpush.key_file')));
    }
}
