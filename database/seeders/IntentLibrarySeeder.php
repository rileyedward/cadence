<?php

namespace Database\Seeders;

use App\Models\Intent;
use Illuminate\Database\Seeder;

class IntentLibrarySeeder extends Seeder
{
    /**
     * System intent library (user_id null, read-only to users; clone-on-edit).
     */
    public function run(): void
    {
        $intents = [
            ['name' => 'Recovery', 'slug' => 'recovery', 'color' => '#60a5fa', 'icon' => 'moon', 'description' => 'Rest and recharge.'],
            ['name' => 'Deep Work', 'slug' => 'deep-work', 'color' => '#6366f1', 'icon' => 'brain', 'description' => 'Focused, demanding work.'],
            ['name' => 'Movement', 'slug' => 'movement', 'color' => '#22c55e', 'icon' => 'activity', 'description' => 'Exercise and physical activity.'],
            ['name' => 'Food', 'slug' => 'food', 'color' => '#f59e0b', 'icon' => 'utensils', 'description' => 'Meals and nourishment.'],
            ['name' => 'Bonding', 'slug' => 'bonding', 'color' => '#ec4899', 'icon' => 'heart', 'description' => 'Time with people you care about.'],
            ['name' => 'Creative', 'slug' => 'creative', 'color' => '#a855f7', 'icon' => 'palette', 'description' => 'Making and creating.'],
            ['name' => 'Leisure', 'slug' => 'leisure', 'color' => '#14b8a6', 'icon' => 'gamepad-2', 'description' => 'Play and downtime.'],
            ['name' => 'Errands', 'slug' => 'errands', 'color' => '#94a3b8', 'icon' => 'shopping-bag', 'description' => 'Chores and logistics.'],
            ['name' => 'Wind Down', 'slug' => 'wind-down', 'color' => '#818cf8', 'icon' => 'sunset', 'description' => 'Easing toward sleep.'],
            ['name' => 'Commute', 'slug' => 'commute', 'color' => '#64748b', 'icon' => 'car', 'description' => 'Getting from place to place.'],
        ];

        foreach ($intents as $intent) {
            Intent::query()->updateOrCreate(
                ['user_id' => null, 'slug' => $intent['slug']],
                $intent,
            );
        }
    }
}
