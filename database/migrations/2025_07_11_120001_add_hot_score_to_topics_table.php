<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // hot_score を double 列で追加（デフォルト 0）
            $table->double('hot_score')->default(0)->after('score');
        });

        // status, community_id, hot_score DESC の複合インデックス
        DB::statement('CREATE INDEX idx_topics_hot ON topics (status, community_id, hot_score DESC)');
    }

    public function down(): void
    {
        // インデックスを削除
        DB::statement('DROP INDEX IF EXISTS idx_topics_hot');

        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn('hot_score');
        });
    }
}; 