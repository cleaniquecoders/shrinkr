<?php

namespace CleaniqueCoders\Shrinkr\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \CleaniqueCoders\Shrinkr\Models\Webhook
 */
class WebhookResource extends JsonResource
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
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'url' => $this->url,
            'events' => $this->events,
            'is_active' => $this->is_active,
            'secret' => $this->when($request->input('show_secret', false), $this->secret),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'last_triggered_at' => $this->last_triggered_at?->toIso8601String(),
        ];
    }
}
