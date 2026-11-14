<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ReplaceOutfitGarmentsRequest;
use App\Http\Requests\StoreOutfitRequest;
use App\Http\Requests\UpdateOutfitRequest;
use App\Http\Resources\OutfitResource;
use App\Services\OutfitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutfitController extends ApiController
{
    public function __construct(
        private readonly OutfitService $outfitService,
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
}
