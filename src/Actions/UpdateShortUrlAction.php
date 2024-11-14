<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use CleaniqueCoders\Shrinkr\Models\Url;

/**
 * Class UpdateShortUrlAction
 *
 * Handles updating a shortened URL, including validating custom slug uniqueness and handling expiry duration.
 */
class UpdateShortUrlAction
{
    /**
     * Updates the given URL model with provided data.
     *
     * @param  Url  $url  The URL model instance to be updated.
     * @param  array<string, mixed>  $data  An associative array containing the update data.
     * @return Url The updated URL model instance.
     *
     * @throws SlugAlreadyExistsException If the custom slug already exists.
     */
    public function execute(Url $url, array $data): Url
    {
        // Validate uniqueness of the custom slug if it's updated
        if ($this->isCustomSlugBeingUpdated($url, $data)) {
            if (is_string($data['custom_slug'])) {
                $this->ensureSlugIsUnique($data['custom_slug']);
            }
        }

        // Handle expiry duration if provided
        if (isset($data['expiry_duration']) && is_numeric($data['expiry_duration'])) {
            $data['expires_at'] = now()->addMinutes((int) $data['expiry_duration']);
            unset($data['expiry_duration']);
        }

        $url->update($data);

        return $url;
    }

    /**
     * Checks if the custom slug is being updated.
     *
     * @param  Url  $url  The URL model instance.
     * @param  array<string, mixed>  $data  The update data.
     * @return bool True if the custom slug is being updated, false otherwise.
     */
    private function isCustomSlugBeingUpdated(Url $url, array $data): bool
    {
        return isset($data['custom_slug']) && $data['custom_slug'] !== $url->custom_slug && is_string($data['custom_slug']);
    }

    /**
     * Ensures that the given custom slug is unique.
     *
     * @param  string  $slug  The custom slug to check for uniqueness.
     *
     * @throws SlugAlreadyExistsException If the custom slug already exists.
     */
    private function ensureSlugIsUnique(string $slug): void
    {
        if (Url::where('shortened_url', $slug)->orWhere('custom_slug', $slug)->exists()) {
            throw new SlugAlreadyExistsException('The custom slug already exists. Please try a different one.');
        }
    }
}
