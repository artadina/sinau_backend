<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-A05, FR-A11: persist question format and all correct answer keys.
    public function up(): void
    {
        foreach (['test_questions', 'unit_quizzes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('question_format', 32)->default('multiple_choice');
                $table->json('correct_keys')->nullable();
            });

            DB::table($tableName)->orderBy('id')->chunkById(500, function ($questions) use ($tableName): void {
                foreach ($questions as $question) {
                    DB::table($tableName)
                        ->where('id', $question->id)
                        ->update(['correct_keys' => json_encode([$question->correct_key])]);
                }
            });
        }

        Schema::table('test_attempt_answers', function (Blueprint $table): void {
            $table->json('selected_keys')->nullable();
        });

        DB::table('test_attempt_answers')->orderBy('id')->chunkById(500, function ($answers): void {
            foreach ($answers as $answer) {
                DB::table('test_attempt_answers')
                    ->where('id', $answer->id)
                    ->update(['selected_keys' => json_encode([$answer->selected_key])]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('test_attempt_answers', function (Blueprint $table): void {
            $table->dropColumn('selected_keys');
        });

        foreach (['unit_quizzes', 'test_questions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['question_format', 'correct_keys']);
            });
        }
    }
};
