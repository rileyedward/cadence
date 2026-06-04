<?php

namespace App\Services\Compiler;

use App\Models\CalendarBusyEvent;
use App\Models\DailyPlan;
use App\Models\DailyPlanBlock;
use App\Support\LogicalDay;

/**
 * Maps an Eloquent DailyPlan into pure BlockInput[] in minutes-from-day_start.
 * This is the only Eloquent-aware part of the compiler pipeline.
 */
class CompilerInputBuilder
{
    /**
     * @param  array<int, array<string,mixed>>  $overrides  Optional unsaved decisions
     *                                                      keyed by daily_plan_block id, used
     *                                                      by the live preview (doc 07).
     * @return array<int, BlockInput>
     */
    public function forPlan(DailyPlan $plan, array $overrides = []): array
    {
        $plan->loadMissing(['blocks.activities', 'user']);
        $dayStart = LogicalDay::dayStartMinutes($plan->user);

        $inputs = $plan->blocks
            ->map(fn (DailyPlanBlock $b) => $this->toInput($b, $dayStart, $overrides[$b->id] ?? null))
            ->all();

        // Imported calendar busy events act as strict anchors the day reflows around (doc 15).
        foreach ($this->busyAnchors($plan, $dayStart) as $anchor) {
            $inputs[] = $anchor;
        }

        return $inputs;
    }

    /**
     * @return array<int, BlockInput>
     */
    private function busyAnchors(DailyPlan $plan, int $dayStart): array
    {
        return CalendarBusyEvent::query()
            ->where('user_id', $plan->user_id)
            ->whereDate('date', $plan->date->toDateString())
            ->get()
            ->map(function (CalendarBusyEvent $busy) use ($dayStart) {
                $startMin = $this->fromDayStart($busy->start_time, $dayStart);
                $endMin = $this->fromDayStart($busy->end_time, $dayStart);
                if ($endMin <= $startMin) {
                    $endMin += 1440;
                }

                return new BlockInput(
                    id: -$busy->id, // negative ids keep imported anchors distinct from plan blocks
                    label: $busy->title ?: 'Busy',
                    startMin: $startMin,
                    endMin: $endMin,
                    mode: 'strict',
                    meta: ['imported' => true],
                );
            })
            ->all();
    }

    /**
     * @param  array<string,mixed>|null  $override
     */
    private function toInput(DailyPlanBlock $block, int $dayStart, ?array $override): BlockInput
    {
        $startMin = $this->fromDayStart($block->start_time, $dayStart);
        $endMin = $this->fromDayStart($block->end_time, $dayStart);
        if ($endMin <= $startMin) {
            $endMin += 1440; // window wraps the logical-day boundary
        }

        if ($override !== null) {
            $energy = $override['energy_level'] ?? null;
            $focus = $override['focus_intensity'] ?? null;
            $activityMinutes = array_map(
                fn ($a) => (int) ($a['estimated_minutes'] ?? 0),
                $override['activities'] ?? [],
            );
        } else {
            $energy = $block->energy_level?->value;
            $focus = $block->focus_intensity?->value;
            $activityMinutes = $block->activities->map(fn ($a) => (int) $a->pivot->estimated_minutes)->all();
        }

        return new BlockInput(
            id: $block->id,
            label: $block->name,
            startMin: $startMin,
            endMin: $endMin,
            mode: $block->flexibility_mode->value,
            energy: $energy,
            focus: $focus,
            priority: 0,
            order: $block->order,
            constraints: $block->constraints ?? [],
            activityMinutes: $activityMinutes,
            mobility: $block->mobility_preference?->value,
            meta: ['intent_id' => $block->intent_id],
        );
    }

    private function fromDayStart(mixed $time, int $dayStart): int
    {
        [$h, $m] = array_map('intval', explode(':', substr((string) $time, 0, 5)));
        $clock = $h * 60 + $m;

        return (($clock - $dayStart) % 1440 + 1440) % 1440;
    }
}
