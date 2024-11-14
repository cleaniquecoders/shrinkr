<?php

namespace CleaniqueCoders\Shrinkr\Database\Factories;

use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RedirectLogFactory extends Factory
{
    protected $model = RedirectLog::class;

    public function definition()
    {
        return [
            'uuid' => Str::uuid(),
            'url_id' => Url::factory(), // Assumes you have a factory for Url model
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referrer' => fake()->url(),
            'headers' => json_encode(['Accept' => 'text/html', 'User-Agent' => fake()->userAgent()]),
            'query_params' => json_encode(['utm_source' => fake()->word, 'utm_medium' => fake()->word]),
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'browser_version' => fake()->randomElement(['89.0', '90.0', '91.0']),
            'platform' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
            'platform_version' => fake()->randomElement(['10', '11', '12']),
            'is_mobile' => fake()->boolean(),
            'is_desktop' => ! fake()->boolean(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
