<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outfit_id' => ['required', 'integer', 'min:1', 'exists:outfits,id'],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'user_id' => ['prohibited'],
        ];
    }
}
