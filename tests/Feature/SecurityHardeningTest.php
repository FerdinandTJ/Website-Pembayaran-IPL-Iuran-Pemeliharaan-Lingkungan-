<?php

namespace Tests\Feature;

use App\Models\Housing;
use App\Models\Invoice;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Housing $housing;

    private User $manager;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->housing = Housing::create(['name' => 'Security A', 'address' => 'A']);
        $this->manager = User::factory()->manager()->create(['housing_id' => $this->housing->id]);
        $this->resident = User::factory()->create(['housing_id' => $this->housing->id]);
        Storage::fake('local');
    }

    private function invoice(): Invoice
    {
        Storage::disk('local')->put('payment-proofs/test.png', 'image');

        return Invoice::create(['housing_id' => $this->housing->id, 'user_id' => $this->resident->id,
            'created_by' => $this->manager->id, 'title' => 'IPL', 'billing_period' => today()->startOfMonth(),
            'due_date' => today(), 'total_amount' => 50000, 'status' => 'unpaid', 'payment_proof_path' => 'payment-proofs/test.png']);
    }

    public function test_inactive_users_and_housing_cannot_open_proofs_profile_or_home_but_can_logout(): void
    {
        $invoice = $this->invoice();
        foreach (['user', 'housing'] as $target) {
            $this->resident->update(['is_active' => $target !== 'user']);
            $this->housing->update(['is_active' => $target !== 'housing']);
            foreach ([route('invoices.proof', $invoice), route('profile.show'), route('home')] as $url) {
                $this->actingAs($this->resident)->get($url)->assertForbidden();
            }
            $this->actingAs($this->resident)->post(route('logout'))->assertRedirect(route('login'));
        }
    }

    public function test_proof_responses_prevent_sniffing_and_private_pages_are_not_cached(): void
    {
        $invoice = $this->invoice();
        $response = $this->actingAs($this->resident)->get(route('invoices.proof', $invoice))->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString("default-src 'none'; sandbox", $response->headers->get('Content-Security-Policy'));
        $this->get(route('profile.show'))->assertOk()->assertHeader('Referrer-Policy', 'same-origin');
    }

    public function test_password_reset_revokes_existing_sessions_and_remember_token(): void
    {
        $before = $this->resident->remember_token;
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $this->resident->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($this->manager)->patch(route('pengurus.residents.password', $this->resident), [
            'password' => 'ResetPassword123', 'password_confirmation' => 'ResetPassword123',
        ])->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertNotEquals($before, $this->resident->refresh()->remember_token);
        $this->assertTrue(Hash::check('ResetPassword123', $this->resident->password));
    }

    public function test_deactivation_revokes_sessions_instead_of_reusing_them_after_reactivation(): void
    {
        $before = $this->resident->remember_token;
        DB::table('sessions')->insert(['id' => 'inactive-session', 'user_id' => $this->resident->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($this->manager)->delete(route('pengurus.residents.destroy', $this->resident))->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'inactive-session']);
        $this->assertNotEquals($before, $this->resident->refresh()->remember_token);
    }

    public function test_password_guessing_on_profile_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->resident)->patchJson(route('profile.password'), [
                'current_password' => 'incorrect', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
            ])->assertUnprocessable();
        }
        $this->patchJson(route('profile.password'), [
            'current_password' => 'incorrect', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ])->assertStatus(429);
    }

    public function test_malformed_login_identifiers_are_validated_without_a_server_error(): void
    {
        $this->postJson(route('login.store'), ['identifier' => ['malformed'], 'password' => 'test'])->assertUnprocessable();
        $this->postJson(route('login.store'), ['identifier' => str_repeat('x', 2000), 'password' => 'test'])->assertUnprocessable();
    }

    public function test_csrf_is_enforced_on_mutations_outside_the_usual_test_bypass(): void
    {
        $this->app->bind(ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends ValidateCsrfToken
            {
                protected function runningUnitTests()
                {
                    return false;
                }
            };
        });
        $this->actingAs($this->resident)->patchJson(route('profile.password'), [
            'current_password' => 'password', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ])->assertStatus(419);
        $this->assertTrue(Hash::check('password', $this->resident->refresh()->password));
    }

    public function test_fake_images_and_out_of_range_cash_amounts_are_rejected(): void
    {
        $invoice = $this->invoice();
        $this->actingAs($this->resident)->post(route('warga.invoices.payment', $invoice), [
            'proof' => UploadedFile::fake()->createWithContent('proof.png', '<?php echo "bad"; ?>'),
        ])->assertSessionHasErrors('proof');
        $this->actingAs($this->manager)->post(route('pengurus.cashflows.store'), [
            'name' => 'Overflow', 'type' => 'income', 'total_cashflow' => '9999999999999999999999999',
        ])->assertSessionHasErrors('total_cashflow');
        $this->assertDatabaseCount('cashflows', 0);
    }

    public function test_production_forces_safe_debug_cookie_and_https_headers(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => true, 'session.secure' => false, 'session.http_only' => false]);
        $this->app->getProvider(AppServiceProvider::class)->boot();
        $this->assertFalse(config('app.debug'));
        $this->assertTrue(config('session.secure'));
        $this->assertTrue(config('session.http_only'));
        $this->get('https://localhost/')
            ->assertOk()->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_mass_assignment_cannot_elevate_resident_to_admin_or_other_housing(): void
    {
        $other = Housing::create(['name' => 'Security B', 'address' => 'B']);
        $this->actingAs($this->manager)->post(route('pengurus.residents.store'), [
            'name' => 'Injected', 'username' => 'injected', 'email' => 'injected@example.com', 'phone_number' => '081111111',
            'house_address' => 'A', 'is_active' => true, 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
            'role' => 'admin', 'housing_id' => $other->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['username' => 'injected', 'role' => 'warga', 'housing_id' => $this->housing->id]);
    }
}
