<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestQuestion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'module_id',
        'type',
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

    // FR-A05: older questions retain their single-key answer as a fallback.
    public function getAnswerKeysAttribute(): array
    {
        return $this->correct_keys ?: [$this->correct_key];
    }

    // FR-A05, AB-12
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
