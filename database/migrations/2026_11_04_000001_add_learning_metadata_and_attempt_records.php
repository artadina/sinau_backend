<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-L01, FR-L02, FR-L21, FR-L22, FR-L30, FR-A02, FR-A03, FR-A05
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_name')->nullable();
            $table->timestamp('consent_at')->nullable();
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->string('cover_image')->nullable();
            $table->text('learning_objectives')->nullable();
            $table->unsignedSmallInteger('pre_question_count')->nullable();
            $table->unsignedSmallInteger('post_question_count')->nullable();
            $table->boolean('shuffle_questions')->nullable();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->text('learning_objective')->nullable();
        });

        Schema::table('test_questions', function (Blueprint $table) {
            $table->enum('type', ['pre', 'post']);
        });

        Schema::create('test_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('test_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('test_questions')->cascadeOnDelete();
            $table->char('selected_key', 1);
            $table->boolean('is_correct');
            $table->unique(['attempt_id', 'question_id']);
        });

        Schema::create('unit_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2);
            $table->timestamp('completed_at')->useCurrent();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->unique(['module_id', 'sequence_order']);
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropUnique(['module_id', 'sequence_order']);
        });

        Schema::dropIfExists('unit_quiz_attempts');
        Schema::dropIfExists('test_attempt_answers');

        Schema::table('test_questions', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('learning_objective');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn([
                'cover_image',
                'learning_objectives',
                'pre_question_count',
                'post_question_count',
                'shuffle_questions',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['business_name', 'consent_at']);
        });
    }
};
