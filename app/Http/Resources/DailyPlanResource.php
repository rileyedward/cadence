<?php

namespace App\Http\Resources;

use App\Models\DailyPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DailyPlan */
class DailyPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'template_id' => $this->template_id,
            'date' => $this->date->toDateString(),
            'status' => $this->status,
            'blocks' => DailyPlanBlockResource::collection($this->whenLoaded('blocks')),
            'current_schedule' => $this->whenLoaded(
                'currentSchedule',
                fn () => $this->currentSchedule ? new ScheduleResource($this->currentSchedule) : null,
            ),
        ];
    }
}
