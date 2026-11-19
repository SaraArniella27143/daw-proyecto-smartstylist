<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreStyleRequest;
use App\Http\Requests\UpdateStyleRequest;
use App\Services\StyleService;
use App\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class StyleController extends Controller
{
    public function __construct(private readonly StyleService $styleService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->styleService->listStyles(),
        ]);
    }

    public function show(int $style): JsonResponse
    {
        return response()->json([
            'data' => $this->styleService->findStyle($style),
        ]);
    }

    public function store(StoreStyleRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->styleService->createStyle($request->validated()),
        ], 201);
    }

    public function update(UpdateStyleRequest $request, int $style): JsonResponse
    {
        return response()->json([
            'data' => $this->styleService->updateStyle($style, $request->validated()),
        ]);
    }

    public function destroy(int $style): JsonResponse
    {
        if (! $this->styleService->deleteStyle($style)) {
            return ProblemDetails::response(409, 'No se puede eliminar un estilo que está asociado a prendas.');
        }

        return response()->json(status: 204);
    }
}