<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// FR-A08, FR-A10
class TestAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'module_id',
        'type',
        'score',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    // FR-A08, FR-A10
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    // FR-L22: answers are locked to the attempt and preserve insertion order.
    public function answers(): HasMany
    {
        return $this->hasMany(TestAttemptAnswer::class, 'attempt_id')->orderBy('id');
    }
}
