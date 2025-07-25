<?php

use App\Http\Controllers\TopicController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CommunityController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

// 絵文字除去ヘルパー関数
function removeEmojis($text) {
    // 絵文字と記号を除去する正規表現
    $cleaned = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text); // emoticons
    $cleaned = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $cleaned); // misc symbols
    $cleaned = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $cleaned); // transport
    $cleaned = preg_replace('/[\x{1F1E0}-\x{1F1FF}]/u', '', $cleaned); // flags
    $cleaned = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $cleaned); // misc symbols
    $cleaned = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $cleaned); // dingbats
    $cleaned = preg_replace('/[\x{1F900}-\x{1F9FF}]/u', '', $cleaned); // supplemental symbols
    $cleaned = preg_replace('/[\x{1FA70}-\x{1FAFF}]/u', '', $cleaned); // symbols and pictographs extended-a
    
    // 余分な空白を削除
    $cleaned = preg_replace('/\s+/', ' ', $cleaned);
    $cleaned = trim($cleaned);
    
    return $cleaned;
}

// テキストを複数行に分割するヘルパー関数（日本語対応）
function wrapText($text, $fontPath, $fontSize, $maxWidth) {
    $lines = [];
    $currentLine = '';
    
    // 日本語テキストは文字単位で処理
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    foreach ($chars as $char) {
        $testLine = $currentLine . $char;
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $testLine);
        $textWidth = $bbox[4] - $bbox[0];
        
        if ($textWidth <= $maxWidth || $currentLine === '') {
            $currentLine = $testLine;
        } else {
            if ($currentLine !== '') {
                $lines[] = $currentLine;
            }
            $currentLine = $char;
        }
    }
    
    if ($currentLine !== '') {
        $lines[] = $currentLine;
    }
    
    return $lines;
}

// 最適なフォントサイズを計算するヘルパー関数
function calculateOptimalFontSize($text, $fontPath, $maxWidth, $maxHeight, $maxLines = 3) {
    $minSize = 20;
    $maxSize = 32;
    
    for ($size = $maxSize; $size >= $minSize; $size -= 2) {
        $lines = wrapText($text, $fontPath, $size, $maxWidth);
        
        if (count($lines) <= $maxLines) {
            // 高さもチェック
            $lineHeight = $size * 1.5; // 実際の描画と同じ行間隔を使用
            $totalHeight = count($lines) * $lineHeight;
            
            if ($totalHeight <= $maxHeight) {
                return [$size, $lines];
            }
        }
    }
    
    // 最小サイズでも収まらない場合は切り詰める
    $lines = wrapText($text, $fontPath, $minSize, $maxWidth);
    if (count($lines) > $maxLines) {
        $lines = array_slice($lines, 0, $maxLines);
        $lines[$maxLines - 1] = mb_substr($lines[$maxLines - 1], 0, -3) . '...';
    }
    
    return [$minSize, $lines];
}

// OG画像生成ヘルパー関数
function generateOgImageInMemory($title, $communityName, $authorName) {
    $width = 1200;
    $height = 630;
    
    // キャンバス作成
    $image = imagecreatetruecolor($width, $height);
    
    // 色を定義
    $bgColor = imagecolorallocate($image, 30, 41, 59); // #1e293b
    $blueColor = imagecolorallocate($image, 59, 130, 246); // #3b82f6
    $whiteColor = imagecolorallocate($image, 255, 255, 255);
    $grayColor = imagecolorallocate($image, 148, 163, 184); // #94a3b8
    
    // 背景を塗りつぶし
    imagefill($image, 0, 0, $bgColor);
    
    // ヘッダー帯
    imagefilledrectangle($image, 0, 0, $width, 80, $blueColor);
    
    // テキストから絵文字を除去
    $cleanTitle = removeEmojis($title);
    $cleanCommunityName = removeEmojis($communityName);
    $cleanAuthorName = removeEmojis($authorName);
    
    // フォントパスを確認（Laravel Cloudでは日本語フォントが利用可能）
    $fontPath = public_path('fonts/NotoSansJP-Bold.ttf');
    $useFont = file_exists($fontPath);
    
    if ($useFont) {
        // TTFフォントを使用した日本語対応テキスト描画
        
        // サイト名
        imagettftext($image, 24, 0, 50, 50, $whiteColor, $fontPath, '賛否両論.com');
        
        // タイトルの最適なサイズと行分割を計算
        $titleMaxWidth = $width - 100; // 左右50pxずつマージン
        $titleMaxHeight = 200; // タイトル用の最大高さ
        list($fontSize, $titleLines) = calculateOptimalFontSize($cleanTitle, $fontPath, $titleMaxWidth, $titleMaxHeight, 3);
        
        // タイトルを複数行で描画（行間隔を広げる）
        $lineHeight = $fontSize * 1.5; // 1.2から1.5に変更してゆとりを持たせる
        $totalTitleHeight = count($titleLines) * $lineHeight;
        $startY = 180 + (($titleMaxHeight - $totalTitleHeight) / 2); // 中央寄せ
        
        foreach ($titleLines as $i => $line) {
            $lineBbox = imagettfbbox($fontSize, 0, $fontPath, $line);
            $lineWidth = $lineBbox[4] - $lineBbox[0];
            $lineX = ($width - $lineWidth) / 2; // 各行を中央揃え
            $lineY = $startY + ($i * $lineHeight);
            imagettftext($image, $fontSize, 0, $lineX, $lineY, $whiteColor, $fontPath, $line);
        }
        
        // コミュニティ名（元の固定位置に戻す）
        imagettftext($image, 18, 0, 100, 500, $grayColor, $fontPath, $cleanCommunityName);
        
        // 投稿者（元の固定位置に戻す）
        imagettftext($image, 18, 0, 100, 540, $grayColor, $fontPath, "by {$cleanAuthorName}");
        
        // サイトURL
        imagettftext($image, 16, 0, 800, 580, $grayColor, $fontPath, 'sanpi-ryoron.com');
        
    } else {
        // フォールバック：シンプルなレイアウト（フォントなし）
        
        // サイト名（大きく表示）
        imagestring($image, 5, 50, 25, 'SANPI-RYORON.COM', $whiteColor);
        
        // 「議題」ラベル
        imagestring($image, 4, 100, 150, 'TOPIC:', $whiteColor);
        
        // タイトルプレースホルダー（日本語は表示不可）
        imagestring($image, 3, 100, 200, 'Japanese Topic Title', $whiteColor);
        imagestring($image, 2, 100, 230, '(Japanese characters cannot be displayed)', $grayColor);
        
        // コミュニティとユーザー情報
        imagestring($image, 3, 100, 480, 'Community: ' . (mb_check_encoding($cleanCommunityName, 'ASCII') ? $cleanCommunityName : 'Japanese Community'), $grayColor);
        imagestring($image, 3, 100, 520, 'Author: ' . (mb_check_encoding($cleanAuthorName, 'ASCII') ? $cleanAuthorName : 'Japanese Author'), $grayColor);
        
        // サイトURL
        imagestring($image, 3, 800, 580, 'sanpi-ryoron.com', $grayColor);
    }
    
    // 画像データを取得
    ob_start();
    imagepng($image);
    $imageData = ob_get_contents();
    ob_end_clean();
    imagedestroy($image);
    
    return $imageData;
}

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

// OG画像生成
Route::get('/og-image/topic/{id}', function ($id) {
    try {
        $topic = \App\Models\Topic::with(['user', 'community'])->find($id);
        if (!$topic) {
            \Log::error('OG Image: Topic not found', ['topic_id' => $id]);
            abort(404);
        }
        
        // メモリ上で直接OG画像を生成
        $imageData = generateOgImageInMemory(
            $topic->title,
            $topic->community->name ?? 'Unknown',
            $topic->user->name ?? 'Anonymous'
        );
        
        return response($imageData)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=31536000')
            ->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
        
    } catch (\Exception $e) {
        \Log::error('OG Image generation failed: ' . $e->getMessage(), [
            'topic_id' => $id,
            'trace' => $e->getTraceAsString()
        ]);
        
        abort(404);
    }
})->name('og-image.topic');

// Storage OG画像のフォールバック（Laravel Cloudのシンボリックリンク問題の回避）
Route::get('/storage/og-images/topic-{id}.png', function ($id) {
    return redirect()->route('og-image.topic', ['id' => $id]);
})->where('id', '[0-9]+');

// OG画像生成デバッグエンドポイント
Route::get('/debug/og-image/topic/{id}', function ($id) {
    try {
        $topic = \App\Models\Topic::with(['user', 'community'])->find($id);
        if (!$topic) {
            return response()->json(['error' => 'Topic not found'], 404);
        }
        
        // 拡張チェック
        $checks = [
            'gd_available' => extension_loaded('gd'),
            'imagick_available' => extension_loaded('imagick'),
            'storage_writable' => is_writable(storage_path('app/public')),
            'font_exists' => file_exists(public_path('fonts/NotoSansJP-Bold.ttf')),
            'topic_data' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'community' => $topic->community->name ?? 'Unknown',
                'author' => $topic->user->name ?? 'Anonymous'
            ]
        ];
        
        // 実際に画像生成を試行
        try {
            $ogImageService = new \App\Services\SimpleOgImageService();
            $imageUrl = $ogImageService->generateTopicOgImage(
                $topic->id,
                $topic->title,
                $topic->community->name ?? 'Unknown',
                $topic->user->name ?? 'Anonymous'
            );
            
            $checks['generation_success'] = true;
            $checks['image_url'] = $imageUrl;
            
            // ファイルの実際の存在確認
            $filename = "og-images/topic-{$topic->id}.png";
            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($filename);
            $checks['file_exists'] = file_exists($fullPath);
            $checks['file_path'] = $fullPath;
            $checks['file_size'] = file_exists($fullPath) ? filesize($fullPath) : 0;
            
        } catch (\Exception $e) {
            $checks['generation_success'] = false;
            $checks['error'] = $e->getMessage();
            $checks['image_url'] = null;
        }
        
        return response()->json($checks);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'gd_available' => extension_loaded('gd'),
            'imagick_available' => extension_loaded('imagick'),
            'storage_writable' => is_writable(storage_path('app/public')),
            'font_exists' => file_exists(public_path('fonts/NotoSansJP-Bold.ttf'))
        ], 500);
    }
});
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
    
    // コメント評価機能
    Route::post('/comments/{comment}/vote', [TopicController::class, 'vote'])->name('comments.vote');
    
    // リンクプレビュー生成機能
    Route::post('/topics/{topic}/generate-previews', [TopicController::class, 'generateLinkPreviews'])->name('topics.generate-previews');
    
    // 議題の保存・削除機能
    Route::post('/topics/{topic}/save', [TopicController::class, 'saveTopic'])->name('topics.save');
    Route::delete('/topics/{topic}/save', [TopicController::class, 'unsaveTopic'])->name('topics.unsave');
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');
    
    // プロフィール関連API
    Route::get('/profile/topics', [TopicController::class, 'getUserTopics'])->name('profile.topics');
    Route::get('/profile/saved', [TopicController::class, 'getSavedTopics'])->name('profile.saved');
    
    // OGP画像生成
    Route::get('/ogp/topic/{id}.png', [\App\Http\Controllers\OgpController::class, 'topicImage'])->name('ogp.topic');

    // リンクプレビューデバッグエンドポイント
    Route::get('/debug/link-previews/{topicId?}', function ($topicId = null) {
        if ($topicId) {
            $topic = \App\Models\Topic::find($topicId);
            if (!$topic) {
                return response()->json(['error' => 'Topic not found'], 404);
            }
            
            return response()->json([
                'topic_id' => $topic->id,
                'title' => $topic->title,
                'content' => $topic->content,
                'link_previews' => $topic->link_previews,
                'link_previews_count' => is_array($topic->link_previews) ? count($topic->link_previews) : 0,
                'has_urls' => str_contains($topic->content, 'http'),
                'database_column_type' => \DB::select("SELECT data_type FROM information_schema.columns WHERE table_name = 'topics' AND column_name = 'link_previews'")[0]->data_type ?? 'unknown'
            ]);
        }
        
        // 全体の統計情報
        $totalTopics = \App\Models\Topic::count();
        $topicsWithLinkPreviews = \App\Models\Topic::whereNotNull('link_previews')
            ->whereRaw("link_previews::text != '[]'")
            ->count();
        $topicsWithUrls = \App\Models\Topic::where('content', 'LIKE', '%http%')->count();
        $sampleTopicsWithUrls = \App\Models\Topic::where('content', 'LIKE', '%http%')
            ->limit(5)
            ->get(['id', 'title', 'link_previews']);
            
        return response()->json([
            'total_topics' => $totalTopics,
            'topics_with_link_previews' => $topicsWithLinkPreviews,
            'topics_with_urls_in_content' => $topicsWithUrls,
            'sample_topics_with_urls' => $sampleTopicsWithUrls->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'link_previews' => $topic->link_previews,
                    'preview_count' => is_array($topic->link_previews) ? count($topic->link_previews) : 0
                ];
            }),
            'database_info' => [
                'link_previews_column_exists' => \Schema::hasColumn('topics', 'link_previews'),
                'column_type' => \DB::select("SELECT data_type FROM information_schema.columns WHERE table_name = 'topics' AND column_name = 'link_previews'")[0]->data_type ?? 'unknown'
            ]
        ]);
    });
    
    // リンクプレビューテスト生成エンドポイント
    Route::get('/debug/test-link-preview-generation/{topicId}', function ($topicId) {
        $topic = \App\Models\Topic::find($topicId);
        if (!$topic) {
            return response()->json(['error' => 'Topic not found'], 404);
        }
        
        $linkPreviewService = new \App\Services\LinkPreviewService();
        $generatedPreviews = $linkPreviewService->extractLinksFromContent($topic->content);
        
        // 実際に更新
        $topic->update(['link_previews' => $generatedPreviews]);
        
        return response()->json([
            'topic_id' => $topic->id,
            'title' => $topic->title,
            'content' => $topic->content,
            'generated_previews' => $generatedPreviews,
            'saved_to_database' => true,
            'preview_count' => count($generatedPreviews)
        ]);
    });

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

Route::get('/admin/link-preview-status', function () {
    try {
        // データベースカラムが存在するか確認
        $hasColumn = \Schema::hasColumn('topics', 'link_previews');
        
        // リンクプレビューがある議題の数を確認
        $topicsWithPreviews = 0;
        if ($hasColumn) {
            $topicsWithPreviews = \App\Models\Topic::whereNotNull('link_previews')
                ->whereRaw("CAST(link_previews AS TEXT) != '[]'")
                ->count();
        }
        
        // 最新の議題を1件取得してテスト
        $latestTopic = \App\Models\Topic::latest()->first();
        $testPreview = null;
        $directUrlTest = null;
        
        if ($latestTopic && $latestTopic->content) {
            try {
                $linkPreviewService = new \App\Services\LinkPreviewService();
                $testPreview = $linkPreviewService->extractLinksFromContent($latestTopic->content);
            } catch (\Exception $e) {
                $testPreview = 'Error: ' . $e->getMessage();
            }
        }
        
        // 直接URLでテスト
        try {
            $linkPreviewService = new \App\Services\LinkPreviewService();
            $testContent = "これはテストです。https://github.com のリンクがあります。";
            $directUrlTest = $linkPreviewService->extractLinksFromContent($testContent);
        } catch (\Exception $e) {
            $directUrlTest = 'Error: ' . $e->getMessage();
        }
        
        return response()->json([
            'link_previews_column_exists' => $hasColumn,
            'topics_with_previews' => $topicsWithPreviews,
            'latest_topic_id' => $latestTopic ? $latestTopic->id : null,
            'latest_topic_content' => $latestTopic ? substr($latestTopic->content, 0, 200) : null,
            'test_preview_result' => $testPreview,
            'direct_url_test' => $directUrlTest,
            'timestamp' => now()->toDateTimeString()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});
