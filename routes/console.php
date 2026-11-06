<?php

use App\Models\HouseImport;
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

Schedule::call(fn () => HouseImport::where('expires_at', '<', now())->delete())->dailyAt('02:05')->name('cleanup-house-imports')->withoutOverlapping();

Schedule::command('app:send-whatsapp')->everyMinute()->withoutOverlapping(10);
