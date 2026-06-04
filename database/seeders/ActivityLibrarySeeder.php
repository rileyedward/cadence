<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Intent;
use Illuminate\Database\Seeder;

class ActivityLibrarySeeder extends Seeder
{
    /**
     * System activity library + intent suggestions (intent_activity pivot).
     * Depends on IntentLibrarySeeder having run.
     */
    public function run(): void
    {
        // name => [minutes, tags, [intent slugs to link]]
        $activities = [
            'Nap' => [30, ['rest'], ['recovery']],
            'Cat time' => [20, ['rest', 'leisure'], ['recovery', 'leisure']],
            'Bike ride' => [60, ['movement'], ['movement']],
            'Dinner run' => [45, ['movement', 'errands'], ['movement', 'errands']],
            'Errands' => [60, ['errands'], ['errands']],
            'Coding' => [120, ['focus'], ['deep-work', 'creative']],
            'Gaming' => [90, ['leisure'], ['leisure']],
            'Reading' => [45, ['leisure', 'wind-down'], ['leisure', 'wind-down']],
            'Morning routine' => [60, ['routine'], ['recovery', 'movement']],
            'Cooking' => [45, ['food'], ['food']],
        ];

        $intentsBySlug = Intent::query()->whereNull('user_id')->get()->keyBy('slug');

        foreach ($activities as $name => [$minutes, $tags, $intentSlugs]) {
            $activity = Activity::query()->updateOrCreate(
                ['user_id' => null, 'name' => $name],
                ['default_duration_minutes' => $minutes, 'tags' => $tags],
            );

            $sync = [];
            foreach ($intentSlugs as $i => $slug) {
                if ($intent = $intentsBySlug->get($slug)) {
                    $sync[$intent->id] = ['weight' => count($intentSlugs) - $i];
                }
            }
            $activity->intents()->sync($sync);
        }
    }
}
