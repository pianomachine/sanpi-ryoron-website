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
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade'); // 親コメント
            $table->integer('depth')->default(0); // コメントの深さ
            $table->integer('score')->default(0); // 投票スコア
            $table->unsignedInteger('upvotes')->default(0); // アップボート数
            $table->unsignedInteger('downvotes')->default(0); // ダウンボート数
            $table->boolean('is_collapsed')->default(false); // 折りたたみ状態
            
            $table->index(['topic_id', 'parent_id']);
            $table->index(['parent_id', 'created_at']);
            $table->index('depth');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'parent_id', 'depth', 'score', 'upvotes', 
                'downvotes', 'is_collapsed'
            ]);
        });
    }
};
