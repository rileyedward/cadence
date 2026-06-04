<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyPlan\StoreDailyPlanRequest;
use App\Http\Requests\DailyPlan\UpdateDailyPlanRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\DailyPlanResource;
use App\Models\Activity;
use App\Models\DailyPlan;
use App\Models\DailyPlanBlock;
use App\Models\Template;
use App\Services\Planning\DailyPlanFactory;
use App\Services\Planning\PlanLifecycle;
use App\Support\LogicalDay;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DailyPlanController extends Controller
{
    public function __construct(
        private readonly DailyPlanFactory $factory,
        private readonly PlanLifecycle $lifecycle,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->lifecycle->sync($user);

        $plans = $user->dailyPlans()
            ->orderBy('date')
            ->get(['id', 'date', 'status', 'template_id'])
            ->map(fn (DailyPlan $p) => [
                'id' => $p->id,
                'date' => $p->date->toDateString(),
                'status' => $p->status,
                'template_id' => $p->template_id,
            ]);

        $templates = Template::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('DailyPlans/Index', [
            'plans' => $plans,
            'templates' => $templates,
            'today' => LogicalDay::currentDate($user)->toDateString(),
        ]);
    }

    public function store(StoreDailyPlanRequest $request): RedirectResponse
    {
        $user = $request->user();
        $template = $request->filled('template_id')
            ? Template::where('user_id', $user->id)->findOrFail($request->integer('template_id'))
            : null;

        $plan = $this->factory->instantiate(
            $user,
            CarbonImmutable::parse($request->validated('date')),
            $template,
        );

        return to_route('plans.show', $plan);
    }

    public function show(Request $request, DailyPlan $plan): Response
    {
        $this->authorize('view', $plan);
        $this->lifecycle->sync($request->user());
        $plan->refresh();

        $plan->load([
            'blocks.activities',
            'blocks.checkIns',
            'currentSchedule.events',
        ]);

        $activityLibrary = Activity::query()
            ->libraryFor($request->user())
            ->with('intents:id')
            ->orderBy('name')
            ->get();

        return Inertia::render('DailyPlans/Show', [
            'plan' => new DailyPlanResource($plan),
            'activityLibrary' => ActivityResource::collection($activityLibrary),
        ]);
    }

    public function update(UpdateDailyPlanRequest $request, DailyPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        DB::transaction(function () use ($request, $plan) {
            $ownedBlockIds = $plan->blocks()->pluck('id')->all();

            foreach ($request->validated('blocks') as $decision) {
                if (! in_array($decision['id'], $ownedBlockIds, true)) {
                    continue; // ignore blocks that aren't part of this plan
                }

                $block = $plan->blocks()->find($decision['id']);
                $block->update([
                    'intent_id' => $decision['intent_id'] ?? null,
                    'secondary_intent_id' => $decision['secondary_intent_id'] ?? null,
                    'energy_level' => $decision['energy_level'] ?? null,
                    'focus_intensity' => $decision['focus_intensity'] ?? null,
                    'social_context' => $decision['social_context'] ?? null,
                    'mobility_preference' => $decision['mobility_preference'] ?? null,
                    'constraints' => $decision['constraints'] ?? null,
                    'context_tags' => $decision['context_tags'] ?? null,
                ]);

                $this->syncActivities($block, $decision['activities'] ?? []);
            }
        });

        return back();
    }

    public function destroy(Request $request, DailyPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return to_route('plans.index');
    }

    /**
     * @param  DailyPlanBlock  $block
     * @param  array<int, array{activity_id:int, estimated_minutes:int, order?:int}>  $activities
     */
    private function syncActivities($block, array $activities): void
    {
        $payload = [];
        foreach (array_values($activities) as $i => $a) {
            $payload[$a['activity_id']] = [
                'order' => $a['order'] ?? $i,
                'estimated_minutes' => $a['estimated_minutes'],
            ];
        }

        $block->activities()->sync($payload);
    }
}
