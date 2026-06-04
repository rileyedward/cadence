<?php

namespace App\Http\Resources;

use App\Models\Intent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Intent */
class IntentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_system' => $this->user_id === null,
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
        ];
    }
}
