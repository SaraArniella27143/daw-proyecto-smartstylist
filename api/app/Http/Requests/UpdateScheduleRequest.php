<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outfit_id' => ['sometimes', 'required', 'integer', 'min:1', 'exists:outfits,id'],
            'scheduled_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'user_id' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && $this->safe()->keys() === []) {
                    $validator->errors()->add('scheduled_date', 'Debe proporcionar al menos un campo para actualizar.');
                }
            },
        ];
    }
}