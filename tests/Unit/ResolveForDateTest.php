<?php

use App\Models\Template;
use App\Models\User;
use Carbon\Carbon;

function assignTemplate(User $user, string $name, array $assignment): Template
{
    $template = Template::factory()->for($user)->create(['name' => $name]);
    $template->assignments()->create($assignment);

    return $template;
}

it('resolves weekday vs weekend templates', function () {
    $user = User::factory()->create();
    $weekday = assignTemplate($user, 'Weekday', ['scope' => 'weekday', 'priority' => 0]);
    $weekend = assignTemplate($user, 'Weekend', ['scope' => 'weekend', 'priority' => 0]);

    // 2026-06-03 is a Wednesday; 2026-06-06 is a Saturday.
    expect(Template::resolveForDate($user, Carbon::parse('2026-06-03'))->id)->toBe($weekday->id)
        ->and(Template::resolveForDate($user, Carbon::parse('2026-06-06'))->id)->toBe($weekend->id);
});

it('resolves custom days-of-week', function () {
    $user = User::factory()->create();
    // Wednesday = 3.
    $custom = assignTemplate($user, 'Gym Days', ['scope' => 'custom', 'days_of_week' => [1, 3, 5], 'priority' => 0]);

    expect(Template::resolveForDate($user, Carbon::parse('2026-06-03'))->id)->toBe($custom->id) // Wed
        ->and(Template::resolveForDate($user, Carbon::parse('2026-06-04')))->toBeNull(); // Thu, no match
});

it('honours priority on conflict', function () {
    $user = User::factory()->create();
    assignTemplate($user, 'Low', ['scope' => 'weekday', 'priority' => 1]);
    $high = assignTemplate($user, 'High', ['scope' => 'weekday', 'priority' => 9]);

    expect(Template::resolveForDate($user, Carbon::parse('2026-06-03'))->id)->toBe($high->id);
});

it('respects date ranges', function () {
    $user = User::factory()->create();
    $seasonal = assignTemplate($user, 'Summer', [
        'scope' => 'weekday',
        'priority' => 5,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-30',
    ]);

    expect(Template::resolveForDate($user, Carbon::parse('2026-06-03'))->id)->toBe($seasonal->id)
        ->and(Template::resolveForDate($user, Carbon::parse('2026-07-03')))->toBeNull();
});

it('ignores inactive templates and other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $inactive = assignTemplate($user, 'Off', ['scope' => 'weekday', 'priority' => 0]);
    $inactive->update(['is_active' => false]);

    assignTemplate($other, 'Theirs', ['scope' => 'weekday', 'priority' => 0]);

    expect(Template::resolveForDate($user, Carbon::parse('2026-06-03')))->toBeNull();
});
