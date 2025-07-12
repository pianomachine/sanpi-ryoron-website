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
            $table->integer('cached_comments_count')->default(0)->index();
            $table->integer('cached_support_votes')->default(0)->index();
            $table->integer('cached_oppose_votes')->default(0)->index();
            $table->integer('cached_anonymous_support_votes')->default(0);
            $table->integer('cached_anonymous_oppose_votes')->default(0);
            $table->integer('cached_unique_commenters')->default(0);
            $table->timestamp('counts_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn([
                'cached_comments_count',
                'cached_support_votes', 
                'cached_oppose_votes',
                'cached_anonymous_support_votes',
                'cached_anonymous_oppose_votes',
                'cached_unique_commenters',
                'counts_updated_at'
            ]);
        });
    }
};
