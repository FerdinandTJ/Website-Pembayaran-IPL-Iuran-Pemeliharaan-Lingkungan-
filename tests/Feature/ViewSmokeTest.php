<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Cashflow;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_role_pages_render_successfully(): void
    {
        $housing = Housing::create(['name' => 'Griya Asri', 'address' => 'Jl. Hijau']);
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $resident = User::factory()->create(['housing_id' => $housing->id]);
        PaymentAccount::create([
            'housing_id' => $housing->id, 'bank_name' => 'BCA',
            'account_number' => '123456789', 'account_holder' => 'Griya Asri',
        ]);
        $invoice = Invoice::create([
            'housing_id' => $housing->id, 'user_id' => $resident->id, 'created_by' => $manager->id,
            'billing_period' => now()->startOfMonth(),
            'due_date' => now()->addMonth(), 'total_amount' => 100000, 'status' => InvoiceStatus::Unpaid,
        ]);
        $invoice->components()->create(['name' => 'IPL', 'amount' => 100000]);
        Cashflow::create([
            'housing_id' => $housing->id, 'created_by' => $manager->id,
            'name' => 'Saldo Awal', 'type' => 'income', 'source' => 'manual', 'total_cashflow' => 500000,
        ]);

        foreach ([
            route('admin.dashboard'), route('admin.housings.index'), route('admin.housings.create'),
            route('admin.managers.index'), route('admin.managers.create'), route('profile.show'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        foreach ([
            route('pengurus.dashboard'), route('pengurus.residents.index'), route('pengurus.residents.create'),
            route('pengurus.invoices.index'), route('pengurus.invoices.create'),
            route('pengurus.payment-accounts.index'),
            route('pengurus.verifications.index'), route('pengurus.cashflows.index'), route('profile.show'),
        ] as $url) {
            $this->actingAs($manager)->get($url)->assertOk();
        }

        foreach ([
            route('warga.dashboard'), route('warga.invoices.index'),
            route('profile.show'),
        ] as $url) {
            $this->actingAs($resident)->get($url)->assertOk();
        }

        $this->actingAs($resident)->get(route('warga.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('123456789')
            ->assertSee('BCA');
    }
}
