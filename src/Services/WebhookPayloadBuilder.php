<?php

namespace CleaniqueCoders\Shrinkr\Services;

use CleaniqueCoders\Shrinkr\Models\Url;

class WebhookPayloadBuilder
{
    /**
     * Build payload for URL created event.
     *
     * @return array<string, mixed>
     */
    public static function forUrlCreated(Url $url): array
    {
        return [
            'event' => 'url.created',
            'timestamp' => now()->toIso8601String(),
            'data' => self::urlToArray($url),
        ];
    }

    /**
     * Build payload for URL updated event.
     *
     * @return array<string, mixed>
     */
    public static function forUrlUpdated(Url $url): array
    {
        return [
            'event' => 'url.updated',
            'timestamp' => now()->toIso8601String(),
            'data' => self::urlToArray($url),
        ];
    }

    /**
     * Build payload for URL deleted event.
     *
     * @return array<string, mixed>
     */
    public static function forUrlDeleted(Url $url): array
    {
        return [
            'event' => 'url.deleted',
            'timestamp' => now()->toIso8601String(),
            'data' => self::urlToArray($url),
        ];
    }

    /**
     * Build payload for URL accessed event.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public static function forUrlAccessed(Url $url, array $metadata = []): array
    {
        return [
            'event' => 'url.accessed',
            'timestamp' => now()->toIso8601String(),
            'data' => array_merge(
                self::urlToArray($url),
                ['access_metadata' => $metadata]
            ),
        ];
    }

    /**
     * Build payload for URL expired event.
     *
     * @return array<string, mixed>
     */
    public static function forUrlExpired(Url $url): array
    {
        return [
            'event' => 'url.expired',
            'timestamp' => now()->toIso8601String(),
            'data' => self::urlToArray($url),
        ];
    }

    /**
     * Convert URL model to array.
     *
     * @return array<string, mixed>
     */
    protected static function urlToArray(Url $url): array
    {
        return [
            'id' => $url->id,
            'uuid' => $url->uuid,
            'user_id' => $url->user_id,
            'original_url' => $url->original_url,
            'shortened_url' => $url->shortened_url,
            'custom_slug' => $url->custom_slug,
            'is_expired' => $url->is_expired,
            'expires_at' => $url->expires_at?->toIso8601String(),
            'created_at' => $url->created_at->toIso8601String(),
            'updated_at' => $url->updated_at->toIso8601String(),
        ];
    }
}
