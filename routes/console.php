<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder otomatis: H-30 fitting, H-7 DP 75%, H-2 pelunasan, H-1 hari H
Schedule::command('reminders:generate')->dailyAt('06:00');
