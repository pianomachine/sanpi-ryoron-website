<?php

namespace Database\Seeders;

use App\Models\TopicVote;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class TopicVoteSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $topics = Topic::whereIn('id', [5, 6, 7, 8, 9, 12])->get();

        if ($users->isEmpty() || $topics->isEmpty()) {
            return;
        }

        // 各投稿に対して投票データを作成
        $votesData = [
            5 => ['support' => 45, 'oppose' => 23], // デート代支払い
            6 => ['support' => 67, 'oppose' => 18], // 女性専用車両
            7 => ['support' => 34, 'oppose' => 28], // 子ども温泉
            8 => ['support' => 41, 'oppose' => 32], // レディースデー
            9 => ['support' => 29, 'oppose' => 38], // 電車内メイク
            12 => ['support' => 52, 'oppose' => 31], // リュック問題
        ];

        foreach ($votesData as $topicId => $votes) {
            $topic = $topics->firstWhere('id', $topicId);
            if (!$topic) continue;

            $usedUserIds = [];
            
            // 十分なユーザーがいるかチェック
            $totalVotesNeeded = $votes['support'] + $votes['oppose'];
            if ($users->count() < $totalVotesNeeded) {
                // 足りない場合は比例配分する
                $ratio = $users->count() / $totalVotesNeeded;
                $votes['support'] = intval($votes['support'] * $ratio);
                $votes['oppose'] = intval($votes['oppose'] * $ratio);
            }

            // 賛成票を作成
            for ($i = 0; $i < $votes['support']; $i++) {
                $availableUsers = $users->whereNotIn('id', $usedUserIds);
                if ($availableUsers->isEmpty()) break;
                
                $user = $availableUsers->random();
                $usedUserIds[] = $user->id;
                
                TopicVote::create([
                    'topic_id' => $topicId,
                    'user_id' => $user->id,
                    'stance' => 'support',
                    'created_at' => now()->subHours(rand(1, 72)),
                    'updated_at' => now()->subHours(rand(1, 72)),
                ]);
            }

            // 反対票を作成
            for ($i = 0; $i < $votes['oppose']; $i++) {
                $availableUsers = $users->whereNotIn('id', $usedUserIds);
                if ($availableUsers->isEmpty()) break;
                
                $user = $availableUsers->random();
                $usedUserIds[] = $user->id;
                
                TopicVote::create([
                    'topic_id' => $topicId,
                    'user_id' => $user->id,
                    'stance' => 'oppose',
                    'created_at' => now()->subHours(rand(1, 72)),
                    'updated_at' => now()->subHours(rand(1, 72)),
                ]);
            }
        }
    }
}
