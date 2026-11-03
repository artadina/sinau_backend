<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['pre', 'post']);
            $table->decimal('score', 5, 2);
            $table->timestamp('completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};
