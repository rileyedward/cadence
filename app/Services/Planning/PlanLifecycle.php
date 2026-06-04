<?php

namespace App\Services\Planning;

use App\Enums\PlanStatus;
use App\Models\User;
use App\Support\LogicalDay;
use Carbon\CarbonInterface;

/**
 * Lazily advances plan statuses on load — no scheduler in this build (doc 06).
 * A plan whose logical day has fully passed becomes `done`; today's generated
 * plan becomes `active`.
 */
class PlanLifecycle
{
    public function sync(User $user, ?CarbonInterface $now = null): void
    {
        $today = LogicalDay::currentDate($user, $now)->toDateString();

        // Past logical days that were never closed → done.
        $user->dailyPlans()
            ->whereDate('date', '<', $today)
            ->whereIn('status', [PlanStatus::Draft->value, PlanStatus::Generated->value, PlanStatus::Active->value])
            ->update(['status' => PlanStatus::Done->value]);

        // Today's compiled-but-not-yet-active plan → active.
        $user->dailyPlans()
            ->whereDate('date', $today)
            ->where('status', PlanStatus::Generated->value)
            ->update(['status' => PlanStatus::Active->value]);
    }
}
