<?php

namespace Tests\Feature;

use App\Models\Housing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_admin_command_only_allows_one_admin(): void
    {
        $options = [
            '--name' => 'Admin Utama', '--username' => 'admin',
            '--email' => 'admin@example.com', '--password' => 'password123',
        ];

        $this->artisan('app:create-admin', $options)->assertSuccessful();
        $this->artisan('app:create-admin', $options)->assertFailed();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['role' => 'admin', 'housing_id' => null]);
    }

    public function test_admin_can_create_housing_and_manager(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.housings.store'), [
            'name' => 'Taman Hijau', 'address' => 'Jl. Mawar 1',
            'phone_number' => '081234567890', 'is_active' => 1,
        ])->assertRedirect(route('admin.housings.index'));

        $housing = Housing::firstOrFail();
        $this->actingAs($admin)->post(route('admin.managers.store'), [
            'housing_id' => $housing->id, 'name' => 'Pengurus Satu',
            'username' => 'pengurussatu', 'email' => 'pengurus@example.com',
            'phone_number' => '081234567890', 'is_active' => 1,
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.managers.index'));

        $this->assertDatabaseHas('users', [
            'housing_id' => $housing->id, 'role' => 'pengurus', 'username' => 'pengurussatu',
        ]);
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $housing = Housing::create(['name' => 'Taman Hijau', 'address' => 'Jl. Mawar']);
        $manager = User::factory()->manager()->create(['housing_id' => $housing->id]);

        $this->actingAs($manager)->get(route('admin.dashboard'))->assertForbidden();
    }
}
