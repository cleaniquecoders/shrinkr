<?php

namespace CleaniqueCoders\Shrinkr\Database\Factories;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Workbench\App\Models\User;

class UrlFactory extends Factory
{
    protected $model = Url::class;

    public function definition()
    {
        return [
            'uuid' => Str::uuid(),
            'user_id' => User::factory(),
            'original_url' => fake()->url(),
            'shortened_url' => $this->generateShortCode(),
            'custom_slug' => null,
            'is_expired' => false,
        ];
    }

    private function generateShortCode()
    {
        return substr(md5(uniqid((string) rand(), true)), 0, 6);
    }
}
