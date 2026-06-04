<?php

use App\Models\CheckIn;
use App\Models\DailyPlan;
use App\Models\Template;
use App\Models\TemplateAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;

it('renders the week view with resolved templates and statuses', function () {
    $user = User::factory()->create();
    $template = Template::factory()->for($user)->create(['name' => 'Weekday']);
    TemplateAssignment::factory()->for($template)->create(['scope' => 'weekday']);

    $this->actingAs($user)
        ->get(route('week'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Week/Index')->has('days', 7));
});

it('renders insights empty when there is no history', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('insights'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('History/Index')->where('hasHistory', false));
});

it('renders insights aggregates from history', function () {
    $user = User::factory()->create();

    foreach (range(1, 3) as $d) {
        $plan = DailyPlan::factory()->for($user)->create([
            'template_id' => null,
            'date' => CarbonImmutable::now()->subDays($d)->toDateString(),
        ]);
        $block = $plan->blocks()->create([
            'name' => 'Focus', 'start_time' => '09:00', 'end_time' => '10:00',
            'flexibility_mode' => 'adaptive', 'energy_level' => 'high', 'order' => 0,
        ]);
        CheckIn::factory()->create(['daily_plan_block_id' => $block->id, 'status' => 'completed']);
    }

    $this->actingAs($user)
        ->get(route('insights'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('History/Index')
            ->where('hasHistory', true)
            ->where('patterns.energy_distribution.high', 3)
        );
});
