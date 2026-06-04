<?php

namespace Database\Factories;

use App\Models\Intent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Intent>
 */
class IntentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'user_id' => null, // system by default
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['sparkles', 'zap', 'moon', 'coffee', 'book']),
            'description' => fake()->optional()->sentence(),
            'plugin_key' => null,
        ];
    }

    public function system(): static
    {
        return $this->state(['user_id' => null]);
    }
}
