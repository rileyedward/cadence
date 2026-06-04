<?php

namespace App\Services\Intelligence;

use App\Queries\HistoryQuery;

/**
 * Turns history aggregates into actionable, deterministic recommendations
 * (doc 10). Pure suggestions — never auto-applied. Each recommendation carries
 * an optional `action` payload the UI can one-click apply.
 */
class Optimizer
{
    public function __construct(private readonly HistoryQuery $history) {}

    /**
     * @return array<int, array{kind:string, message:string, target:array<string,mixed>, action:?array<string,mixed>}>
     */
    public function recommend(): array
    {
        $minSamples = (int) config('cadence.intelligence.min_samples', 3);
        $driftMinutes = 15; // minimum average drift worth surfacing

        $recommendations = [];

        // 1. Chronically over/under-estimated blocks → suggest resizing the window.
        foreach ($this->history->durationDriftByBlockName() as $name => $stat) {
            if ($stat['samples'] < $minSamples) {
                continue;
            }
            if ($stat['avg_drift_min'] >= $driftMinutes) {
                $recommendations[] = [
                    'kind' => 'duration_drift',
                    'message' => "{$name} runs about {$stat['avg_drift_min']} min over planned — consider extending its window.",
                    'target' => ['block_name' => $name],
                    'action' => ['type' => 'extend_window', 'minutes' => (int) round($stat['avg_drift_min'])],
                ];
            } elseif ($stat['avg_drift_min'] <= -$driftMinutes) {
                $recommendations[] = [
                    'kind' => 'duration_drift',
                    'message' => "{$name} finishes about ".abs((int) $stat['avg_drift_min']).' min early — you could shorten its window.',
                    'target' => ['block_name' => $name],
                    'action' => ['type' => 'shorten_window', 'minutes' => abs((int) round($stat['avg_drift_min']))],
                ];
            }
        }

        // 2. Frequently skipped blocks → suggest making them adaptive.
        foreach ($this->history->skipCountByBlockName() as $name => $count) {
            if ($count >= $minSamples) {
                $recommendations[] = [
                    'kind' => 'frequent_skip',
                    'message' => "{$name} is often skipped ({$count}×) — consider making it adaptive or optional.",
                    'target' => ['block_name' => $name],
                    'action' => ['type' => 'set_flexibility', 'mode' => 'adaptive'],
                ];
            }
        }

        // 3. Low completion rate per intent.
        foreach ($this->history->completionRateByIntent() as $intent => $stat) {
            if ($stat['total'] >= $minSamples && $stat['rate'] < 0.5) {
                $pct = (int) round($stat['rate'] * 100);
                $recommendations[] = [
                    'kind' => 'low_completion',
                    'message' => "Only {$pct}% of your {$intent} blocks get completed — they may be over-scheduled.",
                    'target' => ['intent' => $intent],
                    'action' => null,
                ];
            }
        }

        return $recommendations;
    }
}
