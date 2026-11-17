<?php

namespace App\Services;

use App\Models\OutfitLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutfitLogService
{
    public function recordForUser(User $user, int $outfitId, string $usedAt): OutfitLog
    {
        $outfit = $user->outfits()->find($outfitId);

        if ($outfit === null) {
            throw ValidationException::withMessages([
                'outfit_id' => 'El outfit debe pertenecer al usuario autenticado.',
            ]);
        }

        $usedAtUtc = Carbon::parse($usedAt)->utc();

        return $outfit->logs()->create([
            'used_at' => $usedAtUtc,
        ])->load(['outfit.garments.category', 'outfit.garments.color', 'outfit.garments.style']);
    }

    /**
     * @return array{
     *     total_uses: int,
     *     most_used_garments: list<array{garment: array<string, mixed>, usage_count: int}>,
     *     least_used_garments: list<array{garment: array<string, mixed>, usage_count: int}>,
     *     frequent_combinations: list<array{
     *         garments: list<array{id: int, name: string}>,
     *         usage_count: int,
     *         outfits: list<array{id: int, name: string}>
     *     }>
     * }
     */
    public function statisticsForUser(User $user): array
    {
        $garmentUsage = DB::table('outfit_logs')
            ->join('outfits', 'outfits.id', '=', 'outfit_logs.outfit_id')
            ->join('outfit_garments', 'outfit_garments.outfit_id', '=', 'outfits.id')
            ->where('outfits.user_id', $user->id)
            ->select('outfit_garments.garment_id')
            ->selectRaw('COUNT(outfit_logs.id) as usage_count')
            ->groupBy('outfit_garments.garment_id')
            ->pluck('usage_count', 'garment_id')
            ->map(fn ($count): int => (int) $count);

        $garments = $user->garments()
            ->with(['category', 'color', 'style'])
            ->orderBy('id')
            ->get()
            ->map(fn ($garment): array => [
                'garment' => [
                    'id' => $garment->id,
                    'name' => $garment->name,
                    'season' => $garment->season,
                    'category' => $garment->category,
                    'color' => $garment->color,
                    'style' => $garment->style,
                ],
                'usage_count' => $garmentUsage->get($garment->id, 0),
            ]);

        $maximumGarmentUsage = $garments->max('usage_count') ?? 0;
        $minimumGarmentUsage = $garments->min('usage_count');
        $leastUsageThreshold = $garments->contains(fn (array $item): bool => $item['usage_count'] === 0)
            ? 0
            : $minimumGarmentUsage;

        return [
            'total_uses' => $user->outfitLogs()->count(),
            'most_used_garments' => $maximumGarmentUsage === 0
                ? []
                : $garments->where('usage_count', $maximumGarmentUsage)->values()->all(),
            'least_used_garments' => $leastUsageThreshold === null
                ? []
                : $garments->where('usage_count', $leastUsageThreshold)->values()->all(),
            'frequent_combinations' => $this->frequentCombinations($user),
        ];
    }

    /**
     * @return list<array{
     *     garments: list<array{id: int, name: string}>,
     *     usage_count: int,
     *     outfits: list<array{id: int, name: string}>
     * }>
     */
    private function frequentCombinations(User $user): array
    {
        $outfits = $user->outfits()
            ->with(['garments:id,name'])
            ->withCount([
                'logs as usage_count',
            ])
            ->orderBy('id')
            ->get();

        $combinations = [];

        foreach ($outfits as $outfit) {
            if ((int) $outfit->usage_count === 0) {
                continue;
            }

            $garments = $outfit->garments
                ->sortBy('id')
                ->map(fn ($garment): array => [
                    'id' => $garment->id,
                    'name' => $garment->name,
                ])
                ->values()
                ->all();

            if ($garments === []) {
                continue;
            }

            $signature = implode(',', array_column($garments, 'id'));
            $combinations[$signature] ??= [
                'garments' => $garments,
                'usage_count' => 0,
                'outfits' => [],
            ];
            $combinations[$signature]['usage_count'] += (int) $outfit->usage_count;
            $combinations[$signature]['outfits'][] = [
                'id' => $outfit->id,
                'name' => $outfit->name,
            ];
        }

        return collect($combinations)
            ->sort(function (array $left, array $right): int {
                return ($right['usage_count'] <=> $left['usage_count'])
                    ?: (implode(',', array_column($left['garments'], 'id'))
                        <=> implode(',', array_column($right['garments'], 'id')));
            })
            ->values()
            ->all();
    }
}
