<?php

namespace CleaniqueCoders\Shrinkr\Database\Factories;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModelFactory extends Factory
{
    protected $model = Url::class;

    public function definition()
    {
        return [
            'original_url' => $this->faker->url,
            'shortened_url' => $this->generateShortCode(),
            'custom_slug' => null,
            'is_expired' => false,
        ];
    }

    private function generateShortCode()
    {
        return substr(md5(uniqid(rand(), true)), 0, 6);
    }
}
