<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Topic;
use App\Models\User;
use App\Models\TopicVote;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $topics = Topic::all();

        if ($users->isEmpty() || $topics->isEmpty()) {
            return;
        }

        $commentsData = [
            // 投稿ID 9（電車内メイク）のコメント
            [
                'topic_id' => 9,
                'content' => '私も同じことを考えていました！電車内でのメイクって本当に必要なんでしょうか？',
                'stance' => 'oppose',
                'score' => 23,
                'upvotes' => 33,
                'downvotes' => 10,
                'replies' => [
                    [
                        'content' => '時間がない時は仕方ないと思います。ただ、マナーとして周りに配慮は必要ですよね。',
                        'stance' => 'support',
                        'score' => 12,
                        'upvotes' => 18,
                        'downvotes' => 6,
                    ]
                ]
            ],
            [
                'topic_id' => 9,
                'content' => '海外では普通の光景だと聞きました。日本も時代と共に変わっていくのでは？',
                'stance' => 'support',
                'score' => 45,
                'upvotes' => 52,
                'downvotes' => 7,
            ],
            
            // 投稿ID 5（デート代支払い）のコメント
            [
                'topic_id' => 5,
                'content' => '完全に割り勘が当たり前だと思います。男性が多く払うなんて時代遅れです。',
                'stance' => 'oppose',
                'score' => 34,
                'upvotes' => 41,
                'downvotes' => 7,
                'replies' => [
                    [
                        'content' => 'でも誘った方が多めに払うのが自然じゃないでしょうか？',
                        'stance' => 'support',
                        'score' => 18,
                        'upvotes' => 23,
                        'downvotes' => 5,
                    ]
                ]
            ],
            [
                'topic_id' => 5,
                'content' => '男性として、やはりエスコートしたい気持ちがあります。押し付けはよくないですが。',
                'stance' => 'support',
                'score' => 28,
                'upvotes' => 35,
                'downvotes' => 7,
            ],
            
            // 投稿ID 6（女性専用車両）のコメント
            [
                'topic_id' => 6,
                'content' => '痴漢被害を考えると必要だと思います。安心して通勤できる環境は大切です。',
                'stance' => 'support',
                'score' => 67,
                'upvotes' => 74,
                'downvotes' => 7,
            ],
            [
                'topic_id' => 6,
                'content' => '逆差別だと思います。男性にも配慮が必要では？',
                'stance' => 'oppose',
                'score' => 15,
                'upvotes' => 25,
                'downvotes' => 10,
                'replies' => [
                    [
                        'content' => '痴漢の実態を理解していない発言だと思います。',
                        'stance' => 'support',
                        'score' => 22,
                        'upvotes' => 27,
                        'downvotes' => 5,
                    ]
                ]
            ],
            
            // 投稿ID 7（子ども温泉）のコメント
            [
                'topic_id' => 7,
                'content' => '5-6歳くらいまでなら仕方ないと思います。それ以上は配慮が必要。',
                'stance' => 'support',
                'score' => 42,
                'upvotes' => 47,
                'downvotes' => 5,
            ],
            [
                'topic_id' => 7,
                'content' => '他の利用者の気持ちも考えるべき。年齢関係なく同性と入るべきです。',
                'stance' => 'oppose',
                'score' => 31,
                'upvotes' => 38,
                'downvotes' => 7,
            ],
            
            // 投稿ID 8（レディースデー）のコメント
            [
                'topic_id' => 8,
                'content' => 'マーケティング戦略として理解できます。女性の社会進出支援にもなる。',
                'stance' => 'support',
                'score' => 29,
                'upvotes' => 35,
                'downvotes' => 6,
            ],
            [
                'topic_id' => 8,
                'content' => '明らかな性別差別です。法的に問題があると思います。',
                'stance' => 'oppose',
                'score' => 24,
                'upvotes' => 32,
                'downvotes' => 8,
                'replies' => [
                    [
                        'content' => '差別とマーケティングは違うと思います。企業の自由では？',
                        'stance' => 'support',
                        'score' => 16,
                        'upvotes' => 21,
                        'downvotes' => 5,
                    ]
                ]
            ],
            
            // 投稿ID 12（電車内マナー）のコメント
            [
                'topic_id' => 12,
                'content' => '音量を適切にして、周りに迷惑をかけなければ問題ないと思います。',
                'stance' => 'support',
                'score' => 38,
                'upvotes' => 43,
                'downvotes' => 5,
            ],
            [
                'topic_id' => 12,
                'content' => '公共の場ではなるべく静かにするのがマナーだと思います。',
                'stance' => 'oppose',
                'score' => 33,
                'upvotes' => 39,
                'downvotes' => 6,
            ],
        ];

        foreach ($commentsData as $commentData) {
            $userId = $users->random()->id;
            
            $comment = Comment::create([
                'topic_id' => $commentData['topic_id'],
                'user_id' => $userId,
                'content' => $commentData['content'],
                'stance' => $commentData['stance'],
                'score' => $commentData['score'],
                'upvotes' => $commentData['upvotes'],
                'downvotes' => $commentData['downvotes'],
                'depth' => 0,
                'parent_id' => null,
                'created_at' => now()->subHours(rand(1, 24)),
                'updated_at' => now()->subHours(rand(1, 24)),
            ]);

            // 対応する投票レコードを作成
            TopicVote::updateOrCreate(
                [
                    'topic_id' => $commentData['topic_id'],
                    'user_id' => $userId,
                ],
                [
                    'stance' => $commentData['stance'],
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->created_at,
                ]
            );

            // 返信コメントがある場合
            if (isset($commentData['replies'])) {
                foreach ($commentData['replies'] as $replyData) {
                    $replyUserId = $users->random()->id;
                    
                    $reply = Comment::create([
                        'topic_id' => $commentData['topic_id'],
                        'user_id' => $replyUserId,
                        'parent_id' => $comment->id,
                        'content' => $replyData['content'],
                        'stance' => $replyData['stance'],
                        'score' => $replyData['score'],
                        'upvotes' => $replyData['upvotes'],
                        'downvotes' => $replyData['downvotes'],
                        'depth' => 1,
                        'created_at' => $comment->created_at->addMinutes(rand(10, 60)),
                        'updated_at' => $comment->created_at->addMinutes(rand(10, 60)),
                    ]);

                    // 返信ユーザーの投票レコードも作成
                    TopicVote::updateOrCreate(
                        [
                            'topic_id' => $commentData['topic_id'],
                            'user_id' => $replyUserId,
                        ],
                        [
                            'stance' => $replyData['stance'],
                            'created_at' => $reply->created_at,
                            'updated_at' => $reply->created_at,
                        ]
                    );
                }
            }
        }

        $this->command->info('コメントデータを正常に作成しました。');
    }
} 