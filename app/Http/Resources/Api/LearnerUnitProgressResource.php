<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearnerUnitProgressResource extends JsonResource
{
    // FR-S03, AC-15, AC-17: keep progress responses limited to the requested unit.
    public function toArray(Request $request): array
    {
        return [
            'unit_id' => $this['unit_id'],
            'last_video_pos' => (int) $this['last_video_pos'],
            'is_completed' => (bool) $this['is_completed'],
        ];
    }
}
