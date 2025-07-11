<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Community;
use App\Models\User;
use App\Models\Comment;
use App\Models\CommunityMembership;
use App\Models\AnonymousVote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $sort = $request->get('sort', 'hot');
            
            // トピックの取得
            $query = Topic::with(['user', 'community'])
                ->where('status', 'active')
                ->whereNotNull('community_id');

            switch ($sort) {
                case 'new':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'top':
                    $query->orderBy('score', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $allTopics = $query->limit(20)->get();
            
            // データが存在しない場合は空の状態を表示
            if ($allTopics->isEmpty()) {
                return $this->renderEmptyState($request, $sort);
            }
            
            // データ変換処理
            $posts = $allTopics->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'subreddit' => $topic->community ? $topic->community->name : 'Unknown',
                    'subreddit_icon' => $topic->community ? $topic->community->icon : '📝',
                    'subreddit_slug' => $topic->community ? $topic->community->slug : 'unknown',
                    'title' => $topic->title,
                    'content' => $topic->content ?? '',
                    'type' => $topic->type ?? 'text',
                    'author' => [
                        'username' => $topic->user ? $topic->user->name : 'Anonymous',
                        'karma' => 1000,
                        'cake_day' => $topic->user ? $topic->user->created_at->format('Y-m-d') : date('Y-m-d')
                    ],
                    'votes' => [
                        'upvotes' => $topic->upvotes ?? 0,
                        'downvotes' => $topic->downvotes ?? 0,
                        'score' => $topic->score ?? 0
                    ],
                    'comments_count' => $topic->comments_count ?? 0,
                    'awards' => [],
                    'created_at' => $topic->created_at,
                    'url' => $topic->url,
                    'image_url' => $topic->image_url,
                    'is_nsfw' => $topic->is_nsfw ?? false,
                    'is_spoiler' => $topic->is_spoiler ?? false,
                    'flair' => $topic->flair
                ];
            });
            
            // サイドバーデータの取得
            $trending_communities = [];
            $popular_posts_today = [];
            
            try {
                $trending_communities = Community::orderBy('members_count', 'desc')
                    ->take(5)
                    ->get()
                    ->map(function ($community) {
                        return [
                            'name' => $community->name,
                            'members' => $community->getMembersFormatted(),
                            'icon' => $community->icon,
                            'description' => $community->description
                        ];
                    });
            } catch (\Exception $e) {
                \Log::error('Error getting trending communities: ' . $e->getMessage());
            }
            
            return Inertia::render('home/index', [
                'posts' => $posts->values(),
                'all_topics' => $posts->values(),
                'current_sort' => $sort,
                'trending_communities' => $trending_communities,
                'popular_posts_today' => $popular_posts_today,
                'user' => $request->user()
            ]);

        } catch (\Exception $e) {
            \Log::error('HomeController error: ' . $e->getMessage());
            return $this->renderEmptyState($request, $request->get('sort', 'hot'));
        }
    }

    /**
     * データが存在しない場合の空の状態を表示
     */
    private function renderEmptyState(Request $request, string $sort)
    {
        return Inertia::render('home/index', [
            'posts' => [],
            'all_topics' => [],
            'current_sort' => $sort,
            'trending_communities' => [],
            'popular_posts_today' => [],
            'user' => $request->user()
        ]);
    }

    /**
     * TopicをフロントエンドFormatに変換
     */
    private function formatTopicForFrontend($topic, $commentsCount)
    {
        return [
            'id' => $topic->id,
            'subreddit' => $topic->community->name ?? 'Unknown',
            'subreddit_icon' => $topic->community->icon ?? '📝',
            'subreddit_slug' => $topic->community->slug ?? 'unknown',
            'title' => $topic->title,
            'content' => $topic->content,
            'type' => $topic->type ?? 'text',
            'author' => [
                'username' => $topic->user->name ?? 'Anonymous',
                'karma' => rand(1000, 5000), // 仮のカルマ値
                'cake_day' => $topic->user ? $topic->user->created_at->format('Y-m-d') : date('Y-m-d')
            ],
            'votes' => [
                'upvotes' => $topic->upvotes ?? 0,
                'downvotes' => $topic->downvotes ?? 0,
                'score' => $topic->score ?? 0
            ],
            'comments_count' => $commentsCount[$topic->id] ?? 0,
            'awards' => $topic->awards ?? [],
            'created_at' => $topic->created_at,
            'url' => $topic->url,
            'image_url' => $topic->image_url,
            'is_nsfw' => $topic->is_nsfw ?? false,
            'is_spoiler' => $topic->is_spoiler ?? false,
            'flair' => $topic->flair
        ];
    }

    public function show(Request $request, $id)
    {
        // データベースから投稿を取得
        $topic = Topic::with(['user', 'community'])->find($id);
        
        if (!$topic) {
            abort(404);
        }

        // 投票割合を計算（認証済み + 匿名投票の合計）
        $authSupportVotes = \App\Models\TopicVote::where('topic_id', $topic->id)
            ->where('stance', 'support')
            ->count();
        $authOpposeVotes = \App\Models\TopicVote::where('topic_id', $topic->id)
            ->where('stance', 'oppose')
            ->count();
            
        $anonSupportVotes = AnonymousVote::where('topic_id', $topic->id)
            ->where('stance', 'support')
            ->count();
        $anonOpposeVotes = AnonymousVote::where('topic_id', $topic->id)
            ->where('stance', 'oppose')
            ->count();
            
        $supportVotes = $authSupportVotes + $anonSupportVotes;
        $opposeVotes = $authOpposeVotes + $anonOpposeVotes;
        $totalVotes = $supportVotes + $opposeVotes;
        $supportPercentage = $totalVotes > 0 ? round(($supportVotes / $totalVotes) * 100) : 0;
        $opposePercentage = $totalVotes > 0 ? round(($opposeVotes / $totalVotes) * 100) : 0;

        // ユーザーの投票情報を取得（コメント用）
        $userVotes = \App\Models\TopicVote::where('topic_id', $topic->id)
            ->get()
            ->keyBy('user_id');

        // 現在のユーザーの投票状況を取得
        $currentUserVote = null;
        if ($request->user()) {
            $currentUserVote = \App\Models\TopicVote::where('topic_id', $topic->id)
                ->where('user_id', $request->user()->id)
                ->first();
        } else {
            // 匿名ユーザーの場合はセッションベースで投票状況を確認
            $sessionId = $request->session()->getId();
            $ipAddress = $request->ip();
            $anonymousVote = AnonymousVote::getVoteBySessionOrIp($topic->id, $sessionId, $ipAddress);
            if ($anonymousVote) {
                $currentUserVote = (object)['stance' => $anonymousVote->stance];
            }
        }

        // 現在のユーザーがこの議題を保存しているかチェック
        $isSaved = false;
        if ($request->user()) {
            $isSaved = \App\Models\SavedTopic::where('user_id', $request->user()->id)
                ->where('topic_id', $topic->id)
                ->exists();
        }

        // データベースからコメントを取得（階層構造で）
        $dbComments = Comment::with(['user', 'allChildren.user'])
            ->where('topic_id', $topic->id)
            ->whereNull('parent_id') // トップレベルのコメントのみ
            ->orderBy('score', 'desc')
            ->get();

        // 実際のコメント数を計算（すべてのコメント：親 + 子コメント）
        $totalCommentsCount = Comment::where('topic_id', $topic->id)->count();

        // フロントエンド用の形式に変換
        $comments = $dbComments->map(function ($comment) use ($userVotes) {
            return $this->formatCommentForFrontend($comment, $userVotes);
        })->values()->toArray();

        // 同じコミュニティの他の議題を取得（切り替え用）
        $relatedTopics = Topic::with(['user', 'community'])
            ->where('community_id', $topic->community_id)
            ->where('status', 'active')
            ->where('id', '!=', $topic->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($relatedTopic) {
                return [
                    'id' => $relatedTopic->id,
                    'title' => $relatedTopic->title,
                    'author' => [
                        'username' => $relatedTopic->user->name,
                    ],
                    'created_at' => $relatedTopic->created_at->toISOString(),
                    'score' => $relatedTopic->score
                ];
            });

        return Inertia::render('post/show', [
            'post' => [
                'id' => $topic->id,
                'subreddit' => $topic->community->name,
                'subreddit_icon' => $topic->community->icon ?? '📱',
                'title' => $topic->title,
                'content' => $topic->content,
                'type' => $topic->type ?? 'text',
                'author' => [
                    'username' => $topic->user->name,
                    'karma' => 1500,
                    'cake_day' => $topic->user->created_at->format('Y-m-d')
                ],
                'votes' => [
                    'upvotes' => $topic->upvotes,
                    'downvotes' => $topic->downvotes,
                    'score' => $topic->score
                ],
                'comments_count' => $totalCommentsCount,
                'awards' => [],
                'created_at' => $topic->created_at->toISOString(),
                'url' => $topic->url,
                'image_url' => $topic->image_url,
                'is_nsfw' => $topic->is_nsfw ?? false,
                'is_spoiler' => $topic->is_spoiler ?? false,
                'flair' => $topic->flair,
                'gilded' => 0,
                'saved' => false,
                'hidden' => false
            ],
            'voting_results' => [
                'support_votes' => $supportVotes,
                'oppose_votes' => $opposeVotes,
                'total_votes' => $totalVotes,
                'support_percentage' => $supportPercentage,
                'oppose_percentage' => $opposePercentage
            ],
            'current_user_vote' => $currentUserVote ? $currentUserVote->stance : null,
            'isSaved' => $isSaved,
            'comments' => $comments,
            'related_topics' => $relatedTopics,
            'community' => [
                'id' => $topic->community->id,
                'name' => $topic->community->name,
                'slug' => $topic->community->slug,
                'icon' => $topic->community->icon ?? '📱',
                'members' => $topic->community->getMembersFormatted(),
                'online' => $topic->community->getOnlineFormatted(),
                'description' => $topic->community->description,
                'rules' => $topic->community->rules,
                'moderators' => $topic->community->moderators,
                'created_at' => $topic->community->created_at->format('Y年m月d日'),
                'is_member' => $request->user() ? CommunityMembership::where('user_id', $request->user()->id)
                    ->where('community_id', $topic->community->id)
                    ->exists() : false
            ],
            'user' => $request->user()
        ]);
    }

    public function community(Request $request, $slug)
    {
        $sort = $request->get('sort', 'hot');

        // データベースからコミュニティを取得
        $community = Community::where('slug', $slug)->first();
        if (!$community) {
            abort(404);
        }

        // フロントエンド用の形式に変換
        $community_data = [
            'id' => $community->id,
            'name' => $community->name,
            'icon' => $community->icon,
            'slug' => $community->slug,
            'members' => $community->getMembersFormatted(),
            'online' => $community->getOnlineFormatted(),
            'description' => $community->description,
            'created_at' => $community->created_at->format('Y年m月d日'),
            'rules' => $community->rules,
            'moderators' => $community->moderators,
            'banner_color' => $community->banner_color,
            'is_member' => $request->user() ? CommunityMembership::where('user_id', $request->user()->id)
                ->where('community_id', $community->id)
                ->exists() : false
        ];

        // そのコミュニティの投稿をデータベースから取得
        $query = Topic::with(['user', 'community'])
            ->where('community_id', $community->id)
            ->where('status', 'active');

        // ソート
        switch ($sort) {
            case 'new':
                $query->orderBy('created_at', 'desc');
                break;
            case 'top':
                $query->orderBy('score', 'desc');
                break;
            case 'hot':
            default:
                $query->orderByRaw('(score + comments_count * 0.5) / (JULIANDAY("now") - JULIANDAY(created_at) + 1) DESC');
                break;
        }

        $topics = $query->get();

        // 全ての投稿の実際のコメント数を効率的に取得
        $topicIds = $topics->pluck('id');
        $commentsCount = Comment::whereIn('topic_id', $topicIds)
            ->selectRaw('topic_id, COUNT(*) as comments_count')
            ->groupBy('topic_id')
            ->pluck('comments_count', 'topic_id');

        // フロントエンド用のデータ形式に変換
        $posts = $topics->map(function ($topic) use ($commentsCount) {
            return [
                'id' => $topic->id,
                'subreddit' => $topic->community->name,
                'subreddit_slug' => $topic->community->slug,
                'title' => $topic->title,
                'content' => $topic->content,
                'author' => [
                    'username' => $topic->user->name,
                    'karma' => rand(1000, 5000)
                ],
                'votes' => ['score' => $topic->score],
                'comments_count' => $commentsCount[$topic->id] ?? 0,
                'created_at' => $topic->created_at,
                'flair' => $topic->flair
            ];
        });

        return Inertia::render('community/show', [
            'community' => $community_data,
            'posts' => $posts->values(),
            'current_sort' => $sort,
            'user' => $request->user()
        ]);
    }

    /**
     * コメントを投稿する
     */
    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:10000',
            'parent_id' => 'nullable|integer|exists:comments,id'
        ]);

        // 投稿が存在するかチェック
        $topic = Topic::find($id);
        if (!$topic) {
            return response()->json(['error' => '投稿が見つかりません'], 404);
        }

        // ユーザーの投票状況を取得
        $userVote = \App\Models\TopicVote::where('topic_id', $topic->id)
            ->where('user_id', $request->user()->id)
            ->first();

        $stance = $userVote ? $userVote->stance : 'support'; // デフォルトは賛成

        // コメントを作成
        $comment = Comment::create([
            'topic_id' => $topic->id,
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
            'stance' => $stance,
            'upvotes' => 0,
            'downvotes' => 0,
            'score' => 0
        ]);

        // 作成されたコメントをリロードして関連データを取得
        $comment->load(['user', 'allChildren.user']);

        // ユーザー投票データを構築
        $userVotes = collect([$comment->user_id => (object)['stance' => $stance]]);

        // フロントエンド用の形式で返す
        return response()->json($this->formatCommentForFrontend($comment, $userVotes));
    }

    /**
     * コメントをフロントエンド用の形式にフォーマット
     */
    private function formatCommentForFrontend(Comment $comment, $userVotes = null): array
    {
        // ユーザーの投票状況を取得
        $userVote = $userVotes ? $userVotes->get($comment->user_id) : null;
        $stance = $userVote ? $userVote->stance : null;
        
        return [
            'id' => $comment->id,
            'author' => [
                'username' => $comment->user->name,
                'karma' => 1500, // 仮の値
                'cake_day' => $comment->user->created_at->format('Y-m-d'),
                'stance' => $stance // 賛成/反対の立場を追加
            ],
            'content' => $comment->content,
            'votes' => [
                'upvotes' => $comment->upvotes,
                'downvotes' => $comment->downvotes,
                'score' => $comment->score
            ],
            'created_at' => $comment->created_at->toISOString(),
            'awards' => [],
            'gilded' => 0,
            'replies' => $comment->allChildren->map(function ($reply) use ($userVotes) {
                return $this->formatCommentForFrontend($reply, $userVotes);
            })->values()->toArray()
        ];
    }
}
