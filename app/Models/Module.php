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
        'status',
        'created_by',
        'pre_question_count',
        'post_question_count',
        'shuffle_questions',
    ];

    protected function casts(): array
    {
        return [
            'pre_question_count' => 'integer',
            'post_question_count' => 'integer',
            'shuffle_questions' => 'boolean',
        ];
    }

    // AC-14: learner-facing module queries can explicitly select published modules only.
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    // FR-A03, FR-A04
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('sequence_order');
    }

    // FR-A05, AB-12
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
}
