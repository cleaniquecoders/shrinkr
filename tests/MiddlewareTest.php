<?php

use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use Illuminate\Support\Facades\Config;
use Workbench\App\Models\User;

use function Pest\Laravel\get;

beforeEach(function () {
    Config::set('shrinkr.domain', 'shrinkr.test');

    Config::set('app.url', 'http://shrinkr.test');
});

it('allows requests within the rate limit and blocks when limit is exceeded', function () {
    $shortenedUrl = Shrinkr::shorten('https://g.com/abc123', User::factory()->create());

    // Make requests within the limit
    for ($i = 0; $i < 60; $i++) {
        // Fake request on the specified domain
        $response = get("http://shrinkr.test/s/{$shortenedUrl}");

        // $response->ddHeaders();

        // Assert the response status, assuming it should redirect or render a view
        $response->assertRedirect('https://g.com/abc123'); // or use assertRedirect if a redirect is expected
    }

    // The next request should exceed the rate limit and return a 429 status
    $response = get("http://shrinkr.test/s/{$shortenedUrl}");
    $response->assertStatus(429);
});
