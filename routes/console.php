<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('suggestions:expire')->everyMinute();
Schedule::command('matching:post-session')->everyMinute();
Schedule::command('events:purge-expired')->daily();
