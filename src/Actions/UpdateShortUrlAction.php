<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use CleaniqueCoders\Shrinkr\Models\Url;

class UpdateShortUrlAction
{
    public function execute(Url $url, array $data): Url
    {
        // Validate uniqueness of the custom slug if it's updated
        if ($this->isCustomSlugBeingUpdated($url, $data)) {
            $this->ensureSlugIsUnique($data['custom_slug']);
        }

        // Handle expiry duration if provided
        if (isset($data['expiry_duration'])) {
            $data['expires_at'] = now()->addMinutes($data['expiry_duration']);
            unset($data['expiry_duration']);
        }

        $url->update($data);

        return $url;
    }

    private function isCustomSlugBeingUpdated(Url $url, array $data): bool
    {
        return isset($data['custom_slug']) && $data['custom_slug'] !== $url->custom_slug;
    }

    private function ensureSlugIsUnique(string $slug): void
    {
        if (Url::where('shortened_url', $slug)->orWhere('custom_slug', $slug)->exists()) {
            throw new SlugAlreadyExistsException('The custom slug already exists. Please try a different one.');
        }
    }
}
