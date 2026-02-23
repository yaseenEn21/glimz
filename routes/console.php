<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reminders:check-expiring')
    ->dailyAt('00:10')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('promotional-notifications:send-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('bookings:cancel-stale-pending')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();