<?php

namespace App\Models;

use Database\Factories\CalendarBusyEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarBusyEvent extends Model
{
    /** @use HasFactory<CalendarBusyEventFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'source_uid',
        'title',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
