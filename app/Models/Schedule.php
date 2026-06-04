<?php

namespace App\Models;

use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'daily_plan_id',
        'version',
        'generated_at',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'is_current' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function dailyPlan(): BelongsTo
    {
        return $this->belongsTo(DailyPlan::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ScheduleEvent::class)->orderBy('order');
    }
}
