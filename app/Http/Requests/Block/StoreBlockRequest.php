<?php

namespace App\Http\Requests\Block;

use App\Enums\FlexibilityMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The parent template is authorized in the controller.
        return true;
    }

    /**
     * Blocks may legitimately wrap past clock-midnight (e.g. 20:00 → 01:30), so we
     * do not enforce end > start here — only that the two differ. The compiler
     * resolves ordering via minutes-from-day_start (doc 07).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'flexibility_mode' => ['required', Rule::in(FlexibilityMode::values())],
            'category' => ['nullable', 'string', 'max:60'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],

            'constraints' => ['nullable', 'array'],
            'constraints.minDuration' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'constraints.maxDuration' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'constraints.minRestAfter' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'constraints.noOverlap' => ['nullable', 'boolean'],

            'context' => ['nullable', 'array'],
            'context.location' => ['nullable', 'string', 'max:120'],
            'context.mobility' => ['nullable', 'string', 'max:40'],
            'context.social' => ['nullable', 'string', 'max:40'],

            'default_intents' => ['nullable', 'array'],
            'default_intents.*.intent_id' => ['required', 'integer', 'exists:intents,id'],
            'default_intents.*.weight' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
