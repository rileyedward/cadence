<?php

namespace App\Http\Requests\CheckIn;

use App\Enums\CheckInStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller authorizes via the bound block's plan policy
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(CheckInStatus::values())],
            'actual_start' => ['nullable', 'date'],
            'actual_end' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
