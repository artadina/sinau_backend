<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitQuiz extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'unit_id',
        'question_text',
        'question_format',
        'options',
        'correct_key',
        'correct_keys',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_keys' => 'array',
        ];
    }

    // FR-A11: older questions retain their single-key answer as a fallback.
    public function getAnswerKeysAttribute(): array
    {
        return $this->correct_keys ?: [$this->correct_key];
    }

    // FR-A11, AB-12
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
