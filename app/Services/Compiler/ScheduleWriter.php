<?php

namespace App\Services\Compiler;

use App\Models\DailyPlan;
use App\Models\Schedule;
use App\Support\LogicalDay;
use Illuminate\Support\Facades\DB;

/**
 * Persists a CompileResult as a new versioned Schedule + schedule_events, then
 * prunes old versions to config('cadence.max_schedule_versions') (doc 07).
 */
class ScheduleWriter
{
    public function write(DailyPlan $plan, CompileResult $result): Schedule
    {
        $plan->loadMissing('user');
        $dayStart = LogicalDay::dayStartMinutes($plan->user);

        return DB::transaction(function () use ($plan, $result, $dayStart) {
            $plan->schedules()->where('is_current', true)->update(['is_current' => false]);

            $version = (int) $plan->schedules()->max('version') + 1;

            $schedule = $plan->schedules()->create([
                'version' => $version,
                'generated_at' => now(),
                'is_current' => true,
            ]);

            foreach ($result->events as $order => $event) {
                $schedule->events()->create([
                    'source_block_id' => $event->sourceBlockId,
                    'type' => $event->type,
                    'label' => $event->label,
                    'start_time' => $this->toClock($event->startMin, $dayStart),
                    'end_time' => $this->toClock($event->endMin, $dayStart),
                    'order' => $order,
                    // Stash integer minutes so the client can position past-midnight
                    // events unambiguously and the recompiler can read them back.
                    'metadata' => array_merge($event->metadata, [
                        'start_min' => $event->startMin,
                        'end_min' => $event->endMin,
                    ]),
                ]);
            }

            $this->prune($plan);

            return $schedule->load('events');
        });
    }

    private function toClock(int $minutesFromDayStart, int $dayStart): string
    {
        $clock = ($minutesFromDayStart + $dayStart) % 1440;
        $clock = ($clock + 1440) % 1440;

        return sprintf('%02d:%02d', intdiv($clock, 60), $clock % 60);
    }

    private function prune(DailyPlan $plan): void
    {
        $keep = (int) config('cadence.max_schedule_versions', 10);

        $keepIds = $plan->schedules()
            ->orderByDesc('version')
            ->limit($keep)
            ->pluck('id');

        $plan->schedules()
            ->whereNotIn('id', $keepIds)
            ->where('is_current', false)
            ->delete();
    }
}
