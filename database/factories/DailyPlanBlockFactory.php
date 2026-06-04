<?php

namespace Database\Factories;

use App\Enums\FlexibilityMode;
use App\Models\DailyPlan;
use App\Models\DailyPlanBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyPlanBlock>
 */
class DailyPlanBlockFactory extends Factory
{
    public function definition(): array
    {
        $startHour = fake()->numberBetween(6, 20);

        return [
            'daily_plan_id' => DailyPlan::factory(),
            'block_id' => null,
            'name' => fake()->randomElement(['Morning Routine', 'Work', 'Recovery', 'Freedom']),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', min(23, $startHour + 1)),
            'flexibility_mode' => FlexibilityMode::Adaptive,
            'intent_id' => null,
            'secondary_intent_id' => null,
            'energy_level' => null,
            'focus_intensity' => null,
            'social_context' => null,
            'mobility_preference' => null,
            'constraints' => null,
            'context_tags' => null,
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}
