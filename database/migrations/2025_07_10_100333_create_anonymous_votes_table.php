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
        Schema::create('anonymous_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('session_id', 255); // セッションID
            $table->string('fingerprint', 255)->nullable(); // ブラウザフィンガープリント（オプション）
            $table->enum('stance', ['support', 'oppose', 'neutral']);
            $table->ipAddress('ip_address')->nullable(); // IP アドレス
            $table->timestamps();
            
            // 同じセッション・IPが同じ議題に複数投票できないようにする
            $table->unique(['topic_id', 'session_id']);
            $table->index(['topic_id']);
            $table->index(['session_id']);
            $table->index(['ip_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anonymous_votes');
    }
};
