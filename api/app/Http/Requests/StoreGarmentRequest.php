<?php

namespace App\Http\Requests;

class StoreGarmentRequest extends GarmentRequest
{
    public function rules(): array
    {
        return $this->garmentRules(isUpdate: false);
    }
}
