<?php

namespace CleaniqueCoders\Shrinkr\Database\Factories;

use CleaniqueCoders\Shrinkr\Models\Webhook;
use CleaniqueCoders\Shrinkr\Models\WebhookCall;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookCall>
 */
class WebhookCallFactory extends Factory
{
    protected $model = WebhookCall::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::orderedUuid(),
            'webhook_id' => Webhook::factory(),
            'event' => fake()->randomElement(['url.created', 'url.updated', 'url.deleted', 'url.accessed', 'url.expired']),
            'payload' => [
                'event' => 'url.created',
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'id' => fake()->randomNumber(),
                    'shortened_url' => fake()->slug(),
                ],
            ],
            'status' => 'pending',
            'attempts' => 0,
            'response_status_code' => null,
            'response_body' => null,
            'error_message' => null,
            'delivered_at' => null,
            'failed_at' => null,
            'next_retry_at' => null,
        ];
    }

    /**
     * Indicate that the webhook call was successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'attempts' => 1,
            'response_status_code' => 200,
            'response_body' => json_encode(['success' => true]),
            'delivered_at' => now(),
            'next_retry_at' => null,
        ]);
    }

    /**
     * Indicate that the webhook call failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'attempts' => 3,
            'response_status_code' => 500,
            'error_message' => 'Internal Server Error',
            'failed_at' => now(),
            'next_retry_at' => null,
        ]);
    }

    /**
     * Indicate that the webhook call is pending retry.
     */
    public function pendingRetry(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'attempts' => fake()->numberBetween(1, 2),
            'error_message' => 'Connection timeout',
            'next_retry_at' => now()->addMinutes(5),
        ]);
    }
}
