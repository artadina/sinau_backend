<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table): void {
            $table->enum('status', [
                'draft',
                'submitted',
                'revision_required',
                'approved',
                'published',
                'archived',
            ])->default('draft')->change();
            $table->string('category', 100)->nullable();
            $table->text('target_audience')->nullable();
            $table->string('difficulty', 20)->nullable();
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('revision_of_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->index(['status', 'active_from', 'active_until']);
            $table->index('revision_of_id');
        });

        Schema::create('module_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('status_from', 30)->nullable();
            $table->string('status_to', 30)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['module_id', 'created_at']);
        });

        Schema::create('module_extension_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->date('previous_active_until');
            $table->date('requested_active_until');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
            $table->index(['module_id', 'status']);
        });

        Schema::create('module_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before');
            $table->date('active_until');
            $table->timestamp('sent_at')->useCurrent();
            $table->unique(['module_id', 'days_before', 'active_until']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('module_reminders');
        Schema::dropIfExists('module_extension_requests');
        Schema::dropIfExists('module_reviews');

        Schema::table('modules', function (Blueprint $table): void {
            $table->dropIndex(['status', 'active_from', 'active_until']);
            $table->dropIndex(['revision_of_id']);
            $table->dropConstrainedForeignId('revision_of_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'category',
                'target_audience',
                'difficulty',
                'active_from',
                'active_until',
                'submitted_at',
                'approved_at',
            ]);
            $table->enum('status', ['draft', 'published'])->default('draft')->change();
        });
    }
};
