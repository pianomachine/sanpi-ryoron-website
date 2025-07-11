<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DeploySeeder extends Command
{
    protected $signature = 'deploy:seed {--force : Force seeding even if data exists}';
    protected $description = 'Deploy sample data for production environment';

    public function handle()
    {
        $this->info('🚀 Starting deployment seeder...');
        
        // データ確認
        $topicCount = \App\Models\Topic::count();
        $communityCount = \App\Models\Community::count();
        
        $this->info("Current data:");
        $this->info("- Topics: {$topicCount}");
        $this->info("- Communities: {$communityCount}");
        
        if ($topicCount > 0 && !$this->option('force')) {
            $this->warn('Data already exists. Use --force to override.');
            return;
        }
        
        try {
            $this->info('Running community seeder...');
            Artisan::call('db:seed', ['--class' => 'CommunitySeeder']);
            
            $this->info('Running topic seeder...');
            Artisan::call('db:seed', ['--class' => 'TopicSeeder']);
            
            $this->info('Running comment seeder...');
            Artisan::call('db:seed', ['--class' => 'CommentSeeder']);
            
            $this->info('✅ Sample data deployed successfully!');
            
            $newTopicCount = \App\Models\Topic::count();
            $newCommunityCount = \App\Models\Community::count();
            
            $this->info("Final data:");
            $this->info("- Topics: {$newTopicCount}");
            $this->info("- Communities: {$newCommunityCount}");
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
} 