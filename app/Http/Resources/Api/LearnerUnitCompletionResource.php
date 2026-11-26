<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearnerUnitCompletionResource extends JsonResource
{
    // FR-L13, AC-17: return the completed unit's progress and next active unit.
    public function toArray(Request $request): array
    {
        return [
            'unit_id' => $this['unit_id'],
            'is_completed' => (bool) $this['is_completed'],
            'module_progress_percentage' => (int) $this['module_progress_percentage'],
            'next_unit' => $this['next_unit'],
        ];
    }
}
