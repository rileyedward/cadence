<?php

namespace App\Models\Concerns;

use App\Models\Activity;
use App\Models\DailyPlan;
use App\Models\Intent;
use App\Models\Template;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cadence-owned relations for the User model.
 */
trait HasCadenceRelations
{
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /** Custom (user-owned) intents only; system intents have a null user_id. */
    public function intents(): HasMany
    {
        return $this->hasMany(Intent::class);
    }

    /** Custom (user-owned) activities only; system activities have a null user_id. */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function dailyPlans(): HasMany
    {
        return $this->hasMany(DailyPlan::class);
    }
}
