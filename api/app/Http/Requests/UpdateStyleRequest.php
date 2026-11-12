<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStyleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $style = $this->route('style');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:80', Rule::unique('styles', 'name')->ignore($style)],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && $this->safe()->keys() === []) {
                    $validator->errors()->add('name', 'Debe proporcionar al menos un campo para actualizar.');
                }
            },
        ];
    }
}
