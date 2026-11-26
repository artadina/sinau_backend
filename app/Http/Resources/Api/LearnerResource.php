<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearnerResource extends JsonResource
{
    // FR-L03, FR-L05, AB-16: expose only the account fields in the API contract.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'business_name' => $this->business_name,
            'role' => $this->role,
            'consent_given' => $this->consent_given,
            'consent_at' => $this->consent_at?->toIso8601String(),
            'consent_required' => ! $this->consent_given,
        ];
    }
}
