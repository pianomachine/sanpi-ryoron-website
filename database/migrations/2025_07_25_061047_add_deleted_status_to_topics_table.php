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
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL用：既存の制約を削除してからカラムを変更
            DB::statement("ALTER TABLE topics DROP CONSTRAINT IF EXISTS topics_status_check");
            DB::statement("ALTER TABLE topics ALTER COLUMN status TYPE varchar(255)");
            DB::statement("ALTER TABLE topics ADD CONSTRAINT topics_status_check CHECK (status IN ('active', 'closed', 'archived', 'deleted'))");
        } else {
            // SQLite用
            Schema::table('topics', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL用：制約を削除して元のenum型に戻す
            DB::statement("ALTER TABLE topics DROP CONSTRAINT IF EXISTS topics_status_check");
            // 元のenum型を再作成するのは複雑なので、stringのままにする
        } else {
            // SQLite用
            Schema::table('topics', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });
        }
    }
};
