<?php

namespace App\Services;

use App\Models\Outfit;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function listForUser(User $user, ?string $from, ?string $to): Collection
    {
        return $user->schedules()
            ->with(['outfit.garments.category', 'outfit.garments.color', 'outfit.garments.style'])
            ->when($from !== null, fn ($query) => $query->whereDate('schedules.scheduled_date', '>=', $from))
            ->when($to !== null, fn ($query) => $query->whereDate('schedules.scheduled_date', '<=', $to))
            ->orderBy('schedules.scheduled_date')
            ->orderBy('schedules.id')
            ->get();
    }

    public function findForUser(User $user, int $id): Schedule
    {
        return $user->schedules()
            ->with(['outfit.garments.category', 'outfit.garments.color', 'outfit.garments.style'])
            ->findOrFail($id);
    }

    public function createForUser(User $user, int $outfitId, string $scheduledDate): ?Schedule
    {
        return DB::transaction(function () use ($user, $outfitId, $scheduledDate): ?Schedule {
            $this->lockUser($user);
            $outfit = $this->completeOutfitForUser($user, $outfitId);

            if ($this->scheduleExistsForUserDate($user, $scheduledDate)) {
                return null;
            }

            return $outfit->schedules()->create([
                'scheduled_date' => $scheduledDate,
            ])->load(['outfit.garments.category', 'outfit.garments.color', 'outfit.garments.style']);
        });
    }

    /**
     * @param  array{outfit_id?: int, scheduled_date?: string}  $attributes
     */
    public function updateForUser(User $user, int $id, array $attributes): ?Schedule
    {
        return DB::transaction(function () use ($user, $id, $attributes): ?Schedule {
            $this->lockUser($user);
            $schedule = $user->schedules()->findOrFail($id);
            $outfitId = $attributes['outfit_id'] ?? $schedule->outfit_id;
            $scheduledDate = $attributes['scheduled_date'] ?? $schedule->scheduled_date->toDateString();
            $this->completeOutfitForUser($user, $outfitId);

            if ($this->scheduleExistsForUserDate($user, $scheduledDate, $schedule->id)) {
                return null;
            }

            $schedule->update([
                'outfit_id' => $outfitId,
                'scheduled_date' => $scheduledDate,
            ]);

            return $schedule->refresh()
                ->load(['outfit.garments.category', 'outfit.garments.color', 'outfit.garments.style']);
        });
    }

    public function deleteForUser(User $user, int $id): void
    {
        $user->schedules()->findOrFail($id)->delete();
    }

    private function completeOutfitForUser(User $user, int $outfitId): Outfit
    {
        $outfit = $user->outfits()->find($outfitId);

        if ($outfit === null) {
            throw ValidationException::withMessages([
                'outfit_id' => 'El outfit debe pertenecer al usuario autenticado.',
            ]);
        }

        if (! $outfit->garments()->wherePivot('is_primary', true)->exists()) {
            throw ValidationException::withMessages([
                'outfit_id' => 'Solo se pueden planificar outfits completos con al menos una prenda principal.',
            ]);
        }

        return $outfit;
    }

    private function lockUser(User $user): void
    {
        User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
    }

    private function scheduleExistsForUserDate(User $user, string $scheduledDate, ?int $exceptId = null): bool
    {
        return $user->schedules()
            ->whereDate('schedules.scheduled_date', $scheduledDate)
            ->when($exceptId !== null, fn ($query) => $query->where('schedules.id', '!=', $exceptId))
            ->exists();
    }
}
