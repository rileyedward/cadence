<?php

namespace App\Http\Requests\Block;

use Illuminate\Foundation\Http\FormRequest;

class ReorderBlocksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller authorizes the parent template
    }

    /**
     * Ordered list of block ids; index becomes the new `order`.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'block_ids' => ['required', 'array', 'min:1'],
            'block_ids.*' => ['integer', 'exists:blocks,id'],
        ];
    }
}
