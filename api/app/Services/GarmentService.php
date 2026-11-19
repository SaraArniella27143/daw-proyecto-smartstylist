<?php

namespace App\Services;

use App\Models\Garment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
     * @param  array{category_id: int, color_id: int, style_id?: int|null, name: string, season: string, image?: UploadedFile|null}  $attributes
     */
    public function createForUser(User $user, array $attributes): Garment
    {
        $image = $attributes['image'] ?? null;
        unset($attributes['image']);

        if ($image instanceof UploadedFile) {
            $attributes['image_path'] = $this->storeImage($user, $image);
        }

        try {
            $garment = $user->garments()->create($attributes);
        } catch (Throwable $exception) {
            if (isset($attributes['image_path'])) {
                $this->deleteImage($attributes['image_path']);
            }

            throw $exception;
        }

        return $garment->load(self::RELATIONS);
    }

    /**
     * @param  array{category_id?: int, color_id?: int, style_id?: int|null, name?: string, season?: string, image?: UploadedFile|null}  $attributes
     */
    public function updateForUser(User $user, int $id, array $attributes): Garment
    {
        $garment = $user->garments()->findOrFail($id);
        $oldImagePath = $garment->image_path;
        $imageProvided = array_key_exists('image', $attributes);
        $image = $attributes['image'] ?? null;
        unset($attributes['image']);

        if ($imageProvided) {
            $attributes['image_path'] = $image instanceof UploadedFile
                ? $this->storeImage($user, $image)
                : null;
        }

        try {
            $garment->update($attributes);
        } catch (Throwable $exception) {
            if (
                isset($attributes['image_path'])
                && $attributes['image_path'] !== $oldImagePath
            ) {
                $this->deleteImage($attributes['image_path']);
            }

            throw $exception;
        }

        if ($imageProvided && $oldImagePath !== null && $oldImagePath !== $garment->image_path) {
            $this->deleteImage($oldImagePath);
        }

        return $garment->refresh()->load(self::RELATIONS);
    }

    public function deleteForUser(User $user, int $id): void
    {
        $garment = $user->garments()->findOrFail($id);
        $garment->delete();

        if ($garment->image_path !== null) {
            $this->deleteImage($garment->image_path);
        }
    }

    public function imageForUser(User $user, int $id): StreamedResponse
    {
        $garment = $user->garments()->findOrFail($id);

        if ($garment->image_path === null) {
            abort(404);
        }

        $disk = Storage::disk($this->imageDisk());

        if (! $disk->exists($garment->image_path)) {
            abort(404);
        }

        return $disk->response($garment->image_path);
    }

    private function storeImage(User $user, UploadedFile $image): string
    {
        $path = $image->store("garments/{$user->id}", $this->imageDisk());

        if ($path === false) {
            throw new RuntimeException('No se pudo guardar la imagen de la prenda.');
        }

        return $path;
    }

    private function deleteImage(string $path): void
    {
        if (! Storage::disk($this->imageDisk())->delete($path)) {
            throw new RuntimeException('No se pudo eliminar la imagen de la prenda.');
        }
    }

    private function imageDisk(): string
    {
        return (string) config('filesystems.default');
    }
}
