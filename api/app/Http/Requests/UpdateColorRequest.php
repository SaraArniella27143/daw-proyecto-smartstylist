<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $color = $this->route('color');

        return [
            'name' => ['required_without:hex_code', 'string', 'max:80', Rule::unique('colors', 'name')->ignore($color)],
            'hex_code' => ['required_without:name', 'string', 'size:7', 'regex:/^#[0-9A-Fa-f]{6}$/', Rule::unique('colors', 'hex_code')->ignore($color)],
        ];
    }
}
