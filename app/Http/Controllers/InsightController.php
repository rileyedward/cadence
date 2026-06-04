<?php

namespace App\Http\Controllers;

use App\Models\DailyPlan;
use App\Models\User;
use App\Queries\HistoryQuery;
use App\Services\Intelligence\EnergyAdvisor;
use App\Services\Intelligence\HabitDetector;
use App\Services\Intelligence\Optimizer;
use App\Services\Intelligence\PatternAnalyzer;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsightController extends Controller
{
    /**
     * Plan-scoped insights for the planning session (doc 06): habit quick-fill,
     * energy suggestions, and optimizer recommendations.
     */
    public function forPlan(Request $request, DailyPlan $plan): JsonResponse
    {
        $this->authorize('view', $plan);

        $plan->loadMissing(['blocks.activities', 'blocks.block.defaultIntents']);
        $history = $this->history($request->user());

        return response()->json([
            'quick_fill' => (new HabitDetector($history))->quickFill($plan->blocks),
            'energy' => (new EnergyAdvisor($history))->suggestionsForPlan($plan->blocks),
            'recommendations' => (new Optimizer($history))->recommend(),
        ]);
    }

    /**
     * Range-scoped patterns + recommendations for the history/insights views.
     */
    public function range(Request $request): JsonResponse
    {
        $history = $this->history($request->user());

        return response()->json([
            'patterns' => (new PatternAnalyzer($history))->summary(),
            'recommendations' => (new Optimizer($history))->recommend(),
        ]);
    }

    private function history(User $user): HistoryQuery
    {
        $days = (int) config('cadence.intelligence.window_days', 56);

        return HistoryQuery::forRange(
            $user,
            CarbonImmutable::now($user->timezone)->subDays($days),
            CarbonImmutable::now($user->timezone),
        );
    }
}
