<?php

namespace App\Services;

use App\Models\Color;
use Illuminate\Database\Eloquent\Collection;

class ColorService
{
    public function listColors(): Collection
    {
        return Color::query()->orderBy('id')->get();
    }

    public function findColor(int $id): Color
    {
        return Color::query()->findOrFail($id);
    }

    public function createColor(array $attributes): Color
    {
        return Color::query()->create($attributes);
    }

    public function updateColor(int $id, array $attributes): Color
    {
        $color = $this->findColor($id);
        $color->update($attributes);

        return $color->refresh();
    }

    public function deleteColor(int $id): bool
    {
        $color = $this->findColor($id);

        if ($color->garments()->exists()) {
            return false;
        }

        $color->delete();

        return true;
    }
}
