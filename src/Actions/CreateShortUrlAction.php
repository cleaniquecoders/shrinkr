<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Support\Str;

class CreateShortUrlAction
{
    public function execute(array $data): Url
    {
        $slug = $data['custom_slug'] ?? $this->generateShortCode();

        // Ensure the slug is unique
        if (Url::where('shortened_url', $slug)->orWhere('custom_slug', $slug)->exists()) {
            throw new \Exception('The slug already exists. Please try a different one.');
        }

        return Url::create([
            'uuid' => data_get($data, 'uuid', Str::orderedUuid()),
            'original_url' => $data['original_url'],
            'shortened_url' => $slug,
            'custom_slug' => $data['custom_slug'] ?? null,
            'is_expired' => false,
        ]);
    }

    private function generateShortCode()
    {
        return substr(md5(uniqid(rand(), true)), 0, 6);
    }
}
