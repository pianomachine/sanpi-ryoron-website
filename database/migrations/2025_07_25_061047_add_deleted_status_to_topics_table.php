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
        Schema::table('topics', function (Blueprint $table) {
            // カラムを再定義してdeletedを追加
            $table->enum('status', ['active', 'closed', 'archived', 'deleted'])->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // deletedを削除して元に戻す
            $table->enum('status', ['active', 'closed', 'archived'])->default('active')->change();
        });
    }
};
