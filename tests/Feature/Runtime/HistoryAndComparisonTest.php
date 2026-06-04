<?php

use App\Models\CheckIn;
use App\Models\DailyPlan;
use App\Models\Intent;
use App\Models\User;
use App\Queries\HistoryQuery;
use App\Queries\PlanBlockComparison;
use App\Services\Compiler\ScheduleGenerator;
use Carbon\CarbonImmutable;

it('computes planned vs actual deltas from a check-in', function () {
    $user = User::factory()->create(['day_start_time' => '04:00', 'timezone' => 'UTC']);
    $plan = DailyPlan::factory()->for($user)->create(['template_id' => null]);
    $block = $plan->blocks()->create([
        'name' => 'Work', 'start_time' => '09:00', 'end_time' => '10:00',
        'flexibility_mode' => 'adaptive', 'order' => 0,
    ]);
    app(ScheduleGenerator::class)->generate($plan);

    // Planned 09:00-10:00 (300..360). Actual 09:10-10:30 in UTC.
    CheckIn::factory()->create([
        'daily_plan_block_id' => $block->id,
        'status' => 'completed',
        'actual_start' => CarbonImmutable::parse('09:10', 'UTC'),
        'actual_end' => CarbonImmutable::parse('10:30', 'UTC'),
    ]);

    $cmp = collect(PlanBlockComparison::forPlan($plan->fresh()))->firstWhere('label', 'Work');

    expect($cmp->plannedMinutes)->toBe(60)
        ->and($cmp->actualMinutes)->toBe(80)
        ->and($cmp->startDeltaMin)->toBe(10)   // started 10 min late
        ->and($cmp->durationDeltaMin)->toBe(20); // ran 20 min long
});

it('aggregates history across multiple past plans', function () {
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $intent = Intent::factory()->for($user)->create(['name' => 'Deep Work']);

    foreach (range(1, 3) as $d) {
        $plan = DailyPlan::factory()->for($user)->create([
            'template_id' => null,
            'date' => now()->subDays($d)->toDateString(),
        ]);
        $block = $plan->blocks()->create([
            'name' => 'Focus', 'start_time' => '09:00', 'end_time' => '10:00',
            'flexibility_mode' => 'adaptive', 'intent_id' => $intent->id,
            'energy_level' => 'high', 'order' => 0,
        ]);
        CheckIn::factory()->create([
            'daily_plan_block_id' => $block->id,
            'status' => $d === 3 ? 'skipped' : 'completed',
        ]);
    }

    $history = HistoryQuery::forRange($user, now()->subDays(10), now());

    expect($history->completionRateByIntent()['Deep Work']['total'])->toBe(3)
        ->and($history->completionRateByIntent()['Deep Work']['completed'])->toBe(2)
        ->and($history->skipCountByBlockName()['Focus'])->toBe(1)
        ->and($history->energyDistribution()['high'])->toBe(3);
});
