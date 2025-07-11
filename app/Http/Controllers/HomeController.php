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
                case 'hot':
                    try {
                        // テーブルの存在確認
                        if (!\Schema::hasTable('topic_votes') || 
                            !\Schema::hasTable('anonymous_votes') || 
                            !\Schema::hasTable('comments')) {
                            \Log::error('Required tables do not exist for hot sorting');
                            $query->orderBy('created_at', 'desc');
                            break;
                        }

                        // PostgreSQL対応のホットソート
                        $query->select('topics.*')
                            ->selectSub(
                                function($query) {
                                    $query->selectRaw('COUNT(*)')
                                        ->from('topic_votes')
                                        ->whereColumn('topic_votes.topic_id', 'topics.id')
                                        ->where('stance', 'support');
                                },
                                'auth_support_votes'
                            )
                            ->selectSub(
                                function($query) {
                                    $query->selectRaw('COUNT(*)')
                                        ->from('anonymous_votes')
                                        ->whereColumn('anonymous_votes.topic_id', 'topics.id')
                                        ->where('stance', 'support');
                                },
                                'anon_support_votes'
                            )
                            ->selectSub(
                                function($query) {
                                    $query->selectRaw('COUNT(*)')
                                        ->from('comments')
                                        ->whereColumn('comments.topic_id', 'topics.id');
                                },
                                'comment_count'
                            )
                            ->selectSub(
                                function($query) {
                                    $query->selectRaw('COUNT(DISTINCT user_id)')
                                        ->from('comments')
                                        ->whereColumn('comments.topic_id', 'topics.id');
                                },
                                'unique_commenter_count'
                            )
                            ->selectRaw('
                                COALESCE(topics.score, 0) + 
                                COALESCE(auth_support_votes, 0) + 
                                COALESCE(anon_support_votes, 0) + 
                                COALESCE(comment_count, 0) + 
                                (COALESCE(unique_commenter_count, 0) * 3) as popularity_score
                            ')
                            ->orderBy('popularity_score', 'desc');
                    } catch (\Exception $e) {
                        \Log::error('HomeController error: ' . $e->getMessage());
                        $query->orderBy('created_at', 'desc');
                    }
                    break;
                case 'rising':
                    // 上昇中：最近24時間で人気が上昇している議題
                    $query->where('created_at', '>=', now()->subHours(24))
                          ->withCount(['votes as recent_votes', 'comments as recent_comments'])
                          ->orderBy('recent_votes', 'desc')
                          ->orderBy('recent_comments', 'desc');
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
            
            // データ変換処理（プレミアムプロモーション無し）
            $posts = $allTopics->map(function ($topic) {
                return $this->formatTopicForApi($topic);
            });
            
            // サイドバーデータの取得
            $trending_communities = [];
            $popular_posts_today = [];
            
            try {
                // 人気の議論カテゴリ（活動度ベースで算出）
                $trending_communities = $this->getTrendingCommunities();
                
                // 本日の人気議題（今日の投稿の中でスコアが高いもの）
                $popular_posts_today = $this->getPopularPostsToday();
                
            } catch (\Exception $e) {
                \Log::error('Error getting sidebar data: ' . $e->getMessage());
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
                // PostgreSQL対応のホットソート
                if (config('database.default') === 'pgsql') {
                    $query->orderByRaw('(score + comments_count * 0.5) / (EXTRACT(EPOCH FROM (now() - created_at)) / 3600 + 1) DESC');
                } else {
                    // SQLite用
                    $query->orderByRaw('(score + comments_count * 0.5) / (JULIANDAY("now") - JULIANDAY(created_at) + 1) DESC');
                }
                break;
        }

        $topics = $query->get();

        // フロントエンド用のデータ形式に変換
        $posts = $topics->take(20)->map(function ($topic) {
            return $this->formatTopicForApi($topic);
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

    /**
     * 人気の議論カテゴリを取得（活動度ベースで算出）
     */
    private function getTrendingCommunities()
    {
        try {
            // テーブルの存在確認
            if (!\Schema::hasTable('communities') || 
                !\Schema::hasTable('topics') || 
                !\Schema::hasTable('comments')) {
                \Log::error('Required tables do not exist for trending communities');
                return collect([]);
            }

            $since = now()->subDays(7);

            return \App\Models\Community::select('communities.*')
                ->addSelect(\DB::raw('(
                    SELECT COALESCE(COUNT(*), 0)
                    FROM topics
                    WHERE topics.community_id = communities.id
                    AND topics.created_at >= \'' . $since->toDateTimeString() . '\'
                ) as recent_topics_count'))
                ->addSelect(\DB::raw('(
                    SELECT COALESCE(COUNT(*), 0)
                    FROM comments
                    INNER JOIN topics ON topics.id = comments.topic_id
                    WHERE topics.community_id = communities.id
                    AND comments.created_at >= \'' . $since->toDateTimeString() . '\'
                ) as recent_comments_count'))
                ->whereRaw('(
                    SELECT COUNT(*)
                    FROM topics
                    WHERE topics.community_id = communities.id
                    AND topics.created_at >= \'' . $since->toDateTimeString() . '\'
                ) > 0 OR (
                    SELECT COUNT(*)
                    FROM comments
                    INNER JOIN topics ON topics.id = comments.topic_id
                    WHERE topics.community_id = communities.id
                    AND comments.created_at >= \'' . $since->toDateTimeString() . '\'
                ) > 0')
                ->orderByDesc('recent_topics_count')
                ->orderByDesc('recent_comments_count')
                ->limit(5)
                ->get();

        } catch (\Exception $e) {
            \Log::error('Error in getTrendingCommunities: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * 本日の人気議題を取得（今日の投稿の中でスコアが高いもの）
     */
    private function getPopularPostsToday()
    {
        try {
            // 今日の投稿をまず確認
            $todayStart = now()->startOfDay();
            $todayTopics = Topic::where('created_at', '>=', $todayStart)
                ->where('status', 'active')
                ->count();
            
            // 今日の投稿がない場合は過去3日間に拡張
            $searchStart = $todayTopics > 0 ? $todayStart : now()->subDays(3);
            
            $topics = Topic::with(['community', 'user'])
                ->where('created_at', '>=', $searchStart)
                ->where('status', 'active')
                ->get();
            
            // PHPで人気度を計算
            $topicsWithScore = $topics->map(function ($topic) {
                // 実際のコメント数を取得
                $actualCommentsCount = Comment::where('topic_id', $topic->id)->count();
                
                // ユニークコメンター数を取得（同じ人が何回コメントしても1人としてカウント）
                $uniqueCommenters = Comment::where('topic_id', $topic->id)
                    ->distinct('user_id')
                    ->count('user_id');
                
                // 実際の投票数を取得（認証済み + 匿名）
                $authVotes = \App\Models\TopicVote::where('topic_id', $topic->id)->count();
                $anonVotes = AnonymousVote::where('topic_id', $topic->id)->count();
                $totalVotes = $authVotes + $anonVotes;
                
                // 賛成票のみを集計（認証済み + 匿名）
                $authSupportVotes = \App\Models\TopicVote::where('topic_id', $topic->id)
                    ->where('stance', 'support')
                    ->count();
                $anonSupportVotes = AnonymousVote::where('topic_id', $topic->id)
                    ->where('stance', 'support')
                    ->count();
                $totalSupportVotes = $authSupportVotes + $anonSupportVotes;
                
                // 改良された人気度スコア計算
                // ベーススコア + (コメント数 × 1) + (ユニークコメンター数 × 3) + (投票数 × 1)
                // ユニークコメンター数により高い重みを付けることで、同じ人の連続コメントを防ぐ
                $popularityScore = ($topic->score ?? 0) + 
                                 ($actualCommentsCount * 1) + 
                                 ($uniqueCommenters * 3) + 
                                 ($totalVotes * 1);
                
                return [
                    'topic' => $topic,
                    'actual_comments_count' => $actualCommentsCount,
                    'unique_commenters' => $uniqueCommenters,
                    'total_votes' => $totalVotes,
                    'total_support_votes' => $totalSupportVotes,
                    'popularity_score' => $popularityScore
                ];
            });
            
            // 人気度順でソートして上位5件を返す
            return $topicsWithScore
                ->sortByDesc('popularity_score')
                ->take(5)
                ->map(function ($item) {
                    $topic = $item['topic'];
                    return [
                        'id' => $topic->id,
                        'title' => $topic->title,
                        'subreddit' => $topic->community ? $topic->community->name : 'Unknown',
                        'subreddit_slug' => $topic->community ? ($topic->community->slug ?? 'unknown') : 'unknown',
                        'score' => $item['total_support_votes'], // 実際の賛成票数を表示
                        'comments_count' => $item['actual_comments_count'],
                        'unique_commenters' => $item['unique_commenters'],
                        'votes_count' => $item['total_votes'],
                        'author' => [
                            'username' => $topic->user ? $topic->user->name : 'Anonymous'
                        ],
                        'created_at' => $topic->created_at->format('m/d H:i'),
                        'popularity_score' => $item['popularity_score']
                    ];
                })
                ->values();
                
        } catch (\Exception $e) {
            \Log::error('Error in getPopularPostsToday: ' . $e->getMessage());
            
            // エラー時は空の配列を返す
            return collect([]);
        }
    }

    /**
     * 無限スクロール用：ホームページの投稿を取得
     */
    public function getPosts(Request $request)
    {
        try {
            $sort = $request->input('sort', 'hot');
            $page = (int) $request->input('page', 1);
            $perPage = 20;
            $offset = ($page - 1) * $perPage;
            
            // トピックの取得
            $query = Topic::with(['user', 'community'])
                ->select('topics.*');

            // ソート方法の適用
            switch ($sort) {
                case 'new':
                    $query->orderBy('created_at', 'desc');
                    break;
                
                case 'hot':
                    try {
                        // テーブルの存在確認
                        if (!\Schema::hasTable('topic_votes') || 
                            !\Schema::hasTable('anonymous_votes') || 
                            !\Schema::hasTable('comments')) {
                            \Log::error('Required tables do not exist for hot sorting');
                            $query->orderBy('created_at', 'desc');
                            break;
                        }

                        // PostgreSQL対応のホットソート
                        $query->addSelect(\DB::raw('(
                            SELECT COALESCE(COUNT(*), 0)
                            FROM topic_votes
                            WHERE topic_votes.topic_id = topics.id
                            AND topic_votes.stance = \'support\'
                        ) as auth_support_count'))
                        ->addSelect(\DB::raw('(
                            SELECT COALESCE(COUNT(*), 0)
                            FROM anonymous_votes
                            WHERE anonymous_votes.topic_id = topics.id
                            AND anonymous_votes.stance = \'support\'
                        ) as anon_support_count'))
                        ->addSelect(\DB::raw('(
                            SELECT COALESCE(COUNT(*), 0)
                            FROM comments
                            WHERE comments.topic_id = topics.id
                        ) as comment_count'))
                        ->addSelect(\DB::raw('(
                            SELECT COALESCE(COUNT(DISTINCT user_id), 0)
                            FROM comments
                            WHERE comments.topic_id = topics.id
                        ) as unique_commenter_count'))
                        ->addSelect(\DB::raw('(
                            COALESCE(topics.score, 0) + 
                            (SELECT COALESCE(COUNT(*), 0)
                             FROM topic_votes
                             WHERE topic_votes.topic_id = topics.id
                             AND topic_votes.stance = \'support\') +
                            (SELECT COALESCE(COUNT(*), 0)
                             FROM anonymous_votes
                             WHERE anonymous_votes.topic_id = topics.id
                             AND anonymous_votes.stance = \'support\') +
                            (SELECT COALESCE(COUNT(*), 0)
                             FROM comments
                             WHERE comments.topic_id = topics.id) +
                            ((SELECT COALESCE(COUNT(DISTINCT user_id), 0)
                              FROM comments
                              WHERE comments.topic_id = topics.id) * 3)
                        ) as popularity_score'))
                        ->where('status', 'active')
                        ->where('community_id', '!=', null)
                        ->orderBy('popularity_score', 'desc');
                    } catch (\Exception $e) {
                        \Log::error('HomeController error: ' . $e->getMessage());
                        $query->orderBy('created_at', 'desc');
                    }
                    break;
                
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $topics = $query->limit($perPage)->offset($offset)->get();
            
            return response()->json([
                'topics' => $topics,
                'has_more' => $topics->count() === $perPage
            ]);

        } catch (\Exception $e) {
            \Log::error('getPosts API error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * 無限スクロール用：コミュニティの投稿を取得
     */
    public function getCommunityPosts(Request $request, $slug)
    {
        try {
            $sort = $request->get('sort', 'hot');
            $page = (int) $request->get('page', 1);
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            // コミュニティを取得
            $community = Community::where('slug', $slug)->first();
            if (!$community) {
                return response()->json(['error' => 'コミュニティが見つかりません'], 404);
            }

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
                    // 人気度計算
                    $query->withCount([
                        'votes as total_votes',
                        'comments as total_comments',
                        'comments as unique_commenters' => function ($query) {
                            $query->distinct('user_id');
                        }
                    ])
                    ->selectRaw('
                        topics.*,
                        (
                            COALESCE((SELECT COUNT(*) FROM topic_votes WHERE topic_votes.topic_id = topics.id), 0) +
                            COALESCE((SELECT COUNT(*) FROM anonymous_votes WHERE anonymous_votes.topic_id = topics.id), 0) +
                            COALESCE((SELECT COUNT(*) FROM comments WHERE comments.topic_id = topics.id), 0) +
                            COALESCE((SELECT COUNT(DISTINCT user_id) FROM comments WHERE comments.topic_id = topics.id), 0) * 3 +
                            COALESCE(topics.score, 0)
                        ) as popularity_score
                    ')
                    ->orderBy('popularity_score', 'desc');
                    break;
            }

            // ページネーション処理
            $totalCount = $query->count();
            $topics = $query->offset($offset)->limit($perPage)->get();

            // データ変換処理
            $posts = $topics->map(function ($topic) {
                return $this->formatTopicForApi($topic);
            });

            return response()->json([
                'posts' => $posts->values(),
                'has_more' => ($offset + $perPage) < $totalCount,
                'current_page' => $page,
                'total_pages' => ceil($totalCount / $perPage),
                'total_count' => $totalCount
            ]);

        } catch (\Exception $e) {
            \Log::error('getCommunityPosts API error: ' . $e->getMessage());
            return response()->json([
                'posts' => [],
                'has_more' => false,
                'current_page' => 1,
                'total_pages' => 1,
                'total_count' => 0,
                'error' => 'データの取得に失敗しました'
            ], 500);
        }
    }

    /**
     * API用にトピックをフォーマット
     */
    private function formatTopicForApi($topic)
    {
        try {
            // 実際の投票数を計算（エラーハンドリング付き）
            $authSupportVotes = 0;
            $authOpposeVotes = 0;
            $anonSupportVotes = 0;
            $anonOpposeVotes = 0;
            $commentsCount = 0;

            try {
                $authSupportVotes = \App\Models\TopicVote::where('topic_id', $topic->id)
                    ->where('stance', 'support')
                    ->count();
                $authOpposeVotes = \App\Models\TopicVote::where('topic_id', $topic->id)
                    ->where('stance', 'oppose')
                    ->count();
            } catch (\Exception $e) {
                \Log::warning('Failed to get TopicVote data: ' . $e->getMessage());
            }

            try {
                $anonSupportVotes = \App\Models\AnonymousVote::where('topic_id', $topic->id)
                    ->where('stance', 'support')
                    ->count();
                $anonOpposeVotes = \App\Models\AnonymousVote::where('topic_id', $topic->id)
                    ->where('stance', 'oppose')
                    ->count();
            } catch (\Exception $e) {
                \Log::warning('Failed to get AnonymousVote data: ' . $e->getMessage());
            }

            try {
                $commentsCount = \App\Models\Comment::where('topic_id', $topic->id)->count();
            } catch (\Exception $e) {
                \Log::warning('Failed to get Comment data: ' . $e->getMessage());
            }
            
            $supportVotes = $authSupportVotes + $anonSupportVotes;
            $opposeVotes = $authOpposeVotes + $anonOpposeVotes;
            
            return [
                'id' => $topic->id,
                'subreddit' => $topic->community ? $topic->community->name : 'Unknown',
                'subreddit_icon' => $topic->community ? $topic->community->icon : '📝',
                'subreddit_slug' => $topic->community ? ($topic->community->slug ?? 'unknown') : 'unknown',
                'title' => $topic->title ?? 'No Title',
                'content' => $topic->content ?? '',
                'type' => $topic->type ?? 'text',
                'author' => [
                    'username' => $topic->user ? $topic->user->name : 'Anonymous',
                    'karma' => 1000,
                    'cake_day' => $topic->user ? $topic->user->created_at->format('Y-m-d') : date('Y-m-d')
                ],
                'votes' => [
                    'upvotes' => $supportVotes,
                    'downvotes' => $opposeVotes,
                    'score' => $supportVotes
                ],
                'comments_count' => $commentsCount,
                'awards' => [],
                'created_at' => $topic->created_at,
                'url' => $topic->url ?? '',
                'image_url' => $topic->image_url ?? '',
                'is_nsfw' => $topic->is_nsfw ?? false,
                'is_spoiler' => $topic->is_spoiler ?? false,
                'flair' => $topic->flair ?? '',
                'popularity_score' => $topic->popularity_score ?? 0
            ];
        } catch (\Exception $e) {
            \Log::error('Error in formatTopicForApi: ' . $e->getMessage());
            // フォールバック: 最小限のデータを返す
            return [
                'id' => $topic->id ?? 0,
                'subreddit' => 'Unknown',
                'subreddit_icon' => '📝',
                'subreddit_slug' => 'unknown',
                'title' => $topic->title ?? 'No Title',
                'content' => '',
                'type' => 'text',
                'author' => [
                    'username' => 'Anonymous',
                    'karma' => 1000,
                    'cake_day' => date('Y-m-d')
                ],
                'votes' => [
                    'upvotes' => 0,
                    'downvotes' => 0,
                    'score' => 0
                ],
                'comments_count' => 0,
                'awards' => [],
                'created_at' => $topic->created_at ?? now(),
                'url' => '',
                'image_url' => '',
                'is_nsfw' => false,
                'is_spoiler' => false,
                'flair' => '',
                'popularity_score' => 0
            ];
        }
    }
}
