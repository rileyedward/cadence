<?php

namespace Database\Factories;

use App\Models\CalendarBusyEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CalendarBusyEvent>
 */
class CalendarBusyEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => now()->toDateString(),
            'source_uid' => Str::uuid()->toString(),
            'title' => fake()->randomElement(['Standup', 'Dentist', '1:1', 'Lunch']),
            'start_time' => '11:00',
            'end_time' => '12:00',
        ];
    }
}
