<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainCheckResource extends JsonResource
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
            'domain_id' => $this->domain_id,
            'status' => $this->status,
            'checked_at' => $this->checked_at,
            'http_status_code' => $this->http_status_code,
            'response_time_ms' => $this->response_time_ms,
            'error_message' => $this->error_message,
            'metadata' => $this->metadata, // Always include metadata if the resource is loaded
        ];
    }
}
