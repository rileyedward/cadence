<?php

namespace App\Plugins\Samples;

use App\Models\DailyPlanBlock;
use App\Plugins\Contracts\IntentModule;

/**
 * Sample intent module: adds fitness-oriented intents + activities.
 */
class FitnessModule implements IntentModule
{
    public function key(): string
    {
        return 'fitness';
    }

    public function name(): string
    {
        return 'Fitness';
    }

    public function intents(): array
    {
        return [
            ['name' => 'Training', 'slug' => 'training', 'color' => '#ef4444', 'icon' => 'dumbbell', 'description' => 'Structured exercise.'],
            ['name' => 'Mobility', 'slug' => 'mobility', 'color' => '#10b981', 'icon' => 'stretch-horizontal', 'description' => 'Stretching and recovery work.'],
        ];
    }

    public function activities(): array
    {
        return [
            ['name' => 'Strength training', 'default_duration_minutes' => 45, 'tags' => ['movement'], 'intents' => ['training']],
            ['name' => 'Run', 'default_duration_minutes' => 30, 'tags' => ['movement'], 'intents' => ['training']],
            ['name' => 'Stretching', 'default_duration_minutes' => 15, 'tags' => ['recovery'], 'intents' => ['mobility']],
        ];
    }

    public function suggestions(?DailyPlanBlock $block): array
    {
        return [];
    }
}
