<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Support\Str;

class CreateShortUrlAction
{
    public function execute(array $data): Url
    {
        $slug = $data['custom_slug'] ?? $this->generateShortCode();

        // Ensure the slug is unique
        if ($this->slugExists($slug)) {
            throw new SlugAlreadyExistsException('The slug already exists. Please try a different one.');
        }

        return Url::create($this->prepareData($data, $slug));
    }

    private function slugExists(string $slug): bool
    {
        return Url::where('shortened_url', $slug)
            ->orWhere('custom_slug', $slug)
            ->exists();
    }

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

    private function getExpiryDate(array $data): ?\Carbon\Carbon
    {
        $duration = data_get($data, 'expiry_duration');

        return $duration ? now()->addMinutes($duration) : null;
    }

    private function generateShortCode(): string
    {
        return Str::random(6);
    }
}
