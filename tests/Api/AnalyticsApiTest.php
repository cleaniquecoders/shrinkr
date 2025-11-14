<?php

use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use CleaniqueCoders\Shrinkr\Models\Url;
use Workbench\App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    config(['shrinkr.api.enabled' => true]);
    $this->user = User::factory()->create();
    $this->url = Url::factory()->create(['user_id' => $this->user->id]);
});

it('can get analytics for a url', function () {
    RedirectLog::factory()->count(10)->create(['url_id' => $this->url->id]);

    $response = getJson('/api/shrinkr/urls/'.$this->url->id.'/analytics');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'url_id',
                    'ip_address',
                    'user_agent',
                    'referrer',
                    'browser',
                    'platform',
                    'accessed_at',
                ],
            ],
        ]);
});

it('can get analytics summary for a url', function () {
    RedirectLog::factory()->count(20)->create(['url_id' => $this->url->id]);

    $response = getJson('/api/shrinkr/urls/'.$this->url->id.'/analytics/summary');

    $response->assertOk()
        ->assertJsonStructure([
            'url_id',
            'url_uuid',
            'shortened_url',
            'total_clicks',
            'unique_ips',
            'clicks_today',
            'clicks_this_week',
            'clicks_this_month',
            'top_referrers',
            'top_browsers',
            'top_platforms',
        ]);
});

it('can filter analytics by date range', function () {
    RedirectLog::factory()->create([
        'url_id' => $this->url->id,
        'created_at' => now()->subDays(10),
    ]);

    RedirectLog::factory()->count(5)->create([
        'url_id' => $this->url->id,
        'created_at' => now(),
    ]);

    $response = getJson('/api/shrinkr/urls/'.$this->url->id.'/analytics?start_date='.now()->subDay()->toDateString());

    $response->assertOk()
        ->assertJsonCount(5, 'data');
});
