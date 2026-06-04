<?php

namespace App\Http\Controllers;

use App\Models\CalendarBusyEvent;
use App\Models\CalendarConnection;
use App\Models\DailyPlan;
use App\Services\Calendar\CalendarSync;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(private readonly CalendarSync $calendar) {}

    public function settings(Request $request): Response
    {
        $connection = CalendarConnection::where('user_id', $request->user()->id)->first();

        return Inertia::render('settings/Calendar', [
            'configured' => $this->calendar->isConfigured(),
            'connected' => $connection !== null,
            'calendarId' => $connection?->calendar_id,
            'pushBuffers' => (bool) config('cadence.calendar.push_buffers', false),
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        if (! $this->calendar->isConfigured()) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No calendar provider is configured.']);
        }

        return redirect()->away($this->calendar->connectUrl($request->user()));
    }

    public function callback(Request $request): RedirectResponse
    {
        $this->calendar->handleCallback($request->user(), $request);

        return to_route('calendar.settings')->with('flash', ['type' => 'success', 'message' => 'Calendar connected.']);
    }

    public function push(Request $request, DailyPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $schedule = $plan->currentSchedule()->with('events')->first();
        abort_if($schedule === null, 409, 'Generate a schedule before syncing.');

        $result = $this->calendar->pushSchedule($schedule);

        return back()->with('flash', [
            'type' => $result->skipped ? 'error' : 'success',
            'message' => $result->skipped
                ? 'Connect a calendar first.'
                : "Synced {$result->created} events to your calendar.",
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate(['date' => ['required', 'date']]);
        $user = $request->user();
        $date = CarbonImmutable::parse($data['date']);

        $busy = $this->calendar->importEvents($user, $date);

        DB::transaction(function () use ($user, $date, $busy) {
            CalendarBusyEvent::where('user_id', $user->id)->whereDate('date', $date->toDateString())->delete();

            foreach ($busy as $event) {
                CalendarBusyEvent::create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'source_uid' => $event['source_uid'],
                    'title' => $event['title'] ?? null,
                    'start_time' => $event['start_time'],
                    'end_time' => $event['end_time'],
                ]);
            }
        });

        return back()->with('flash', ['type' => 'success', 'message' => count($busy).' busy events imported.']);
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $this->calendar->disconnect($request->user());

        return back()->with('flash', ['type' => 'success', 'message' => 'Calendar disconnected.']);
    }
}
