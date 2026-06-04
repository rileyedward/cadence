<?php

use App\Enums\PlanStatus;
use App\Models\Block;
use App\Models\DailyPlan;
use App\Models\Template;
use App\Models\User;
use App\Services\Compiler\ScheduleGenerator;
use App\Services\Planning\DailyPlanFactory;

function planWithBlocks(User $user): DailyPlan
{
    $plan = DailyPlan::factory()->for($user)->create(['template_id' => null, 'status' => PlanStatus::Draft]);
    // Two adaptive blocks back-to-back so a buffer is inserted.
    $plan->blocks()->create([
        'name' => 'Morning', 'start_time' => '07:00', 'end_time' => '08:00',
        'flexibility_mode' => 'adaptive', 'order' => 0,
    ]);
    $plan->blocks()->create([
        'name' => 'Wind Down', 'start_time' => '01:30', 'end_time' => '02:00',
        'flexibility_mode' => 'adaptive', 'order' => 1,
    ]);

    return $plan;
}

it('generates a persisted, versioned, current schedule with events', function () {
    $user = User::factory()->create(['timezone' => 'UTC', 'day_start_time' => '04:00']);
    $plan = planWithBlocks($user);

    $schedule = app(ScheduleGenerator::class)->generate($plan);

    expect($schedule->version)->toBe(1)
        ->and($schedule->is_current)->toBeTrue()
        ->and($schedule->events()->where('type', 'block')->count())->toBe(2);

    // Past-day_start block (01:30) sorts after the 07:00 block via the +1440 rule.
    $events = $schedule->events()->orderBy('order')->get();
    $blocks = $events->where('type', 'block')->values();
    expect($blocks[0]->metadata['start_min'])->toBeLessThan($blocks[1]->metadata['start_min'])
        ->and($blocks[1]->label)->toBe('Wind Down')
        ->and($blocks[1]->metadata['start_min'])->toBeGreaterThan(1000); // 01:30 -> 1290-ish
});

it('increments versions and keeps a single current schedule', function () {
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $plan = planWithBlocks($user);
    $gen = app(ScheduleGenerator::class);

    $gen->generate($plan);
    $second = $gen->generate($plan);

    expect($second->version)->toBe(2)
        ->and($plan->schedules()->where('is_current', true)->count())->toBe(1)
        ->and($plan->currentSchedule->id)->toBe($second->id);
});

it('prunes old schedule versions beyond the configured maximum', function () {
    config(['cadence.max_schedule_versions' => 3]);
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $plan = planWithBlocks($user);
    $gen = app(ScheduleGenerator::class);

    foreach (range(1, 6) as $_) {
        $gen->generate($plan);
    }

    expect($plan->schedules()->count())->toBe(3)
        ->and($plan->schedules()->max('version'))->toBe(6)
        ->and($plan->schedules()->where('is_current', true)->count())->toBe(1);
});

it('produces an empty schedule for a blank day', function () {
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $plan = DailyPlan::factory()->for($user)->create(['template_id' => null]);

    $schedule = app(ScheduleGenerator::class)->generate($plan);

    expect($schedule->events()->count())->toBe(0);
});

it('generate endpoint flips plan status and returns the schedule', function () {
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $template = Template::factory()->for($user)->create();
    Block::factory()->for($template)->create(['start_time' => '09:00', 'end_time' => '10:00', 'order' => 0]);

    $plan = app(DailyPlanFactory::class)->instantiate($user, now(), $template);

    $this->actingAs($user)->post(route('plans.generate', $plan))->assertRedirect(route('plans.show', $plan));

    expect($plan->fresh()->status)->toBeIn([PlanStatus::Generated, PlanStatus::Active])
        ->and($plan->currentSchedule)->not->toBeNull();
});

it('preview compiles without persisting', function () {
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $plan = planWithBlocks($user);

    $this->actingAs($user)
        ->post(route('plans.preview', $plan))
        ->assertOk()
        ->assertJsonStructure(['events' => [['type', 'label', 'start_time', 'end_time', 'start_min', 'end_min']]]);

    expect($plan->schedules()->count())->toBe(0); // nothing persisted
});
