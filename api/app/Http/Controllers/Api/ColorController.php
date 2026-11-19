<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreColorRequest;
use App\Http\Requests\UpdateColorRequest;
use App\Services\ColorService;
use App\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ColorController extends Controller
{
    public function __construct(private readonly ColorService $colorService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->colorService->listColors(),
        ]);
    }

    public function show(int $color): JsonResponse
    {
        return response()->json([
            'data' => $this->colorService->findColor($color),
        ]);
    }

    public function store(StoreColorRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->colorService->createColor($request->validated()),
        ], 201);
    }

    public function update(UpdateColorRequest $request, int $color): JsonResponse
    {
        return response()->json([
            'data' => $this->colorService->updateColor($color, $request->validated()),
        ]);
    }

    public function destroy(int $color): JsonResponse
    {
        if (! $this->colorService->deleteColor($color)) {
            return ProblemDetails::response(409, 'No se puede eliminar un color que está asociado a prendas.');
        }

        return response()->json(status: 204);
    }
}
