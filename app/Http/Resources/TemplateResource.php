<?php

namespace App\Http\Resources;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Template */
class TemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'forked_from_id' => $this->forked_from_id,
            'blocks_count' => $this->whenCounted('blocks'),
            'blocks' => BlockResource::collection($this->whenLoaded('blocks')),
            'assignments' => TemplateAssignmentResource::collection($this->whenLoaded('assignments')),
        ];
    }
}
