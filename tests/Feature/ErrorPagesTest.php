<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public static function errorStatuses(): array
    {
        return array_map(fn ($status) => [$status], [400, 401, 403, 404, 405, 408, 409, 410, 413, 419, 422, 429, 500, 502, 503, 504]);
    }

    #[DataProvider('errorStatuses')]
    public function test_browser_errors_keep_the_status_and_show_a_private_standalone_page(int $status): void
    {
        config(['app.debug' => true]);
        Route::get('/_test/error-page', fn () => abort($status, 'INTERNAL-SENSITIVE-MESSAGE'));

        $this->get('/_test/error-page')->assertStatus($status)
            ->assertSee('EnviroPay')->assertSee('HALAMAN '.$status)
            ->assertSee('id="error-heading"', false)
            ->assertDontSee('INTERNAL-SENSITIVE-MESSAGE')
            ->assertDontSee('Stack trace')->assertDontSee('vendor/laravel')
            ->assertDontSee('type="module"', false)->assertDontSee('fonts.googleapis.com')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_unexpected_failures_do_not_expose_debug_details_even_when_debug_is_enabled(): void
    {
        config(['app.debug' => true]);
        Route::get('/_test/unexpected-error', fn () => throw new \RuntimeException('PRIVATE-SQL-CONNECTION-DETAILS'));

        $this->get('/_test/unexpected-error')->assertStatus(500)
            ->assertSee('Ada kendala di sisi kami')
            ->assertDontSee('PRIVATE-SQL-CONNECTION-DETAILS')
            ->assertDontSee('RuntimeException');
    }

    public function test_missing_routes_render_without_database_or_built_assets(): void
    {
        // This class does not run migrations: rendering must not query users or sessions.
        $this->get('/halaman-yang-tidak-ada')->assertNotFound()
            ->assertSee('Sepertinya Anda salah jalan')
            ->assertDontSee('build/assets')->assertDontSee('csrf-token');
    }

    public function test_rate_limit_and_method_headers_are_preserved(): void
    {
        Route::get('/_test/rate-limit', fn () => abort(429, 'Private provider details', ['Retry-After' => '60']));
        $this->get('/_test/rate-limit')->assertStatus(429)->assertHeader('Retry-After', '60');

        $this->post('/_test/rate-limit')->assertStatus(405)->assertHeader('Allow', 'GET, HEAD');
    }

    public function test_json_errors_and_validation_responses_keep_their_contract(): void
    {
        Route::get('/_test/json-error', fn () => abort(403, 'Access denied.'));
        $this->getJson('/_test/json-error')->assertForbidden()
            ->assertJsonPath('message', 'Access denied.')->assertDontSee('<!doctype html>', false);

        $this->postJson(route('login.store'), ['identifier' => ['invalid'], 'password' => 'test'])
            ->assertUnprocessable()->assertJsonValidationErrors('identifier');

        $this->from('/')->post(route('login.store'), ['identifier' => ['invalid'], 'password' => 'test'])
            ->assertRedirect('/')->assertSessionHasErrors('identifier');
    }

    public function test_unauthenticated_requests_still_redirect_to_login(): void
    {
        $this->get('/pengurus/dashboard')->assertRedirect(route('login'));
    }
}
