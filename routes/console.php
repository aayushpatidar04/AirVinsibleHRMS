<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Attendance, leave and payroll housekeeping (needs the scheduler: `php artisan schedule:work` or a cron entry).
\Illuminate\Support\Facades\Schedule::command('attendance:process-daily')->dailyAt('01:00')->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('leave:accrue')->dailyAt('02:00')->withoutOverlapping();
