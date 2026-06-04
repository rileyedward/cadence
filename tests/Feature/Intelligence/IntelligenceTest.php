<?php

use App\Models\Activity;
use App\Models\CheckIn;
use App\Models\DailyPlan;
use App\Models\Intent;
use App\Models\User;
use App\Queries\HistoryQuery;
use App\Services\Intelligence\EnergyAdvisor;
use App\Services\Intelligence\HabitDetector;
use App\Services\Intelligence\Optimizer;
use Carbon\CarbonImmutable;

function seedHistory(User $user, int $days, string $energy = 'high', string $status = 'completed'): Intent
{
    $intent = Intent::factory()->for($user)->create(['name' => 'Deep Work']);
    $activity = Activity::factory()->for($user)->create(['name' => 'Coding']);

    foreach (range(1, $days) as $d) {
        $plan = DailyPlan::factory()->for($user)->create([
            'template_id' => null,
            'date' => CarbonImmutable::now()->subDays($d)->toDateString(),
        ]);
        $block = $plan->blocks()->create([
            'name' => 'Focus', 'start_time' => '09:00', 'end_time' => '10:00',
            'flexibility_mode' => 'adaptive', 'intent_id' => $intent->id,
            'energy_level' => $energy, 'order' => 0,
        ]);
        $block->activities()->attach($activity->id, ['order' => 0, 'estimated_minutes' => 60]);
        CheckIn::factory()->create(['daily_plan_block_id' => $block->id, 'status' => $status]);
    }

    return $intent;
}

function historyFor(User $user): HistoryQuery
{
    return HistoryQuery::forRange($user, CarbonImmutable::now()->subDays(60), CarbonImmutable::now());
}

it('detects a habitual intent once the minimum sample is met', function () {
    config(['cadence.intelligence.min_samples' => 3]);
    $user = User::factory()->create();
    $intent = seedHistory($user, 4);

    $habits = new HabitDetector(historyFor($user));

    expect($habits->frequentIntentByBlockName())->toBe(['Focus' => $intent->id])
        ->and($habits->frequentActivitiesByBlockName()['Focus'] ?? [])->not->toBeEmpty();
});

it('returns nothing below the minimum sample (cold start)', function () {
    config(['cadence.intelligence.min_samples' => 3]);
    $user = User::factory()->create();
    seedHistory($user, 2); // below threshold

    $habits = new HabitDetector(historyFor($user));
    $energy = new EnergyAdvisor(historyFor($user));

    expect($habits->frequentIntentByBlockName())->toBe([])
        ->and($energy->typicalEnergyByBlockName())->toBe([]);
});

it('learns typical energy per block name', function () {
    config(['cadence.intelligence.min_samples' => 3]);
    $user = User::factory()->create();
    seedHistory($user, 4, energy: 'high');

    expect((new EnergyAdvisor(historyFor($user)))->typicalEnergyByBlockName())
        ->toBe(['Focus' => 'high']);
});

it('recommends adaptive mode for frequently skipped blocks', function () {
    config(['cadence.intelligence.min_samples' => 3]);
    $user = User::factory()->create();
    seedHistory($user, 4, status: 'skipped');

    $recs = (new Optimizer(historyFor($user)))->recommend();
    $skip = collect($recs)->firstWhere('kind', 'frequent_skip');

    expect($skip)->not->toBeNull()
        ->and($skip['target']['block_name'])->toBe('Focus')
        ->and($skip['action']['mode'])->toBe('adaptive');
});

it('is deterministic for a fixed seeded history', function () {
    config(['cadence.intelligence.min_samples' => 3]);
    $user = User::factory()->create();
    seedHistory($user, 4);

    $a = (new Optimizer(historyFor($user)))->recommend();
    $b = (new Optimizer(historyFor($user)))->recommend();

    expect($a)->toEqual($b);
});

it('serves plan-scoped insights as JSON', function () {
    config(['cadence.intelligence.min_samples' => 3]);
    $user = User::factory()->create();
    seedHistory($user, 4);

    $plan = DailyPlan::factory()->for($user)->create(['template_id' => null]);
    $plan->blocks()->create([
        'name' => 'Focus', 'start_time' => '09:00', 'end_time' => '10:00',
        'flexibility_mode' => 'adaptive', 'order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('plans.insights', $plan))
        ->assertOk()
        ->assertJsonStructure(['quick_fill', 'energy', 'recommendations']);
});
