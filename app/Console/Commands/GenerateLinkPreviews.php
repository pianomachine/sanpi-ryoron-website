<?php

namespace App\Console\Commands;

use App\Models\Topic;
use App\Services\LinkPreviewService;
use Illuminate\Console\Command;

class GenerateLinkPreviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'topics:generate-link-previews {--topic-id= : Specific topic ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate link previews for existing topics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $linkPreviewService = new LinkPreviewService();
        
        // 特定の議題IDが指定された場合
        if ($topicId = $this->option('topic-id')) {
            $topic = Topic::find($topicId);
            if (!$topic) {
                $this->error("Topic with ID {$topicId} not found.");
                return 1;
            }
            
            $this->info("Processing topic {$topic->id}: {$topic->title}");
            $this->generatePreviewsForTopic($topic, $linkPreviewService);
            return 0;
        }
        
        // 全ての議題を処理 (PostgreSQL対応)
        $topics = Topic::where('status', 'active')->get()->filter(function ($topic) {
            return is_null($topic->link_previews) || 
                   (is_array($topic->link_previews) && count($topic->link_previews) === 0);
        });
            
        if ($topics->isEmpty()) {
            $this->info('No topics found that need link preview generation.');
            return 0;
        }
        
        $this->info("Found {$topics->count()} topics to process.");
        
        $bar = $this->output->createProgressBar($topics->count());
        $bar->start();
        
        $processed = 0;
        $withPreviews = 0;
        
        foreach ($topics as $topic) {
            $linkPreviews = $this->generatePreviewsForTopic($topic, $linkPreviewService);
            
            if (!empty($linkPreviews)) {
                $withPreviews++;
            }
            
            $processed++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Processed {$processed} topics.");
        $this->info("{$withPreviews} topics had link previews generated.");
        
        return 0;
    }
    
    private function generatePreviewsForTopic(Topic $topic, LinkPreviewService $linkPreviewService): array
    {
        try {
            $linkPreviews = $linkPreviewService->extractLinksFromContent($topic->content);
            $topic->update(['link_previews' => $linkPreviews]);
            
            if (!empty($linkPreviews)) {
                $this->line("  Generated " . count($linkPreviews) . " previews for topic {$topic->id}");
            }
            
            return $linkPreviews;
        } catch (\Exception $e) {
            $this->error("  Failed to generate previews for topic {$topic->id}: " . $e->getMessage());
            return [];
        }
    }
}