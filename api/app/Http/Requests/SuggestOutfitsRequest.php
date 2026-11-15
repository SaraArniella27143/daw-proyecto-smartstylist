<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuggestOutfitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'season' => ['required', 'string', Rule::in(['spring', 'summer', 'autumn', 'winter'])],
            'color_id' => ['sometimes', 'integer', 'min:1', 'exists:colors,id'],
            'style_id' => ['sometimes', 'integer', 'min:1', 'exists:styles,id'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ];
    }
}
