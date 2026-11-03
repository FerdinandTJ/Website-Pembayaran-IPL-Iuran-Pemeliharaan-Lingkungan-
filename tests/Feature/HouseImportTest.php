<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\HouseImport;
use App\Models\Housing;
use App\Models\ResidentImport;
use App\Models\User;
use App\Services\HouseCsvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HouseImportTest extends TestCase
{
    use RefreshDatabase;

    private Housing $housing;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->housing = Housing::create(['name' => 'Impor Rumah A', 'address' => 'A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $this->housing->id]);
    }

    private function upload(array $rows, string $delimiter = ',', ?array $header = null)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $header ?? HouseCsvService::COLUMNS, $delimiter, '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, $row, $delimiter, '"', '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $this->actingAs($this->manager)->post(route('pengurus.house-imports.preview'), ['csv' => UploadedFile::fake()->createWithContent('rumah.csv', $csv)]);
    }

    public function test_house_import_preview_confirmation_defaults_and_manual_add_remain_available(): void
    {
        $rows = [
            ['a-01', 'Jalan A 1', 'Dihuni', 'Pemilik Satu', 'Penghuni Satu', '', '', ''],
            ['a-02', 'Jalan A 2', 'Dikontrakkan', 'Pemilik Dua', 'Penyewa Dua', 'Tarif penuh', '', ''],
            ['a-03', 'Jalan A 3', 'Kosong', 'Pemilik Tiga', '', 'Nominal khusus', '25000', 'Belum ditempati'],
            ['a-04', 'Jalan A 4', 'vacant', '', '', 'exempt', '', ''],
            ['a-05', 'Jalan A 5', 'Renovasi', '', '', '', '', ''],
        ];
        $this->upload($rows, ';')->assertOk()->assertSee('5 rumah valid')->assertSee('Dikontrakkan')->assertSee('Rp 25.000');
        $batch = HouseImport::firstOrFail();
        $this->assertDatabaseCount('houses', 0);
        $this->assertStringNotContainsString('Pemilik Satu', DB::table('house_imports')->value('rows'));
        $this->post(route('pengurus.house-imports.confirm', $batch->id))->assertRedirect(route('pengurus.houses.index'));
        $this->assertDatabaseCount('houses', 5);
        $this->assertDatabaseCount('house_histories', 5);
        $this->assertSame(1, User::count());
        $this->assertSame(0, House::whereNotNull('responsible_user_id')->count());
        $this->assertDatabaseHas('houses', ['code' => 'A-02', 'occupancy' => 'rented', 'housing_id' => $this->housing->id]);
        $this->assertDatabaseHas('houses', ['code' => 'A-03', 'vacant_amount' => 25000, 'vacant_billing' => 'custom']);
        $this->assertSame([], $batch->refresh()->rows);
        $this->post(route('pengurus.house-imports.confirm', $batch->id))->assertRedirect();
        $this->assertDatabaseCount('houses', 5);
        $this->get(route('pengurus.houses.index'))->assertOk()->assertSee('Impor CSV Rumah')->assertSee('Tambah Rumah');
        $this->get(route('pengurus.houses.create'))->assertOk();
        $this->post(route('pengurus.houses.store'), ['code' => 'A-06', 'address' => 'Jalan A 6', 'occupancy' => 'occupied'])->assertRedirect();
        $this->assertDatabaseCount('houses', 6);
    }

    public function test_minimal_columns_are_supported_and_imported_houses_can_receive_imported_residents(): void
    {
        $this->upload([['A-01', 'Jalan A 1', 'Dihuni']], ',', ['code', 'address', 'occupancy'])->assertOk();
        $this->post(route('pengurus.house-imports.confirm', HouseImport::first()->id))->assertRedirect();
        $csv = "name,username,email,phone_number,house_code,password,is_responsible\nWarga Baru,wargabaru,warga@example.com,08123456,A-01,Rahasia123,1\n";
        $this->post(route('pengurus.imports.preview'), ['csv' => UploadedFile::fake()->createWithContent('warga.csv', $csv)])->assertOk();
        $batch = ResidentImport::firstOrFail();
        $this->post(route('pengurus.imports.confirm', $batch->id))->assertRedirect();
        $resident = User::where('username', 'wargabaru')->firstOrFail();
        $house = House::where('code', 'A-01')->firstOrFail();
        $this->assertSame($house->id, $resident->house_id);
        $this->assertSame($resident->id, $house->responsible_user_id);
    }

    public function test_duplicate_codes_and_invalid_tariffs_hold_entire_batch(): void
    {
        $this->upload([
            ['A-01', 'One', 'Dihuni', '', '', '', '', ''],
            ['a-01', 'Two', 'Dikontrakkan', '', '', '', '', ''],
            ['A-03', 'Three', 'Dihuni', '', '', 'custom', '25000', ''],
            ['A-04', 'Four', 'Kosong', '', '', 'custom', '0', ''],
            ['A-05', 'Five', 'Kosong', '', '', 'full', '25000', ''],
        ])->assertOk()->assertSee('Seluruh impor ditahan')->assertSee('Kode rumah berulang');
        $this->assertDatabaseCount('house_imports', 0);
        $this->assertDatabaseCount('houses', 0);
    }

    public function test_existing_code_is_updated_but_same_code_in_other_housing_is_created(): void
    {
        $existing = House::create(['housing_id' => $this->housing->id, 'code' => 'A-01', 'address' => 'Alamat asli']);
        $response = $this->upload([['A-01', 'Alamat pengganti', 'Dihuni']], ',', ['code', 'address', 'occupancy'])->assertOk()->assertSee('Perbarui');
        $this->assertSame('Alamat asli', $existing->refresh()->address);
        $this->post(route('pengurus.house-imports.confirm', $response->viewData('batch')->id))->assertRedirect();
        $this->assertSame('Alamat pengganti', $existing->refresh()->address);
        $other = Housing::create(['name' => 'Other', 'address' => 'B']);
        House::create(['housing_id' => $other->id, 'code' => 'A-02', 'address' => 'B']);
        $response = $this->upload([['A-02', 'A', 'Kosong']], ',', ['code', 'address', 'occupancy'])->assertOk();
        $this->post(route('pengurus.house-imports.confirm', $response->viewData('batch')->id))->assertRedirect();
        $this->assertSame(2, House::where('code', 'A-02')->count());
    }

    public function test_confirmation_rechecks_uniqueness_and_rolls_back_the_entire_batch(): void
    {
        $this->upload([['A-01', 'One', 'Dihuni'], ['A-02', 'Two', 'Kosong']], ',', ['code', 'address', 'occupancy'])->assertOk();
        House::create(['housing_id' => $this->housing->id, 'code' => 'A-02', 'address' => 'Dibuat sementara']);
        $this->post(route('pengurus.house-imports.confirm', HouseImport::first()->id))->assertSessionHasErrors('csv');
        $this->assertDatabaseMissing('houses', ['code' => 'A-01']);
        $this->assertDatabaseCount('house_histories', 0);
        $this->assertNull(HouseImport::first()->completed_at);
    }

    public function test_batch_is_bound_to_manager_housing_and_expiry(): void
    {
        $this->upload([['A-01', 'One', 'Dihuni']], ',', ['code', 'address', 'occupancy'])->assertOk();
        $batch = HouseImport::firstOrFail();
        $colleague = User::factory()->manager()->create(['housing_id' => $this->housing->id]);
        $this->actingAs($colleague)->post(route('pengurus.house-imports.confirm', $batch->id))->assertNotFound();
        $other = Housing::create(['name' => 'Other', 'address' => 'B']);
        $foreign = User::factory()->manager()->create(['housing_id' => $other->id]);
        $this->actingAs($foreign)->post(route('pengurus.house-imports.confirm', $batch->id))->assertNotFound();
        $batch->update(['expires_at' => now()->subMinute()]);
        $this->actingAs($this->manager)->post(route('pengurus.house-imports.confirm', $batch->id))->assertSessionHasErrors('csv');
        $this->assertDatabaseCount('houses', 0);
    }

    public function test_upload_validation_and_warga_cannot_import_houses(): void
    {
        $this->upload([['A-01', 'One', 'Dihuni', 999]], ',', ['code', 'address', 'occupancy', 'housing_id'])->assertSessionHasErrors('csv');
        $this->upload([], ',', ['code', 'address', 'occupancy'])->assertSessionHasErrors('csv');
        $this->post(route('pengurus.house-imports.preview'), ['csv' => UploadedFile::fake()->create('rumah.csv', 2049, 'text/csv')])->assertSessionHasErrors('csv');
        $this->post(route('pengurus.house-imports.preview'), ['csv' => UploadedFile::fake()->createWithContent('empty.csv', "\n")])->assertSessionHasErrors('csv');
        $resident = User::factory()->create(['housing_id' => $this->housing->id]);
        $this->actingAs($resident)->get(route('pengurus.house-imports.index'))->assertForbidden();
        $this->get(route('pengurus.house-imports.template'))->assertForbidden();
        $this->post(route('pengurus.house-imports.preview'))->assertForbidden();
        $this->post(route('pengurus.house-imports.confirm', 'unused'))->assertForbidden();
    }

    public function test_template_and_preview_escape_text_and_limit_file_rows(): void
    {
        $this->actingAs($this->manager)->get(route('pengurus.house-imports.index'))->assertOk();
        $response = $this->get(route('pengurus.house-imports.template'))->assertOk()->assertDownload('template-rumah.csv');
        $this->assertStringContainsString('code,address,occupancy', $response->streamedContent());
        $this->upload([['A-01', '<script>alert(1)</script>', 'Dihuni']], ',', ['code', 'address', 'occupancy'])->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false);
        $rows = [];
        for ($i = 0; $i <= HouseCsvService::MAX_ROWS; $i++) {
            $rows[] = ['LIMIT-'.$i, 'Alamat', 'Dihuni'];
        }
        $this->upload($rows, ',', ['code', 'address', 'occupancy'])->assertSessionHasErrors('csv');
    }
}
