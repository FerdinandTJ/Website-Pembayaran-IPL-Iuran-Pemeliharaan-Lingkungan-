<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Cashflow;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HousingIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_cannot_change_resources_from_another_housing(): void
    {
        [$managerA, $residentA, $housingA] = $this->housingUsers('A');
        [$managerB, $residentB, $housingB] = $this->housingUsers('B');
        $invoiceB = $this->invoice($housingB, $managerB, $residentB, InvoiceStatus::Pending);
        $cashflowB = Cashflow::create([
            'housing_id' => $housingB->id, 'created_by' => $managerB->id,
            'name' => 'Kas B', 'type' => 'income', 'source' => 'manual', 'total_cashflow' => 10000,
        ]);

        $this->actingAs($managerA)->put(route('pengurus.residents.update', $residentB), [
            'name' => 'Diubah', 'username' => $residentB->username, 'email' => $residentB->email,
            'phone_number' => '08123456789', 'house_address' => 'Alamat', 'is_active' => 1,
        ])->assertForbidden();
        $this->actingAs($managerA)->post(route('pengurus.invoices.verify', $invoiceB))->assertForbidden();
        $this->actingAs($managerA)->put(route('pengurus.cashflows.update', $cashflowB), [
            'name' => 'Diubah', 'type' => 'expense', 'total_cashflow' => 1,
        ])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $residentB->id, 'name' => $residentB->name]);
    }

    public function test_resident_cannot_view_invoice_or_proof_owned_by_another_resident(): void
    {
        [$manager, $residentA, $housing] = $this->housingUsers('A');
        $residentB = User::factory()->create(['housing_id' => $housing->id]);
        $invoiceB = $this->invoice($housing, $manager, $residentB, InvoiceStatus::Pending);
        Storage::fake('local');
        Storage::disk('local')->put('payment-proofs/proof.png', 'fake');
        $invoiceB->update(['payment_proof_path' => 'payment-proofs/proof.png']);

        $this->actingAs($residentA)->get(route('warga.invoices.show', $invoiceB))->assertForbidden();
        $this->actingAs($residentA)->get(route('invoices.proof', $invoiceB))->assertForbidden();
        $this->actingAs($manager)->get(route('invoices.proof', $invoiceB))->assertOk();
    }

    private function housingUsers(string $suffix): array
    {
        $housing = Housing::create(['name' => 'Housing '.$suffix, 'address' => 'Alamat '.$suffix]);
        $manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $resident = User::factory()->create(['housing_id' => $housing->id]);

        return [$manager, $resident, $housing];
    }

    private function invoice(Housing $housing, User $manager, User $resident, InvoiceStatus $status): Invoice
    {
        return Invoice::create([
            'housing_id' => $housing->id, 'user_id' => $resident->id, 'created_by' => $manager->id,
            'billing_period' => now()->startOfMonth(),
            'due_date' => now()->addMonth(), 'total_amount' => 50000, 'status' => $status,
            'submitted_at' => $status === InvoiceStatus::Pending ? now() : null,
        ]);
    }
}
