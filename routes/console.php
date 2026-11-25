<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('platform:expire-approvals')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('platform:backup')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('platform:backup-prune')->dailyAt('02:00')->withoutOverlapping();

Schedule::command('inventory:release-reusable-products')->everyMinute()->withoutOverlapping();
