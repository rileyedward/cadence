<?php

namespace App\Models;

use App\Enums\CheckInStatus;
use App\Enums\EnergyLevel;
use App\Enums\FlexibilityMode;
use App\Enums\FocusIntensity;
use App\Enums\MobilityPref;
use App\Enums\SocialContext;
use Database\Factories\DailyPlanBlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyPlanBlock extends Model
{
    /** @use HasFactory<DailyPlanBlockFactory> */
    use HasFactory;

    protected $fillable = [
        'daily_plan_id',
        'block_id',
        'name',
        'start_time',
        'end_time',
        'flexibility_mode',
        'intent_id',
        'secondary_intent_id',
        'energy_level',
        'focus_intensity',
        'social_context',
        'mobility_preference',
        'constraints',
        'context_tags',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'flexibility_mode' => FlexibilityMode::class,
            'energy_level' => EnergyLevel::class,
            'focus_intensity' => FocusIntensity::class,
            'social_context' => SocialContext::class,
            'mobility_preference' => MobilityPref::class,
            'constraints' => 'array',
            'context_tags' => 'array',
            'order' => 'integer',
        ];
    }

    public function dailyPlan(): BelongsTo
    {
        return $this->belongsTo(DailyPlan::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function intent(): BelongsTo
    {
        return $this->belongsTo(Intent::class);
    }

    public function secondaryIntent(): BelongsTo
    {
        return $this->belongsTo(Intent::class, 'secondary_intent_id');
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'daily_plan_block_activities')
            ->withPivot('order', 'estimated_minutes')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    /** The latest check-in's status, or null if none. */
    public function currentStatus(): ?CheckInStatus
    {
        return $this->checkIns()->latest('id')->first()?->status;
    }

    public function isFrozen(): bool
    {
        return in_array($this->currentStatus(), [CheckInStatus::Started, CheckInStatus::Completed], true);
    }
}
