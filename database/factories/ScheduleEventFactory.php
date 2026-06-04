<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Schedule;
use App\Models\ScheduleEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleEvent>
 */
class ScheduleEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'source_block_id' => null,
            'type' => EventType::Block,
            'label' => fake()->randomElement(['Work', 'Recovery', 'Freedom']),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'order' => fake()->numberBetween(0, 10),
            'metadata' => null,
        ];
    }
}
