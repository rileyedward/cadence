<?php

namespace App\Services\Intelligence;

use App\Models\DailyPlanBlock;
use App\Queries\HistoryQuery;

/**
 * Detects recurring intent/activity choices per block name from history (doc 10).
 * Deterministic; requires a minimum sample before emitting anything (cold start).
 */
class HabitDetector
{
    public function __construct(private readonly HistoryQuery $history) {}

    /**
     * Most-frequent intent id per block name, when it clears the frequency cutoff
     * and minimum-sample thresholds.
     *
     * @return array<string, int> block name => intent id
     */
    public function frequentIntentByBlockName(): array
    {
        $minSamples = (int) config('cadence.intelligence.min_samples', 3);
        $cutoff = (float) config('cadence.intelligence.frequency_cutoff', 0.6);

        $counts = [];
        foreach ($this->history->blocks() as $block) {
            if ($block->intent_id === null) {
                continue;
            }
            $counts[$block->name][$block->intent_id] = ($counts[$block->name][$block->intent_id] ?? 0) + 1;
        }

        $out = [];
        foreach ($counts as $name => $byIntent) {
            $total = array_sum($byIntent);
            arsort($byIntent);
            $topIntent = array_key_first($byIntent);
            $topCount = $byIntent[$topIntent];

            if ($total >= $minSamples && $topCount / $total >= $cutoff) {
                $out[$name] = (int) $topIntent;
            }
        }

        return $out;
    }

    /**
     * Most-frequent activity ids per block name (above thresholds).
     *
     * @return array<string, array<int, int>> block name => activity ids
     */
    public function frequentActivitiesByBlockName(): array
    {
        $minSamples = (int) config('cadence.intelligence.min_samples', 3);
        $cutoff = (float) config('cadence.intelligence.frequency_cutoff', 0.6);

        $occurrences = [];
        $activityCounts = [];
        foreach ($this->history->blocks() as $block) {
            $occurrences[$block->name] = ($occurrences[$block->name] ?? 0) + 1;
            foreach ($block->activities as $activity) {
                $activityCounts[$block->name][$activity->id] = ($activityCounts[$block->name][$activity->id] ?? 0) + 1;
            }
        }

        $out = [];
        foreach ($activityCounts as $name => $byActivity) {
            $total = $occurrences[$name] ?? 0;
            if ($total < $minSamples) {
                continue;
            }
            foreach ($byActivity as $activityId => $count) {
                if ($count / $total >= $cutoff) {
                    $out[$name][] = (int) $activityId;
                }
            }
        }

        return $out;
    }

    /**
     * Quick-fill suggestion for each block of a plan: a habitual intent +
     * activities, falling back to the block's highest-weight default intent.
     *
     * @param  iterable<DailyPlanBlock>  $planBlocks
     * @return array<int, array{block_id:int, intent_id:?int, activity_ids:array<int,int>}>
     */
    public function quickFill(iterable $planBlocks): array
    {
        $intents = $this->frequentIntentByBlockName();
        $activities = $this->frequentActivitiesByBlockName();

        $out = [];
        foreach ($planBlocks as $block) {
            $intentId = $intents[$block->name] ?? null;
            if ($intentId === null && $block->block) {
                $intentId = $block->block->defaultIntents()->first()?->id;
            }

            $out[] = [
                'block_id' => $block->id,
                'intent_id' => $intentId,
                'activity_ids' => $activities[$block->name] ?? [],
            ];
        }

        return $out;
    }
}
