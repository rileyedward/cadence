<?php

namespace App\Queries;

use App\Enums\CheckInStatus;
use App\Models\DailyPlan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Read-side history aggregates over a user's past plans (doc 09). Reused by the
 * intelligence layer (doc 10) and history views (doc 12) so the logic lives once.
 */
class HistoryQuery
{
    /** @param  Collection<int, DailyPlan>  $plans */
    public function __construct(public readonly Collection $plans) {}

    public static function forRange(User $user, CarbonInterface $from, CarbonInterface $to): self
    {
        $plans = $user->dailyPlans()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->with(['blocks.checkIns', 'blocks.intent', 'currentSchedule.events', 'user'])
            ->orderBy('date')
            ->get();

        return new self($plans);
    }

    /** Every plan block across the range as a flat collection. */
    public function blocks(): Collection
    {
        return $this->plans->flatMap->blocks;
    }

    /**
     * Completion rate (completed / total decided) keyed by intent name.
     *
     * @return array<string, array{completed:int, total:int, rate:float}>
     */
    public function completionRateByIntent(): array
    {
        $out = [];
        foreach ($this->blocks() as $block) {
            $name = $block->intent?->name ?? 'Unassigned';
            $status = $block->currentStatus();
            $out[$name] ??= ['completed' => 0, 'total' => 0, 'rate' => 0.0];
            if ($status !== null) {
                $out[$name]['total']++;
                if ($status === CheckInStatus::Completed) {
                    $out[$name]['completed']++;
                }
            }
        }
        foreach ($out as $k => $v) {
            $out[$k]['rate'] = $v['total'] > 0 ? round($v['completed'] / $v['total'], 3) : 0.0;
        }

        return $out;
    }

    /**
     * Skip counts keyed by block name (most-skipped surfaces first downstream).
     *
     * @return array<string, int>
     */
    public function skipCountByBlockName(): array
    {
        $out = [];
        foreach ($this->blocks() as $block) {
            if ($block->currentStatus() === CheckInStatus::Skipped) {
                $out[$block->name] = ($out[$block->name] ?? 0) + 1;
            }
        }
        arsort($out);

        return $out;
    }

    /**
     * Average actual-vs-planned duration drift (minutes) keyed by block name.
     *
     * @return array<string, array{samples:int, avg_drift_min:float}>
     */
    public function durationDriftByBlockName(): array
    {
        $acc = [];
        foreach ($this->plans as $plan) {
            foreach (PlanBlockComparison::forPlan($plan) as $cmp) {
                if ($cmp->durationDeltaMin === null) {
                    continue;
                }
                $acc[$cmp->label] ??= ['sum' => 0, 'samples' => 0];
                $acc[$cmp->label]['sum'] += $cmp->durationDeltaMin;
                $acc[$cmp->label]['samples']++;
            }
        }

        $out = [];
        foreach ($acc as $name => $v) {
            $out[$name] = [
                'samples' => $v['samples'],
                'avg_drift_min' => $v['samples'] > 0 ? round($v['sum'] / $v['samples'], 1) : 0.0,
            ];
        }

        return $out;
    }

    /**
     * Frequency of each energy level chosen across the range.
     *
     * @return array<string, int>
     */
    public function energyDistribution(): array
    {
        $out = [];
        foreach ($this->blocks() as $block) {
            if ($block->energy_level !== null) {
                $key = $block->energy_level->value;
                $out[$key] = ($out[$key] ?? 0) + 1;
            }
        }

        return $out;
    }
}
