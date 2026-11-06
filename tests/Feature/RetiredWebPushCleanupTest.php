<?php

namespace Tests\Feature;

use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WhatsAppDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class RetiredWebPushCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_installation_does_not_retain_web_push_tables(): void
    {
        $this->assertFalse(Schema::hasTable('push_subscriptions'));
        $this->assertFalse(Schema::hasTable('push_deliveries'));
        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertTrue(Schema::hasTable('whatsapp_deliveries'));
    }

    public function test_upgrade_drops_populated_web_push_tables_without_removing_active_data(): void
    {
        (require database_path('migrations/2026_11_03_000003_create_web_push_tables.php'))->up();
        $housing = Housing::create(['name' => 'Perumahan Cleanup', 'address' => 'A']);
        $manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $resident = User::factory()->create(['housing_id' => $housing->id]);
        $invoice = Invoice::create([
            'housing_id' => $housing->id, 'created_by' => $manager->id, 'user_id' => $resident->id,
            'billing_period' => '2026-11-01', 'due_date' => '2026-11-10', 'total_amount' => 100000,
        ]);
        $notificationId = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $notificationId, 'type' => 'TestNotification', 'notifiable_type' => User::class,
            'notifiable_id' => $resident->id, 'housing_id' => $housing->id, 'data' => '{}',
        ]);
        $delivery = WhatsAppDelivery::create([
            'notification_id' => $notificationId, 'housing_id' => $housing->id, 'user_id' => $resident->id,
            'invoice_id' => $invoice->id, 'event' => 'issued', 'due_date' => $invoice->due_date, 'available_at' => now(),
        ]);
        $subscriptionId = DB::table('push_subscriptions')->insertGetId([
            'user_id' => $resident->id, 'housing_id' => $housing->id, 'endpoint_hash' => str_repeat('a', 64),
            'endpoint' => 'https://push.example.test/subscription', 'public_key' => 'test-key',
            'auth_token' => 'test-token', 'session_hash' => str_repeat('b', 64),
        ]);
        DB::table('push_deliveries')->insert([
            'push_subscription_id' => $subscriptionId, 'notification_id' => $notificationId, 'available_at' => now(),
        ]);

        $cleanup = require database_path('migrations/2026_11_06_000006_drop_retired_web_push_tables.php');
        $cleanup->up();
        $cleanup->up();

        $this->assertFalse(Schema::hasTable('push_subscriptions'));
        $this->assertFalse(Schema::hasTable('push_deliveries'));
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'total_amount' => 100000]);
        $this->assertDatabaseHas('notifications', ['id' => $notificationId]);
        $this->assertDatabaseHas('whatsapp_deliveries', ['id' => $delivery->id]);
        $this->assertDatabaseHas('users', ['id' => $resident->id]);

        $cleanup->down();
        $this->assertTrue(Schema::hasTable('push_subscriptions'));
        $this->assertTrue(Schema::hasTable('push_deliveries'));
        $this->assertDatabaseCount('push_subscriptions', 0);
        $this->assertDatabaseCount('push_deliveries', 0);
        $cleanup->up();
    }
}
