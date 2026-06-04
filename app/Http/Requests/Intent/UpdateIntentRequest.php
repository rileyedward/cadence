<?php

namespace App\Http\Requests\Intent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorized in the controller via the route-model-bound intent policy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:32'],
            'icon' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
