<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceOutfitGarmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'garments' => ['present', 'array', 'max:50'],
            'garments.*' => ['required', 'array'],
            'garments.*.garment_id' => ['required', 'integer', 'min:1', 'distinct', 'exists:garments,id'],
            'garments.*.is_primary' => ['required', 'boolean'],
            'garments.*.note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
