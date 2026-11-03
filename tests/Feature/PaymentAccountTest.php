<?php

namespace Tests\Feature;

use App\Models\Housing;
use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_housing_can_have_multiple_payment_accounts(): void
    {
        $housing = Housing::create(['name' => 'Griya Asri', 'address' => 'Jakarta']);
        $manager = User::factory()->manager()->create(['housing_id' => $housing->id]);

        foreach ([
            ['bank_name' => 'BCA', 'account_number' => '111111', 'account_holder' => 'Kas Griya'],
            ['bank_name' => 'Mandiri', 'account_number' => '222222', 'account_holder' => 'Kas Griya'],
        ] as $account) {
            $this->actingAs($manager)->post(route('pengurus.payment-accounts.store'), [
                ...$account, 'is_active' => 1,
            ])->assertRedirect();
        }

        $this->assertDatabaseCount('payment_accounts', 2);
    }

    public function test_manager_cannot_change_another_housings_account(): void
    {
        $housingA = Housing::create(['name' => 'Housing A', 'address' => 'Jakarta']);
        $housingB = Housing::create(['name' => 'Housing B', 'address' => 'Bandung']);
        $managerA = User::factory()->manager()->create(['housing_id' => $housingA->id]);
        $accountB = PaymentAccount::create([
            'housing_id' => $housingB->id, 'bank_name' => 'BCA',
            'account_number' => '123456', 'account_holder' => 'Housing B',
        ]);

        $this->actingAs($managerA)->put(route('pengurus.payment-accounts.update', $accountB), [
            'bank_name' => 'BRI', 'account_number' => '999999',
            'account_holder' => 'Diubah', 'is_active' => 1,
        ])->assertForbidden();

        $this->assertDatabaseHas('payment_accounts', ['id' => $accountB->id, 'bank_name' => 'BCA']);
    }
}
