<?php

namespace App\Queries;

use App\Models\DailyPlan;
use App\Models\DailyPlanBlock;
use App\Models\ScheduleEvent;
use App\Support\LogicalDay;
use Carbon\CarbonInterface;

/**
 * Derived (no table) read model comparing a block's planned window vs the actual
 * check-in times (doc 09). All minutes are minutes-from-day_start.
 */
class PlanBlockComparison
{
    public function __construct(
        public int $dailyPlanBlockId,
        public string $label,
        public int $plannedStartMin,
        public int $plannedEndMin,
        public int $plannedMinutes,
        public ?int $actualStartMin,
        public ?int $actualEndMin,
        public ?int $actualMinutes,
        public ?int $startDeltaMin,
        public ?int $durationDeltaMin,
        public ?string $status,
    ) {}

    /**
     * Build comparisons for every block in a plan, pairing the current schedule
     * event with the latest check-in.
     *
     * @return array<int, self>
     */
    public static function forPlan(DailyPlan $plan): array
    {
        $plan->loadMissing(['blocks.checkIns', 'currentSchedule.events', 'user']);
        $dayStart = LogicalDay::dayStartMinutes($plan->user);
        $tz = $plan->user->timezone;

        $eventsByBlock = collect($plan->currentSchedule?->events ?? [])
            ->keyBy('source_block_id');

        return $plan->blocks->map(function (DailyPlanBlock $block) use ($eventsByBlock, $dayStart, $tz) {
            /** @var ScheduleEvent|null $event */
            $event = $eventsByBlock->get($block->id);

            [$plannedStart, $plannedEnd] = self::plannedWindow($block, $event, $dayStart);

            $checkIn = $block->checkIns->sortByDesc('id')->first();
            $actualStart = $checkIn?->actual_start ? self::toMin($checkIn->actual_start, $dayStart, $tz) : null;
            $actualEnd = $checkIn?->actual_end ? self::toMin($checkIn->actual_end, $dayStart, $tz) : null;
            $actualMinutes = ($actualStart !== null && $actualEnd !== null)
                ? self::span($actualStart, $actualEnd)
                : null;

            return new self(
                dailyPlanBlockId: $block->id,
                label: $block->name,
                plannedStartMin: $plannedStart,
                plannedEndMin: $plannedEnd,
                plannedMinutes: $plannedEnd - $plannedStart,
                actualStartMin: $actualStart,
                actualEndMin: $actualEnd,
                actualMinutes: $actualMinutes,
                startDeltaMin: $actualStart !== null ? $actualStart - $plannedStart : null,
                durationDeltaMin: $actualMinutes !== null ? $actualMinutes - ($plannedEnd - $plannedStart) : null,
                status: $checkIn?->status->value,
            );
        })->all();
    }

    /**
     * @return array{0:int,1:int}
     */
    private static function plannedWindow(DailyPlanBlock $block, ?ScheduleEvent $event, int $dayStart): array
    {
        if ($event && isset($event->metadata['start_min'], $event->metadata['end_min'])) {
            return [(int) $event->metadata['start_min'], (int) $event->metadata['end_min']];
        }

        $start = self::clockToMin((string) $block->start_time, $dayStart);
        $end = self::clockToMin((string) $block->end_time, $dayStart);
        if ($end <= $start) {
            $end += 1440;
        }

        return [$start, $end];
    }

    private static function clockToMin(string $time, int $dayStart): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));

        return (($h * 60 + $m - $dayStart) % 1440 + 1440) % 1440;
    }

    private static function toMin(CarbonInterface $dt, int $dayStart, string $tz): int
    {
        $local = $dt->copy()->setTimezone($tz);
        $minuteOfDay = $local->hour * 60 + $local->minute;

        return (($minuteOfDay - $dayStart) % 1440 + 1440) % 1440;
    }

    private static function span(int $start, int $end): int
    {
        return $end >= $start ? $end - $start : $end - $start + 1440;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'daily_plan_block_id' => $this->dailyPlanBlockId,
            'label' => $this->label,
            'planned_start_min' => $this->plannedStartMin,
            'planned_end_min' => $this->plannedEndMin,
            'planned_minutes' => $this->plannedMinutes,
            'actual_start_min' => $this->actualStartMin,
            'actual_end_min' => $this->actualEndMin,
            'actual_minutes' => $this->actualMinutes,
            'start_delta_min' => $this->startDeltaMin,
            'duration_delta_min' => $this->durationDeltaMin,
            'status' => $this->status,
        ];
    }
}
