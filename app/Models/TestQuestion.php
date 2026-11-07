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
        'options',
        'correct_key',
        'explanation',
    ];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    // FR-A05, AB-12
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
