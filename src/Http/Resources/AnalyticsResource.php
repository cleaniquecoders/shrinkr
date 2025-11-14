<?php

namespace CleaniqueCoders\Shrinkr\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \CleaniqueCoders\Shrinkr\Models\RedirectLog
 */
class AnalyticsResource extends JsonResource
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
            'url_id' => $this->url_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'referrer' => $this->referrer,
            'browser' => $this->browser,
            'platform' => $this->platform,
            'device' => $this->device,
            'headers' => $this->headers,
            'query_params' => $this->query_params,
            'accessed_at' => $this->created_at->toIso8601String(),
        ];
    }
}
