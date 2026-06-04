<?php

use App\Models\CalendarBusyEvent;
use App\Models\DailyPlan;
use App\Models\Schedule;
use App\Models\User;
use App\Services\Calendar\CalendarPushResult;
use App\Services\Calendar\CalendarSync;
use App\Services\Calendar\NullCalendarDriver;
use App\Services\Compiler\ScheduleGenerator;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/** A configurable in-memory CalendarSync for tests (no live Google calls). */
function fakeCalendar(array $busy = [], ?int &$pushCount = null): CalendarSync
{
    return new class($busy, $pushCount) implements CalendarSync
    {
        public function __construct(private array $busy, private ?int &$pushCount) {}

        public function isConfigured(): bool
        {
            return true;
        }

        public function connectUrl(User $user): string
        {
            return 'https://accounts.google.test/auth';
        }

        public function handleCallback(User $user, Request $request): void {}

        public function pushSchedule(Schedule $schedule): CalendarPushResult
        {
            $this->pushCount = ($this->pushCount ?? 0) + 1;

            return new CalendarPushResult(created: $schedule->events()->count());
        }

        public function importEvents(User $user, CarbonInterface $date): array
        {
            return $this->busy;
        }

        public function disconnect(User $user): void {}
    };
}

it('works fully with no calendar provider configured', function () {
    config(['cadence.calendar.driver' => 'null']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar.settings'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/Calendar')->where('configured', false));

    // The null driver is the bound implementation.
    expect(app(CalendarSync::class))->toBeInstanceOf(NullCalendarDriver::class);
});

it('imports busy events and stores them', function () {
    $user = User::factory()->create();
    app()->instance(CalendarSync::class, fakeCalendar([
        ['source_uid' => 'evt-1', 'title' => 'Dentist', 'start_time' => '11:00', 'end_time' => '12:00'],
    ]));

    $this->actingAs($user)
        ->post(route('calendar.import'), ['date' => '2026-06-10'])
        ->assertRedirect();

    expect(CalendarBusyEvent::where('user_id', $user->id)->count())->toBe(1)
        ->and(CalendarBusyEvent::first()->title)->toBe('Dentist');
});

it('treats imported busy events as strict anchors the compiler respects', function () {
    $user = User::factory()->create(['day_start_time' => '04:00', 'timezone' => 'UTC']);
    $plan = DailyPlan::factory()->for($user)->create(['template_id' => null]);
    $plan->blocks()->create([
        'name' => 'Work', 'start_time' => '09:00', 'end_time' => '17:00',
        'flexibility_mode' => 'adaptive', 'order' => 0,
    ]);
    // Busy 12:00-13:00 -> minutes 480..540 from a 04:00 day start.
    CalendarBusyEvent::factory()->create([
        'user_id' => $user->id, 'date' => $plan->date->toDateString(),
        'title' => 'Lunch meeting', 'start_time' => '12:00', 'end_time' => '13:00',
    ]);

    $schedule = app(ScheduleGenerator::class)->generate($plan);
    $events = $schedule->events()->get();

    $busy = $events->firstWhere('label', 'Lunch meeting');
    $work = $events->firstWhere('label', 'Work');

    expect($busy)->not->toBeNull()
        ->and($busy->metadata['start_min'])->toBe(480)
        ->and($busy->source_block_id)->toBeNull()             // imported anchors have no FK
        ->and($work->metadata['end_min'])->toBeLessThanOrEqual(480); // Work reflows before the anchor
});

it('pushes the current schedule via the configured driver', function () {
    $user = User::factory()->create(['day_start_time' => '04:00']);
    $plan = DailyPlan::factory()->for($user)->create(['template_id' => null, 'status' => 'generated']);
    $plan->blocks()->create(['name' => 'A', 'start_time' => '09:00', 'end_time' => '10:00', 'flexibility_mode' => 'adaptive', 'order' => 0]);
    app(ScheduleGenerator::class)->generate($plan);

    $count = 0;
    app()->instance(CalendarSync::class, fakeCalendar(pushCount: $count));

    $this->actingAs($user)
        ->post(route('calendar.push', $plan))
        ->assertRedirect();
});

it('blocks cross-user calendar push', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $plan = DailyPlan::factory()->for($owner)->create();

    $this->actingAs($other)->post(route('calendar.push', $plan))->assertForbidden();
});
