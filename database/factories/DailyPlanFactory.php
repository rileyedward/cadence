<?php

namespace Database\Factories;

use App\Enums\PlanStatus;
use App\Models\DailyPlan;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyPlan>
 */
class DailyPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'template_id' => Template::factory(),
            'date' => fake()->dateTimeBetween('-30 days', '+7 days')->format('Y-m-d'),
            'status' => PlanStatus::Draft,
        ];
    }
}
