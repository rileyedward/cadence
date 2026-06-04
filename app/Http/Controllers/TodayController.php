<?php

namespace App\Http\Controllers;

use App\Http\Resources\DailyPlanResource;
use App\Queries\PlanBlockComparison;
use App\Services\Planning\PlanLifecycle;
use App\Support\LogicalDay;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodayController extends Controller
{
    public function __construct(private readonly PlanLifecycle $lifecycle) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->lifecycle->sync($user);

        $today = LogicalDay::currentDate($user)->toDateString();

        $plan = $user->dailyPlans()
            ->whereDate('date', $today)
            ->with(['blocks.activities', 'blocks.checkIns', 'currentSchedule.events'])
            ->first();

        $comparisons = $plan
            ? array_map(fn ($c) => $c->toArray(), PlanBlockComparison::forPlan($plan))
            : [];

        return Inertia::render('Today/Index', [
            'plan' => $plan ? new DailyPlanResource($plan) : null,
            'comparisons' => $comparisons,
            'nowMin' => LogicalDay::currentMinute($user),
            'dayStartMin' => LogicalDay::dayStartMinutes($user),
            'date' => $today,
        ]);
    }
}
