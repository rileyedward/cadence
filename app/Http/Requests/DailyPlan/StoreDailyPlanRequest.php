<?php

namespace App\Http\Requests\DailyPlan;

use App\Models\DailyPlan;
use Illuminate\Foundation\Http\FormRequest;

class StoreDailyPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DailyPlan::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
        ];
    }
}
