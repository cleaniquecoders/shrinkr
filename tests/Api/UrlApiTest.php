<?php

use CleaniqueCoders\Shrinkr\Models\Url;
use Workbench\App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['shrinkr.api.enabled' => true]);
    $this->user = User::factory()->create();
});

it('can list urls', function () {
    Url::factory()->count(5)->create(['user_id' => $this->user->id]);

    $response = getJson('/api/shrinkr/urls');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'uuid',
                    'user_id',
                    'original_url',
                    'shortened_url',
                    'custom_slug',
                    'is_expired',
                    'created_at',
                ],
            ],
            'meta',
            'links',
        ]);
});

it('can filter urls by user', function () {
    Url::factory()->count(3)->create(['user_id' => $this->user->id]);
    Url::factory()->count(2)->create();

    $response = getJson('/api/shrinkr/urls?user_id='.$this->user->id);

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('can create a url', function () {
    $data = [
        'original_url' => 'https://example.com/long-url',
        'user_id' => $this->user->id,
    ];

    $response = postJson('/api/shrinkr/urls', $data);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'uuid',
                'original_url',
                'shortened_url',
            ],
        ]);

    $this->assertDatabaseHas('urls', [
        'original_url' => 'https://example.com/long-url',
        'user_id' => $this->user->id,
    ]);
});

it('can create a url with custom slug', function () {
    $data = [
        'original_url' => 'https://example.com/long-url',
        'custom_slug' => 'my-custom-slug',
        'user_id' => $this->user->id,
    ];

    $response = postJson('/api/shrinkr/urls', $data);

    $response->assertCreated()
        ->assertJson([
            'data' => [
                'custom_slug' => 'my-custom-slug',
            ],
        ]);
});

it('validates required fields when creating url', function () {
    $response = postJson('/api/shrinkr/urls', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['original_url']);
});

it('can show a single url by id', function () {
    $url = Url::factory()->create(['user_id' => $this->user->id]);

    $response = getJson('/api/shrinkr/urls/'.$url->id);

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $url->id,
                'original_url' => $url->original_url,
            ],
        ]);
});

it('can show a single url by uuid', function () {
    $url = Url::factory()->create(['user_id' => $this->user->id]);

    $response = getJson('/api/shrinkr/urls/'.$url->uuid);

    $response->assertOk()
        ->assertJson([
            'data' => [
                'uuid' => $url->uuid,
            ],
        ]);
});

it('can update a url', function () {
    $url = Url::factory()->create(['user_id' => $this->user->id]);

    $data = [
        'original_url' => 'https://example.com/updated-url',
    ];

    $response = patchJson('/api/shrinkr/urls/'.$url->id, $data);

    $response->assertOk()
        ->assertJson([
            'data' => [
                'original_url' => 'https://example.com/updated-url',
            ],
        ]);

    $this->assertDatabaseHas('urls', [
        'id' => $url->id,
        'original_url' => 'https://example.com/updated-url',
    ]);
});

it('can delete a url', function () {
    $url = Url::factory()->create(['user_id' => $this->user->id]);

    $response = deleteJson('/api/shrinkr/urls/'.$url->id);

    $response->assertOk()
        ->assertJson([
            'message' => 'URL deleted successfully',
        ]);

    $this->assertDatabaseMissing('urls', [
        'id' => $url->id,
    ]);
});

it('can get url statistics', function () {
    Url::factory()->count(10)->create(['user_id' => $this->user->id]);
    Url::factory()->count(5)->create(['user_id' => $this->user->id, 'is_expired' => true]);

    $response = getJson('/api/shrinkr/urls/stats?user_id='.$this->user->id);

    $response->assertOk()
        ->assertJsonStructure([
            'total_urls',
            'active_urls',
            'expired_urls',
            'urls_with_custom_slug',
            'urls_with_expiry',
        ]);
});
