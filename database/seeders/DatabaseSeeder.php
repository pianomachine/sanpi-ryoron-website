<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // テストユーザーを作成（Fakerを使わない）
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'テストユーザー',
                'workos_id' => 'test-workos-id',
                'avatar' => ''
            ]
        );

        // サンプル用ユーザーを数名追加（Fakerを使わない）
        $sampleUsers = [
            ['name' => '田中太郎', 'email' => 'tanaka@example.com', 'workos_id' => 'sample-1'],
            ['name' => '佐藤花子', 'email' => 'sato@example.com', 'workos_id' => 'sample-2'],
            ['name' => '山田次郎', 'email' => 'yamada@example.com', 'workos_id' => 'sample-3'],
            ['name' => '鈴木美咲', 'email' => 'suzuki@example.com', 'workos_id' => 'sample-4'],
            ['name' => '高橋健太', 'email' => 'takahashi@example.com', 'workos_id' => 'sample-5'],
        ];

        foreach ($sampleUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'workos_id' => $userData['workos_id'],
                    'avatar' => ''
                ]
            );
        }

        // 議題とコメントのサンプルデータを生成
        $this->call([
            CommunitySeeder::class,
            TopicSeeder::class,
            CommentSeeder::class,
        ]);
    }
}
