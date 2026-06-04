<?php

namespace Database\Seeders;

use App\Enums\FlexibilityMode;
use App\Enums\TemplateScope;
use App\Models\Intent;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dev/demo only: the spec's example weekday (doc 03 / doc 07 worked example).
 * Windows match doc 07 (day_start 04:00). Idempotent on the demo user + template name.
 */
class SampleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'demo@cadence.test'],
            [
                'first_name' => 'Demo',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'timezone' => 'America/Chicago',
                'day_start_time' => '04:00',
                'email_verified_at' => now(),
            ],
        );

        $intents = Intent::query()->whereNull('user_id')->get()->keyBy('slug');

        $template = Template::query()->updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Sample Weekday'],
            ['description' => 'The spec example day.', 'is_active' => true],
        );

        // Reset blocks so re-seeding is clean.
        $template->blocks()->delete();
        $template->assignments()->delete();

        $template->assignments()->create([
            'scope' => TemplateScope::Weekday,
            'priority' => 0,
        ]);

        // [name, start, end, mode, [default intent slugs (first = primary)]]
        $blocks = [
            ['Morning Routine', '07:00', '08:00', FlexibilityMode::Adaptive, ['recovery', 'movement']],
            ['Commute Prep', '08:00', '08:30', FlexibilityMode::Strict, ['commute']],
            ['Work', '08:30', '16:00', FlexibilityMode::Strict, ['deep-work']],
            ['Commute Home', '16:00', '16:30', FlexibilityMode::Strict, ['commute']],
            ['Recovery', '16:30', '18:00', FlexibilityMode::Adaptive, ['recovery']],
            ['Activation', '18:00', '20:00', FlexibilityMode::Soft, ['movement']],
            ['Freedom', '20:00', '01:30', FlexibilityMode::Adaptive, ['leisure', 'creative']],
            ['Wind Down', '01:30', '02:00', FlexibilityMode::Adaptive, ['wind-down']],
        ];

        foreach ($blocks as $order => [$name, $start, $end, $mode, $intentSlugs]) {
            $block = $template->blocks()->create([
                'name' => $name,
                'start_time' => $start,
                'end_time' => $end,
                'flexibility_mode' => $mode,
                'order' => $order,
            ]);

            $sync = [];
            foreach ($intentSlugs as $i => $slug) {
                if ($intent = $intents->get($slug)) {
                    $sync[$intent->id] = ['weight' => count($intentSlugs) - $i];
                }
            }
            $block->defaultIntents()->sync($sync);
        }
    }
}
