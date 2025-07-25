<?php

namespace Database\Seeders;

use App\Models\Topic;
use App\Models\User;
use App\Models\Community;
use App\Services\LinkPreviewService;
use Illuminate\Database\Seeder;

class TestLinkPreviewSeeder extends Seeder
{
    public function run(): void
    {
        // テスト用ユーザーとコミュニティを取得または作成
        $user = User::first();
        $community = Community::first();
        
        if (!$user || !$community) {
            $this->command->error('User or Community not found. Please run other seeders first.');
            return;
        }
        
        $linkPreviewService = new LinkPreviewService();
        
        // URLを含むテストトピックを作成
        $testTopics = [
            [
                'title' => 'GitHub Copilotの未来について議論しましょう',
                'content' => 'AI支援プログラミングツールの進化について意見交換しましょう。

GitHubの最新記事: https://github.blog/2024-01-15-github-copilot-chat-general-availability/

皆さんはAIツールをどのように活用していますか？',
            ],
            [
                'title' => 'リモートワークのベストプラクティス',
                'content' => '効率的なリモートワークのための工夫を共有しましょう。

参考記事: https://www.atlassian.com/blog/teamwork/remote-work-productivity

みなさんの経験談をお聞かせください。',
            ],
            [
                'title' => 'オープンソースプロジェクトへの貢献方法',
                'content' => '初心者がオープンソースに貢献する方法について。

GitHubのガイド: https://docs.github.com/en/get-started/exploring-projects-on-github/finding-ways-to-contribute-to-open-source-on-github

最初の一歩をどう踏み出しましたか？',
            ],
        ];
        
        foreach ($testTopics as $topicData) {
            $linkPreviews = $linkPreviewService->extractLinksFromContent($topicData['content']);
            
            $topic = Topic::create([
                'title' => $topicData['title'],
                'content' => $topicData['content'],
                'description' => $topicData['content'],
                'user_id' => $user->id,
                'community_id' => $community->id,
                'status' => 'active',
                'upvotes' => rand(10, 100),
                'downvotes' => rand(0, 20),
                'score' => rand(10, 80),
                'views_count' => rand(50, 500),
                'comments_count' => 0,
                'is_nsfw' => false,
                'is_spoiler' => false,
                'link_previews' => $linkPreviews,
            ]);
            
            $this->command->info("Created topic: {$topic->title} with " . count($linkPreviews) . " link previews");
        }
    }
}