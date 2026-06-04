<?php

namespace App\Services\Planning;

use App\Enums\MobilityPref;
use App\Enums\PlanStatus;
use App\Enums\SocialContext;
use App\Models\Block;
use App\Models\DailyPlan;
use App\Models\Template;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Instantiates a DailyPlan for a date from a template, snapshotting each block so
 * later template edits never mutate an existing day (doc 06 / doc 02).
 */
class DailyPlanFactory
{
    public function instantiate(User $user, CarbonInterface $date, ?Template $template = null): DailyPlan
    {
        $template ??= Template::resolveForDate($user, $date);

        return DB::transaction(function () use ($user, $date, $template) {
            $plan = DailyPlan::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $date->toDateString())
                ->first();

            // Re-instantiating an existing date returns the existing plan, never a duplicate.
            if ($plan) {
                return $plan;
            }

            $plan = DailyPlan::create([
                'user_id' => $user->id,
                'template_id' => $template?->id,
                'date' => $date->toDateString(),
                'status' => PlanStatus::Draft,
            ]);

            if ($template) {
                $template->load(['blocks.defaultIntents']);
                foreach ($template->blocks as $block) {
                    $this->snapshotBlock($plan, $block);
                }
            }

            return $plan;
        });
    }

    private function snapshotBlock(DailyPlan $plan, Block $block): void
    {
        $context = $block->context ?? [];
        $primaryIntentId = $block->defaultIntents->first()?->id; // ordered by weight desc

        $plan->blocks()->create([
            'block_id' => $block->id,
            'name' => $block->name,
            'start_time' => $block->start_time,
            'end_time' => $block->end_time,
            'flexibility_mode' => $block->flexibility_mode,
            'intent_id' => $primaryIntentId,
            'social_context' => SocialContext::tryFrom($context['social'] ?? ''),
            'mobility_preference' => MobilityPref::tryFrom($context['mobility'] ?? ''),
            'constraints' => $block->constraints,
            'context_tags' => isset($context['location']) ? [$context['location']] : null,
            'order' => $block->order,
        ]);
    }
}
