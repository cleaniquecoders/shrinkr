<?php

namespace CleaniqueCoders\Shrinkr;

use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Foundation\Auth\User;

class Shrinkr
{
    public function shorten(string $originalUrl, User $user): string
    {
        return (new CreateShortUrlAction)->execute([
            'original_url' => $originalUrl,
            'user_id' => $user->{$user->getKeyName()},
        ])->shortened_url;
    }

    public function resolve(string $url): string
    {
        $url = Url::where('shortened_url', $url)->firstOrFail();

        return $url->original_url;
    }
}
