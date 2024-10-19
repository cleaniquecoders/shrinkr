<?php

use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workbench\App\Models\User as User;

uses(RefreshDatabase::class);

/**
 * Test: Shorten a URL for a Specific User Using the Shrinkr Facade
 */
it('can shorten a URL and associate it with a user', function () {
    $user = User::factory()->create();

    $shortUrl = Shrinkr::shorten('https://example.com/user-url', $user);
    $url = Url::where('shortened_url', $shortUrl)->first();

    expect($url)->not->toBeNull()
        ->and($url->user_id)->toBe($user->id); // Check if the URL is associated with the correct user
});

/**
 * Test: Resolve a Shortened URL Using the Shrinkr Facade
 */
it('can resolve a shortened URL to its original URL', function () {
    $user = User::factory()->create();

    $originalUrl = 'https://example.com/resolved-url';
    $shortUrl = Shrinkr::shorten($originalUrl, $user);

    $resolvedUrl = Shrinkr::resolve($shortUrl);

    expect($resolvedUrl)->toBe($originalUrl); // Ensure the original URL matches
});
