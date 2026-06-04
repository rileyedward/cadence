<?php

namespace App\Http\Resources;

use App\Models\TemplateAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TemplateAssignment */
class TemplateAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_id' => $this->template_id,
            'scope' => $this->scope,
            'days_of_week' => $this->days_of_week,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'priority' => $this->priority,
        ];
    }
}
