<?php

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Create two URLs for testing
    Url::factory()->create([
        'original_url' => 'https://example.com',
        'is_expired' => false,
    ]);

    Url::factory()->create([
        'original_url' => 'https://nonexistent-url.com',
        'is_expired' => false,
    ]);
});

it('marks URLs as active or expired based on health check', function () {
    // Mock HTTP responses
    Http::fake([
        'https://example.com' => Http::response('', 200), // Healthy URL
        'https://nonexistent-url.com' => Http::response('', 404), // Broken URL
    ]);

    // Run the command
    Artisan::call('shrinkr:check-health');

    // Assertions: Ensure the healthy URL is active
    expect(Url::where('original_url', 'https://example.com')->first()->is_expired)
        ->toBeFalse();

    // Assertions: Ensure the broken URL is marked as expired
    expect(Url::where('original_url', 'https://nonexistent-url.com')->first()->is_expired)
        ->toBeTrue();
});
