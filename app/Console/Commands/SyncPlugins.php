<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Intent;
use App\Plugins\PluginRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Idempotently syncs config-registered intent modules into the system library,
 * tagging rows with their plugin key, and prunes rows for disabled modules
 * without touching user data (doc 14).
 */
class SyncPlugins extends Command
{
    protected $signature = 'cadence:sync-plugins';

    protected $description = 'Register intents/activities from enabled plugin modules (idempotent).';

    public function handle(PluginRegistry $registry): int
    {
        $activeKeys = $registry->activeModuleKeys();

        DB::transaction(function () use ($registry, $activeKeys) {
            foreach ($registry->intentModules() as $module) {
                $this->syncIntents($module->key(), $module->intents());
                $this->syncActivities($module->key(), $module->activities());
            }

            // Remove rows for modules that are no longer enabled.
            Intent::whereNotNull('plugin_key')->whereNotIn('plugin_key', $activeKeys ?: ['__none__'])->delete();
            Activity::whereNotNull('plugin_key')->whereNotIn('plugin_key', $activeKeys ?: ['__none__'])->delete();
        });

        $this->info('Synced '.count($activeKeys).' plugin module(s): '.(implode(', ', $activeKeys) ?: 'none'));

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string,mixed>>  $intents
     */
    private function syncIntents(string $key, array $intents): void
    {
        foreach ($intents as $def) {
            Intent::updateOrCreate(
                ['user_id' => null, 'slug' => $def['slug']],
                [
                    'name' => $def['name'],
                    'color' => $def['color'] ?? null,
                    'icon' => $def['icon'] ?? null,
                    'description' => $def['description'] ?? null,
                    'plugin_key' => $key,
                ],
            );
        }
    }

    /**
     * @param  array<int, array<string,mixed>>  $activities
     */
    private function syncActivities(string $key, array $activities): void
    {
        $intentsBySlug = Intent::whereNull('user_id')->pluck('id', 'slug');

        foreach ($activities as $def) {
            $activity = Activity::updateOrCreate(
                ['user_id' => null, 'name' => $def['name']],
                [
                    'default_duration_minutes' => $def['default_duration_minutes'],
                    'tags' => $def['tags'] ?? [],
                    'plugin_key' => $key,
                ],
            );

            $sync = [];
            foreach ($def['intents'] ?? [] as $slug) {
                if ($id = $intentsBySlug->get($slug)) {
                    $sync[$id] = ['weight' => 1];
                }
            }
            $activity->intents()->syncWithoutDetaching($sync);
        }
    }
}
