<?php

namespace Database\Factories;

use App\Enums\CheckInStatus;
use App\Models\CheckIn;
use App\Models\DailyPlanBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckIn>
 */
class CheckInFactory extends Factory
{
    public function definition(): array
    {
        return [
            'daily_plan_block_id' => DailyPlanBlock::factory(),
            'status' => CheckInStatus::Completed,
            'actual_start' => now()->subHour(),
            'actual_end' => now(),
            'note' => null,
        ];
    }

    public function started(): static
    {
        return $this->state(['status' => CheckInStatus::Started, 'actual_end' => null]);
    }

    public function skipped(): static
    {
        return $this->state(['status' => CheckInStatus::Skipped, 'actual_start' => null, 'actual_end' => null]);
    }
}
