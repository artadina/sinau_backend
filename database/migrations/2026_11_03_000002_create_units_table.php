<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('video_url', 255);
            $table->text('reading_content');
            $table->integer('sequence_order');
            $table->integer('duration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
