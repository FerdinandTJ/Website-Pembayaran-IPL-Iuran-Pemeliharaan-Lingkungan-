<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Cashflow;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\PaymentAccount;
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
        $account = PaymentAccount::create([
            'housing_id' => $housing->id, 'bank_name' => 'BCA',
            'account_number' => '123456789', 'account_holder' => 'Pengurus Griya Asri',
        ]);

        $payload = [
            'title' => 'IPL Bulanan',
            'billing_period' => '2026-07', 'due_date' => '2026-08-10',
            'recipient_mode' => 'all',
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

    public function test_manager_can_select_recipients_and_send_multiple_invoices_in_same_period(): void
    {
        Storage::fake('local');
        $housing = Housing::create(['name' => 'Griya Asri', 'address' => 'Jl. Hijau']);
        $manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $resident = User::factory()->create(['housing_id' => $housing->id]);
        $secondResident = User::factory()->create(['housing_id' => $housing->id]);
        $account = PaymentAccount::create([
            'housing_id' => $housing->id, 'bank_name' => 'BRI',
            'account_number' => '987654321', 'account_holder' => 'Kas Griya Asri',
        ]);
        $payload = [
            'title' => 'Tagihan Khusus',
            'billing_period' => '2026-07', 'due_date' => '2026-08-10',
            'recipient_mode' => 'selected', 'resident_ids' => [$resident->id],
            'components' => [['name' => 'IPL', 'amount' => 100000]],
        ];

        $this->actingAs($manager)->post(route('pengurus.invoices.store'), $payload)
            ->assertRedirect(route('pengurus.invoices.index'))
            ->assertSessionHas('success', 'Tagihan berhasil dikirim kepada 1 warga.');

        $this->actingAs($manager)->post(route('pengurus.invoices.store'), [
            ...$payload,
            'title' => 'Tagihan Tambahan',
        ])->assertRedirect(route('pengurus.invoices.index'));

        $this->assertDatabaseCount('invoices', 2);
        $this->assertSame(2, Invoice::where('user_id', $resident->id)->whereDate('billing_period', '2026-07-01')->count());
        $this->assertSame(0, Invoice::where('user_id', $secondResident->id)->count());

        $this->actingAs($manager)->post(route('pengurus.invoices.store'), [
            ...$payload,
            'title' => 'Tagihan untuk Semua',
            'recipient_mode' => 'all',
            'resident_ids' => [],
        ])->assertSessionHas('success', 'Tagihan berhasil dikirim kepada 2 warga.');
        $this->assertDatabaseCount('invoices', 4);

        $invoice = Invoice::where('user_id', $resident->id)->firstOrFail();
        $this->actingAs($resident)->post(route('warga.invoices.payment', $invoice), [
            'proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('proof');
    }
}
