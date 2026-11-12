<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Services\CategoryService;
use App\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->categoryService->listCategories(),
        ]);
    }

    public function show(int $category): JsonResponse
    {
        return response()->json([
            'data' => $this->categoryService->findCategory($category),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->categoryService->createCategory($request->validated()),
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, int $category): JsonResponse
    {
        return response()->json([
            'data' => $this->categoryService->updateCategory($category, $request->validated()),
        ]);
    }

    public function destroy(int $category): JsonResponse
    {
        if (! $this->categoryService->deleteCategory($category)) {
            return ProblemDetails::response(409, 'No se puede eliminar una categoría que está asociada a prendas.');
        }

        return response()->json(status: 204);
    }
}
