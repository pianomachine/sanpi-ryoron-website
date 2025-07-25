<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class SimpleOgImageService
{
    /**
     * シンプルなOG画像を生成（HTML Canvas経由でPNG）
     */
    public function generateTopicOgImage(int $topicId, string $title, string $communityName, string $authorName): string
    {
        // キャッシュチェック
        $filename = "og-images/topic-{$topicId}.png";
        if (Storage::disk('public')->exists($filename)) {
            return Storage::url($filename);
        }
        
        // ディレクトリを作成
        $directory = Storage::disk('public')->path('og-images');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // タイトルを適切な長さに制限
        $displayTitle = mb_strlen($title) > 60 ? mb_substr($title, 0, 57) . '...' : $title;
        
        // GDが利用可能な場合はGDで生成、そうでなければSVG
        if (extension_loaded('gd')) {
            $imagePath = $this->generateWithGd($displayTitle, $communityName, $authorName, $topicId);
        } else {
            // フォールバック：SVGを生成
            $svg = $this->generateSvg($displayTitle, $communityName, $authorName);
            Storage::disk('public')->put("og-images/topic-{$topicId}.svg", $svg);
            return Storage::url("og-images/topic-{$topicId}.svg");
        }
        
        return Storage::url($filename);
    }
    
    private function generateWithGd(string $title, string $communityName, string $authorName, int $topicId): string
    {
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
        
        // テキストを追加（フォントがない場合はビルトインフォントを使用）
        imagestring($image, 5, 50, 30, '賛否両論.com', $whiteColor);
        
        // タイトル（中央）
        $titleX = ($width - strlen($title) * 10) / 2;
        imagestring($image, 5, max(50, $titleX), 200, $title, $whiteColor);
        
        // コミュニティ名
        imagestring($image, 3, 100, 480, $communityName, $grayColor);
        
        // 投稿者
        imagestring($image, 3, 100, 520, "by {$authorName}", $grayColor);
        
        // サイトURL
        imagestring($image, 3, 800, 520, 'sanpi-ryoron.com', $grayColor);
        
        // ファイルに保存
        $filename = "og-images/topic-{$topicId}.png";
        $fullPath = Storage::disk('public')->path($filename);
        imagepng($image, $fullPath);
        imagedestroy($image);
        
        return $filename;
    }
    
    private function generateSvg(string $title, string $communityName, string $authorName): string
    {
        $escapedTitle = htmlspecialchars($title, ENT_XML1, 'UTF-8');
        $escapedCommunity = htmlspecialchars($communityName, ENT_XML1, 'UTF-8');
        $escapedAuthor = htmlspecialchars($authorName, ENT_XML1, 'UTF-8');
        
        return <<<SVG
<svg width="1200" height="630" xmlns="http://www.w3.org/2000/svg">
  <!-- 背景 -->
  <rect width="1200" height="630" fill="#1e293b"/>
  
  <!-- ヘッダー帯 -->
  <rect width="1200" height="80" fill="#3b82f6"/>
  
  <!-- サイト名 -->
  <text x="50" y="50" font-family="Arial, sans-serif" font-size="36" font-weight="bold" fill="white">賛否両論.com</text>
  
  <!-- タイトル -->
  <text x="600" y="200" font-family="Arial, sans-serif" font-size="48" font-weight="bold" fill="white" text-anchor="middle">{$escapedTitle}</text>
  
  <!-- コミュニティ名 -->
  <text x="100" y="500" font-family="Arial, sans-serif" font-size="24" fill="#94a3b8">{$escapedCommunity}</text>
  
  <!-- 投稿者 -->
  <text x="100" y="540" font-family="Arial, sans-serif" font-size="24" fill="#94a3b8">by {$escapedAuthor}</text>
  
  <!-- サイトURL -->
  <text x="900" y="540" font-family="Arial, sans-serif" font-size="24" fill="#94a3b8">sanpi-ryoron.com</text>
</svg>
SVG;
    }
}