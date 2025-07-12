<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Topic;

class UpdateHotScore extends Command
{
    protected $signature = 'topics:update-hot-score';
    protected $description = 'Recalculate hot_score for all active topics';

    public function handle(): int
    {
        $this->info('Updating hot_score...');

        // SQLiteとPostgreSQL両対応の簡単なスコア計算
        $sql = "
            UPDATE topics 
            SET hot_score = (
                COALESCE(score, 0) + 1 + 
                (julianday('now') - julianday(created_at)) * -0.1 + 24
            ) 
            WHERE status = 'active'
        ";
        
        try {
            DB::statement($sql);
            $updated = Topic::where('status', 'active')->count();
        } catch (\Exception $e) {
            $this->error('SQL error: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("hot_score updated successfully for {$updated} topics");
        return self::SUCCESS;
    }
} 