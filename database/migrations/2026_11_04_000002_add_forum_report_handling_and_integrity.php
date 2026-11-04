<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-L35, FR-A14, FR-A15, EC-11, EC-12
    public function up(): void
    {
        Schema::table('forum_reports', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
            $table->unsignedBigInteger('post_id')->nullable()->change();
            $table->foreign('post_id')->references('id')->on('forum_posts')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->unique(['post_id', 'reported_by']);
        });

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->foreign('parent_id')->references('id')->on('forum_posts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->foreign('parent_id')->references('id')->on('forum_posts')->nullOnDelete();
        });

        Schema::table('forum_reports', function (Blueprint $table) {
            $table->dropUnique(['post_id', 'reported_by']);
            $table->dropForeign(['handled_by']);
            $table->dropColumn(['handled_by', 'handled_at']);
            $table->dropForeign(['post_id']);
            $table->unsignedBigInteger('post_id')->nullable(false)->change();
            $table->foreign('post_id')->references('id')->on('forum_posts')->cascadeOnDelete();
        });
    }
};
