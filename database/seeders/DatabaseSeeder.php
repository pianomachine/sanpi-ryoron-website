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
        // テストユーザーを作成
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User']
        );

        // 追加のユーザーを作成（投票用）
        User::factory(100)->create();

        // 議題とコメントのサンプルデータを生成
        $this->call([
            CommunitySeeder::class,
            TopicSeeder::class,
            CommentSeeder::class,
        ]);
    }
}
