<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkPreviewService
{
    /**
     * URLからリンクを抽出してプレビューデータを生成
     */
    public function extractLinksFromContent(string $content): array
    {
        $links = [];
        
        // URLパターンをマッチング
        $pattern = '/https?:\/\/[^\s\<\>"\']+/i';
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[0] as $match) {
            $url = $match[0];
            $position = $match[1];
            
            // 重複チェック
            $exists = collect($links)->firstWhere('url', $url);
            if (!$exists) {
                $preview = $this->fetchLinkPreview($url);
                if ($preview) {
                    $preview['position'] = $position;
                    $links[] = $preview;
                }
            }
        }
        
        return $links;
    }
    
    /**
     * 指定URLからOpen Graphデータを取得
     */
    public function fetchLinkPreview(string $url): ?array
    {
        try {
            $response = Http::timeout(10)->get($url);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            $domain = parse_url($url, PHP_URL_HOST);
            
            return [
                'url' => $url,
                'title' => $this->extractMetaTag($html, 'og:title') ?: $this->extractTitle($html) ?: $domain,
                'description' => $this->extractMetaTag($html, 'og:description') ?: $this->extractMetaTag($html, 'description'),
                'image' => $this->extractMetaTag($html, 'og:image'),
                'domain' => $domain,
                'site_name' => $this->extractMetaTag($html, 'og:site_name') ?: $domain,
            ];
            
        } catch (\Exception $e) {
            Log::warning('Link preview fetch failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * HTMLからメタタグを抽出
     */
    private function extractMetaTag(string $html, string $property): ?string
    {
        // Open Graph tags
        $pattern = '/<meta\s+property=["\']' . preg_quote($property, '/') . '["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i';
        if (preg_match($pattern, $html, $matches)) {
            return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        }
        
        // Regular meta tags
        $pattern = '/<meta\s+name=["\']' . preg_quote($property, '/') . '["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i';
        if (preg_match($pattern, $html, $matches)) {
            return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        }
        
        return null;
    }
    
    /**
     * HTMLからtitleタグを抽出
     */
    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>([^<]*)<\/title>/i', $html, $matches)) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }
        
        return null;
    }
    
    /**
     * 相対URLを絶対URLに変換
     */
    private function resolveUrl(string $baseUrl, string $relativeUrl): string
    {
        if (filter_var($relativeUrl, FILTER_VALIDATE_URL)) {
            return $relativeUrl;
        }
        
        $base = parse_url($baseUrl);
        
        if (str_starts_with($relativeUrl, '//')) {
            return $base['scheme'] . ':' . $relativeUrl;
        }
        
        if (str_starts_with($relativeUrl, '/')) {
            return $base['scheme'] . '://' . $base['host'] . $relativeUrl;
        }
        
        return $baseUrl . '/' . $relativeUrl;
    }
}