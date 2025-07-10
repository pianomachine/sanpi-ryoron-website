<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->constrained()->onDelete('cascade'); // コミュニティ
            $table->text('content')->nullable(); // 投稿内容
            $table->enum('type', ['text', 'link', 'image', 'video'])->default('text'); // 投稿タイプ
            $table->string('url')->nullable(); // リンク投稿の場合のURL
            $table->string('image_url')->nullable(); // 画像投稿の場合のURL
            $table->string('flair')->nullable(); // フレア
            $table->integer('score')->default(0); // 投票スコア
            $table->unsignedInteger('upvotes')->default(0); // アップボート数
            $table->unsignedInteger('downvotes')->default(0); // ダウンボート数
            $table->boolean('is_nsfw')->default(false); // 成人向けコンテンツ
            $table->boolean('is_spoiler')->default(false); // ネタバレ
            $table->json('awards')->nullable(); // 受賞履歴
            
            $table->index(['community_id', 'created_at']);
            $table->index(['score', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropForeign(['community_id']);
            $table->dropColumn([
                'community_id', 'content', 'type', 'url', 'image_url', 
                'flair', 'score', 'upvotes', 'downvotes', 'is_nsfw', 
                'is_spoiler', 'awards'
            ]);
        });
    }
};
