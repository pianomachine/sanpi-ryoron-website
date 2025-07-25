<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OgImageService
{
    private ?ImageManager $manager = null;
    
    public function __construct()
    {
        try {
            $this->manager = new ImageManager(new Driver());
        } catch (\Exception $e) {
            \Log::error('Failed to initialize ImageManager: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 議題用のOG画像を生成
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
        
        // 画像サイズ (OGP推奨: 1200x630)
        $width = 1200;
        $height = 630;
        
        // キャンバス作成
        $img = $this->manager->create($width, $height);
        
        // 背景色をグラデーション風に
        $img->fill('#1e293b'); // dark slate
        
        // 上部の帯を追加
        $img->drawRectangle(0, 0, function($draw) use ($width) {
            $draw->size($width, 80);
            $draw->background('#3b82f6'); // blue
        });
        
        // フォントファイルのパスを確認
        $fontBold = public_path('fonts/NotoSansJP-Bold.ttf');
        $fontRegular = public_path('fonts/NotoSansJP-Regular.ttf');
        
        // フォントが存在しない場合はデフォルトフォントを使用
        $hasFonts = file_exists($fontBold) && file_exists($fontRegular);
        
        // サイト名
        $img->text('賛否両論.com', 50, 40, function($font) use ($fontBold, $hasFonts) {
            if ($hasFonts) {
                $font->file($fontBold);
            }
            $font->size(36);
            $font->color('#ffffff');
            $font->align('left');
            $font->valign('middle');
        });
        
        // タイトル（折り返し対応）
        $titleLines = $this->wrapText($title, 40); // 40文字で折り返し
        $y = 180;
        foreach ($titleLines as $index => $line) {
            if ($index >= 3) break; // 最大3行まで
            
            $img->text($line, 600, $y, function($font) use ($fontBold, $hasFonts) {
                if ($hasFonts) {
                    $font->file($fontBold);
                }
                $font->size(48);
                $font->color('#ffffff');
                $font->align('center');
                $font->valign('top');
            });
            $y += 70;
        }
        
        // コミュニティ名と投稿者（フォントなしでも表示）
        $img->text("{$communityName}", 100, 500, function($font) use ($fontRegular, $hasFonts) {
            if ($hasFonts) {
                $font->file($fontRegular);
            }
            $font->size(24);
            $font->color('#94a3b8'); // slate-400
            $font->align('left');
            $font->valign('middle');
        });
        
        $img->text("by {$authorName}", 100, 540, function($font) use ($fontRegular, $hasFonts) {
            if ($hasFonts) {
                $font->file($fontRegular);
            }
            $font->size(24);
            $font->color('#94a3b8');
            $font->align('left');
            $font->valign('middle');
        });
        
        // 右下にサイト情報
        $img->text('sanpi-ryoron.com', 900, 540, function($font) use ($fontRegular, $hasFonts) {
            if ($hasFonts) {
                $font->file($fontRegular);
            }
            $font->size(24);
            $font->color('#94a3b8');
            $font->align('left');
            $font->valign('middle');
        });
        
        // 画像を保存
        $img->save(Storage::disk('public')->path($filename));
        
        return Storage::url($filename);
    }
    
    /**
     * テキストを指定文字数で折り返し
     */
    private function wrapText(string $text, int $width): array
    {
        $lines = [];
        $words = mb_str_split($text);
        $currentLine = '';
        
        foreach ($words as $char) {
            if (mb_strlen($currentLine . $char) <= $width) {
                $currentLine .= $char;
            } else {
                $lines[] = $currentLine;
                $currentLine = $char;
            }
        }
        
        if ($currentLine) {
            $lines[] = $currentLine;
        }
        
        return $lines;
    }
}