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
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // コミュニティ名
            $table->string('slug')->unique(); // URL用スラッグ
            $table->string('icon')->nullable(); // 絵文字アイコン
            $table->text('description')->nullable(); // 説明
            $table->string('banner_color')->default('from-gray-100 to-blue-100'); // バナー色
            $table->json('rules')->nullable(); // ルール（JSON配列）
            $table->json('moderators')->nullable(); // モデレーター（JSON配列）
            $table->unsignedInteger('members_count')->default(0); // メンバー数
            $table->unsignedInteger('online_count')->default(0); // オンライン数
            $table->enum('status', ['active', 'restricted', 'private'])->default('active');
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
