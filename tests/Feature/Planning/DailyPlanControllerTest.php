<?php

use App\Enums\PlanStatus;
use App\Models\Activity;
use App\Models\Block;
use App\Models\DailyPlan;
use App\Models\Intent;
use App\Models\Template;
use App\Models\User;
use App\Support\LogicalDay;

function templateWithBlock(User $user, array $blockAttrs = []): array
{
    $template = Template::factory()->for($user)->create();
    $intent = Intent::factory()->for($user)->create();
    $block = Block::factory()->for($template)->create(array_merge([
        'name' => 'Work', 'start_time' => '08:30', 'end_time' => '16:00', 'order' => 0,
    ], $blockAttrs));
    $block->defaultIntents()->attach($intent->id, ['weight' => 5]);

    return [$template, $block, $intent];
}

it('instantiates a date, snapshotting blocks and prefilling default intents', function () {
    $user = User::factory()->create();
    [$template, $block, $intent] = templateWithBlock($user);

    $this->actingAs($user)
        ->post(route('plans.store'), ['date' => '2026-06-10', 'template_id' => $template->id])
        ->assertRedirect();

    $plan = DailyPlan::where('user_id', $user->id)->firstOrFail();
    $planBlock = $plan->blocks()->firstOrFail();

    expect($plan->status)->toBe(PlanStatus::Draft)
        ->and($planBlock->name)->toBe('Work')
        ->and(substr((string) $planBlock->start_time, 0, 5))->toBe('08:30')
        ->and($planBlock->intent_id)->toBe($intent->id);
});

it('does not let later template edits mutate an existing plan (snapshot)', function () {
    $user = User::factory()->create();
    [$template, $block] = templateWithBlock($user);

    $this->actingAs($user)->post(route('plans.store'), [
        'date' => '2026-06-11', 'template_id' => $template->id,
    ])->assertRedirect();

    // Mutate the source block afterwards.
    $block->update(['name' => 'Different', 'start_time' => '06:00']);

    $planBlock = DailyPlan::where('user_id', $user->id)->first()->blocks()->first();
    expect($planBlock->name)->toBe('Work')
        ->and(substr((string) $planBlock->start_time, 0, 5))->toBe('08:30');
});

it('returns the existing plan when re-instantiating the same date', function () {
    $user = User::factory()->create();
    [$template] = templateWithBlock($user);

    $this->actingAs($user)->post(route('plans.store'), ['date' => '2026-06-12', 'template_id' => $template->id]);
    $this->actingAs($user)->post(route('plans.store'), ['date' => '2026-06-12', 'template_id' => $template->id]);

    expect(DailyPlan::where('user_id', $user->id)->whereDate('date', '2026-06-12')->count())->toBe(1);
});

it('persists per-block decisions including activities with order and minutes', function () {
    $user = User::factory()->create();
    [$template, $block, $intent] = templateWithBlock($user);
    $activity = Activity::factory()->for($user)->create();

    $this->actingAs($user)->post(route('plans.store'), ['date' => '2026-06-13', 'template_id' => $template->id]);
    $plan = DailyPlan::where('user_id', $user->id)->first();
    $planBlock = $plan->blocks()->first();

    $this->actingAs($user)->put(route('plans.update', $plan), [
        'blocks' => [[
            'id' => $planBlock->id,
            'intent_id' => $intent->id,
            'energy_level' => 'high',
            'focus_intensity' => 'deep',
            'activities' => [
                ['activity_id' => $activity->id, 'estimated_minutes' => 90, 'order' => 0],
            ],
        ]],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $planBlock->refresh();
    expect($planBlock->energy_level->value)->toBe('high')
        ->and($planBlock->focus_intensity->value)->toBe('deep')
        ->and($planBlock->activities()->first()->pivot->estimated_minutes)->toBe(90);
});

it('flips past plans to done and todays generated plan to active on load', function () {
    $user = User::factory()->create(['timezone' => 'UTC', 'day_start_time' => '04:00']);

    $past = DailyPlan::factory()->for($user)->create([
        'date' => now()->subDays(3)->toDateString(), 'status' => PlanStatus::Generated,
    ]);
    $today = DailyPlan::factory()->for($user)->create([
        'date' => LogicalDay::currentDate($user)->toDateString(),
        'status' => PlanStatus::Generated,
    ]);

    $this->actingAs($user)->get(route('plans.index'))->assertOk();

    expect($past->fresh()->status)->toBe(PlanStatus::Done)
        ->and($today->fresh()->status)->toBe(PlanStatus::Active);
});

it('blocks cross-user plan access', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $plan = DailyPlan::factory()->for($owner)->create();

    $this->actingAs($other)->get(route('plans.show', $plan))->assertForbidden();
    $this->actingAs($other)->put(route('plans.update', $plan), ['blocks' => []])->assertForbidden();
    $this->actingAs($other)->delete(route('plans.destroy', $plan))->assertForbidden();
});
