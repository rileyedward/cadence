<?php

namespace Database\Factories;

use App\Models\DailyPlan;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'daily_plan_id' => DailyPlan::factory(),
            'version' => 1,
            'generated_at' => now(),
            'is_current' => true,
        ];
    }
}
