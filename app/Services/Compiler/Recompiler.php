<?php

namespace App\Services\Compiler;

use App\Enums\CheckInStatus;
use App\Models\Schedule;
use App\Models\ScheduleEvent;
use Illuminate\Support\Collection;

/**
 * Runtime recompiler (doc 08). Builds on the compiler but freezes the past:
 * events that already ended (≤ fromMin) and any block with a started/completed
 * check-in are copied verbatim; only the remaining blocks reflow. Server is
 * authoritative for "now" — fromMin is validated/clamped by the caller.
 */
class Recompiler
{
    public function __construct(
        private readonly CompilerInputBuilder $builder,
        private readonly TimelineCompiler $compiler,
        private readonly ScheduleWriter $writer,
    ) {}

    /**
     * @param  array<int, array<string,mixed>>  $ops
     */
    public function recompile(Schedule $current, int $fromMin, array $ops = []): Schedule
    {
        $plan = $current->dailyPlan;
        $plan->loadMissing(['blocks.checkIns', 'user']);
        $current->loadMissing('events');

        // Blocks pinned by a started/completed check-in are always frozen.
        $checkedInBlockIds = $plan->blocks
            ->filter(fn ($b) => in_array($b->currentStatus(), [CheckInStatus::Started, CheckInStatus::Completed], true))
            ->pluck('id')
            ->all();

        // Freeze events that ended before "now" or belong to a checked-in block.
        $frozenEvents = $current->events->filter(function (ScheduleEvent $e) use ($fromMin, $checkedInBlockIds) {
            $endMin = (int) ($e->metadata['end_min'] ?? PHP_INT_MAX);

            return $endMin <= $fromMin
                || ($e->source_block_id !== null && in_array($e->source_block_id, $checkedInBlockIds, true));
        });

        $frozenBlockIds = $frozenEvents->pluck('source_block_id')->filter()->unique()->all();
        $maxFrozenEnd = (int) $frozenEvents->max(fn (ScheduleEvent $e) => (int) ($e->metadata['end_min'] ?? 0));

        // Remaining (unfrozen) blocks → mutable params → apply ops.
        $remaining = collect($this->builder->forPlan($plan))
            ->reject(fn (BlockInput $b) => in_array($b->id, $frozenBlockIds, true))
            ->mapWithKeys(fn (BlockInput $b) => [$b->id => $this->toParams($b)]);

        $remaining = $this->applyOps($remaining, $ops);

        $inputs = $remaining->map(fn (array $p) => $this->fromParams($p))->values()->all();

        $cursorStart = max($fromMin, $maxFrozenEnd);
        $recompiled = $this->compiler->compile($inputs, CompileConfig::fromConfig(), $cursorStart);

        // Frozen events first (verbatim), then the freshly reflowed tail.
        $events = collect($frozenEvents)
            ->map(fn (ScheduleEvent $e) => $this->freeze($e))
            ->values()
            ->concat($recompiled->events)
            ->all();

        return $this->writer->write($plan, new CompileResult($events, $recompiled->warnings));
    }

    private function freeze(ScheduleEvent $e): EventDraft
    {
        return new EventDraft(
            type: $e->type->value,
            label: $e->label,
            startMin: (int) ($e->metadata['start_min'] ?? 0),
            endMin: (int) ($e->metadata['end_min'] ?? 0),
            sourceBlockId: $e->source_block_id,
            metadata: array_merge($e->metadata ?? [], ['frozen' => true]),
        );
    }

    /**
     * @param  Collection<int, array<string,mixed>>  $remaining
     * @param  array<int, array<string,mixed>>  $ops
     * @return Collection<int, array<string,mixed>>
     */
    private function applyOps(Collection $remaining, array $ops): Collection
    {
        foreach ($ops as $op) {
            $type = $op['op'] ?? null;
            $id = (int) ($op['block_id'] ?? 0);

            switch ($type) {
                case 'skip':
                    $remaining->forget($id);
                    break;

                case 'extend':
                case 'shorten':
                    if ($remaining->has($id)) {
                        $p = $remaining->get($id);
                        $delta = (int) ($op['delta'] ?? 0) * ($type === 'shorten' ? -1 : 1);
                        $newDur = max(1, $this->durationOf($p) + $delta);
                        $p['activityMinutes'] = [$newDur];
                        $remaining->put($id, $p);
                    }
                    break;

                case 'swap':
                    if ($remaining->has($id)) {
                        $p = $remaining->get($id);
                        $p['activityMinutes'] = [max(1, (int) ($op['estimated_minutes'] ?? $this->durationOf($p)))];
                        $remaining->put($id, $p);
                    }
                    break;

                case 'drag':
                    if ($remaining->has($id)) {
                        $p = $remaining->get($id);
                        $dur = $p['endMin'] - $p['startMin'];
                        $p['startMin'] = (int) ($op['start_min'] ?? $p['startMin']);
                        $p['endMin'] = $p['startMin'] + $dur;
                        $remaining->put($id, $p);
                    }
                    break;

                case 'split':
                    if ($remaining->has($id)) {
                        $remaining = $this->split($remaining, $id, (int) ($op['at_min'] ?? 0));
                    }
                    break;

                case 'merge':
                    $withId = (int) ($op['with_block_id'] ?? 0);
                    if ($remaining->has($id) && $remaining->has($withId)) {
                        $remaining = $this->merge($remaining, $id, $withId);
                    }
                    break;

                case 'recompile':
                default:
                    break; // reflow only
            }
        }

        return $remaining->sortBy('startMin')->values()->mapWithKeys(fn (array $p) => [$p['id'] => $p]);
    }

    /**
     * @param  Collection<int, array<string,mixed>>  $remaining
     * @return Collection<int, array<string,mixed>>
     */
    private function split(Collection $remaining, int $id, int $atMin): Collection
    {
        $p = $remaining->get($id);
        $atMin = max($p['startMin'] + 1, min($atMin, $p['endMin'] - 1));

        $first = $p;
        $first['endMin'] = $atMin;
        $first['activityMinutes'] = [$atMin - $p['startMin']];

        $second = $p;
        $second['id'] = $p['id'] * 100000 + 1; // synthetic id for the new half
        $second['startMin'] = $atMin;
        $second['activityMinutes'] = [$p['endMin'] - $atMin];
        $second['mode'] = 'adaptive';

        $remaining->put($id, $first);
        $remaining->put($second['id'], $second);

        return $remaining;
    }

    /**
     * @param  Collection<int, array<string,mixed>>  $remaining
     * @return Collection<int, array<string,mixed>>
     */
    private function merge(Collection $remaining, int $id, int $withId): Collection
    {
        $a = $remaining->get($id);
        $b = $remaining->get($withId);

        $a['startMin'] = min($a['startMin'], $b['startMin']);
        $a['endMin'] = max($a['endMin'], $b['endMin']);
        $a['activityMinutes'] = [($a['endMin'] - $a['startMin'])];
        $a['mode'] = 'adaptive';

        $remaining->put($id, $a);
        $remaining->forget($withId);

        return $remaining;
    }

    private function durationOf(array $p): int
    {
        $sum = array_sum($p['activityMinutes']);

        return $sum > 0 ? $sum : ($p['endMin'] - $p['startMin']);
    }

    /**
     * @return array<string,mixed>
     */
    private function toParams(BlockInput $b): array
    {
        return [
            'id' => $b->id,
            'label' => $b->label,
            'startMin' => $b->startMin,
            'endMin' => $b->endMin,
            'mode' => $b->mode,
            'energy' => $b->energy,
            'focus' => $b->focus,
            'priority' => $b->priority,
            'order' => $b->order,
            'constraints' => $b->constraints,
            'activityMinutes' => $b->activityMinutes,
            'mobility' => $b->mobility,
            'meta' => $b->meta,
        ];
    }

    /**
     * @param  array<string,mixed>  $p
     */
    private function fromParams(array $p): BlockInput
    {
        return new BlockInput(
            id: $p['id'],
            label: $p['label'],
            startMin: $p['startMin'],
            endMin: $p['endMin'],
            mode: $p['mode'],
            energy: $p['energy'],
            focus: $p['focus'],
            priority: $p['priority'],
            order: $p['order'],
            constraints: $p['constraints'],
            activityMinutes: $p['activityMinutes'],
            mobility: $p['mobility'],
            meta: $p['meta'],
        );
    }
}
