<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:notify-guard-about-visitors')->dailyAt('06:30');
Schedule::command('app:notify-guard-about-visitors')->dailyAt('18:30');
Schedule::command('app:notify-guard-about-visitors')->dailyAt('19:30');
Schedule::command('app:notify-guard-about-visitors')->dailyAt('20:30');
Schedule::command('app:send-room-reservation-reminder-emails')->everyMinute();
Schedule::command('app:mark-completed-room-reservations')->everyMinute();
Schedule::command('approvals:sync-external')->everyTenMinutes();

