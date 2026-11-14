<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class UpdateGarmentRequest extends GarmentRequest
{
    private const UPDATABLE_FIELDS = [
        'category_id',
        'color_id',
        'style_id',
        'name',
        'season',
        'image_url',
    ];

    public function rules(): array
    {
        return $this->garmentRules(isUpdate: true);
    }

    /**
     * @return array<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $validator->errors()->isEmpty()
                    && array_intersect(array_keys($this->all()), self::UPDATABLE_FIELDS) === []
                ) {
                    $validator->errors()->add('name', 'Debe proporcionar al menos un campo para actualizar.');
                }
            },
        ];
    }
}
