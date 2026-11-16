<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Micro-tasks (PLAN M17): new questions for new values, and contributors whose accounts aged into trust.
Schedule::command('atlas:tasks:generate')->hourly()->withoutOverlapping();
Schedule::command('atlas:trust:promote')->daily();
