<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Hot スコアを 5 分毎に更新
        $schedule->command('topics:update-hot-score')->everyFiveMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // routes/console.php に定義済みの Closure コマンドも読み込む
        require base_path('routes/console.php');
    }
} 