<?php

namespace App\Providers;

use App\Models\AccountingPeriod;
use App\Models\ArrearsFollowUp;
use App\Models\Cashflow;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\InvoiceTemplateRun;
use App\Models\PaymentBatch;
use App\Observers\FinancialActivityObserver;
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
        foreach ([Invoice::class, Cashflow::class, PaymentBatch::class,
            InvoiceTemplate::class, InvoiceTemplateRun::class, ArrearsFollowUp::class, AccountingPeriod::class] as $model) {
            $model::observe(FinancialActivityObserver::class);
        }
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
