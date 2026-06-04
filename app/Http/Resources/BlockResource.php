<?php

namespace App\Http\Resources;

use App\Models\Block;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Block */
class BlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_id' => $this->template_id,
            'name' => $this->name,
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'flexibility_mode' => $this->flexibility_mode,
            'category' => $this->category,
            'priority' => $this->priority,
            'constraints' => $this->constraints,
            'context' => $this->context,
            'order' => $this->order,
            'default_intents' => $this->whenLoaded('defaultIntents', fn () => $this->defaultIntents->map(fn ($intent) => [
                'id' => $intent->id,
                'name' => $intent->name,
                'slug' => $intent->slug,
                'color' => $intent->color,
                'icon' => $intent->icon,
                'weight' => $intent->pivot->weight,
            ])),
        ];
    }
}
