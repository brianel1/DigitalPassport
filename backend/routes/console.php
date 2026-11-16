<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('jobs:sync --location="Malaysia"')
    ->timezone('Asia/Kuala_Lumpur')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Artisan::command('jobs:check-links', function (\App\Services\JobLinkChecker $checker) {
    $this->info($checker->check(20).' provider links checked.');
});
Schedule::command('jobs:check-links')->timezone('Asia/Kuala_Lumpur')->dailyAt('08:10')->withoutOverlapping();
