<?php

namespace App\Http\Requests\Intent;

use App\Models\Intent;
use Illuminate\Foundation\Http\FormRequest;

class StoreIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Intent::class);
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
