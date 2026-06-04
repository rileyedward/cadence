<?php

namespace Database\Factories;

use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Weekday', 'Weekend', 'Travel', 'Recovery']).' '.fake()->word(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'forked_from_id' => null,
        ];
    }
}
