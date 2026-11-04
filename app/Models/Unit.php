<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

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
}
