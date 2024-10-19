<?php

use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

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
