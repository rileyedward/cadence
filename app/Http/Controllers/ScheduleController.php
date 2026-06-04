<?php

namespace App\Http\Controllers;

use App\Models\DailyPlan;
use App\Services\Compiler\CompileResult;
use App\Services\Compiler\ScheduleGenerator;
use App\Support\LogicalDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(private readonly ScheduleGenerator $generator) {}

    /**
     * Persist an authoritative schedule for the plan (doc 06 "Generate Day").
     */
    public function generate(Request $request, DailyPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $this->generator->generate($plan);

        return to_route('plans.show', $plan);
    }

    /**
     * Compile without persisting and return events as JSON for the live preview.
     */
    public function preview(Request $request, DailyPlan $plan): JsonResponse
    {
        $this->authorize('view', $plan);

        // Optional unsaved decisions, keyed by block id, so the preview reflects
        // the user's in-progress edits without persisting them.
        $overrides = [];
        foreach ($request->input('blocks', []) as $decision) {
            if (isset($decision['id'])) {
                $overrides[(int) $decision['id']] = $decision;
            }
        }

        $result = $this->generator->preview($plan, $overrides);

        return response()->json([
            'events' => $this->mapEvents($result, $plan),
        ]);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function mapEvents(CompileResult $result, DailyPlan $plan): array
    {
        $dayStart = LogicalDay::dayStartMinutes($plan->user);

        return array_map(function ($e) use ($dayStart) {
            return [
                'type' => $e->type,
                'label' => $e->label,
                'start_time' => $this->toClock($e->startMin, $dayStart),
                'end_time' => $this->toClock($e->endMin, $dayStart),
                'start_min' => $e->startMin,
                'end_min' => $e->endMin,
                'metadata' => $e->metadata,
            ];
        }, $result->events);
    }

    private function toClock(int $minutes, int $dayStart): string
    {
        $clock = (($minutes + $dayStart) % 1440 + 1440) % 1440;

        return sprintf('%02d:%02d', intdiv($clock, 60), $clock % 60);
    }
}
