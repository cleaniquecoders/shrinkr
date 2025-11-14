<?php

namespace CleaniqueCoders\Shrinkr\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \CleaniqueCoders\Shrinkr\Models\Url
 */
class UrlResource extends JsonResource
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
            'original_url' => $this->original_url,
            'shortened_url' => $this->shortened_url,
            'custom_slug' => $this->custom_slug,
            'is_expired' => $this->is_expired,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'recheck_at' => $this->recheck_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'full_shortened_url' => $this->getFullShortenedUrl(),
            'click_count' => $this->when(
                $request->input('include_analytics', false),
                fn () => $this->redirectLogs()->count()
            ),
        ];
    }

    /**
     * Get the full shortened URL with domain
     */
    protected function getFullShortenedUrl(): string
    {
        $domain = config('shrinkr.domain') ?? request()->getSchemeAndHttpHost();
        $prefix = config('shrinkr.prefix', 's');

        return rtrim($domain, '/').'/'.trim($prefix, '/').'/'.$this->shortened_url;
    }
}
