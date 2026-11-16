<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scheduled_date' => $this->scheduled_date->toDateString(),
            'outfit' => new OutfitResource($this->whenLoaded('outfit')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
