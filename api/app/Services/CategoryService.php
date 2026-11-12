<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function listCategories(): Collection
    {
        return Category::query()->orderBy('id')->get();
    }

    public function findCategory(int $id): Category
    {
        return Category::query()->findOrFail($id);
    }

    /**
     * @param  array{name: string, description?: string|null}  $attributes
     */
    public function createCategory(array $attributes): Category
    {
        return Category::query()->create($attributes);
    }

    /**
     * @param  array{name?: string, description?: string|null}  $attributes
     */
    public function updateCategory(int $id, array $attributes): Category
    {
        $category = $this->findCategory($id);
        $category->update($attributes);

        return $category->refresh();
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->findCategory($id);

        if ($category->garments()->exists()) {
            return false;
        }

        $category->delete();

        return true;
    }
}
