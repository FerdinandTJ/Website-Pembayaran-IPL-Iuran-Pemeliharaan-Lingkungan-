<?php

use App\Models\HouseImport;
use App\Models\PushDelivery;
use App\Models\ResidentImport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('app:send-invoice-reminders')
    ->dailyAt('08:00')->timezone('Asia/Jakarta')->withoutOverlapping(60);

Schedule::call(fn () => ResidentImport::where('expires_at', '<', now())->delete())->dailyAt('02:00')->name('cleanup-resident-imports')->withoutOverlapping();

Schedule::command('app:send-web-push')->everyMinute()->withoutOverlapping(10);
Schedule::call(fn () => PushDelivery::where('updated_at', '<', now()->subDays(7))->delete())->dailyAt('02:15')->name('cleanup-push-deliveries')->withoutOverlapping();

Schedule::call(fn () => HouseImport::where('expires_at', '<', now())->delete())->dailyAt('02:05')->name('cleanup-house-imports')->withoutOverlapping();
