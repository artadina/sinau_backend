<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Module extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'description',
        'cover_image',
        'learning_objectives',
        'category',
        'target_audience',
        'difficulty',
        'status',
        'created_by',
        'pre_question_count',
        'post_question_count',
        'shuffle_questions',
        'active_from',
        'active_until',
        'submitted_at',
        'approved_by',
        'approved_at',
        'revision_of_id',
    ];

    protected function casts(): array
    {
        return [
            'pre_question_count' => 'integer',
            'post_question_count' => 'integer',
            'shuffle_questions' => 'boolean',
            'active_from' => 'date',
            'active_until' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    // PS-21: Learner-facing content requires publication within the active period.
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereDate('active_from', '<=', today())
            ->whereDate('active_until', '>=', today());
    }

    // FR-A03, PS-30
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('sequence_order');
    }

    // FR-A05, PS-30
    public function testQuestions(): HasMany
    {
        return $this->hasMany(TestQuestion::class);
    }

    public function questions(): HasMany
    {
        return $this->testQuestions();
    }

    // AB-12, AC-13: retain the creator identity on shared modules after Admin soft deletion.
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function revisionOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revision_of_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'revision_of_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ModuleReview::class)->orderByDesc('created_at');
    }

    public function extensionRequests(): HasMany
    {
        return $this->hasMany(ModuleExtensionRequest::class)->latest();
    }
}
