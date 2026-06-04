<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller authorizes via the bound activity policy
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'default_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:40'],
            'intent_ids' => ['nullable', 'array'],
            'intent_ids.*' => ['integer', 'exists:intents,id'],
        ];
    }
}
