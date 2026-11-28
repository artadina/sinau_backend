<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// FR-L21, FR-L22
class TestAttemptAnswer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_key',
        'selected_keys',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'selected_keys' => 'array',
            'is_correct' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class, 'question_id');
    }
}
