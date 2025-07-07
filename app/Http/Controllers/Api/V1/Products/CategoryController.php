<?php
// FILE: app/Http/Controllers/Api/V1/CategoryController.php

namespace App\Http\Controllers\Api\V1\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Products\CreateCategoryRequest;
use App\Http\Requests\V1\Products\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\V1\Products\CategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(protected CategoryService $categoryService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->categoryService->getAllCategories($request);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve categories', 500, $e->getMessage());
        }
    }

    public function show(string $slug): JsonResponse
    {
        try {
            $result = $this->categoryService->getCategory($slug);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Category not found', 404, $e->getMessage());
        }
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        try {
            $result = $this->categoryService->createCategory($request->validated());
            return $this->successResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create category', 422, $e->getMessage());
        }
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        try {
            $result = $this->categoryService->updateCategory($category, $request->validated());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update category', 422, $e->getMessage());
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $result = $this->categoryService->deleteCategory($category);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete category', 500, $e->getMessage());
        }
    }
}
