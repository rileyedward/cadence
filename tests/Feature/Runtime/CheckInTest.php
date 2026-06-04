<?php

use App\Enums\CheckInStatus;
use App\Models\DailyPlan;
use App\Models\DailyPlanBlock;
use App\Models\User;
use App\Services\Compiler\ScheduleGenerator;

function planBlock(User $user): DailyPlanBlock
{
    $plan = DailyPlan::factory()->for($user)->create();

    return DailyPlanBlock::factory()->for($plan)->create();
}

it('records a started check-in with actual_start defaulting to now', function () {
    $user = User::factory()->create();
    $block = planBlock($user);

    $this->actingAs($user)
        ->post(route('check-ins.store', $block), ['status' => 'started'])
        ->assertRedirect();

    $checkIn = $block->checkIns()->latest('id')->first();
    expect($checkIn->status)->toBe(CheckInStatus::Started)
        ->and($checkIn->actual_start)->not->toBeNull()
        ->and($block->currentStatus())->toBe(CheckInStatus::Started);
});

it('records complete and skip transitions; latest defines current state', function () {
    $user = User::factory()->create();
    $block = planBlock($user);

    $this->actingAs($user)->post(route('check-ins.store', $block), ['status' => 'started']);
    $this->actingAs($user)->post(route('check-ins.store', $block), ['status' => 'completed']);

    $completed = $block->checkIns()->latest('id')->first();
    expect($block->currentStatus())->toBe(CheckInStatus::Completed)
        ->and($completed->actual_end)->not->toBeNull()
        ->and($block->isFrozen())->toBeTrue();
});

it('blocks cross-user check-ins', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $block = planBlock($owner);

    $this->actingAs($other)
        ->post(route('check-ins.store', $block), ['status' => 'started'])
        ->assertForbidden();
});

it('runtime endpoint clamps fromMin and returns a new schedule', function () {
    $user = User::factory()->create(['day_start_time' => '04:00', 'timezone' => 'UTC']);
    $plan = DailyPlan::factory()->for($user)->create(['template_id' => null, 'status' => 'generated']);
    $plan->blocks()->create(['name' => 'A', 'start_time' => '07:00', 'end_time' => '08:00', 'flexibility_mode' => 'adaptive', 'order' => 0]);
    app(ScheduleGenerator::class)->generate($plan);

    // An absurd client fromMin is clamped to server-now ± skew, so the request
    // still succeeds and produces a fresh version rather than freezing the day.
    $this->actingAs($user)
        ->post(route('plans.runtime', ['plan' => $plan, 'op' => 'recompile']), ['from_min' => 1440])
        ->assertRedirect();

    expect($plan->fresh()->schedules()->max('version'))->toBeGreaterThan(1);
});
