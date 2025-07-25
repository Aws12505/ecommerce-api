<?php
// FILE: app/Services/V1/OrderService.php

namespace App\Services\V1\Checkout;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\V1\CartService;

class OrderService
{
    public function getUserOrders(Request $request): array
    {
        $user = Auth::user();
        
        $query = Order::with(['items.product', 'user'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Date range filter
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by order number
        if ($request->has('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $perPage = min($request->get('per_page', 15), 50);
        $orders = $query->paginate($perPage);

        return [
            'data' => $orders,
            'message' => 'Orders retrieved successfully'
        ];
    }

    public function getOrder(Order $order): array
    {
        // Ensure user can only see their own orders
        if ($order->user_id !== Auth::id()) {
            throw ValidationException::withMessages([
                'order' => ['Order not found.']
            ]);
        }

        $order->load(['items.product', 'user', 'couponUsage.coupon']);

        return [
            'data' => $order,
            'message' => 'Order retrieved successfully'
        ];
    }

    public function cancelOrder(Order $order): array
    {
        // Ensure user can only cancel their own orders
        if ($order->user_id !== Auth::id()) {
            throw ValidationException::withMessages([
                'order' => ['Order not found.']
            ]);
        }

        if (!$order->canBeCancelled()) {
            throw ValidationException::withMessages([
                'order' => ['This order cannot be cancelled.']
            ]);
        }

        DB::transaction(function () use ($order) {
            $previousStatus = $order->status;

            // Restore stock for all items
            foreach ($order->items as $item) {
                $item->product->increaseStock($item->quantity);
            }

            // Update order status
            $order->update([
                'status' => 'cancelled'
            ]);

            // Send notification
            $order->user->notify(new OrderStatusUpdated($order, $previousStatus));
        });

        return [
            'data' => $order->fresh(),
            'message' => 'Order cancelled successfully'
        ];
    }

    public function updateOrderStatus(Order $order, string $newStatus, array $additionalData = []): array
    {
        if (!in_array($newStatus, ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])) {
            throw ValidationException::withMessages([
                'status' => ['Invalid order status.']
            ]);
        }

        DB::transaction(function () use ($order, $newStatus, $additionalData) {
            $previousStatus = $order->status;

            $updateData = ['status' => $newStatus];

            // Set timestamps for specific statuses
            switch ($newStatus) {
                case 'shipped':
                    $updateData['shipped_at'] = now();
                    break;
                case 'delivered':
                    $updateData['delivered_at'] = now();
                    break;
            }

            // Merge additional data
            $updateData = array_merge($updateData, $additionalData);

            $order->update($updateData);

            // Send notification
            $order->user->notify(new OrderStatusUpdated($order, $previousStatus));
        });

        return [
            'data' => $order->fresh(),
            'message' => 'Order status updated successfully'
        ];
    }

    public function getOrderStatistics(User $user = null): array
    {
        $user = $user ?? Auth::user();

        $query = Order::where('user_id', $user->id);

        $statistics = [
            'total_orders' => $query->count(),
            'completed_orders' => $query->where('status', 'delivered')->count(),
            'pending_orders' => $query->where('status', 'pending')->count(),
            'cancelled_orders' => $query->where('status', 'cancelled')->count(),
            'total_spent' => $query->where('payment_status', 'paid')->sum('total'),
            'average_order_value' => $query->where('payment_status', 'paid')->avg('total'),
            'recent_orders' => $query->with(['items.product'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return [
            'data' => $statistics,
            'message' => 'Order statistics retrieved successfully'
        ];
    }

    public function reorderOrder(Order $order): array
    {
        // Ensure user can only reorder their own orders
        if ($order->user_id !== Auth::id()) {
            throw ValidationException::withMessages([
                'order' => ['Order not found.']
            ]);
        }

        $cartService = new CartService();
        $cart = $cartService->getCurrentCart();

        DB::transaction(function () use ($order, $cart) {
            foreach ($order->items as $item) {
                // Check if product is still available
                if ($item->product && $item->product->canPurchase($item->quantity)) {
                    $cart->addItem($item->product, $item->quantity, $item->product_options ?? []);
                }
            }
        });

        return [
            'data' => $cart->load(['items.product']),
            'message' => 'Items added to cart for reorder'
        ];
    }

    public function getOrderInvoice(Order $order): array
    {
        // Ensure user can only get their own order invoices
        if ($order->user_id !== Auth::id()) {
            throw ValidationException::withMessages([
                'order' => ['Order not found.']
            ]);
        }

        $invoice = [
            'order' => $order->load(['items.product', 'user']),
            'invoice_number' => 'INV-' . $order->order_number,
            'invoice_date' => $order->created_at->format('Y-m-d'),
            'due_date' => $order->created_at->addDays(30)->format('Y-m-d'),
            'company_info' => [
                'name' => config('app.name'),
                'address' => config('app.company_address'),
                'phone' => config('app.company_phone'),
                'email' => config('app.company_email'),
            ],
        ];

        return [
            'data' => $invoice,
            'message' => 'Invoice retrieved successfully'
        ];
    }
}
