<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutfitLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'used_at' => $this->used_at->utc()->format('Y-m-d\\TH:i:s\\Z'),
            'outfit' => new OutfitResource($this->whenLoaded('outfit')),
            'created_at' => $this->created_at,
        ];
    }
}
