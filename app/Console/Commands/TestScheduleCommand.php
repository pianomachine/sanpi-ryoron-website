<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestScheduleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test schedule command';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Test schedule running');
        return self::SUCCESS;
    }
}
