<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'is_active' => (bool) $this->is_active, // Ensure boolean type
            'check_interval_minutes' => $this->check_interval_minutes,
            'last_status' => $this->last_status,
            'last_checked_at' => $this->last_checked_at,
            'last_response_time_ms' => $this->last_response_time_ms,
            'latest_check' => new DomainCheckResource($this->whenLoaded('latestCheck')),
            'expected_content_marker' => $this->expected_content_marker, // Explicitly include
        ];
    }
}
