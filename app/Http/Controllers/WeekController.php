<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Support\LogicalDay;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeekController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $start = $request->filled('start')
            ? CarbonImmutable::parse($request->string('start')->value())->startOfWeek()
            : LogicalDay::currentDate($user)->startOfWeek();

        $plans = $user->dailyPlans()
            ->whereBetween('date', [$start->toDateString(), $start->addDays(6)->toDateString()])
            ->withCount('blocks')
            ->get()
            ->keyBy(fn ($p) => $p->date->toDateString());

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $start->addDays($i);
            $iso = $date->toDateString();
            $plan = $plans->get($iso);
            $template = Template::resolveForDate($user, $date);

            $days[] = [
                'date' => $iso,
                'weekday' => $date->format('D'),
                'day' => $date->day,
                'template' => $template?->name,
                'plan_id' => $plan?->id,
                'status' => $plan?->status,
                'blocks_count' => $plan?->blocks_count ?? ($template?->blocks()->count() ?? 0),
            ];
        }

        return Inertia::render('Week/Index', [
            'days' => $days,
            'weekStart' => $start->toDateString(),
            'today' => LogicalDay::currentDate($user)->toDateString(),
        ]);
    }
}
