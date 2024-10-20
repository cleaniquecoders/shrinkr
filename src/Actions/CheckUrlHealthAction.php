<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Support\Facades\Http;

class CheckUrlHealthAction
{
    /**
     * Execute the health check for the given URL.
     */
    public function execute(Url $url): bool
    {
        try {
            $response = Http::timeout(10)->get($url->original_url);

            if ($response->ok()) {
                // If the URL is accessible, mark it as active.
                $this->markAsActive($url);

                return true;
            } else {
                // If the response is not OK, mark it as expired.
                $this->markAsExpired($url);

                return false;
            }
        } catch (\Exception $e) {
            // On exception (e.g., timeout), mark the URL as expired.
            $this->markAsExpired($url);

            return false;
        }
    }

    /**
     * Mark the URL as expired.
     */
    private function markAsExpired(Url $url): void
    {
        $url->update(['is_expired' => true]);
        UrlExpired::dispatch($url);
    }

    /**
     * Mark the URL as active.
     */
    private function markAsActive(Url $url): void
    {
        $url->update(['is_expired' => false]);
    }
}
