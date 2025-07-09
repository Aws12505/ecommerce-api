<?php
// FILE: app/Http/Controllers/Api/V1/CheckoutController.php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Checkout\CheckoutRequest;
use App\Models\Order;
use App\Services\V1\Checkout\CheckoutService;
use App\Services\V1\CartService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(protected CheckoutService $checkoutService) {}

    public function validate(): JsonResponse
    {
        try {
            $cartService = new CartService();
            $cart = $cartService->getCurrentCart();
            
            $result = $this->checkoutService->validateCheckout($cart);
            return $this->successResponse($result, 'Checkout validation passed');
        } catch (\Exception $e) {
            return $this->errorResponse('Checkout validation failed', 422, $e->getMessage());
        }
    }

    public function process(CheckoutRequest $request): JsonResponse
    {
        try {
            $result = $this->checkoutService->createOrder($request->validated());
            return $this->successResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Checkout failed', 422, $e->getMessage());
        }
    }

    public function createPaymentSession(Request $request, Order $order): JsonResponse
    {
        try {
            $options = [
                'success_url' => $request->success_url,
                'cancel_url' => $request->cancel_url,
            ];
            
            $result = $this->checkoutService->createStripeSession($order, $options);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create payment session', 422, $e->getMessage());
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $result = $this->checkoutService->handleStripeWebhook($payload);
            return $this->successResponse($result['data'] ?? null, $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Webhook processing failed', 422, $e->getMessage());
        }
    }
}
