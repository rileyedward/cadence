<?php

namespace App\Http\Resources;

use App\Models\DailyPlanBlock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DailyPlanBlock */
class DailyPlanBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'daily_plan_id' => $this->daily_plan_id,
            'block_id' => $this->block_id,
            'name' => $this->name,
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'flexibility_mode' => $this->flexibility_mode,
            'intent_id' => $this->intent_id,
            'secondary_intent_id' => $this->secondary_intent_id,
            'energy_level' => $this->energy_level,
            'focus_intensity' => $this->focus_intensity,
            'social_context' => $this->social_context,
            'mobility_preference' => $this->mobility_preference,
            'constraints' => $this->constraints,
            'context_tags' => $this->context_tags,
            'order' => $this->order,
            'activities' => $this->whenLoaded('activities', fn () => $this->activities->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'default_duration_minutes' => $a->default_duration_minutes,
                'pivot' => [
                    'order' => $a->pivot->order,
                    'estimated_minutes' => $a->pivot->estimated_minutes,
                ],
            ])),
            'current_status' => $this->whenLoaded('checkIns', fn () => $this->currentStatus()),
        ];
    }
}
