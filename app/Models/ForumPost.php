<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumPost extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'module_id',
        'unit_id',
        'user_id',
        'parent_id',
        'content',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    // FR-A13, AC-33
    // FR-SA05: keep anonymized authors available to existing Admin forum views.
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // FR-A15, EC-12
    public function reports(): HasMany
    {
        return $this->hasMany(ForumReport::class, 'post_id');
    }
}
