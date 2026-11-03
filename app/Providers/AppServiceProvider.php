<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        if ($this->app->environment('production')) {
            config(['app.debug' => false, 'session.secure' => true, 'session.http_only' => true, 'session.same_site' => 'lax']);
        }

        RateLimiter::for('login', function (Request $request) {
            $value = $request->input('identifier');
            $identifier = is_string($value) ? Str::lower(mb_substr($value, 0, 255)) : 'invalid';

            return [Limit::perMinute(5)->by('login:'.hash('sha256', $identifier).'|'.$request->ip()), Limit::perMinute(30)->by('login-ip:'.$request->ip())];
        });
        RateLimiter::for('sensitive', fn (Request $request) => Limit::perMinute(5)->by('sensitive:'.$request->user()?->id.'|'.$request->ip()));
        RateLimiter::for('imports', fn (Request $request) => Limit::perMinute(5)->by('imports:'.$request->user()?->id));
        RateLimiter::for('documents', fn (Request $request) => Limit::perMinute(30)->by('documents:'.$request->user()?->id));
    }
}
