<?php

namespace App\Services;

use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;

class OgpImageService
{
    private $width = 1200;
    private $height = 630;
    private $backgroundColor = '#1a1a1a';
    private $primaryColor = '#3b82f6';
    private $textColor = '#ffffff';
    
    public function __construct()
    {
        // GDドライバーを使用
        Image::configure(['driver' => 'gd']);
    }
    
    /**
     * 議題用のOGP画像を生成
     */
    public function generateForTopic($topic): string
    {
        // キャッシュキー
        $cacheKey = 'ogp_topic_' . $topic->id . '_' . md5($topic->title . $topic->updated_at);
        $filename = $cacheKey . '.png';
        $path = 'ogp-images/' . $filename;
        
        // 既にキャッシュされている場合はそれを返す
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }
        
        // 画像作成
        $img = Image::canvas($this->width, $this->height, $this->backgroundColor);
        
        // グラデーション背景を追加
        $gradient = Image::canvas($this->width, $this->height);
        for ($i = 0; $i < $this->height; $i++) {
            $opacity = 1 - ($i / $this->height) * 0.5;
            $color = $this->hexToRgba($this->primaryColor, $opacity);
            $gradient->line(0, $i, $this->width, $i, function ($draw) use ($color) {
                $draw->color($color);
            });
        }
        $img->insert($gradient);
        
        // サイトロゴ/タイトル
        $img->text('賛否両論.com', 60, 60, function($font) {
            $font->file($this->getFont('bold'));
            $font->size(36);
            $font->color($this->textColor);
        });
        
        // カテゴリ名
        if ($topic->community) {
            $img->text($topic->community->name, 60, 110, function($font) {
                $font->file($this->getFont());
                $font->size(24);
                $font->color($this->hexToRgba($this->textColor, 0.8));
            });
        }
        
        // タイトル（複数行対応）
        $title = $this->wrapText($topic->title, 40); // 40文字で改行
        $lines = explode("\n", $title);
        $y = 250;
        
        foreach ($lines as $index => $line) {
            if ($index >= 3) break; // 最大3行まで
            
            $img->text($line, 60, $y, function($font) {
                $font->file($this->getFont('bold'));
                $font->size(48);
                $font->color($this->textColor);
            });
            $y += 70;
        }
        
        // 投稿者情報
        $authorText = '投稿者: ' . ($topic->user ? $topic->user->name : 'Anonymous');
        $img->text($authorText, 60, 520, function($font) {
            $font->file($this->getFont());
            $font->size(24);
            $font->color($this->hexToRgba($this->textColor, 0.7));
        });
        
        // 統計情報
        $statsText = sprintf('💬 %d コメント  👍 %d 賛成  👎 %d 反対', 
            $topic->comments_count ?? 0,
            $topic->upvotes ?? 0,
            $topic->downvotes ?? 0
        );
        $img->text($statsText, 60, 560, function($font) {
            $font->file($this->getFont());
            $font->size(20);
            $font->color($this->hexToRgba($this->textColor, 0.6));
        });
        
        // 画像を保存
        Storage::disk('public')->makeDirectory('ogp-images');
        $img->save(storage_path('app/public/' . $path));
        
        return Storage::disk('public')->url($path);
    }
    
    /**
     * テキストを指定文字数で改行
     */
    private function wrapText($text, $width)
    {
        $wrapped = '';
        $length = mb_strlen($text);
        
        for ($i = 0; $i < $length; $i += $width) {
            $wrapped .= mb_substr($text, $i, $width);
            if ($i + $width < $length) {
                $wrapped .= "\n";
            }
        }
        
        return $wrapped;
    }
    
    /**
     * フォントファイルのパスを取得
     */
    private function getFont($weight = 'regular')
    {
        // 日本語対応フォントを使用
        // Noto Sans JPを使用（事前にダウンロードが必要）
        $fontPath = resource_path('fonts/NotoSansJP-' . ucfirst($weight) . '.ttf');
        
        // フォントが存在しない場合はデフォルトフォントを使用
        if (!file_exists($fontPath)) {
            // Laravelのデフォルトフォントまたはシステムフォントを返す
            return public_path('fonts/OpenSans-Regular.ttf');
        }
        
        return $fontPath;
    }
    
    /**
     * HEXカラーをRGBA配列に変換
     */
    private function hexToRgba($hex, $alpha = 1)
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgba($r, $g, $b, $alpha)";
    }
}