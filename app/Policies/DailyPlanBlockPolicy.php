<?php

namespace App\Policies;

use App\Models\DailyPlanBlock;
use App\Models\User;

/**
 * Daily-plan blocks are owned transitively through their plan.
 */
class DailyPlanBlockPolicy
{
    public function view(User $user, DailyPlanBlock $block): bool
    {
        return $block->dailyPlan->user_id === $user->id;
    }

    public function update(User $user, DailyPlanBlock $block): bool
    {
        return $block->dailyPlan->user_id === $user->id;
    }

    public function delete(User $user, DailyPlanBlock $block): bool
    {
        return $block->dailyPlan->user_id === $user->id;
    }
}
