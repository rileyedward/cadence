<?php

namespace App\Http\Resources;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Schedule */
class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'daily_plan_id' => $this->daily_plan_id,
            'version' => $this->version,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'is_current' => $this->is_current,
            'events' => ScheduleEventResource::collection($this->whenLoaded('events')),
        ];
    }
}
