<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Topic;
use App\Models\Community;
use App\Models\User;
use Carbon\Carbon;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // コミュニティとユーザーを取得
        $communities = Community::all()->keyBy('slug');
        $user = User::first() ?? User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        $topics = [
            [
                'community_id' => $communities['dating-payment']->id,
                'user_id' => $user->id,
                'title' => 'デート代は男性が全額払うべきだと思いませんか？',
                'content' => '最近付き合い始めた彼女とのデート代について悩んでいます。毎回私が払っているのですが、これが当然なのでしょうか？友人には「男が払うのが当たり前」という人もいれば、「平等に割り勘にすべき」という人もいて、正解がわからなくなってしまいました。皆さんはどう思いますか？',
                'type' => 'text',
                'flair' => '💙賛成多数',
                'score' => 0, // 実際の投票データに基づく計算のみを使用
                'upvotes' => 0,
                'downvotes' => 0,
                'comments_count' => 0,
                'views_count' => 3420,
                'status' => 'active',
                'created_at' => Carbon::now()->subHours(4)
            ],
            [
                'community_id' => $communities['women-only-cars']->id,
                'user_id' => $user->id,
                'title' => '女性専用車両は男性差別だと思いませんか？',
                'content' => '毎朝の通勤で女性専用車両を見るたびに疑問に思います。確かに痴漢対策は重要ですが、これって男性差別にならないのでしょうか？満員電車で男性だけが押し込まれているのを見ると、なんだか不公平に感じてしまいます。',
                'type' => 'text',
                'flair' => '🔥激論中',
                'score' => 0, // 実際の投票データに基づく計算のみを使用
                'upvotes' => 0,
                'downvotes' => 0,
                'comments_count' => 0,
                'views_count' => 8932,
                'status' => 'active',
                'created_at' => Carbon::now()->subHours(8)
            ],
            [
                'community_id' => $communities['children-spa']->id,
                'user_id' => $user->id,
                'title' => '6歳の異性の子どもを温泉に連れて行くのは問題ないと思いませんか？',
                'content' => 'シングルファザーとして、6歳の娘を温泉に連れて行くことについて悩んでいます。温泉施設によってルールが違うし、周りの目も気になります。娘はまだ一人でお風呂に入るのが不安なようですが、いつまでも一緒というわけにもいかないですよね...',
                'type' => 'text',
                'flair' => '🤱子育て',
                'score' => 0, // 実際の投票データに基づく計算のみを使用
                'upvotes' => 0,
                'downvotes' => 0,
                'comments_count' => 0,
                'views_count' => 2890,
                'status' => 'active',
                'created_at' => Carbon::now()->subHours(12)
            ],
            [
                'community_id' => $communities['ladies-day']->id,
                'user_id' => $user->id,
                'title' => 'レディースデーは男性差別だと思いませんか？',
                'content' => '先日、映画館で「本日はレディースデーのため女性は1,100円」という看板を見て考えさせられました。映画代が1,900円なので800円も安くなります。一方で「メンズデー」はほとんど見かけません。これは企業戦略なのでしょうか、それとも何らかの社会的配慮なのでしょうか？',
                'type' => 'text',
                'flair' => '⚖️ジェンダー',
                'score' => 0, // 実際の投票データに基づく計算のみを使用
                'upvotes' => 0,
                'downvotes' => 0,
                'comments_count' => 0,
                'views_count' => 1567,
                'status' => 'active',
                'created_at' => Carbon::now()->subHours(16)
            ],
            [
                'community_id' => $communities['train-manner']->id,
                'user_id' => $user->id,
                'title' => '電車内でのメイクや身だしなみはマナー違反だと思いませんか？',
                'content' => '毎朝通勤電車で見かける光景について、皆さんの意見を聞きたいです。女性が電車内でメイクをしたり、男性が髭を剃ったりするのをよく見かけますが、これってマナー的にどうなのでしょうか？時間がない現代人の事情もわかりますが、公共の場でやることじゃないという意見も...',
                'type' => 'text',
                'flair' => '🚃マナー',
                'score' => 0, // 実際の投票データに基づく計算のみを使用
                'upvotes' => 0,
                'downvotes' => 0,
                'comments_count' => 0,
                'views_count' => 2134,
                'status' => 'active',
                'created_at' => Carbon::now()->subHours(6)
            ],
            // 追加投稿
            [
                'community_id' => $communities['dating-payment']->id,
                'user_id' => $user->id,
                'title' => '初デートで女性が割り勘を提案するのは脈ありサインだと思いませんか？',
                'content' => '昨日の初デートで、会計の時に女性から「割り勘でお願いします」と言われました。とても好印象だったのですが、これって脈ありサインでしょうか？それとも単純に平等主義なだけ？男性の皆さん、こういう時どう感じますか？',
                'type' => 'text',
                'flair' => '❤️恋愛相談',
                'score' => 0, // 実際の投票データに基づく計算のみを使用
                'upvotes' => 0,
                'downvotes' => 0,
                'comments_count' => 0,
                'views_count' => 892,
                'status' => 'active',
                'created_at' => Carbon::now()->subHours(2)
            ],
            [
                'community_id' => $communities['women-only-cars']->id,
                'user_id' => $user->id,
                'title' => '日本も海外のように女性専用車両を廃止すべきだと思いませんか？',
                'content' => '世界各国の女性専用車両について調査しました。インドでは女性専用車両が一般的で、エジプトでも導入されています。一方、欧米では見かけません。文化的背景や社会情勢が大きく影響しているようです。',
                'type' => 'text',
                'flair' => '📊データ分析',
                'score' => 0, // 実際の投票データに基づく計算のみを使用
                'upvotes' => 0,
                'downvotes' => 0,
                'comments_count' => 0,
                'views_count' => 4329,
                'status' => 'active',
                'created_at' => Carbon::now()->subHours(12)
            ],
            [
                'community_id' => $communities['train-manner']->id,
                'user_id' => $user->id,
                'title' => '満員電車ではリュックを前に抱えるべきだと思いませんか？',
                'content' => '毎朝の満員電車でリュックをどうするか悩みます。前に抱えると手がふさがるし、背負ったままだと後ろの人に当たるし...駅員さんは「前に抱えて」と言いますが、実際どちらが迷惑が少ないのでしょうか？',
                'type' => 'text',
                'flair' => '🎒荷物問題',
                'score' => 0, // 実際の投票データに基づく計算のみを使用
                'upvotes' => 0,
                'downvotes' => 0,
                'comments_count' => 0,
                'views_count' => 2103,
                'status' => 'active',
                'created_at' => Carbon::now()->subHours(14)
            ]
        ];

        // 特定のIDでトピックを作成
        $topicIdMap = [5, 6, 7, 8, 9, 10, 11, 12];
        foreach ($topics as $index => $topic) {
            $topic['id'] = $topicIdMap[$index];
            Topic::create($topic);
        }
    }
}
