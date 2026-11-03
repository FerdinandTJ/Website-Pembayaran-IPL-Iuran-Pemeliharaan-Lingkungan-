<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\HouseHistory;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\ResidentImport;
use App\Models\User;
use App\Services\HouseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HousesAndResidentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Housing $housing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->housing = Housing::create(['name' => 'Rumah A', 'address' => 'A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $this->housing->id]);
    }

    private function house(string $code = 'A-1', array $attributes = []): House
    {
        return House::create($attributes + ['housing_id' => $this->housing->id, 'code' => $code, 'address' => 'Jl. '.$code, 'occupancy' => 'occupied', 'vacant_billing' => 'full']);
    }

    private function upload(array $rows, string $separator = ',')
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, \App\Services\ResidentCsvService::COLUMNS, $separator, '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, $row, $separator, '"', '');
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $this->actingAs($this->manager)->post(route('pengurus.imports.preview'), ['csv' => UploadedFile::fake()->createWithContent('warga.csv', $content)]);
    }

    public function test_house_crud_history_and_single_current_house_preserve_old_invoices(): void
    {
        $resident = User::factory()->create(['housing_id' => $this->housing->id]);
        $payload = ['code' => 'a-1', 'address' => 'Jl. Asri 1', 'occupancy' => 'vacant', 'vacant_billing' => 'custom', 'vacant_amount' => 25000, 'responsible_user_id' => $resident->id, 'owner_name' => 'Pemilik'];
        $this->actingAs($this->manager)->post(route('pengurus.houses.store'), $payload)->assertRedirect(route('pengurus.houses.index'));
        $house = House::firstOrFail();
        $this->assertSame('A-1', $house->code);
        $this->assertSame($house->id, $resident->refresh()->house_id);
        $this->assertTrue($resident->is_active);
        $invoice = Invoice::create(['housing_id' => $this->housing->id, 'house_id' => $house->id, 'house_label' => $house->label, 'user_id' => $resident->id, 'created_by' => $this->manager->id, 'title' => 'Lama', 'billing_period' => today()->startOfMonth(), 'due_date' => today(), 'total_amount' => 10000, 'status' => 'unpaid']);
        $new = $this->house('B-2');
        DB::transaction(fn () => app(HouseService::class)->link($resident, $new->id, true, $this->manager));
        $this->assertSame($new->id, $resident->refresh()->house_id);
        $this->assertNull($house->refresh()->responsible_user_id);
        $this->assertSame($house->id, $invoice->refresh()->house_id);
        $this->assertSame('A-1 · Jl. Asri 1', $invoice->house_label);
        $this->assertGreaterThanOrEqual(3, HouseHistory::count());
        $this->get(route('pengurus.houses.index'))->assertOk();
        $this->get(route('pengurus.houses.edit', $house))->assertOk()->assertSee('Histori Perubahan');
        $this->get(route('pengurus.residents.edit', $resident))->assertOk();
        $this->put(route('pengurus.houses.update', $house), $payload)->assertSessionHasErrors('responsible_user_id');
    }

    public function test_house_tariffs_exemption_and_only_designated_payer_gets_new_invoices(): void
    {
        foreach (['full', 'custom', 'exempt'] as $mode) {
            $user = User::factory()->create(['housing_id' => $this->housing->id]);
            $house = $this->house($mode, ['occupancy' => 'vacant', 'vacant_billing' => $mode, 'vacant_amount' => $mode === 'custom' ? 25000 : null, 'responsible_user_id' => $user->id]);
            $user->update(['house_id' => $house->id]);
        }
        $occupant = User::factory()->create(['housing_id' => $this->housing->id, 'house_id' => House::first()->id]);
        $payload = ['title' => 'IPL', 'billing_period' => today()->format('Y-m'), 'due_date' => today()->addMonth()->toDateString(), 'recipient_mode' => 'all', 'components' => [['name' => 'Keamanan', 'amount' => 100000]]];
        $this->actingAs($this->manager)->post(route('pengurus.invoices.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('invoices', 2);
        $this->assertEqualsCanonicalizing([25000, 100000], Invoice::pluck('total_amount')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(0, $occupant->invoices()->count());
        $this->post(route('pengurus.invoices.store'), $payload + ['apply_house_tariff' => 0])->assertRedirect();
        $this->assertDatabaseCount('invoices', 5);
        $this->get(route('pengurus.dashboard'))->assertOk()->assertViewHas('houseCounts', fn ($c) => $c['vacant'] === 3);
    }

    public function test_rented_status_is_distinct_but_uses_normal_tariff_and_filters(): void
    {
        foreach (['occupied', 'rented', 'renovation'] as $status) {
            $user = User::factory()->create(['housing_id' => $this->housing->id]);
            $house = $this->house($status, ['occupancy' => $status, 'vacant_billing' => 'custom', 'vacant_amount' => 25000, 'responsible_user_id' => $user->id]);
            $user->update(['house_id' => $house->id]);
        }
        $payload = ['title' => 'IPL Normal', 'billing_period' => today()->format('Y-m'), 'due_date' => today()->addMonth()->toDateString(), 'recipient_mode' => 'all', 'components' => [['name' => 'IPL', 'amount' => 100000]]];
        $this->actingAs($this->manager)->post(route('pengurus.invoices.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('invoices', 3);
        $this->assertSame(3, Invoice::where('total_amount', 100000)->count());
        $this->get(route('pengurus.houses.index', ['occupancy' => 'rented']))->assertOk()
            ->assertViewHas('houses', fn ($houses) => $houses->total() === 1 && $houses->first()->occupancy === 'rented')
            ->assertSee('Dikontrakkan')->assertSee('Komponen normal');
        $this->get(route('pengurus.dashboard'))->assertOk()
            ->assertViewHas('houseCounts', fn ($counts) => $counts['rented'] === 1)->assertSee('Rumah Dikontrakkan');
    }

    public function test_custom_amount_validation_and_transition_keeps_inactive_settings(): void
    {
        $payload = ['code' => 'KH-1', 'address' => 'Jl. Khusus', 'occupancy' => 'vacant', 'vacant_billing' => 'custom'];
        foreach ([null, 0, -1, 2.5] as $amount) {
            $this->actingAs($this->manager)->post(route('pengurus.houses.store'), $payload + ['vacant_amount' => $amount])
                ->assertSessionHasErrors('vacant_amount');
        }
        $this->assertDatabaseCount('houses', 0);
        $this->post(route('pengurus.houses.store'), $payload + ['vacant_amount' => 25000])->assertRedirect();
        $house = House::firstOrFail();
        $this->get(route('pengurus.houses.edit', $house))->assertOk()
            ->assertSee('nominal khusus menggantikan seluruh komponen normal')
            ->assertSee('bukan potongan Rp 25.000');
        $rented = array_replace($payload, ['occupancy' => 'rented', 'vacant_amount' => 12345]);
        $this->put(route('pengurus.houses.update', $house), $rented)->assertRedirect();
        $this->assertSame('rented', $house->refresh()->occupancy);
        $this->assertSame('25000.00', $house->vacant_amount);
        $this->assertSame('custom', $house->vacant_billing);
        $this->get(route('pengurus.houses.edit', $house))->assertOk()
            ->assertSee('data-vacant-billing disabled', false)
            ->assertSee('data-vacant-amount disabled', false)
            ->assertSee('Tidak aktif pada kondisi ini');
        $this->put(route('pengurus.houses.update', $house), $payload + ['vacant_amount' => 25000])->assertRedirect();
        $this->assertSame('vacant', $house->refresh()->occupancy);
        $this->assertSame('25000.00', $house->vacant_amount);
        $this->get(route('pengurus.houses.edit', $house))->assertOk()->assertSee('Aktif untuk tagihan IPL baru');
        $this->post(route('pengurus.houses.store'), array_replace($payload, ['code' => 'RENT-NEW', 'occupancy' => 'rented', 'vacant_amount' => 12345]))->assertRedirect();
        $this->assertDatabaseHas('houses', ['code' => 'RENT-NEW', 'occupancy' => 'rented', 'vacant_billing' => 'full', 'vacant_amount' => null]);
    }

    public function test_valid_csv_preview_masks_password_then_imports_once_and_hashes_credentials(): void
    {
        $house = $this->house();
        $response = $this->upload([['Warga Baru', 'csvwarga', 'csv@example.com', '081234567890', 'A-1', 'Rahasia-123', '1']], ';');
        $response->assertOk()->assertSee('csvwarga')->assertDontSee('Rahasia-123');
        $batch = ResidentImport::firstOrFail();
        $this->assertSame(1, User::count());
        $stored = DB::table('resident_imports')->value('rows');
        $this->assertStringNotContainsString('Rahasia-123', $stored);
        $this->assertStringNotContainsString('csv@example.com', $stored);
        $this->post(route('pengurus.imports.confirm', $batch->id))->assertRedirect(route('pengurus.residents.index'));
        $user = User::where('username', 'csvwarga')->firstOrFail();
        $this->assertTrue(Hash::check('Rahasia-123', $user->password));
        $this->assertSame('081234567890', $user->phone_number);
        $this->assertSame($house->id, $user->house_id);
        $this->assertSame($user->id, $house->refresh()->responsible_user_id);
        $this->assertSame([], $batch->refresh()->rows);
        $this->post(route('pengurus.imports.confirm', $batch->id))->assertRedirect();
        $this->assertSame(2, User::count());
    }

    public function test_csv_rejects_duplicates_missing_house_short_password_and_expired_batch(): void
    {
        $this->house();
        $rows = [['Satu', 'duplicate', 'one@example.com', '0812345', 'A-1', 'Rahasia-123', '1'], ['Dua', 'duplicate', 'two@example.com', '0812346', 'MISSING', 'short', '1']];
        $this->upload($rows)->assertOk()->assertSee('Seluruh impor ditahan')->assertDontSee('Rahasia-123');
        $this->assertDatabaseCount('resident_imports', 0);
        $this->assertSame(1, User::count());
        $this->upload([$rows[0]])->assertOk();
        $batch = ResidentImport::firstOrFail();
        $batch->update(['expires_at' => now()->subMinute()]);
        $this->post(route('pengurus.imports.confirm', $batch->id))->assertSessionHasErrors('csv');
        $this->assertSame(1, User::count());
    }

    public function test_import_rechecks_changed_data_and_rolls_back_all_rows(): void
    {
        $this->house();
        $this->house('A-2');
        $this->upload([['Satu', 'csvone', 'one@example.com', '0811111', 'A-1', 'Rahasia-123', '1'], ['Dua', 'csvtwo', 'two@example.com', '0822222', 'A-2', 'Rahasia-123', '1']])->assertOk();
        User::factory()->create(['housing_id' => $this->housing->id, 'username' => 'csvtwo']);
        $this->post(route('pengurus.imports.confirm', ResidentImport::first()->id))->assertSessionHasErrors('csv');
        $this->assertDatabaseMissing('users', ['username' => 'csvone']);
        $this->assertNull(House::where('code', 'A-1')->first()->responsible_user_id);
    }

    public function test_house_and_import_access_are_scoped_to_housing_and_role(): void
    {
        $house = $this->house();
        $this->upload([['Satu', 'csvone', 'one@example.com', '0811111', 'A-1', 'Rahasia-123', '1']]);
        $batch = ResidentImport::firstOrFail();
        $housing = Housing::create(['name' => 'B', 'address' => 'B']);
        $other = User::factory()->manager()->create(['housing_id' => $housing->id]);
        $this->actingAs($other)->get(route('pengurus.houses.edit', $house))->assertForbidden();
        $this->post(route('pengurus.imports.confirm', $batch->id))->assertNotFound();
        $resident = User::factory()->create(['housing_id' => $this->housing->id]);
        foreach (['pengurus.houses.index', 'pengurus.imports.index', 'pengurus.imports.template'] as $route) {
            $this->actingAs($resident)->get(route($route))->assertForbidden();
        }
    }
}
