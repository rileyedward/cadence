<?php

use App\Models\Activity;
use App\Models\Intent;
use App\Models\Template;
use App\Models\User;
use App\Plugins\PluginRegistry;
use App\Plugins\Samples\FitnessModule;
use App\Plugins\Samples\FocusSprintBlockType;

beforeEach(function () {
    config([
        'cadence.plugins.intent_modules' => [FitnessModule::class],
        'cadence.plugins.block_types' => [FocusSprintBlockType::class],
    ]);
});

it('registers plugin intents and activities idempotently', function () {
    $this->artisan('cadence:sync-plugins')->assertSuccessful();
    $this->artisan('cadence:sync-plugins')->assertSuccessful(); // re-run

    expect(Intent::where('plugin_key', 'fitness')->count())->toBe(2)
        ->and(Activity::where('plugin_key', 'fitness')->count())->toBe(3);

    // Activity linked to its intent by slug.
    $training = Intent::where('slug', 'training')->first();
    expect($training->activities()->where('name', 'Strength training')->exists())->toBeTrue();
});

it('exposes block type definitions with defaults', function () {
    $defs = app(PluginRegistry::class)->blockTypeDefinitions();

    expect($defs)->toHaveCount(1)
        ->and($defs[0]['key'])->toBe('focus-sprint')
        ->and($defs[0]['defaults']['flexibility_mode'])->toBe('strict')
        ->and($defs[0]['defaults']['constraints']['minDuration'])->toBe(25);
});

it('prunes plugin rows when a module is disabled', function () {
    $this->artisan('cadence:sync-plugins')->assertSuccessful();
    expect(Intent::where('plugin_key', 'fitness')->count())->toBe(2);

    // Disable the module and re-sync.
    config(['cadence.plugins.intent_modules' => []]);
    app()->forgetInstance(PluginRegistry::class);
    $this->artisan('cadence:sync-plugins')->assertSuccessful();

    expect(Intent::where('plugin_key', 'fitness')->count())->toBe(0)
        ->and(Activity::where('plugin_key', 'fitness')->count())->toBe(0);
});

it('surfaces block types in the template editor props', function () {
    $user = User::factory()->create();
    $template = Template::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('templates.edit', $template))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('blockTypes', 1));
});
