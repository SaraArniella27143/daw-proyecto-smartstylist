<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOutfitLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outfit_id' => ['required', 'integer', 'min:1', 'exists:outfits,id'],
            'used_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],
            'user_id' => ['prohibited'],
        ];
    }
}
