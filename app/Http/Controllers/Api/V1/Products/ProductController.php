<?php
// FILE: app/Http/Controllers/Api/V1/ProductController.php

namespace App\Http\Controllers\Api\V1\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Products\CreateProductRequest;
use App\Http\Requests\V1\Products\UpdateProductRequest;
use App\Models\Product;
use App\Services\V1\Products\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(protected ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->productService->getAllProducts($request);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve products', 500, $e->getMessage());
        }
    }

    public function show(string $slug): JsonResponse
    {
        try {
            $result = $this->productService->getProduct($slug);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Product not found', 404, $e->getMessage());
        }
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        
        try {
            $result = $this->productService->createProduct($request->validated());
            return $this->successResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create product', 422, $e->getMessage());
        }
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        try {
            $result = $this->productService->updateProduct($product, $request->validated());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product', 422, $e->getMessage());
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            $result = $this->productService->deleteProduct($product);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product', 500, $e->getMessage());
        }
    }
}
