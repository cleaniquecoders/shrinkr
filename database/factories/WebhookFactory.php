<?php

namespace CleaniqueCoders\Shrinkr\Database\Factories;

use CleaniqueCoders\Shrinkr\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::orderedUuid(),
            'user_id' => null,
            'url' => fake()->url(),
            'events' => fake()->randomElements(
                ['url.created', 'url.updated', 'url.deleted', 'url.accessed', 'url.expired'],
                fake()->numberBetween(1, 3)
            ),
            'secret' => bin2hex(random_bytes(32)),
            'is_active' => true,
            'last_triggered_at' => null,
        ];
    }

    /**
     * Indicate that the webhook is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the webhook has been triggered.
     */
    public function triggered(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_triggered_at' => now(),
        ]);
    }
}
