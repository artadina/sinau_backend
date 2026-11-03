<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->json('options');
            $table->char('correct_key', 1);
            $table->text('explanation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_questions');
    }
};
