<?php
// FILE: app/Http/Controllers/Api/V1/OrderController.php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Order::with(['items.product'])
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc');

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $perPage = min($request->get('per_page', 15), 50);
            $orders = $query->paginate($perPage);

            return $this->successResponse($orders, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve orders', 500, $e->getMessage());
        }
    }

    public function show(Order $order): JsonResponse
    {
        try {
            // Ensure user can only see their own orders
            if ($order->user_id !== Auth::id()) {
                return $this->errorResponse('Order not found', 404);
            }

            $order->load(['items.product', 'user']);
            return $this->successResponse($order, 'Order retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Order not found', 404, $e->getMessage());
        }
    }

    public function cancel(Order $order): JsonResponse
    {
        try {
            if ($order->user_id !== Auth::id()) {
                return $this->errorResponse('Order not found', 404);
            }

            if (!$order->canBeCancelled()) {
                return $this->errorResponse('Order cannot be cancelled', 422);
            }

            // Restore stock
            foreach ($order->items as $item) {
                $item->product->increaseStock($item->quantity);
            }

            $order->update(['status' => 'cancelled']);

            return $this->successResponse($order, 'Order cancelled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel order', 422, $e->getMessage());
        }
    }
}
