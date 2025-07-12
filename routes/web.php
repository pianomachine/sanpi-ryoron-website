<?php

use App\Http\Controllers\TopicController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CommunityController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

// メインアプリ - トップページを/homeにリダイレクト
Route::get('/', function () {
    return redirect('/welcome');
});

// Welcomeページ
Route::get('/welcome', function () {
    return Inertia::render('welcome');
})->name('welcome');

// メインアプリのルート
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/post/{id}', [HomeController::class, 'show'])->name('post.show');
Route::get('/community/{slug}', [HomeController::class, 'community'])->name('community.show');
Route::get('/search', function() {
    $query = request('q', '');
    $sort = request('sort', 'hot');
    
    return \Inertia\Inertia::render('search', [
        'query' => $query,
        'posts' => [],
        'total_results' => 0,
        'current_sort' => $sort
    ]);
})->name('search');

// 無限スクロール用のAPIエンドポイントを追加
Route::get('/api/posts', [HomeController::class, 'getPosts'])->name('api.posts');
Route::get('/api/community/{slug}/posts', [HomeController::class, 'getCommunityPosts'])->name('api.community.posts');

// 検索用APIエンドポイント
Route::get('/api/search', [HomeController::class, 'search'])->name('api.search');

// 投票機能（匿名投票対応）
Route::post('/topics/{topic}/vote', [TopicController::class, 'voteTopic'])->name('topics.vote');

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    
    // 議題投稿機能（旧システムとの互換性維持）
    Route::post('/topics', [TopicController::class, 'store'])->name('topics.store');
    
    // コミュニティ管理
    Route::get('/api/communities', [CommunityController::class, 'index'])->name('communities.index');
    Route::post('/api/communities', [CommunityController::class, 'store'])->name('communities.store');
    Route::get('/api/communities/check-name', [CommunityController::class, 'checkName'])->name('communities.check-name');
    
    // コミュニティメンバーシップ
    Route::post('/api/communities/{community}/join', [CommunityController::class, 'join'])->name('communities.join');
    Route::delete('/api/communities/{community}/leave', [CommunityController::class, 'leave'])->name('communities.leave');
    Route::get('/api/communities/{community}/membership', [CommunityController::class, 'checkMembership'])->name('communities.membership');
    
    // 投票機能
    // 匿名投票を許可するため認証不要に移動
    
    // コメント機能
    Route::post('/posts/{id}/comments', [HomeController::class, 'storeComment'])->name('posts.comments.store');
    
    // 議題の保存・削除機能
    Route::post('/topics/{topic}/save', [TopicController::class, 'saveTopic'])->name('topics.save');
    Route::delete('/topics/{topic}/save', [TopicController::class, 'unsaveTopic'])->name('topics.unsave');
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');
    
    // プロフィール関連API
    Route::get('/profile/topics', [TopicController::class, 'getUserTopics'])->name('profile.topics');
    Route::get('/profile/saved', [TopicController::class, 'getSavedTopics'])->name('profile.saved');

    Route::get('/debug/popular-posts-calculation', function () {
        $todayStart = now()->startOfDay();
        $todayTopics = \App\Models\Topic::where('created_at', '>=', $todayStart)
            ->where('status', 'active')
            ->count();
            
        $searchStart = $todayTopics > 0 ? $todayStart : now()->subDays(3);
        
        $topics = \App\Models\Topic::with(['community', 'user'])
            ->where('created_at', '>=', $searchStart)
            ->where('status', 'active')
            ->get();
            
        $calculations = $topics->map(function ($topic) {
            $actualCommentsCount = \App\Models\Comment::where('topic_id', $topic->id)->count();
            $uniqueCommenters = \App\Models\Comment::where('topic_id', $topic->id)
                ->distinct('user_id')
                ->count('user_id');
            $authVotes = \App\Models\TopicVote::where('topic_id', $topic->id)->count();
            $anonVotes = \App\Models\AnonymousVote::where('topic_id', $topic->id)->count();
            $totalVotes = $authVotes + $anonVotes;
            
            // 賛成票のみを集計（認証済み + 匿名）
            $authSupportVotes = \App\Models\TopicVote::where('topic_id', $topic->id)
                ->where('stance', 'support')
                ->count();
            $anonSupportVotes = \App\Models\AnonymousVote::where('topic_id', $topic->id)
                ->where('stance', 'support')
                ->count();
            $totalSupportVotes = $authSupportVotes + $anonSupportVotes;
            
            $baseScore = $topic->score ?? 0;
            $commentScore = $actualCommentsCount * 1;
            $uniqueCommenterScore = $uniqueCommenters * 3;
            $voteScore = $totalVotes * 1;
            $popularityScore = $baseScore + $commentScore + $uniqueCommenterScore + $voteScore;
            
            return [
                'topic_id' => $topic->id,
                'title' => $topic->title,
                'base_score' => $baseScore,
                'total_comments' => $actualCommentsCount,
                'unique_commenters' => $uniqueCommenters,
                'total_votes' => $totalVotes,
                'support_votes' => $totalSupportVotes,
                'oppose_votes' => $totalVotes - $totalSupportVotes,
                'displayed_score' => $totalSupportVotes, // フロントエンドに表示される「○賛成票」の数値
                'created_at' => $topic->created_at->toISOString(),
                'calculation' => [
                    'base_score' => $baseScore,
                    'comment_score' => "{$actualCommentsCount} × 1 = {$commentScore}",
                    'unique_commenter_score' => "{$uniqueCommenters} × 3 = {$uniqueCommenterScore}",
                    'vote_score' => "{$totalVotes} × 1 = {$voteScore}",
                    'total_popularity_score' => $popularityScore
                ],
                'formula' => "{$baseScore} + ({$actualCommentsCount} × 1) + ({$uniqueCommenters} × 3) + ({$totalVotes} × 1) = {$popularityScore}"
            ];
        })->sortByDesc('calculation.total_popularity_score');
        
        return response()->json([
            'search_period' => $todayTopics > 0 ? 'today' : 'last_3_days',
            'search_start' => $searchStart->toISOString(),
            'total_topics_found' => $topics->count(),
            'calculations' => $calculations->values(),
            'ranking_issue_analysis' => [
                'note' => '同じ賛成票数でも、ユニークコメンター数(×3)と総投票数が順位に大きく影響します',
                'factors' => [
                    'ユニークコメンター数' => '重み3倍',
                    '総コメント数' => '重み1倍', 
                    '総投票数（賛成+反対）' => '重み1倍',
                    '作成日時' => '同スコア時の暗黙ソート'
                ]
            ],
            'explanation' => [
                'new_formula' => 'popularity_score = base_score + (comments × 1) + (unique_commenters × 3) + (votes × 1)',
                'improvement' => '同じ人の連続コメントを防ぐため、ユニークコメンター数により高い重みを付与',
                'score_display_fix' => 'フロントエンドの「○賛成票」表示を実際の賛成投票数（認証済み+匿名）に修正',
                'benefits' => [
                    '多様な参加者がいる議論が上位にランク',
                    '同じ人の連続投稿による不正な人気度上昇を防止',
                    'より公平で健全な議論評価',
                    '正確な賛成票数の表示'
                ]
            ]
        ]);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// 管理者用シーダー実行ルート（本番環境でのサンプルデータ作成用）
Route::get('/admin/seed', function () {
    if (config('app.env') === 'production') {
        // 本番環境では簡単な認証チェック
        $expectedPassword = env('ADMIN_SEED_PASSWORD', 'sanpi-ryoron-2024');
        $providedPassword = request('password');
        
        if ($providedPassword !== $expectedPassword) {
            return response('認証が必要です。パスワードを確認してください。', 401);
        }
    }
    
    try {
        // シーダー実行
        \Artisan::call('db:seed');
        $output = \Artisan::output();
        
        return response()->json([
            'status' => 'success',
            'message' => 'シーダーの実行が完了しました！',
            'output' => $output,
            'timestamp' => now()->toDateTimeString()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error', 
            'message' => 'シーダーの実行中にエラーが発生しました：' . $e->getMessage(),
            'timestamp' => now()->toDateTimeString()
        ], 500);
    }
});

Route::get('/admin/seed-status', function () {
    try {
        $topicCount = \App\Models\Topic::count();
        $communityCount = \App\Models\Community::count();
        $userCount = \App\Models\User::count();
        
        return response()->json([
            'topics' => $topicCount,
            'communities' => $communityCount, 
            'users' => $userCount,
            'has_sample_data' => $topicCount > 0 && $communityCount > 0,
            'timestamp' => now()->toDateTimeString()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});
