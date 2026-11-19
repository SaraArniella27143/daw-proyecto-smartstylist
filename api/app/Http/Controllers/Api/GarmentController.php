<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreGarmentRequest;
use App\Http\Requests\UpdateGarmentRequest;
use App\Services\GarmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GarmentController extends ApiController
{
    public function __construct(private readonly GarmentService $garmentService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->garmentService->listForUser($this->authenticatedUser($request)),
        ]);
    }

    public function show(Request $request, int $garment): JsonResponse
    {
        return response()->json([
            'data' => $this->garmentService->findForUser(
                $this->authenticatedUser($request),
                $garment,
            ),
        ]);
    }

    public function image(Request $request, int $garment): StreamedResponse
    {
        return $this->garmentService->imageForUser(
            $this->authenticatedUser($request),
            $garment,
        );
    }

    public function store(StoreGarmentRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->garmentService->createForUser(
                $this->authenticatedUser($request),
                $request->validated(),
            ),
        ], 201);
    }

    public function update(UpdateGarmentRequest $request, int $garment): JsonResponse
    {
        return response()->json([
            'data' => $this->garmentService->updateForUser(
                $this->authenticatedUser($request),
                $garment,
                $request->validated(),
            ),
        ]);
    }

    public function destroy(Request $request, int $garment): JsonResponse
    {
        $this->garmentService->deleteForUser($this->authenticatedUser($request), $garment);

        return response()->json(status: 204);
    }
}
