<?php

namespace App\Services;

use App\Models\Outfit;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutfitService
{
    public function listForUser(User $user): Collection
    {
        return $user->outfits()
            ->with(['garments.category', 'garments.color', 'garments.style'])
            ->orderBy('id')
            ->get();
    }

    public function findForUser(User $user, int $id): Outfit
    {
        return $user->outfits()
            ->with(['garments.category', 'garments.color', 'garments.style'])
            ->findOrFail($id);
    }

    /**
     * @param  array{name: string, description?: string|null}  $attributes
     */
    public function createForUser(User $user, array $attributes): Outfit
    {
        $outfit = $user->outfits()->create($attributes);

        return $outfit->load(['garments.category', 'garments.color', 'garments.style']);
    }

    /**
     * @param  array{name?: string, description?: string|null}  $attributes
     */
    public function updateForUser(User $user, int $id, array $attributes): Outfit
    {
        $outfit = $user->outfits()->findOrFail($id);
        $outfit->update($attributes);

        return $outfit->refresh()->load(['garments.category', 'garments.color', 'garments.style']);
    }

    public function deleteForUser(User $user, int $id): void
    {
        $user->outfits()->findOrFail($id)->delete();
    }

    /**
     * @param  list<array{garment_id: int, is_primary: bool, note?: string|null}>  $garments
     */
    public function replaceGarments(User $user, int $id, array $garments): Outfit
    {
        return DB::transaction(function () use ($user, $id, $garments): Outfit {
            $outfit = $user->outfits()->lockForUpdate()->findOrFail($id);
            $garmentIds = array_column($garments, 'garment_id');
            $ownedGarmentIds = $user->garments()
                ->whereIn('id', $garmentIds)
                ->pluck('id')
                ->map(fn ($garmentId): int => (int) $garmentId)
                ->all();
            $errors = [];

            foreach ($garments as $index => $garment) {
                if (! in_array((int) $garment['garment_id'], $ownedGarmentIds, true)) {
                    $errors["garments.{$index}.garment_id"] = 'Todas las prendas deben pertenecer al usuario autenticado.';
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $syncData = [];

            foreach ($garments as $garment) {
                $syncData[$garment['garment_id']] = [
                    'is_primary' => $garment['is_primary'],
                    'note' => $garment['note'] ?? null,
                ];
            }

            $outfit->garments()->sync($syncData);

            return $outfit->load(['garments.category', 'garments.color', 'garments.style']);
        });
    }
}
