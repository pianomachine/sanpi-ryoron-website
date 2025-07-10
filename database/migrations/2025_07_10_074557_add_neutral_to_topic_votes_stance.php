<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLiteの場合、enumは制約として実装されているため、
        // テーブルを再作成する必要があります
        if (DB::getDriverName() === 'sqlite') {
            // 一時テーブルにデータをバックアップ
            DB::statement('CREATE TABLE topic_votes_backup AS SELECT * FROM topic_votes');
            
            // 元のテーブルを削除
            Schema::dropIfExists('topic_votes');
            
            // 新しい構造でテーブルを再作成
            Schema::create('topic_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('topic_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('stance', ['support', 'oppose', 'neutral']); // neutralを追加
                $table->timestamps();
                
                // 同じユーザーが同じ投稿に複数投票できないようにする
                $table->unique(['topic_id', 'user_id']);
                $table->index(['topic_id']);
                $table->index(['user_id']);
            });
            
            // データを復元
            DB::statement('INSERT INTO topic_votes SELECT * FROM topic_votes_backup');
            
            // バックアップテーブルを削除
            DB::statement('DROP TABLE topic_votes_backup');
        } else {
            // MySQLやPostgreSQLの場合
            DB::statement("ALTER TABLE topic_votes MODIFY COLUMN stance ENUM('support', 'oppose', 'neutral')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // 一時テーブルにデータをバックアップ（neutralの行は除外）
            DB::statement("CREATE TABLE topic_votes_backup AS SELECT * FROM topic_votes WHERE stance != 'neutral'");
            
            // 元のテーブルを削除
            Schema::dropIfExists('topic_votes');
            
            // 元の構造でテーブルを再作成
            Schema::create('topic_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('topic_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('stance', ['support', 'oppose']); // neutralを除外
                $table->timestamps();
                
                // 同じユーザーが同じ投稿に複数投票できないようにする
                $table->unique(['topic_id', 'user_id']);
                $table->index(['topic_id']);
                $table->index(['user_id']);
            });
            
            // データを復元
            DB::statement('INSERT INTO topic_votes SELECT * FROM topic_votes_backup');
            
            // バックアップテーブルを削除
            DB::statement('DROP TABLE topic_votes_backup');
        } else {
            // MySQLやPostgreSQLの場合
            DB::statement("ALTER TABLE topic_votes MODIFY COLUMN stance ENUM('support', 'oppose')");
        }
    }
};
