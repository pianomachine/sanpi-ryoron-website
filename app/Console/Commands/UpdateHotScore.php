<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateHotScore extends Command
{
    protected $signature = 'topics:update-hot-score';
    protected $description = 'Recalculate hot_score for all active topics';

    public function handle(): int
    {
        $this->info('Updating hot_score...');

        $sql = <<<SQL
        UPDATE topics
        SET hot_score = (
            (
                COALESCE(score, 0) +
                (
                    SELECT COUNT(*) FROM topic_votes
                    WHERE topic_votes.topic_id = topics.id
                    AND topic_votes.stance = 'support'
                ) +
                (
                    SELECT COUNT(*) FROM anonymous_votes
                    WHERE anonymous_votes.topic_id = topics.id
                    AND anonymous_votes.stance = 'support'
                ) +
                (
                    SELECT COUNT(*) FROM comments
                    WHERE comments.topic_id = topics.id
                ) +
                (
                    SELECT COUNT(DISTINCT user_id) * 3
                    FROM comments
                    WHERE comments.topic_id = topics.id
                )
            ) / POWER(((EXTRACT(EPOCH FROM (NOW() - topics.created_at)) / 3600) + 2), 1.5)
        )
        WHERE status = 'active';
        SQL;

        DB::statement($sql);

        $this->info('hot_score updated successfully');
        return self::SUCCESS;
    }
} 