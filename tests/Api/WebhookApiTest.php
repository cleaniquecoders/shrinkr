<?php

use CleaniqueCoders\Shrinkr\Jobs\DispatchWebhookJob;
use CleaniqueCoders\Shrinkr\Models\Webhook;
use CleaniqueCoders\Shrinkr\Models\WebhookCall;
use Illuminate\Support\Facades\Queue;
use Workbench\App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['shrinkr.api.enabled' => true]);
    config(['shrinkr.webhooks.enabled' => true]);
    $this->user = User::factory()->create();
});

it('can list webhooks', function () {
    Webhook::factory()->count(5)->create(['user_id' => $this->user->id]);

    $response = getJson('/api/shrinkr/webhooks');

    $response->assertOk()
        ->assertJsonCount(5, 'data');
});

it('can create a webhook', function () {
    $data = [
        'url' => 'https://example.com/webhook',
        'events' => ['url.created', 'url.accessed'],
        'user_id' => $this->user->id,
    ];

    $response = postJson('/api/shrinkr/webhooks', $data);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'uuid',
                'url',
                'events',
                'is_active',
            ],
        ]);

    $this->assertDatabaseHas('webhooks', [
        'url' => 'https://example.com/webhook',
        'user_id' => $this->user->id,
    ]);
});

it('validates webhook events', function () {
    $data = [
        'url' => 'https://example.com/webhook',
        'events' => ['invalid.event'],
        'user_id' => $this->user->id,
    ];

    $response = postJson('/api/shrinkr/webhooks', $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['events.0']);
});

it('can show a single webhook', function () {
    $webhook = Webhook::factory()->create(['user_id' => $this->user->id]);

    $response = getJson('/api/shrinkr/webhooks/'.$webhook->id);

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $webhook->id,
                'url' => $webhook->url,
            ],
        ]);
});

it('can update a webhook', function () {
    $webhook = Webhook::factory()->create(['user_id' => $this->user->id]);

    $data = [
        'events' => ['url.expired'],
        'is_active' => false,
    ];

    $response = patchJson('/api/shrinkr/webhooks/'.$webhook->id, $data);

    $response->assertOk()
        ->assertJson([
            'data' => [
                'events' => ['url.expired'],
                'is_active' => false,
            ],
        ]);
});

it('can delete a webhook', function () {
    $webhook = Webhook::factory()->create(['user_id' => $this->user->id]);

    $response = deleteJson('/api/shrinkr/webhooks/'.$webhook->id);

    $response->assertOk();

    $this->assertDatabaseMissing('webhooks', [
        'id' => $webhook->id,
    ]);
});

it('can test webhook delivery', function () {
    Queue::fake();

    $webhook = Webhook::factory()->create(['user_id' => $this->user->id]);

    $response = postJson('/api/shrinkr/webhooks/'.$webhook->id.'/test');

    $response->assertOk()
        ->assertJson([
            'message' => 'Test webhook dispatched successfully',
        ]);

    Queue::assertPushed(DispatchWebhookJob::class);
});

it('can get webhook delivery history', function () {
    $webhook = Webhook::factory()->create(['user_id' => $this->user->id]);
    WebhookCall::factory()->count(10)->create(['webhook_id' => $webhook->id]);

    $response = getJson('/api/shrinkr/webhooks/'.$webhook->id.'/calls');

    $response->assertOk()
        ->assertJsonStructure([
            'webhook_id',
            'data',
            'meta',
        ]);
});
