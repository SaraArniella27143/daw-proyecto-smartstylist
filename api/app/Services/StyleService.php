<?php

namespace App\Services;

use App\Models\Style;
use Illuminate\Database\Eloquent\Collection;

class StyleService
{
    public function listStyles(): Collection
    {
        return Style::query()->orderBy('id')->get();
    }

    public function findStyle(int $id): Style
    {
        return Style::query()->findOrFail($id);
    }

    /**
     * @param  array{name: string, description?: string|null}  $attributes
     */
    public function createStyle(array $attributes): Style
    {
        return Style::query()->create($attributes);
    }

    /**
     * @param  array{name?: string, description?: string|null}  $attributes
     */
    public function updateStyle(int $id, array $attributes): Style
    {
        $style = $this->findStyle($id);
        $style->update($attributes);

        return $style->refresh();
    }

    public function deleteStyle(int $id): bool
    {
        $style = $this->findStyle($id);

        if ($style->garments()->exists()) {
            return false;
        }

        $style->delete();

        return true;
    }
}
