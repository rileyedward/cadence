<?php

namespace App\Models;

use App\Enums\PlanStatus;
use Database\Factories\DailyPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DailyPlan extends Model
{
    /** @use HasFactory<DailyPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_id',
        'date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => PlanStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(DailyPlanBlock::class)->orderBy('order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function currentSchedule(): HasOne
    {
        return $this->hasOne(Schedule::class)->where('is_current', true);
    }
}
