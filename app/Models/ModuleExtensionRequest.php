<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleExtensionRequest extends Model
{
    protected $fillable = [
        'module_id',
        'requested_by',
        'previous_active_until',
        'requested_active_until',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'decision_notes',
    ];

    protected function casts(): array
    {
        return [
            'previous_active_until' => 'date',
            'requested_active_until' => 'date',
            'decided_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by')->withTrashed();
    }
}
