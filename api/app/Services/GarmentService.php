<?php

namespace App\Services;

use App\Models\Garment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GarmentService
{
    private const RELATIONS = ['category', 'color', 'style'];

    public function listForUser(User $user): Collection
    {
        return $user->garments()
            ->with(self::RELATIONS)
            ->orderBy('id')
            ->get();
    }

    public function findForUser(User $user, int $id): Garment
    {
        return $user->garments()
            ->with(self::RELATIONS)
            ->findOrFail($id);
    }

    /**
     * @param  array{category_id: int, color_id: int, style_id?: int|null, name: string, season: string, image_url?: string|null}  $attributes
     */
    public function createForUser(User $user, array $attributes): Garment
    {
        $garment = $user->garments()->create($attributes);

        return $garment->load(self::RELATIONS);
    }

    /**
     * @param  array{category_id?: int, color_id?: int, style_id?: int|null, name?: string, season?: string, image_url?: string|null}  $attributes
     */
    public function updateForUser(User $user, int $id, array $attributes): Garment
    {
        $garment = $user->garments()->findOrFail($id);
        $garment->update($attributes);

        return $garment->refresh()->load(self::RELATIONS);
    }

    public function deleteForUser(User $user, int $id): void
    {
        $user->garments()->findOrFail($id)->delete();
    }
}
