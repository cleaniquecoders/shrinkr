<?php

use CleaniqueCoders\Shrinkr\Actions\Logger\LogToDatabase;
use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

uses(RefreshDatabase::class);

it('logs redirect details to file', function () {
    Log::spy();  // Intercept log messages

    $url = Url::factory()->create(['uuid' => 'test-uuid']);
    $agent = new Agent;
    $request = Request::create(
        route(config('shrinkr.route-name'), ['shortenedUrl' => $url->shortened_url]),
        'GET', [], [], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']
    );

    (new LogToFile)->log($url, $request, $agent);

    Log::shouldHaveReceived('info')
        ->once()
        ->with('Redirect Log', \Mockery::type('array'));
});

/**
 * Test: Log to Database
 */
it('logs redirect details to the database', function () {
    $url = Url::factory()->create(['uuid' => 'test-uuid']);
    $agent = new Agent;
    $request = Request::create(
        route(config('shrinkr.route-name'), ['shortenedUrl' => $url->shortened_url]),
        'GET', [], [], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']
    );

    (new LogToDatabase)->log($url, $request, $agent);

    $this->assertDatabaseHas('redirect_logs', [
        'url_id' => $url->id,
    ]);
});
