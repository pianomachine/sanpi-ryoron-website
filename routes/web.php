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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// デプロイ確認用テストルート
Route::get('/test-deploy', function () {
    return response()->json([
        'status' => 'deployed',
        'timestamp' => now()->toDateTimeString(),
        'version' => 'v2024-01-20'
    ]);
});

// サイドバーデータのデバッグ用ルート
Route::get('/debug/sidebar-data', function () {
    try {
        // 基本データ確認
        $topicCount = \App\Models\Topic::count();
        $communityCount = \App\Models\Community::count();
        $commentCount = \App\Models\Comment::count();
        $voteCount = \App\Models\TopicVote::count();
        $anonVoteCount = \App\Models\AnonymousVote::count();
        
        // 今日の投稿確認
        $todayStart = now()->startOfDay();
        $todayTopics = \App\Models\Topic::where('created_at', '>=', $todayStart)
            ->where('status', 'active')
            ->with(['community', 'user'])
            ->get();
        
        // 過去7日間の活動確認
        $sevenDaysAgo = now()->subDays(7);
        $recentActivity = \App\Models\Topic::where('created_at', '>=', $sevenDaysAgo)
            ->where('status', 'active')
            ->count();
        
        // コミュニティの活動度確認
        $communities = \App\Models\Community::all()->map(function($community) use ($sevenDaysAgo) {
            $recentTopics = \App\Models\Topic::where('community_id', $community->id)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->where('status', 'active')
                ->count();
                
            return [
                'id' => $community->id,
                'name' => $community->name,
                'members_count' => $community->members_count,
                'recent_topics' => $recentTopics
            ];
        });
        
        return response()->json([
            'database_counts' => [
                'topics' => $topicCount,
                'communities' => $communityCount,
                'comments' => $commentCount,
                'auth_votes' => $voteCount,
                'anonymous_votes' => $anonVoteCount
            ],
            'today_info' => [
                'today_start' => $todayStart->toDateTimeString(),
                'current_time' => now()->toDateTimeString(),
                'topics_today' => $todayTopics->count(),
                'today_topics_list' => $todayTopics->map(function($topic) {
                    return [
                        'id' => $topic->id,
                        'title' => $topic->title,
                        'created_at' => $topic->created_at->toDateTimeString(),
                        'score' => $topic->score,
                        'community' => $topic->community ? $topic->community->name : null
                    ];
                })
            ],
            'recent_activity' => [
                'seven_days_ago' => $sevenDaysAgo->toDateTimeString(),
                'topics_last_7_days' => $recentActivity
            ],
            'communities_activity' => $communities,
            'timezone' => config('app.timezone'),
            'db_connection' => config('database.default')
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

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
