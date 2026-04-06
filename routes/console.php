<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command(\App\Console\Commands\UpdateOgpCachesCommand::class)
    ->hourly();

Schedule::command(\App\Console\Commands\CloseResolvedContacts::class)
    ->dailyAt('04:00');

Schedule::command(\App\Console\Commands\InvalidateUnverifiedUsers::class)
    ->everyFiveMinutes();

Schedule::command(\App\Console\Commands\PurgeWithdrawnUsersCommand::class)
    ->dailyAt('03:30');

Schedule::command(\App\Console\Commands\CleanupExpiredEmailChangeRequests::class)
    ->everyFifteenMinutes();

Schedule::command(\App\Console\Commands\RecalculateFearMeterStatisticsCommand::class)
    ->everyFourHours();

Schedule::command(\App\Console\Commands\SendApachePhpErrorLogCommand::class)
    ->dailyAt('11:00')
    ->environments(['production']);

Schedule::command(\App\Console\Commands\SendApachePhpErrorLogCommand::class)
    ->dailyAt('10:00')
    ->environments(['staging']);