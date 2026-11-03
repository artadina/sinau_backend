<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_unit_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->integer('last_video_pos')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('updated_at')->useCurrent();
            $table->unique(['user_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_unit_progress');
    }
};
