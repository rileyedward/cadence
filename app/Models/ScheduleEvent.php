<?php

namespace App\Models;

use App\Enums\EventType;
use Database\Factories\ScheduleEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleEvent extends Model
{
    /** @use HasFactory<ScheduleEventFactory> */
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'schedule_id',
        'source_block_id',
        'type',
        'label',
        'start_time',
        'end_time',
        'order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'metadata' => 'array',
            'order' => 'integer',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function sourceBlock(): BelongsTo
    {
        return $this->belongsTo(DailyPlanBlock::class, 'source_block_id');
    }
}
