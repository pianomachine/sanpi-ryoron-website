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
