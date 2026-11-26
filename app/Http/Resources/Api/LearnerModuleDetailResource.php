<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearnerModuleDetailResource extends JsonResource
{
    // FR-L09, AB-03: return active-unit and test states for the authenticated Learner.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'learning_objectives' => $this->learning_objectives,
            'progress_percentage' => (int) $this->progress_percentage,
            'pre_test' => ['status' => $this->pre_test_status],
            'post_test' => ['status' => $this->post_test_status],
            'units' => LearnerModuleUnitResource::collection($this->units)
                ->resolve($request),
        ];
    }
}
