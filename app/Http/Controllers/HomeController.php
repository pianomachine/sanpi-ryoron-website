<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Community;
use App\Models\User;
use App\Models\Comment;
use App\Models\CommunityMembership;
use App\Models\AnonymousVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $sort = $request->get('sort', 'hot');
            
            // トピックの取得
            $query = Topic::with(['user', 'community'])
                ->where('status', '=', 'active')
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

                        // PostgreSQL対応のホットソート - サブクエリを1つにまとめる
                        $query->where('status','active')
                              ->whereNotNull('community_id')
                              ->orderBy('hot_score','desc')
                              ->orderBy('topics.id','desc');

                        \Log::info('Hot sort query: ' . $query->toSql());
                        \Log::info('Hot sort bindings: ' . json_encode($query->getBindings()));
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
            \Log::info('Topics found: ' . $allTopics->count());
            \Log::info('First topic data: ' . json_encode($allTopics->first()));
            
            // データが存在しない場合は空の状態を表示
            if ($allTopics->isEmpty()) {
                \Log::warning('No topics found');
                return $this->renderEmptyState($request, $sort);
            }
            
            // データ変換処理（プレミアムプロモーション無し）
            $posts = $allTopics->map(function ($topic) {
                $formattedTopic = $this->formatTopicForApi($topic);
                \Log::info('Formatted topic: ' . json_encode([
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'community' => $topic->community ? $topic->community->name : 'Unknown',
                    'popularity_score' => $topic->popularity_score
                ]));
                return $formattedTopic;
            })->filter();  // nullを除外
            
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
            
            $response = [
                'posts' => $posts->values(),
                'all_topics' => $posts->values(),
                'current_sort' => $sort,
                'trending_communities' => $trending_communities,
                'popular_posts_today' => $popular_posts_today,
                'user' => $request->user()
            ];
            \Log::info('Response data structure: ' . json_encode(array_keys($response)));
            
            return Inertia::render('home/index', $response);

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
            
            // トピックIDのみ取得して、後でバッチクエリを実行
            $topicIds = Topic::where('created_at', '>=', $searchStart)
                ->where('status', 'active')
                ->pluck('id');
            
            // バッチでカウントを取得
            $commentCounts = Comment::whereIn('topic_id', $topicIds)
                ->groupBy('topic_id')
                ->selectRaw('topic_id, COUNT(*) as count')
                ->pluck('count', 'topic_id');
            
            $uniqueCommenterCounts = Comment::whereIn('topic_id', $topicIds)
                ->groupBy('topic_id')
                ->selectRaw('topic_id, COUNT(DISTINCT user_id) as count')
                ->pluck('count', 'topic_id');
            
            $authVoteCounts = \App\Models\TopicVote::whereIn('topic_id', $topicIds)
                ->groupBy('topic_id')
                ->selectRaw('topic_id, COUNT(*) as count')
                ->pluck('count', 'topic_id');
            
            $anonVoteCounts = AnonymousVote::whereIn('topic_id', $topicIds)
                ->groupBy('topic_id')
                ->selectRaw('topic_id, COUNT(*) as count')
                ->pluck('count', 'topic_id');
            
            $authSupportVoteCounts = \App\Models\TopicVote::whereIn('topic_id', $topicIds)
                ->where('stance', 'support')
                ->groupBy('topic_id')
                ->selectRaw('topic_id, COUNT(*) as count')
                ->pluck('count', 'topic_id');
            
            $anonSupportVoteCounts = AnonymousVote::whereIn('topic_id', $topicIds)
                ->where('stance', 'support')
                ->groupBy('topic_id')
                ->selectRaw('topic_id, COUNT(*) as count')
                ->pluck('count', 'topic_id');
            
            // トピックを取得（関連データも含む）
            $topics = Topic::with(['community', 'user'])
                ->whereIn('id', $topicIds)
                ->get();
            
            // PHPで人気度を計算
            $topicsWithScore = $topics->map(function ($topic) use (
                $commentCounts, 
                $uniqueCommenterCounts, 
                $authVoteCounts, 
                $anonVoteCounts,
                $authSupportVoteCounts,
                $anonSupportVoteCounts
            ) {
                $topicId = $topic->id;
                
                // 事前に取得したカウントを使用
                $actualCommentsCount = $commentCounts->get($topicId, 0);
                $uniqueCommenters = $uniqueCommenterCounts->get($topicId, 0);
                $authVotes = $authVoteCounts->get($topicId, 0);
                $anonVotes = $anonVoteCounts->get($topicId, 0);
                $totalVotes = $authVotes + $anonVotes;
                $authSupportVotes = $authSupportVoteCounts->get($topicId, 0);
                $anonSupportVotes = $anonSupportVoteCounts->get($topicId, 0);
                $totalSupportVotes = $authSupportVotes + $anonSupportVotes;
                
                // 改良された人気度スコア計算
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

                        // PostgreSQL対応のホットソート - サブクエリを1つにまとめる
                        $query->where('status','active')
                              ->where('community_id','!=',null)
                              ->orderBy('hot_score','desc')
                              ->orderBy('topics.id','desc');
                    } catch (\Exception $e) {
                        \Log::error('HomeController error: ' . $e->getMessage());
                        $query->orderBy('created_at', 'desc');
                    }
                    break;
                
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // 合計件数を先に取得
            $totalCount = $query->count();

            // ページネーション
            $topics = $query->limit($perPage)->offset($offset)->get();

            // データ変換処理
            $posts = $topics->map(fn($topic) => $this->formatTopicForApi($topic));

            return response()->json([
                'posts' => $posts->values(),
                'has_more' => ($offset + $perPage) < $totalCount,
                'current_page' => $page,
                'total_pages' => ceil($totalCount / $perPage),
                'total_count' => $totalCount
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
                    $query->orderBy('hot_score','desc')
                          ->orderBy('topics.id','desc');
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
            // キャッシュキーを生成
            $cacheKey = "topic_stats_{$topic->id}";
            
            // キャッシュから統計情報を取得（5分間キャッシュ）
            $stats = Cache::remember($cacheKey, 300, function () use ($topic) {
                $authSupportVotes = 0;
                $authOpposeVotes = 0;
                $anonSupportVotes = 0;
                $anonOpposeVotes = 0;
                $commentsCount = 0;

                try {
                    $authVotes = \App\Models\TopicVote::where('topic_id', $topic->id)
                        ->groupBy('stance')
                        ->selectRaw('stance, COUNT(*) as count')
                        ->pluck('count', 'stance');
                    
                    $authSupportVotes = $authVotes->get('support', 0);
                    $authOpposeVotes = $authVotes->get('oppose', 0);
                } catch (\Exception $e) {
                    \Log::warning('Failed to get TopicVote data: ' . $e->getMessage());
                }

                try {
                    $anonVotes = \App\Models\AnonymousVote::where('topic_id', $topic->id)
                        ->groupBy('stance')
                        ->selectRaw('stance, COUNT(*) as count')
                        ->pluck('count', 'stance');
                    
                    $anonSupportVotes = $anonVotes->get('support', 0);
                    $anonOpposeVotes = $anonVotes->get('oppose', 0);
                } catch (\Exception $e) {
                    \Log::warning('Failed to get AnonymousVote data: ' . $e->getMessage());
                }

                try {
                    $commentsCount = \App\Models\Comment::where('topic_id', $topic->id)->count();
                } catch (\Exception $e) {
                    \Log::warning('Failed to get Comment data: ' . $e->getMessage());
                }
                
                return [
                    'authSupportVotes' => $authSupportVotes,
                    'authOpposeVotes' => $authOpposeVotes,
                    'anonSupportVotes' => $anonSupportVotes,
                    'anonOpposeVotes' => $anonOpposeVotes,
                    'commentsCount' => $commentsCount
                ];
            });
            
            $supportVotes = $stats['authSupportVotes'] + $stats['anonSupportVotes'];
            $opposeVotes = $stats['authOpposeVotes'] + $stats['anonOpposeVotes'];

            $formatted = [
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
                'comments_count' => $stats['commentsCount'],
                'awards' => [],
                'created_at' => $topic->created_at,
                'url' => $topic->url ?? '',
                'image_url' => $topic->image_url ?? '',
                'is_nsfw' => $topic->is_nsfw ?? false,
                'is_spoiler' => $topic->is_spoiler ?? false,
                'flair' => $topic->flair ?? '',
                'popularity_score' => $topic->popularity_score ?? 0
            ];

            \Log::info('Formatted topic data: ' . json_encode([
                'id' => $topic->id,
                'title' => $topic->title,
                'votes' => [
                    'support' => $supportVotes,
                    'oppose' => $opposeVotes
                ],
                'comments' => $commentsCount
            ]));

            return $formatted;
        } catch (\Exception $e) {
            \Log::error('Error in formatTopicForApi: ' . $e->getMessage());
            return null;
        }
    }
}
