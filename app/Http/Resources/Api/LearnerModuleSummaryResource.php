<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LearnerModuleSummaryResource extends JsonResource
{
    // FR-L08, AC-14: expose only published-module metadata and the current Learner's progress.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'cover' => $this->cover_image === null
                ? null
                : Storage::disk('public')->url($this->cover_image),
            'active_unit_count' => (int) $this->active_unit_count,
            'estimated_duration' => (int) ($this->estimated_duration ?? 0),
            'progress_percentage' => (int) $this->progress_percentage,
        ];
    }
}
