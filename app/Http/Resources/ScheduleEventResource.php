<?php

namespace App\Http\Resources;

use App\Models\ScheduleEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScheduleEvent */
class ScheduleEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->schedule_id,
            'source_block_id' => $this->source_block_id,
            'type' => $this->type,
            'label' => $this->label,
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'order' => $this->order,
            'metadata' => $this->metadata,
        ];
    }
}
