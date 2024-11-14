<?php

use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Workbench\App\Models\User;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutMiddleware;

beforeEach(function () {
    Config::set('shrinkr.domain', 'shrinkr.test');

    Config::set('app.url', 'http://shrinkr.test');
});

it('routes correctly to the controller with domain constraint', function () {
    $shortenedUrl = Shrinkr::shorten('https://g.com/abc123', User::factory()->create());

    withoutMiddleware();

    // Fake request on the specified domain
    $response = get("http://shrinkr.test/s/{$shortenedUrl}");

    // Assert the response status, assuming it should redirect or render a view
    $response->assertRedirect('https://g.com/abc123'); // or use assertRedirect if a redirect is expected

    // Check the correct route name
    expect(Route::currentRouteName())->toBe(config('shrinkr.route-name'));
});
