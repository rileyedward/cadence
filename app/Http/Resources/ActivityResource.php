<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'default_duration_minutes' => $this->default_duration_minutes,
            'tags' => $this->tags ?? [],
            'is_system' => $this->user_id === null,
            'intent_ids' => $this->whenLoaded('intents', fn () => $this->intents->pluck('id')),
            'intents' => IntentResource::collection($this->whenLoaded('intents')),
        ];
    }
}
