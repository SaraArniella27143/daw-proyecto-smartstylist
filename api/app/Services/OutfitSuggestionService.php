<?php

namespace App\Services;

use App\Models\Garment;
use App\Models\User;

class OutfitSuggestionService
{
    /**
     * @return list<array{name: string, is_complete: true, garments: list<array<string, mixed>>}>
     */
    public function suggestForUser(
        User $user,
        string $season,
        ?int $colorId,
        ?int $styleId,
        int $limit,
    ): array {
        $tops = $this->eligibleGarments($user, 'upper_body', $season, $colorId, $styleId, $limit);
        $bottoms = $this->eligibleGarments($user, 'lower_body', $season, $colorId, $styleId, $limit);
        $dresses = $this->eligibleGarments($user, 'full_body', $season, $colorId, $styleId, $limit);
        $optionalGarments = $this->eligibleGarments($user, 'optional', $season, $colorId, $styleId, $limit);

        $separateOutfits = [];

        foreach ($tops as $top) {
            foreach ($bottoms as $bottom) {
                $separateOutfits[] = [$top, $bottom];
            }
        }

        $suggestions = [];
        $separateIndex = 0;
        $dressIndex = 0;

        while (
            count($suggestions) < $limit
            && ($separateIndex < count($separateOutfits) || $dressIndex < count($dresses))
        ) {
            if ($dressIndex < count($dresses)) {
                $number = count($suggestions) + 1;
                $suggestions[] = $this->formatSuggestion(
                    [$dresses[$dressIndex++]],
                    $this->optionalForSuggestion($optionalGarments, $number),
                    $number,
                );
            }

            if ($separateIndex < count($separateOutfits) && count($suggestions) < $limit) {
                $number = count($suggestions) + 1;
                $suggestions[] = $this->formatSuggestion(
                    $separateOutfits[$separateIndex++],
                    $this->optionalForSuggestion($optionalGarments, $number),
                    $number,
                );
            }
        }

        return $suggestions;
    }

    /**
     * @return list<Garment>
     */
    private function eligibleGarments(
        User $user,
        string $outfitGroup,
        string $season,
        ?int $colorId,
        ?int $styleId,
        int $limit,
    ): array {
        return $user->garments()
            ->with(['category', 'color', 'style'])
            ->whereIn('season', [$season, 'year_round'])
            ->when($colorId !== null, fn ($query) => $query->where('color_id', $colorId))
            ->when($styleId !== null, fn ($query) => $query->where('style_id', $styleId))
            ->whereHas('category', fn ($query) => $query->where('outfit_group', $outfitGroup))
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * @param  list<Garment>  $optionalGarments
     * @return list<Garment>
     */
    private function optionalForSuggestion(array $optionalGarments, int $suggestionNumber): array
    {
        if ($optionalGarments === []) {
            return [];
        }

        return [$optionalGarments[($suggestionNumber - 1) % count($optionalGarments)]];
    }

    /**
     * @param  list<Garment>  $baseGarments
     * @param  list<Garment>  $extras
     * @return array{name: string, is_complete: true, garments: list<array<string, mixed>>}
     */
    private function formatSuggestion(array $baseGarments, array $extras, int $number): array
    {
        $garments = [];

        foreach ([...$baseGarments, ...$extras] as $index => $garment) {
            $garments[] = [
                'id' => $garment->id,
                'name' => $garment->name,
                'season' => $garment->season,
                'category' => $garment->category,
                'color' => $garment->color,
                'style' => $garment->style,
                'is_primary' => $index === 0,
                'note' => null,
            ];
        }

        return [
            'name' => "Sugerencia {$number}",
            'is_complete' => true,
            'garments' => $garments,
        ];
    }
}
