<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutfitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $garments = $this->resource->relationLoaded('garments')
            ? $this->resource->garments
            : collect();
        $isComplete = $garments->isNotEmpty()
            && $garments->contains(fn ($garment): bool => (bool) $garment->pivot->is_primary);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_complete' => $isComplete,
            'garments' => $garments->map(fn ($garment): array => [
                'id' => $garment->id,
                'name' => $garment->name,
                'season' => $garment->season,
                'category' => $garment->category,
                'color' => $garment->color,
                'style' => $garment->style,
                'is_primary' => (bool) $garment->pivot->is_primary,
                'note' => $garment->pivot->note,
            ])->values(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
