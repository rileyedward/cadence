<?php

namespace Database\Factories;

use App\Enums\FlexibilityMode;
use App\Models\Block;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    public function definition(): array
    {
        $startHour = fake()->numberBetween(6, 20);

        return [
            'template_id' => Template::factory(),
            'name' => fake()->randomElement(['Morning Routine', 'Work', 'Recovery', 'Activation', 'Freedom', 'Wind Down']),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', min(23, $startHour + 1)),
            'flexibility_mode' => fake()->randomElement(FlexibilityMode::cases()),
            'category' => fake()->optional()->word(),
            'priority' => 0,
            'constraints' => null,
            'context' => null,
            'order' => fake()->numberBetween(0, 10),
        ];
    }

    public function strict(): static
    {
        return $this->state(['flexibility_mode' => FlexibilityMode::Strict]);
    }

    public function window(string $start, string $end): static
    {
        return $this->state(['start_time' => $start, 'end_time' => $end]);
    }
}
