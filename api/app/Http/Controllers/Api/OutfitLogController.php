<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreOutfitLogRequest;
use App\Http\Resources\OutfitLogResource;
use App\Services\OutfitLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutfitLogController extends ApiController
{
    public function __construct(private readonly OutfitLogService $logService) {}

    public function store(StoreOutfitLogRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $attributes = $request->validated();

        return (new OutfitLogResource(
            $this->logService->recordForUser(
                $user,
                $attributes['outfit_id'],
                $attributes['used_at'],
            )
        ))->response()->setStatusCode(201);
    }

    public function statistics(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->logService->statisticsForUser($this->authenticatedUser($request)),
        ]);
    }
}
