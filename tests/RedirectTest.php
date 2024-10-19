<?php

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Test: Redirect and Track Click
 */
it('redirects', function () {
    Url::factory()->create([
        'uuid' => Str::orderedUuid(),
        'original_url' => 'https://example.com',
        'shortened_url' => 'abc123',
    ]);

    $this->get(route(config('shrinkr.route-name'), ['shortenedUrl' => 'abc123']))
        ->assertRedirect('https://example.com');
});
