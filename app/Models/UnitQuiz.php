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
        'options',
        'correct_key',
        'explanation',
    ];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    // FR-A11, AB-12
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
