<?php

use App\Models\CheckIn;
use App\Models\DailyPlan;
use App\Models\User;
use App\Services\Compiler\Recompiler;
use App\Services\Compiler\ScheduleGenerator;

function generatedPlan(User $user): DailyPlan
{
    $plan = DailyPlan::factory()->for($user)->create(['template_id' => null, 'status' => 'generated']);
    foreach ([['A', '07:00', '08:00'], ['B', '08:00', '09:00'], ['C', '09:00', '10:00']] as $i => [$name, $s, $e]) {
        $plan->blocks()->create([
            'name' => $name, 'start_time' => $s, 'end_time' => $e,
            'flexibility_mode' => 'adaptive', 'order' => $i,
        ]);
    }
    app(ScheduleGenerator::class)->generate($plan);

    return $plan->fresh();
}

function blockId(DailyPlan $plan, string $name): int
{
    return $plan->blocks()->where('name', $name)->value('id');
}

it('freezes a started block verbatim during recompile', function () {
    $user = User::factory()->create(['day_start_time' => '04:00', 'timezone' => 'UTC']);
    $plan = generatedPlan($user);

    // A spans minutes 180..240 (07:00-08:00 from a 04:00 day start). Mark it started.
    CheckIn::factory()->started()->create(['daily_plan_block_id' => blockId($plan, 'A')]);

    $newSchedule = app(Recompiler::class)->recompile($plan->currentSchedule, fromMin: 200);

    $aEvent = $newSchedule->events()->where('source_block_id', blockId($plan, 'A'))->first();
    expect($aEvent->metadata['start_min'])->toBe(180)
        ->and($aEvent->metadata['end_min'])->toBe(240)
        ->and($aEvent->metadata['frozen'] ?? false)->toBeTrue();
});

it('freezes events that already ended before fromMin', function () {
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $plan = generatedPlan($user);

    // fromMin 250: A (180..240) is fully past and frozen.
    $newSchedule = app(Recompiler::class)->recompile($plan->currentSchedule, fromMin: 250);

    $aEvent = $newSchedule->events()->where('source_block_id', blockId($plan, 'A'))->first();
    expect($aEvent->metadata['start_min'])->toBe(180)
        ->and($aEvent->metadata['end_min'])->toBe(240)
        ->and($aEvent->metadata['frozen'] ?? false)->toBeTrue();
});

it('skips a block and reflows the rest', function () {
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $plan = generatedPlan($user);

    $newSchedule = app(Recompiler::class)->recompile(
        $plan->currentSchedule,
        fromMin: 100,
        ops: [['op' => 'skip', 'block_id' => blockId($plan, 'B')]],
    );

    expect($newSchedule->events()->where('source_block_id', blockId($plan, 'B'))->exists())->toBeFalse()
        ->and($newSchedule->events()->where('source_block_id', blockId($plan, 'C'))->exists())->toBeTrue();
});

it('extends a block duration via a runtime op', function () {
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $plan = generatedPlan($user);

    $newSchedule = app(Recompiler::class)->recompile(
        $plan->currentSchedule,
        fromMin: 100,
        ops: [['op' => 'extend', 'block_id' => blockId($plan, 'A'), 'delta' => 30]],
    );

    $aEvent = $newSchedule->events()->where('source_block_id', blockId($plan, 'A'))->first();
    // A's window is 60 min; extending by 30 makes it 90.
    expect($aEvent->metadata['end_min'] - $aEvent->metadata['start_min'])->toBe(90);
});

it('persists a new current schedule version on recompile', function () {
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $plan = generatedPlan($user);
    $firstVersion = $plan->currentSchedule->version;

    $newSchedule = app(Recompiler::class)->recompile($plan->currentSchedule, fromMin: 100);

    expect($newSchedule->version)->toBe($firstVersion + 1)
        ->and($newSchedule->is_current)->toBeTrue()
        ->and($plan->fresh()->schedules()->where('is_current', true)->count())->toBe(1);
});
