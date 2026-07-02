<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Cashflow;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BillingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_manual_payment_workflow_and_cashflow_integrity(): void
    {
        Storage::fake('local');
        $housing = Housing::create(['name' => 'Griya Asri', 'address' => 'Jl. Hijau']);
        $manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $resident = User::factory()->create(['housing_id' => $housing->id]);
        User::factory()->inactive()->create(['housing_id' => $housing->id]);

        $payload = [
            'account_number' => 'BCA 123456789',
            'billing_period' => '2026-07', 'due_date' => '2026-08-10',
            'components' => [
                ['name' => 'Kebersihan', 'amount' => 50000],
                ['name' => 'Keamanan', 'amount' => 75000],
            ],
        ];
        $this->actingAs($manager)->post(route('pengurus.invoices.store'), $payload)
            ->assertRedirect(route('pengurus.invoices.index'));

        $this->assertDatabaseCount('invoices', 1);
        $invoice = Invoice::firstOrFail();
        $this->assertSame($resident->id, $invoice->user_id);
        $this->assertSame('125000.00', $invoice->total_amount);
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);

        $this->actingAs($resident)->post(route('warga.invoices.payment', $invoice), [
            'proof' => UploadedFile::fake()->image('bukti.png', 900, 1200),
        ])->assertRedirect(route('warga.invoices.show', $invoice));
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Pending, $invoice->status);
        Storage::disk('local')->assertExists($invoice->payment_proof_path);

        $this->actingAs($manager)->post(route('pengurus.invoices.reject', $invoice), [
            'rejection_reason' => 'Nominal pada bukti tidak terbaca.',
        ])->assertRedirect();
        $this->assertSame(InvoiceStatus::Rejected, $invoice->refresh()->status);

        $this->actingAs($resident)->post(route('warga.invoices.payment', $invoice), [
            'proof' => UploadedFile::fake()->image('bukti-baru.jpg', 900, 1200),
        ])->assertRedirect();
        $this->assertSame(InvoiceStatus::Pending, $invoice->refresh()->status);
        $this->assertNull($invoice->rejection_reason);

        $this->actingAs($manager)->post(route('pengurus.invoices.verify', $invoice))->assertRedirect();
        $this->assertSame(InvoiceStatus::Verified, $invoice->refresh()->status);
        $this->assertDatabaseHas('cashflows', [
            'invoice_id' => $invoice->id, 'source' => 'invoice', 'type' => 'income',
        ]);

        $this->actingAs($manager)->post(route('pengurus.invoices.verify', $invoice))->assertSessionHasErrors('invoice');
        $this->assertSame(1, Cashflow::where('invoice_id', $invoice->id)->count());

        $autoCashflow = Cashflow::where('invoice_id', $invoice->id)->firstOrFail();
        $this->actingAs($manager)->put(route('pengurus.cashflows.update', $autoCashflow), [
            'name' => 'Diubah', 'type' => 'expense', 'total_cashflow' => 1,
        ])->assertForbidden();
    }

    public function test_duplicate_period_and_invalid_proof_are_rejected(): void
    {
        Storage::fake('local');
        $housing = Housing::create(['name' => 'Griya Asri', 'address' => 'Jl. Hijau']);
        $manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $resident = User::factory()->create(['housing_id' => $housing->id]);
        $payload = [
            'account_number' => '123', 'billing_period' => '2026-07', 'due_date' => '2026-08-10',
            'components' => [['name' => 'IPL', 'amount' => 100000]],
        ];

        $this->actingAs($manager)->post(route('pengurus.invoices.store'), $payload)->assertRedirect();
        $this->actingAs($manager)->post(route('pengurus.invoices.store'), $payload)
            ->assertSessionHasErrors('billing_period');

        $invoice = Invoice::firstOrFail();
        $this->actingAs($resident)->post(route('warga.invoices.payment', $invoice), [
            'proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('proof');
    }
}
