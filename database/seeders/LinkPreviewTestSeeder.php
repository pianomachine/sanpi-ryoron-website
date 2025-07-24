<?php

namespace Database\Seeders;

use App\Models\Topic;
use App\Models\Community;
use App\Models\User;
use App\Services\LinkPreviewService;
use Illuminate\Database\Seeder;

class LinkPreviewTestSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $linkPreviewService = new LinkPreviewService();
        
        // テスト用ユーザーを取得または作成
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create([
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
            ]);
        }
        
        // テスト用コミュニティを取得
        $community = Community::first();
        if (!$community) {
            $community = Community::create([
                'name' => 'テクノロジー',
                'slug' => 'technology',
                'icon' => '💻',
                'description' => 'テクノロジーに関する議論',
            ]);
        }

        // リンクプレビューのテスト用議題を作成
        $testTopics = [
            [
                'title' => 'GitHub Copilotは開発者の仕事を奪うと思いますか？',
                'content' => 'AI支援プログラミングツールの普及について議論しましょう。

参考記事：https://github.blog/2021-06-29-introducing-github-copilot-ai-pair-programmer/

実際に使ってみた感想や、将来の開発現場への影響について皆さんの意見をお聞かせください。',
                'stance' => 'neutral'
            ],
            [
                'title' => 'リモートワークは生産性を向上させると思いますか？',
                'content' => 'コロナ禍以降、リモートワークが一般的になりました。

関連データ：https://www.mckinsey.com/featured-insights/future-of-work/whats-next-for-remote-work-an-analysis-of-2000-tasks-763-jobs-and-27-countries

実際の経験談や、今後の働き方について議論しましょう。',
                'stance' => 'support'
            ],
            [
                'title' => 'NFTアートは本当に価値があるのでしょうか？',
                'content' => 'デジタルアートとブロックチェーン技術について考えてみましょう。

OpenSeaの現状：https://opensea.io/

アート市場やデジタル所有権についてどう思われますか？',
                'stance' => 'oppose'
            ]
        ];

        foreach ($testTopics as $topicData) {
            // リンクプレビューを生成
            $linkPreviews = $linkPreviewService->extractLinksFromContent($topicData['content']);
            
            $topic = Topic::create([
                'title' => $topicData['title'],
                'content' => $topicData['content'],
                'description' => $topicData['content'],
                'community_id' => $community->id,
                'user_id' => $user->id,
                'status' => 'active',
                'upvotes' => rand(5, 50),
                'downvotes' => rand(1, 10),
                'score' => rand(10, 100),
                'views_count' => rand(100, 1000),
                'is_nsfw' => false,
                'is_spoiler' => false,
                'link_previews' => $linkPreviews,
            ]);
            
            $this->command->info("Created topic: {$topic->title} with " . count($linkPreviews) . " link previews");
        }
    }
}