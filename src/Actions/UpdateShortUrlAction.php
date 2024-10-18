<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Models\Url;

class UpdateShortUrlAction
{
    public function execute(Url $url, array $data): Url
    {
        // Validate that custom slug is unique if it’s being updated
        if (isset($data['custom_slug']) && $data['custom_slug'] !== $url->custom_slug) {
            if (Url::where('shortened_url', $data['custom_slug'])
                ->orWhere('custom_slug', $data['custom_slug'])
                ->exists()) {
                throw new \Exception('The custom slug already exists. Please try a different one.');
            }
        }

        $url->update($data);

        return $url;
    }
}
