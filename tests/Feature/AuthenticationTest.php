<?php

namespace Tests\Feature;

use App\Models\Housing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_with_email_or_username(): void
    {
        $housing = Housing::create(['name' => 'Griya Asri', 'address' => 'Jakarta']);
        $manager = User::factory()->manager()->create([
            'housing_id' => $housing->id,
            'email' => 'pengurus@example.com',
            'username' => 'pengurus',
            'password' => 'password123',
        ]);

        $this->post(route('login.store'), ['identifier' => $manager->email, 'password' => 'password123'])
            ->assertRedirect(route('pengurus.dashboard'));
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.store'), ['identifier' => $manager->username, 'password' => 'password123'])
            ->assertRedirect(route('pengurus.dashboard'));
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'password123']);

        $this->post(route('login.store'), ['identifier' => $user->email, 'password' => 'password123'])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_user_from_inactive_housing_cannot_login(): void
    {
        $housing = Housing::create(['name' => 'Nonaktif', 'address' => 'Jakarta', 'is_active' => false]);
        $user = User::factory()->manager()->create([
            'housing_id' => $housing->id,
            'password' => 'password123',
        ]);

        $this->post(route('login.store'), ['identifier' => $user->email, 'password' => 'password123'])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), ['identifier' => 'unknown@example.com', 'password' => 'wrong']);
        }

        $this->post(route('login.store'), ['identifier' => 'unknown@example.com', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_logout_only_accepts_post(): void
    {
        $this->get('/logout')->assertStatus(405);
    }
}
