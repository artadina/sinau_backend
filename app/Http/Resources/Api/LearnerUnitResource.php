<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearnerUnitResource extends JsonResource
{
    // FR-L10–FR-L12, FR-S07, AC-15, AC-18: return safe media and the current user's unit state.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module' => [
                'id' => $this->module->id,
                'title' => $this->module->title,
            ],
            'title' => $this->title,
            'learning_objective' => $this->learning_objective,
            'duration' => $this->duration,
            'reading_content' => $this->sanitized_reading_content,
            'video' => $this->video_payload,
            'last_video_pos' => $this->last_video_pos,
            'is_completed' => (bool) $this->is_completed,
        ];
    }
}
