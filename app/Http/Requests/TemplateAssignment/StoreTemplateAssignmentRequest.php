<?php

namespace App\Http\Requests\TemplateAssignment;

use App\Enums\TemplateScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller authorizes the parent template
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(TemplateScope::values())],
            // Required only when scope is custom; each day in 0..6.
            'days_of_week' => ['nullable', 'array', Rule::requiredIf($this->input('scope') === TemplateScope::Custom->value)],
            'days_of_week.*' => ['integer', 'between:0,6'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
