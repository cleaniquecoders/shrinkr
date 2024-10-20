<?php

use Carbon\Carbon;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Support\Facades\Artisan;

it('marks the URL as expired when the expiry time passes', function () {
    // Create a short URL with an expiry time of 1 minute
    $url = Url::factory()->create([
        'expires_at' => now()->addMinute(),
    ]);

    // Fast-forward time by 2 minutes
    Carbon::setTestNow(now()->addMinutes(2));

    // Check if the URL is marked as expired
    $this->assertTrue($url->hasExpired());
});

it('marks expired URLs as expired using Artisan command', function () {
    // Create a URL with an expiry time of 1 minute ago
    $url = Url::factory()->create([
        'expires_at' => now()->subMinute(),
        'is_expired' => false,
    ]);

    // Run the command
    Artisan::call('shrinkr:check-expiry');

    // Refresh the model and check if it’s marked as expired
    $url->refresh();
    expect($url->is_expired)->toBeTrue();
});
