<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class GarmentRequest extends FormRequest
{
    protected const ALLOWED_SEASONS = ['spring', 'summer', 'autumn', 'winter', 'year_round'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function garmentRules(bool $isUpdate): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return [
            'category_id' => [...$required, 'integer', 'min:1', 'exists:categories,id'],
            'color_id' => [...$required, 'integer', 'min:1', 'exists:colors,id'],
            'style_id' => ['sometimes', 'nullable', 'integer', 'min:1', 'exists:styles,id'],
            'name' => [...$required, 'string', 'max:120'],
            'season' => [...$required, 'string', 'in:'.implode(',', self::ALLOWED_SEASONS)],
            'image_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'user_id' => ['prohibited'],
        ];
    }
}
