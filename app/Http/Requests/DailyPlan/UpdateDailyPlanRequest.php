<?php

namespace App\Http\Requests\DailyPlan;

use App\Enums\EnergyLevel;
use App\Enums\FocusIntensity;
use App\Enums\MobilityPref;
use App\Enums\SocialContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDailyPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller authorizes the bound plan
    }

    /**
     * Batch of per-block decisions. Each entry targets a daily_plan_block by id.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'blocks' => ['present', 'array'],
            'blocks.*.id' => ['required', 'integer', 'exists:daily_plan_blocks,id'],
            'blocks.*.intent_id' => ['nullable', 'integer', 'exists:intents,id'],
            'blocks.*.secondary_intent_id' => ['nullable', 'integer', 'exists:intents,id'],
            'blocks.*.energy_level' => ['nullable', Rule::in(EnergyLevel::values())],
            'blocks.*.focus_intensity' => ['nullable', Rule::in(FocusIntensity::values())],
            'blocks.*.social_context' => ['nullable', Rule::in(SocialContext::values())],
            'blocks.*.mobility_preference' => ['nullable', Rule::in(MobilityPref::values())],
            'blocks.*.constraints' => ['nullable', 'array'],
            'blocks.*.context_tags' => ['nullable', 'array'],
            'blocks.*.context_tags.*' => ['string', 'max:60'],
            'blocks.*.activities' => ['nullable', 'array'],
            'blocks.*.activities.*.activity_id' => ['required', 'integer', 'exists:activities,id'],
            'blocks.*.activities.*.estimated_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'blocks.*.activities.*.order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
