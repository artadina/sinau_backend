<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearnerHomeResource extends JsonResource
{
    // FR-L14, AC-17: expose only the latest continuation item belonging to the current Learner.
    public function toArray(Request $request): array
    {
        return ['continue_learning' => $this->resource];
    }
}
