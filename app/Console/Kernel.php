<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('topics:update-hot-score')
                 ->everyFiveMinutes()
                 ->onOneServer();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}