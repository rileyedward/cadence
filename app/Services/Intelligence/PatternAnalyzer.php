<?php

namespace App\Services\Intelligence;

use App\Queries\HistoryQuery;

/**
 * Thin aggregator over HistoryQuery for completion/skip/drift stats (doc 10).
 * Pure pass-through of deterministic aggregates that views and the optimizer read.
 */
class PatternAnalyzer
{
    public function __construct(private readonly HistoryQuery $history) {}

    /**
     * @return array{
     *   completion_by_intent: array<string, array{completed:int,total:int,rate:float}>,
     *   skip_by_block: array<string, int>,
     *   drift_by_block: array<string, array{samples:int,avg_drift_min:float}>,
     *   energy_distribution: array<string, int>,
     * }
     */
    public function summary(): array
    {
        return [
            'completion_by_intent' => $this->history->completionRateByIntent(),
            'skip_by_block' => $this->history->skipCountByBlockName(),
            'drift_by_block' => $this->history->durationDriftByBlockName(),
            'energy_distribution' => $this->history->energyDistribution(),
        ];
    }
}
