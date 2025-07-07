<?php
// FILE: app/Http/Controllers/Api/V1/CartController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Cart\AddToCartRequest;
use App\Http\Requests\V1\Cart\UpdateCartItemRequest;
use App\Services\V1\CartService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(protected CartService $cartService) {}

    public function index(): JsonResponse
    {
        try {
            $result = $this->cartService->getCart();
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve cart', 500, $e->getMessage());
        }
    }

    public function store(AddToCartRequest $request): JsonResponse
    {
        try {
            $result = $this->cartService->addToCart($request->validated());
            return $this->successResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add item to cart', 422, $e->getMessage());
        }
    }

    public function update(UpdateCartItemRequest $request, int $cartItemId): JsonResponse
    {
        try {
            $result = $this->cartService->updateCartItem($cartItemId, $request->validated());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update cart item', 422, $e->getMessage());
        }
    }

    public function destroy(int $cartItemId): JsonResponse
    {
        try {
            $result = $this->cartService->removeFromCart($cartItemId);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to remove item from cart', 422, $e->getMessage());
        }
    }

    public function clear(): JsonResponse
    {
        try {
            $result = $this->cartService->clearCart();
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to clear cart', 500, $e->getMessage());
        }
    }
}
