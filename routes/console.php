<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Test schedule command
Schedule::command('test:schedule')
    ->name('Test schedule')
    ->description('Test schedule command')
    ->everyMinute();

// Hot score update command
Schedule::command('topics:update-hot-score')
    ->name('Update topics hot score')
    ->description('Updates hot score for all topics')
    ->everyFiveMinutes()
    ->onOneServer();
