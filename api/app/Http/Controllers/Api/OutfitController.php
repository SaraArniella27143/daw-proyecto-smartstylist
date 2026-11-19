<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ReplaceOutfitGarmentsRequest;
use App\Http\Requests\StoreOutfitRequest;
use App\Http\Requests\UpdateOutfitRequest;
use App\Http\Resources\OutfitResource;
use App\Http\Requests\SuggestOutfitsRequest;
use App\Services\OutfitService;
use App\Services\OutfitSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutfitController extends ApiController
{
    public function __construct(
        private readonly OutfitService $outfitService,
        private readonly OutfitSuggestionService $suggestionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return OutfitResource::collection(
            $this->outfitService->listForUser($this->authenticatedUser($request))
        )->response();
    }

    public function show(Request $request, int $outfit): OutfitResource
    {
        return new OutfitResource(
            $this->outfitService->findForUser($this->authenticatedUser($request), $outfit)
        );
    }

    public function store(StoreOutfitRequest $request): JsonResponse
    {
        return (new OutfitResource(
            $this->outfitService->createForUser($this->authenticatedUser($request), $request->validated())
        ))->response()->setStatusCode(201);
    }

    public function update(UpdateOutfitRequest $request, int $outfit): OutfitResource
    {
        return new OutfitResource(
            $this->outfitService->updateForUser(
                $this->authenticatedUser($request),
                $outfit,
                $request->validated(),
            )
        );
    }

    public function destroy(Request $request, int $outfit): JsonResponse
    {
        $this->outfitService->deleteForUser($this->authenticatedUser($request), $outfit);

        return response()->json(status: 204);
    }

    public function replaceGarments(ReplaceOutfitGarmentsRequest $request, int $outfit): OutfitResource
    {
        $user = $this->authenticatedUser($request);
        $attributes = $request->validated();

        return new OutfitResource(
            $this->outfitService->replaceGarments($user, $outfit, $attributes['garments'])
        );
    }

    public function suggestions(SuggestOutfitsRequest $request): JsonResponse
    {
        $attributes = $request->validated();

        return response()->json([
            'data' => $this->suggestionService->suggestForUser(
                $this->authenticatedUser($request),
                $attributes['season'],
                $attributes['color_id'] ?? null,
                $attributes['style_id'] ?? null,
                $attributes['limit'] ?? 5,
            ),
        ]);
    }
}
