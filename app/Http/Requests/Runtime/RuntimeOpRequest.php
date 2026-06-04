<?php

namespace App\Http\Requests\Runtime;

use Illuminate\Foundation\Http\FormRequest;

/**
 * One runtime op per request (doc 08). The op name comes from the route; the
 * relevant fields below are read by the Recompiler. `from_min` is a client hint
 * validated/clamped against server time in the controller.
 */
class RuntimeOpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller authorizes the bound plan
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_min' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'block_id' => ['nullable', 'integer'],
            'delta' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'at_min' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'start_min' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'with_block_id' => ['nullable', 'integer'],
            'activity_id' => ['nullable', 'integer'],
        ];
    }
}
