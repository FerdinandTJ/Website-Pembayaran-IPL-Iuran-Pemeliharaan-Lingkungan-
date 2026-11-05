<?php

namespace Tests\Feature;

use App\Models\Cashflow;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\PaymentBatch;
use App\Models\Receipt;
use App\Models\User;
use App\Services\BatchPaymentNotifier;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BatchPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $housing = Housing::create(['name' => 'Perumahan Gabungan', 'address' => 'Jalan IPL']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->resident = User::factory()->create(['housing_id' => $housing->id]);
    }

    private function invoice(string $amount = '150000', string $status = 'unpaid', ?User $resident = null): Invoice
    {
        $resident ??= $this->resident;
        $invoice = Invoice::create([
            'housing_id' => $resident->housing_id, 'user_id' => $resident->id, 'created_by' => $this->manager->id,
            'house_label' => 'A-01 · Rumah awal', 'title' => 'IPL tagihan '.(Invoice::count() + 1),
            'billing_period' => now()->subMonths(Invoice::count())->startOfMonth(), 'due_date' => now()->subDay(),
            'total_amount' => $amount, 'status' => $status,
        ]);
        $invoice->components()->create(['name' => 'Iuran lingkungan', 'amount' => $amount]);

        return $invoice;
    }

    private function transfer(array $ids, string $total = '300000.00')
    {
        return $this->actingAs($this->resident)->post(route('warga.payment-batches.store'), [
            'invoice_ids' => $ids, 'expected_total' => $total, 'proof' => UploadedFile::fake()->image('gabungan.png'),
        ]);
    }

    public function test_one_selected_transfer_uses_individual_payment_verification_cashflow_and_receipt(): void
    {
        $invoice = $this->invoice();
        $this->actingAs($this->resident)->get(route('warga.payment-batches.create', ['invoice_ids' => [$invoice->id]]))
            ->assertOk()->assertSee('Tinjau Pembayaran Tagihan')->assertDontSee('Tinjau Pembayaran Gabungan');
        $this->transfer([$invoice->id], '150000.00')->assertRedirect(route('warga.invoices.show', $invoice));
        $this->assertSame('pending', $invoice->fresh()->status->value);
        $this->assertNull($invoice->fresh()->payment_batch_id);
        $this->assertDatabaseCount('payment_batches', 0);
        $this->assertDatabaseCount('payment_batch_invoice', 0);
        $this->assertSame('submitted', $this->manager->notifications()->first()->data['event']);
        $this->actingAs($this->manager)->get(route('pengurus.verifications.index'))
            ->assertOk()->assertSee($invoice->title)->assertDontSee('Pembayaran gabungan #');
        $this->post(route('pengurus.invoices.verify', $invoice))->assertRedirect();
        $this->assertSame('verified', $invoice->fresh()->status->value);
        $this->assertSame($invoice->id, Cashflow::first()->invoice_id);
        $this->assertNull(Cashflow::first()->payment_batch_id);
        $this->assertSame($invoice->id, Receipt::first()->invoice_id);
        $this->assertNull(Receipt::first()->payment_batch_id);
        $this->assertSame('KW-'.$invoice->housing_id.'-'.$invoice->id, Receipt::first()->number);
        $this->assertSame('verified', $this->resident->notifications()->first()->data['event']);
        $this->assertDatabaseCount('cashflows', 1);
        $this->assertDatabaseCount('receipts', 1);
        $this->actingAs($this->resident)->get(route('receipts.show', $invoice))->assertOk()->assertDontSee('Pembayaran gabungan');
    }

    public function test_one_selected_cash_uses_individual_payment_and_still_revalidates_total_and_status(): void
    {
        $invoice = $this->invoice();
        $this->actingAs($this->manager)->get(route('pengurus.cash-batches.create', ['invoice_ids' => [$invoice->id]]))
            ->assertOk()->assertSee('Konfirmasi Pembayaran Cash')->assertDontSee('Konfirmasi Cash Gabungan');
        $data = ['invoice_ids' => [$invoice->id], 'expected_total' => '150000.00', 'received' => 1];
        $this->post(route('pengurus.cash-batches.store'), [...$data, 'expected_total' => '1'])->assertSessionHasErrors('invoice_ids');
        $this->assertDatabaseCount('cashflows', 0);
        $this->post(route('pengurus.cash-batches.store'), $data)->assertRedirect(route('receipts.show', $invoice));
        $this->assertSame('verified', $invoice->fresh()->status->value);
        $this->assertNull($invoice->fresh()->payment_batch_id);
        $this->assertDatabaseCount('payment_batches', 0);
        $this->assertSame($invoice->id, Cashflow::first()->invoice_id);
        $this->assertSame($invoice->id, Receipt::first()->invoice_id);
        $this->assertSame('KW-'.$invoice->housing_id.'-'.$invoice->id, Receipt::first()->number);
        $this->assertSame('cash_paid', $this->resident->notifications()->first()->data['event']);
        $this->post(route('pengurus.cash-batches.store'), $data)->assertSessionHasErrors('invoice_ids');
        $this->assertDatabaseCount('cashflows', 1);
        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_one_selected_retry_detaches_from_rejected_batch_without_creating_a_new_batch(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $this->transfer([$a->id, $b->id])->assertRedirect();
        $batch = PaymentBatch::firstOrFail();
        $this->actingAs($this->manager)->post(route('pengurus.payment-batches.reject', $batch), ['rejection_reason' => 'Bukti kurang jelas'])->assertRedirect();
        $this->transfer([$a->id], '150000.00')->assertRedirect(route('warga.invoices.show', $a));
        $this->assertNull($a->fresh()->payment_batch_id);
        $this->assertSame('pending', $a->fresh()->status->value);
        $this->assertSame('rejected', $b->fresh()->status->value);
        $this->assertSame('rejected', $batch->fresh()->status->value);
        $this->assertDatabaseCount('payment_batches', 1);
        $this->assertCount(2, $batch->invoices);
        Storage::disk('local')->assertExists($batch->proof_path);
        $this->assertDatabaseCount('cashflows', 0);
    }

    public function test_transfer_batch_verifies_atomically_with_one_cashflow_receipt_and_notification_per_event(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $untouched = $this->invoice();
        $ids = [$a->id, $b->id];
        $this->actingAs($this->resident)->get(route('warga.payment-batches.select'))->assertOk()->assertSee('Bayar Tagihan');
        $this->get(route('warga.payment-batches.create', ['invoice_ids' => $ids]))->assertOk()->assertSee('Rp 300.000');
        $this->transfer($ids)->assertRedirect(route('payment-batches.show', PaymentBatch::first()));
        $batch = PaymentBatch::firstOrFail();
        $this->assertSame('pending', $a->fresh()->status->value);
        $this->assertSame('pending', $b->fresh()->status->value);
        $this->assertSame('unpaid', $untouched->fresh()->status->value);
        $this->assertSame($a->fresh()->payment_proof_path, $b->fresh()->payment_proof_path);
        $this->assertDatabaseCount('cashflows', 0);
        $this->assertDatabaseCount('receipts', 0);
        $this->assertSame(1, $this->manager->notifications()->count());
        $this->get(route('payment-batches.show', $batch))->assertOk()->assertSee('Rp 300.000');
        $this->get(route('payment-batches.proof', $batch))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->manager)->get(route('pengurus.verifications.index'))->assertOk()
            ->assertSee('Pembayaran gabungan #'.$batch->id)->assertSee('Rp 300.000')->assertSee('Periksa Seluruh Pembayaran')
            ->assertViewHas('invoices', fn ($rows) => $rows->count() === 1);
        $this->get(route('pengurus.dashboard'))->assertOk()->assertViewHas('pendingInvoices', fn ($rows) => $rows->count() === 1);
        $this->get(route('pengurus.verifications.index', ['invoice_id' => $b->id]))->assertRedirect(route('payment-batches.show', $batch));
        $this->post(route('pengurus.invoices.verify', $b))->assertRedirect(route('payment-batches.show', $batch));
        $this->assertSame('verified', $a->fresh()->status->value);
        $this->assertSame('verified', $b->fresh()->status->value);
        $this->assertDatabaseCount('cashflows', 1);
        $this->assertDatabaseCount('receipts', 1);
        $this->assertSame('300000.00', Cashflow::first()->total_cashflow);
        $this->assertNull(Cashflow::first()->invoice_id);
        $this->assertSame($batch->id, Cashflow::first()->payment_batch_id);
        $this->assertSame(1, $this->resident->notifications()->count());
        $receipt = Receipt::first();
        $this->assertCount(2, $receipt->snapshot['invoices']);
        $a->components()->first()->update(['name' => 'Komponen setelah lunas']);
        $this->resident->update(['name' => 'Nama setelah lunas']);
        foreach ([$a, $b] as $invoice) {
            $this->actingAs($this->resident)->get(route('receipts.show', $invoice))->assertOk()
                ->assertSee($receipt->number)->assertSee($a->title)->assertSee($b->title)->assertDontSee('Komponen setelah lunas');
        }
        $pdf = $this->get(route('receipts.pdf', $b))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->actingAs($this->manager)->post(route('pengurus.payment-batches.verify', $batch))->assertSessionHasErrors('invoice_ids');
        $this->post(route('pengurus.cash-payments.store', $a), ['received' => 1])->assertSessionHasErrors('invoice');
        $this->assertDatabaseCount('cashflows', 1);
        $this->assertDatabaseCount('receipts', 1);
        $notification = $this->resident->notifications()->first();
        $this->actingAs($this->resident)->patchJson(route('notifications.read', $notification->id))
            ->assertJsonPath('url', route('payment-batches.show', $batch));
    }

    public function test_cash_combines_selected_invoices_and_requires_exact_total_and_received_confirmation(): void
    {
        $a = $this->invoice('125000.25');
        $b = $this->invoice('150000.75', 'rejected');
        $ids = [$a->id, $b->id];
        $this->actingAs($this->manager)->get(route('pengurus.cash-batches.create', ['invoice_ids' => $ids]))->assertOk()->assertSee('Rp 275.001');
        $data = ['invoice_ids' => $ids, 'expected_total' => '275001.00'];
        $this->post(route('pengurus.cash-batches.store'), $data)->assertSessionHasErrors('received');
        $this->post(route('pengurus.cash-batches.store'), [...$data, 'received' => 1, 'expected_total' => '1'])->assertSessionHasErrors('invoice_ids');
        $this->assertDatabaseCount('payment_batches', 0);
        $this->post(route('pengurus.cash-batches.store'), [...$data, 'received' => 1, 'payment_note' => 'Diterima di pos'])->assertRedirect();
        $batch = PaymentBatch::firstOrFail();
        $this->assertSame('cash', $batch->method);
        $this->assertSame('verified', $batch->status->value);
        $this->assertSame('verified', $a->fresh()->status->value);
        $this->assertSame('verified', $b->fresh()->status->value);
        $this->assertSame('275001.00', Cashflow::first()->total_cashflow);
        $this->assertSame('Diterima di pos', Receipt::first()->snapshot['note']);
        $this->post(route('pengurus.cash-batches.store'), [...$data, 'received' => 1])->assertSessionHasErrors('invoice_ids');
        $this->assertDatabaseCount('cashflows', 1);
    }

    public function test_rejection_is_for_all_members_and_old_proof_survives_individual_retries(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $this->transfer([$a->id, $b->id])->assertRedirect();
        $batch = PaymentBatch::firstOrFail();
        $path = $batch->proof_path;
        $this->actingAs($this->manager)->post(route('pengurus.invoices.reject', $b), ['rejection_reason' => 'Nominal transfer belum sesuai'])->assertRedirect();
        $this->assertSame('rejected', $a->fresh()->status->value);
        $this->assertSame('rejected', $b->fresh()->status->value);
        $this->assertSame('rejected', $batch->fresh()->status->value);
        $this->assertDatabaseCount('cashflows', 0);
        foreach ([$a, $b] as $invoice) {
            $this->actingAs($this->resident)->post(route('warga.invoices.payment', $invoice), ['proof' => UploadedFile::fake()->image('ulang.png')])->assertRedirect();
            $this->assertNull($invoice->fresh()->payment_batch_id);
        }
        Storage::disk('local')->assertExists($path);
        $this->get(route('payment-batches.proof', $batch))->assertOk();
        $this->actingAs($this->manager)->post(route('pengurus.payment-batches.verify', $batch))->assertSessionHasErrors('invoice_ids');
        $this->assertDatabaseCount('cashflows', 0);
    }

    public function test_batches_reject_duplicate_mixed_owners_foreign_housing_pending_and_stale_totals(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $other = User::factory()->create(['housing_id' => $this->resident->housing_id]);
        $foreignHousing = Housing::create(['name' => 'Lain', 'address' => 'Lain']);
        $foreign = User::factory()->create(['housing_id' => $foreignHousing->id]);
        $otherInvoice = $this->invoice(resident: $other);
        $foreignInvoice = $this->invoice(resident: $foreign);
        $pending = $this->invoice(status: 'pending');
        foreach ([[], [$a->id, $a->id], [$a->id, $otherInvoice->id], [$a->id, $foreignInvoice->id], [$a->id, $pending->id], [$a->id, 999999]] as $ids) {
            $this->transfer($ids)->assertSessionHasErrors();
        }
        $this->transfer([$a->id, $b->id], '299999.99')->assertSessionHasErrors('invoice_ids');
        $this->assertDatabaseCount('payment_batches', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('payment-proofs'));
        $this->assertSame('unpaid', $a->fresh()->status->value);
        $this->actingAs($this->manager)->post(route('pengurus.cash-batches.store'), [
            'invoice_ids' => [$a->id, $otherInvoice->id], 'expected_total' => '300000', 'received' => 1,
        ])->assertSessionHasErrors('invoice_ids');
        $this->assertDatabaseCount('cashflows', 0);
    }

    public function test_scope_role_and_proof_access_are_enforced_for_every_batch_route(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $this->transfer([$a->id, $b->id])->assertRedirect();
        $batch = PaymentBatch::firstOrFail();
        $other = User::factory()->create(['housing_id' => $this->resident->housing_id]);
        $housing = Housing::create(['name' => 'Housing B', 'address' => 'B']);
        $foreign = User::factory()->manager()->create(['housing_id' => $housing->id]);
        foreach ([$other, $foreign, User::factory()->admin()->create()] as $actor) {
            $this->actingAs($actor)->get(route('payment-batches.show', $batch))->assertForbidden();
            $this->get(route('payment-batches.proof', $batch))->assertForbidden();
            $this->post(route('pengurus.payment-batches.verify', $batch))->assertForbidden();
            $this->post(route('pengurus.payment-batches.reject', $batch), ['rejection_reason' => 'Tidak sah'])->assertForbidden();
        }
        $this->actingAs($this->resident)->post(route('pengurus.cash-batches.store'))->assertForbidden();
        $this->actingAs($this->manager)->post(route('warga.payment-batches.store'))->assertForbidden();
        $this->assertDatabaseCount('cashflows', 0);
    }

    public function test_failures_roll_back_all_members_and_cleanup_new_upload(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $this->transfer([$a->id, $b->id])->assertRedirect();
        $batch = PaymentBatch::firstOrFail();
        $this->mock(BatchPaymentNotifier::class)->shouldReceive('send')->andThrow(new \RuntimeException('Notification failure'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->manager)->post(route('pengurus.payment-batches.verify', $batch));
            $this->fail('Expected rollback');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Notification failure', $exception->getMessage());
        }
        $this->assertSame('pending', $a->fresh()->status->value);
        $this->assertSame('pending', $b->fresh()->status->value);
        $this->assertSame('pending', $batch->fresh()->status->value);
        $this->assertDatabaseCount('cashflows', 0);
        $this->assertDatabaseCount('receipts', 0);
        $c = $this->invoice();
        $d = $this->invoice();
        try {
            $this->transfer([$c->id, $d->id]);
            $this->fail('Expected upload rollback');
        } catch (\RuntimeException) {
            $this->assertSame('unpaid', $c->fresh()->status->value);
            $this->assertSame('unpaid', $d->fresh()->status->value);
        }
        $this->assertDatabaseCount('payment_batches', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles('payment-proofs'));
    }

    public function test_legacy_settlement_cannot_partially_verify_a_batch_and_changed_member_amounts_are_rejected(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $this->transfer([$a->id, $b->id])->assertRedirect();
        try {
            app(PaymentService::class)->settle($a->fresh(), $this->manager, 'transfer');
            $this->fail('Partial verification must be refused');
        } catch (ValidationException) {
            $this->assertSame('pending', $a->fresh()->status->value);
        }
        $batch = PaymentBatch::firstOrFail();
        $a->update(['total_amount' => 100000]);
        $b->update(['total_amount' => 200000]);
        $this->actingAs($this->manager)->post(route('pengurus.payment-batches.verify', $batch))->assertSessionHasErrors('invoice_ids');
        $this->assertDatabaseCount('cashflows', 0);
        $this->assertSame('pending', $batch->fresh()->status->value);
    }

    public function test_amount_overflow_and_invalid_upload_are_refused_without_state_changes(): void
    {
        $a = $this->invoice('9999999999999');
        $b = $this->invoice('9999999999999');
        $this->transfer([$a->id, $b->id], '9999999999999')->assertSessionHasErrors('invoice_ids');
        $small = $this->invoice();
        $this->actingAs($this->resident)->post(route('warga.payment-batches.store'), [
            'invoice_ids' => [$small->id], 'expected_total' => '150000',
            'proof' => UploadedFile::fake()->createWithContent('fake.png', '<?php echo "bad";'),
        ])->assertSessionHasErrors('proof');
        $this->assertDatabaseCount('payment_batches', 0);
        $this->assertDatabaseCount('cashflows', 0);
    }
}
