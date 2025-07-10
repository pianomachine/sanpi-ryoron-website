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
        } elseif (DB::getDriverName() === 'mysql') {
            // MySQLの場合
            DB::statement("ALTER TABLE topic_votes MODIFY COLUMN stance ENUM('support', 'oppose', 'neutral')");
        } else {
            // PostgreSQLの場合
            // 新しいENUM型を作成
            DB::statement("CREATE TYPE stance_enum_new AS ENUM('support', 'oppose', 'neutral')");
            
            // カラムの型を変更
            DB::statement("ALTER TABLE topic_votes ALTER COLUMN stance TYPE stance_enum_new USING stance::text::stance_enum_new");
            
            // 古い型を削除して、新しい型をリネーム
            DB::statement("DROP TYPE IF EXISTS stance_enum");
            DB::statement("ALTER TYPE stance_enum_new RENAME TO stance_enum");
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
        } elseif (DB::getDriverName() === 'mysql') {
            // MySQLの場合
            DB::statement("ALTER TABLE topic_votes MODIFY COLUMN stance ENUM('support', 'oppose')");
        } else {
            // PostgreSQLの場合
            // neutralの投票を削除
            DB::statement("DELETE FROM topic_votes WHERE stance = 'neutral'");
            
            // 新しいENUM型を作成（neutralなし）
            DB::statement("CREATE TYPE stance_enum_old AS ENUM('support', 'oppose')");
            
            // カラムの型を変更
            DB::statement("ALTER TABLE topic_votes ALTER COLUMN stance TYPE stance_enum_old USING stance::text::stance_enum_old");
            
            // 古い型を削除して、新しい型をリネーム
            DB::statement("DROP TYPE IF EXISTS stance_enum");
            DB::statement("ALTER TYPE stance_enum_old RENAME TO stance_enum");
        }
    }
};
