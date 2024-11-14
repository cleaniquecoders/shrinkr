<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use Carbon\Carbon;
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Support\Str;

/**
 * Class CreateShortUrlAction
 *
 * Handles the creation of a shortened URL, including slug generation
 * and ensuring uniqueness of the slug.
 */
class CreateShortUrlAction
{
    /**
     * Executes the action to create a shortened URL.
     *
     * @param  array<string, mixed>  $data  An array of data required for creating the short URL.
     * @return Url The newly created URL model instance.
     *
     * @throws SlugAlreadyExistsException If the slug already exists in the database.
     */
    public function execute(array $data): Url
    {
        $slug = $data['custom_slug'] ?? $this->generateShortCode();

        // avoid non-string at all cost.
        if (! is_string($slug)) {
            $slug = $this->generateShortCode();
        }

        // Ensure the slug is unique
        if ($this->slugExists($slug)) {
            throw new SlugAlreadyExistsException('The slug already exists. Please try a different one.');
        }

        return Url::create($this->prepareData($data, $slug));
    }

    /**
     * Checks if a given slug already exists in the database.
     *
     * @param  string  $slug  The slug to check for uniqueness.
     * @return bool True if the slug exists, false otherwise.
     */
    private function slugExists(string $slug): bool
    {
        return Url::where('shortened_url', $slug)
            ->orWhere('custom_slug', $slug)
            ->exists();
    }

    /**
     * Prepares the data array for creating a new URL model instance.
     *
     * @param  array<string, mixed>  $data  The input data for URL creation.
     * @param  string  $slug  The slug to be used for the shortened URL.
     * @return array<string, mixed> The prepared data for URL creation.
     */
    private function prepareData(array $data, string $slug): array
    {
        return [
            'uuid' => data_get($data, 'uuid', Str::orderedUuid()),
            'user_id' => $data['user_id'] ?? null,
            'original_url' => $data['original_url'],
            'shortened_url' => $slug,
            'custom_slug' => $data['custom_slug'] ?? null,
            'expires_at' => $this->getExpiryDate($data),
            'is_expired' => false,
        ];
    }

    /**
     * Calculates the expiry date for the URL based on the input data.
     *
     * @param  array<string, mixed>  $data  The input data containing the expiry duration.
     * @return Carbon|null The calculated expiry date or null if no expiry is set.
     */
    private function getExpiryDate(array $data): ?Carbon
    {
        $duration = data_get($data, 'expiry_duration');

        // make sure it's int at all cost
        if (! is_int($duration)) {
            $duration = 0;
        }

        return $duration ? now()->addMinutes($duration) : null;
    }

    /**
     * Generates a random shortcode for the URL.
     *
     * @return string The generated shortcode.
     */
    private function generateShortCode(): string
    {
        return Str::random(6);
    }
}
