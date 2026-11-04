<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

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
}
