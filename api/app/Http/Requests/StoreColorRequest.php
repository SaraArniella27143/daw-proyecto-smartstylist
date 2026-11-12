<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', 'unique:colors,name'],
            'hex_code' => ['nullable', 'string', 'size:7', 'regex:/^#[0-9A-Fa-f]{6}$/', 'unique:colors,hex_code'],
        ];
    }
}