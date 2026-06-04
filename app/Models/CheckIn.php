<?php

namespace App\Models;

use App\Enums\CheckInStatus;
use Database\Factories\CheckInFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    /** @use HasFactory<CheckInFactory> */
    use HasFactory;

    protected $fillable = [
        'daily_plan_block_id',
        'status',
        'actual_start',
        'actual_end',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => CheckInStatus::class,
            'actual_start' => 'datetime',
            'actual_end' => 'datetime',
        ];
    }

    public function dailyPlanBlock(): BelongsTo
    {
        return $this->belongsTo(DailyPlanBlock::class);
    }
}
