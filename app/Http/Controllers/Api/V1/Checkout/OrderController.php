<?php
// FILE: app/Http/Controllers/Api/V1/OrderController.php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Checkout\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\V1\Checkout\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->orderService->getUserOrders($request);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve orders', 500, $e->getMessage());
        }
    }

    public function show(Order $order): JsonResponse
    {
        try {
            $result = $this->orderService->getOrder($order);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Order not found', 404, $e->getMessage());
        }
    }

    public function cancel(Order $order): JsonResponse
    {
        try {
            $result = $this->orderService->cancelOrder($order);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel order', 422, $e->getMessage());
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $result = $this->orderService->getOrderStatistics();
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve order statistics', 500, $e->getMessage());
        }
    }

    public function reorder(Order $order): JsonResponse
    {
        try {
            $result = $this->orderService->reorderOrder($order);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reorder', 422, $e->getMessage());
        }
    }

    public function invoice(Order $order): JsonResponse
    {
        try {
            $result = $this->orderService->getOrderInvoice($order);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve invoice', 404, $e->getMessage());
        }
    }

    // Admin only routes
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        try {
            $result = $this->orderService->updateOrderStatus(
                $order, 
                $request->status,
                $request->only(['tracking_number', 'notes'])
            );
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update order status', 422, $e->getMessage());
        }
    }
}
