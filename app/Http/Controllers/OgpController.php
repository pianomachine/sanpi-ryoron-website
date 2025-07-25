<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Services\OgpImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OgpController extends Controller
{
    private $ogpImageService;
    
    public function __construct(OgpImageService $ogpImageService)
    {
        $this->ogpImageService = $ogpImageService;
    }
    
    /**
     * 議題のOGP画像を生成
     */
    public function topicImage($topicId)
    {
        $topic = Topic::with(['user', 'community'])->find($topicId);
        
        if (!$topic) {
            // デフォルト画像を返す
            return redirect('/images/default-og-image.png');
        }
        
        try {
            // OGP画像を生成
            $imageUrl = $this->ogpImageService->generateForTopic($topic);
            
            // 画像ファイルのパスを取得
            $path = str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH));
            
            // 画像を返す
            return response()->file(storage_path('app/public/' . $path), [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=86400' // 24時間キャッシュ
            ]);
            
        } catch (\Exception $e) {
            \Log::error('OGP image generation failed: ' . $e->getMessage());
            // エラー時はデフォルト画像を返す
            return redirect('/images/default-og-image.png');
        }
    }
}