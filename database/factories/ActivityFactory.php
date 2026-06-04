<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null, // system by default
            'name' => fake()->randomElement(['Nap', 'Bike ride', 'Coding', 'Reading', 'Gaming', 'Errands', 'Cat time']),
            'default_duration_minutes' => fake()->randomElement([15, 30, 45, 60, 90]),
            'tags' => fake()->optional()->randomElements(['rest', 'movement', 'focus', 'leisure'], 2),
            'plugin_key' => null,
        ];
    }
}
