<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use App\Services\HouseCsvService;
use App\Services\ResidentCsvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportUpsertTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Housing $housing;

    private House $house;

    protected function setUp(): void
    {
        parent::setUp();
        $this->housing = Housing::create(['name' => 'Upsert A', 'address' => 'A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $this->housing->id]);
        $this->house = House::create(['housing_id' => $this->housing->id, 'code' => 'A-01', 'address' => 'Alamat lama', 'occupancy' => 'occupied', 'vacant_billing' => 'full', 'owner_name' => 'Pemilik lama']);
    }

    private function preview(string $type, array $rows, ?array $header = null)
    {
        $header ??= $type === 'house' ? HouseCsvService::COLUMNS : ResidentCsvService::COLUMNS;
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $header, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, $row, ',', '"', '');
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $this->actingAs($this->manager)->post(route($type === 'house' ? 'pengurus.house-imports.preview' : 'pengurus.imports.preview'), ['csv' => UploadedFile::fake()->createWithContent('data.csv', $content)]);
    }

    private function resident(array $values = []): User
    {
        $user = User::factory()->create($values + ['housing_id' => $this->housing->id, 'house_id' => $this->house->id, 'house_address' => $this->house->address]);
        $this->house->update(['responsible_user_id' => $user->id]);

        return $user;
    }

    public function test_house_upsert_preserves_payer_omitted_fields_and_old_invoice_snapshot(): void
    {
        $user = $this->resident();
        $invoice = Invoice::create(['housing_id' => $this->housing->id, 'house_id' => $this->house->id, 'house_label' => $this->house->label, 'user_id' => $user->id, 'created_by' => $this->manager->id, 'title' => 'Histori', 'billing_period' => today()->startOfMonth(), 'due_date' => today(), 'total_amount' => 10000, 'status' => 'unpaid']);
        $response = $this->preview('house', [['A-01', 'Alamat baru', 'Dikontrakkan'], ['A-02', 'Rumah baru', 'Kosong']], ['code', 'address', 'occupancy'])->assertOk()->assertSee('1 rumah baru dan 1 rumah diperbarui');
        $this->post(route('pengurus.house-imports.confirm', $response->viewData('batch')->id))->assertRedirect()->assertSessionHas('success', 'Impor selesai: 1 rumah baru, 1 rumah diperbarui.');
        $this->assertDatabaseCount('houses', 2);
        $this->assertSame($user->id, $this->house->refresh()->responsible_user_id);
        $this->assertSame('Pemilik lama', $this->house->owner_name);
        $this->assertSame('Alamat baru', $user->refresh()->house_address);
        $this->assertSame('A-01 · Alamat lama', $invoice->refresh()->house_label);
        $this->assertSame($user->id, $invoice->user_id);
    }

    public function test_present_blank_optional_house_field_clears_it_and_stale_preview_is_rejected(): void
    {
        $response = $this->preview('house', [['A-01', 'Alamat baru', 'Dihuni', '']], ['code', 'address', 'occupancy', 'owner_name'])->assertOk();
        $this->post(route('pengurus.house-imports.confirm', $response->viewData('batch')->id))->assertRedirect();
        $this->assertNull($this->house->refresh()->owner_name);
        $response = $this->preview('house', [['A-01', 'Tidak boleh menimpa', 'Dihuni']], ['code', 'address', 'occupancy'])->assertOk();
        $this->house->update(['address' => 'Perubahan pengurus lain']);
        $this->post(route('pengurus.house-imports.confirm', $response->viewData('batch')->id))->assertSessionHasErrors('csv');
        $this->assertSame('Perubahan pengurus lain', $this->house->refresh()->address);
    }

    public function test_resident_upsert_updates_existing_creates_new_and_keeps_blank_password_and_inactive_status(): void
    {
        $user = $this->resident(['username' => 'existing', 'email' => 'existing@example.com', 'is_active' => false]);
        $hash = $user->password;
        House::create(['housing_id' => $this->housing->id, 'code' => 'A-02', 'address' => 'Alamat dua']);
        $response = $this->preview('resident', [
            ['Nama diperbarui', 'existing', 'updated@example.com', '08123456', 'A-01', '', '1'],
            ['Warga baru', 'newresident', 'new@example.com', '08123457', 'A-02', 'NewPassword123', '1'],
        ])->assertOk()->assertSee('Perbarui')->assertSee('Tetap')->assertDontSee('NewPassword123');
        $this->post(route('pengurus.imports.confirm', $response->viewData('batch')->id))->assertRedirect()->assertSessionHas('success', 'Impor selesai: 1 akun warga baru, 1 akun warga diperbarui.');
        $this->assertSame('Nama diperbarui', $user->refresh()->name);
        $this->assertSame($hash, $user->password);
        $this->assertFalse($user->is_active);
        $this->assertSame('warga', $user->role);
        $this->assertSame(3, User::count());
        $this->assertTrue(Hash::check('NewPassword123', User::where('username', 'newresident')->first()->password));
    }

    public function test_matching_email_can_update_username_and_nonblank_password_revokes_sessions(): void
    {
        $user = $this->resident(['username' => 'oldname', 'email' => 'same@example.com']);
        DB::table('sessions')->insert(['id' => 'old-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $response = $this->preview('resident', [['Nama baru', 'newname', 'same@example.com', '08123456', 'A-01', 'ChangedPassword123', '1']])->assertOk()->assertSee('Ganti password');
        $this->post(route('pengurus.imports.confirm', $response->viewData('batch')->id))->assertRedirect();
        $this->assertSame('newname', $user->refresh()->username);
        $this->assertTrue(Hash::check('ChangedPassword123', $user->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-device']);
        $this->assertSame(2, User::count());
    }

    public function test_conflicting_identities_and_foreign_accounts_cannot_be_overwritten(): void
    {
        $one = $this->resident(['username' => 'one', 'email' => 'one@example.com']);
        $two = User::factory()->create(['housing_id' => $this->housing->id, 'username' => 'two', 'email' => 'two@example.com']);
        $this->preview('resident', [['Wrong', 'one', 'two@example.com', '08123456', 'A-01', 'Password123', '1']])->assertOk()->assertSee('dua akun berbeda');
        $foreign = Housing::create(['name' => 'Foreign', 'address' => 'B']);
        $user = User::factory()->create(['housing_id' => $foreign->id, 'username' => 'foreign', 'email' => 'foreign@example.com']);
        $this->preview('resident', [['Wrong', 'foreign', 'foreign@example.com', '08123456', 'A-01', 'Password123', '1']])->assertOk()->assertSee('tidak dapat diperbarui');
        $this->assertDatabaseCount('resident_imports', 0);
        $this->assertSame($foreign->id, $user->refresh()->housing_id);
    }

    public function test_new_payer_replaces_previous_payer_without_reassigning_old_debt(): void
    {
        $old = $this->resident(['name' => 'Penghuni Lama']);
        $invoice = Invoice::create(['housing_id' => $this->housing->id, 'house_id' => $this->house->id, 'house_label' => $this->house->label, 'user_id' => $old->id, 'created_by' => $this->manager->id, 'title' => 'Utang lama', 'billing_period' => today()->startOfMonth(), 'due_date' => today(), 'total_amount' => 10000, 'status' => 'unpaid']);
        $response = $this->preview('resident', [['Penghuni Baru', 'newpayer', 'newpayer@example.com', '08123456', 'A-01', 'Password123', '1']])->assertOk()->assertSee('Menggantikan Penghuni Lama');
        $this->post(route('pengurus.imports.confirm', $response->viewData('batch')->id))->assertRedirect();
        $new = User::where('username', 'newpayer')->firstOrFail();
        $this->assertSame($new->id, $this->house->refresh()->responsible_user_id);
        $this->assertSame($old->id, $invoice->refresh()->user_id);
    }

    public function test_resident_changes_after_preview_abort_all_changes_and_new_password_is_required(): void
    {
        $user = $this->resident(['username' => 'existing', 'email' => 'existing@example.com']);
        $response = $this->preview('resident', [['New value', 'existing', 'existing@example.com', '08123456', 'A-01', '', '1']])->assertOk();
        $user->update(['phone_number' => '08222222']);
        $this->post(route('pengurus.imports.confirm', $response->viewData('batch')->id))->assertSessionHasErrors('csv');
        $this->assertNotEquals('New value', $user->refresh()->name);
        $this->preview('resident',[['New', 'missingpassword', 'missing@example.com', '08123456', 'A-01', '', '1']])->assertOk()->assertSee('Seluruh impor ditahan');
        $this->assertDatabaseMissing('users',['username' => 'missingpassword']);
    }
}
