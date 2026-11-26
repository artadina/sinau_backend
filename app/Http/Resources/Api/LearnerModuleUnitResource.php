<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearnerModuleUnitResource extends JsonResource
{
    // FR-L09, AC-14: list only active units and their owner-specific completion state.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sequence_order' => $this->sequence_order,
            'status' => $this->learner_status,
        ];
    }
}
