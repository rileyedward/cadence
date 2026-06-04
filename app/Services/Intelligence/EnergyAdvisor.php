<?php

namespace App\Services\Intelligence;

use App\Models\DailyPlanBlock;
use App\Queries\HistoryQuery;

/**
 * Learns the typical energy level per block name and flags mismatches (doc 10).
 */
class EnergyAdvisor
{
    public function __construct(private readonly HistoryQuery $history) {}

    /**
     * Most-common energy level per block name, above the minimum sample size.
     *
     * @return array<string, string> block name => energy level
     */
    public function typicalEnergyByBlockName(): array
    {
        $minSamples = (int) config('cadence.intelligence.min_samples', 3);

        $counts = [];
        foreach ($this->history->blocks() as $block) {
            if ($block->energy_level === null) {
                continue;
            }
            $counts[$block->name][$block->energy_level->value] = ($counts[$block->name][$block->energy_level->value] ?? 0) + 1;
        }

        $out = [];
        foreach ($counts as $name => $byLevel) {
            if (array_sum($byLevel) < $minSamples) {
                continue;
            }
            arsort($byLevel);
            $out[$name] = (string) array_key_first($byLevel);
        }

        return $out;
    }

    /**
     * Per-block suggestion + mismatch flag for a plan being edited.
     *
     * @param  iterable<DailyPlanBlock>  $planBlocks
     * @return array<int, array{block_id:int, suggested_energy:string, mismatch:bool}>
     */
    public function suggestionsForPlan(iterable $planBlocks): array
    {
        $typical = $this->typicalEnergyByBlockName();

        $out = [];
        foreach ($planBlocks as $block) {
            if (! isset($typical[$block->name])) {
                continue;
            }
            $suggested = $typical[$block->name];
            $chosen = $block->energy_level?->value;

            $out[] = [
                'block_id' => $block->id,
                'suggested_energy' => $suggested,
                'mismatch' => $chosen !== null && $chosen !== $suggested,
            ];
        }

        return $out;
    }
}
