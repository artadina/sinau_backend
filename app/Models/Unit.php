<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'module_id',
        'title',
        'learning_objective',
        'video_url',
        'duration',
        'reading_content',
        'sequence_order',
        // FR-A08
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getDurationMinutesAttribute(): ?string
    {
        if ($this->duration === null) {
            return null;
        }

        return rtrim(rtrim(number_format($this->duration / 60, 2, '.', ''), '0'), '.');
    }

    public function getDurationLabelAttribute(): ?string
    {
        $minutes = $this->duration_minutes;

        return $minutes === null ? null : str_replace('.', ',', $minutes).' menit';
    }

    // FR-A03
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    // FR-A11, AB-12
    public function quizzes(): HasMany
    {
        return $this->hasMany(UnitQuiz::class);
    }
}
