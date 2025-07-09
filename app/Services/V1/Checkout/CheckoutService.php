<?php
// FILE: app/Services/V1/CheckoutService.php

namespace App\Services\V1\Checkout;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Cashier;
use App\Services\V1\CartService;

class CheckoutService
{
    public function validateCheckout(Cart $cart): array
    {
        if ($cart->is_empty) {
            throw ValidationException::withMessages([
                'cart' => ['Cart is empty.']
            ]);
        }

        // Validate stock availability
        foreach ($cart->items as $item) {
            if (!$item->product->canPurchase($item->quantity)) {
                throw ValidationException::withMessages([
                    'stock' => ["Product '{$item->product->name}' is not available in requested quantity."]
                ]);
            }
        }

        // Validate applied coupons
        if ($cart->applied_coupons) {
            foreach ($cart->applied_coupons as $appliedCoupon) {
                $coupon = Coupon::find($appliedCoupon['id']);
                if (!$coupon || !$coupon->canBeUsedBy(Auth::user())) {
                    throw ValidationException::withMessages([
                        'coupon' => ["Coupon '{$appliedCoupon['code']}' is no longer valid."]
                    ]);
                }
            }
        }

        return [
            'valid' => true,
            'message' => 'Checkout validation passed'
        ];
    }

    public function createOrder(array $checkoutData): array
    {
        $cartService = new CartService();
        $cart = $cartService->getCurrentCart();

        // Validate checkout
        $this->validateCheckout($cart);

        $order = null;

        DB::transaction(function () use ($cart, $checkoutData, &$order) {
            // Calculate totals
            $subtotal = $cart->subtotal;
            $taxAmount = $cart->tax_amount;
            $shippingAmount = $checkoutData['shipping_amount'] ?? 0;
            $discountAmount = 0;

            // Calculate discount from applied coupons
            if ($cart->applied_coupons) {
                foreach ($cart->applied_coupons as $appliedCoupon) {
                    $discountAmount += $appliedCoupon['discount_amount'];
                }
            }

            $total = $subtotal + $taxAmount + $shippingAmount - $discountAmount;

            // Create order
            $order = Order::create([
                'user_id' => Auth::id(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'billing_address' => $checkoutData['billing_address'],
                'shipping_address' => $checkoutData['shipping_address'] ?? $checkoutData['billing_address'],
                'applied_coupons' => $cart->applied_coupons,
                'notes' => $checkoutData['notes'] ?? null,
            ]);

            // Create order items
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'product_sku' => $cartItem->product->sku,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'product_options' => $cartItem->product_options,
                    'product_snapshot' => $cartItem->product->toArray(),
                ]);
            }

            // Record coupon usage
            if ($cart->applied_coupons) {
                foreach ($cart->applied_coupons as $appliedCoupon) {
                    $coupon = Coupon::find($appliedCoupon['id']);
                    if ($coupon) {
                        CouponUsage::create([
                            'coupon_id' => $coupon->id,
                            'user_id' => Auth::id(),
                            'order_id' => $order->id,
                            'discount_amount' => $appliedCoupon['discount_amount'],
                        ]);
                        $coupon->incrementUsage();
                    }
                }
            }

            // Clear cart
            $cart->clear();
        });

        return [
            'data' => $order->load(['items', 'user']),
            'message' => 'Order created successfully'
        ];
    }

    public function createStripeSession(Order $order, array $options = []): array
    {
        $user = $order->user;

        // Create line items for the checkout session
        $lineItems = $order->items->map(function ($item) {
            return [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->product_name,
                        'description' => $item->product_sku,
                    ],
                    'unit_amount' => $item->price * 100, // Stripe uses cents
                ],
                'quantity' => $item->quantity,
            ];
        })->toArray();

        // Add shipping as line item if applicable
        if ($order->shipping_amount > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Shipping',
                    ],
                    'unit_amount' => $order->shipping_amount * 100,
                ],
                'quantity' => 1,
            ];
        }

        // Add tax as line item if applicable
        if ($order->tax_amount > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Tax',
                    ],
                    'unit_amount' => $order->tax_amount * 100,
                ],
                'quantity' => 1,
            ];
        }

        // Create Stripe checkout session using Laravel Cashier
        $checkoutSession = $user->checkout(
            $lineItems,
            [
                'success_url' => $options['success_url'] ?? config('app.frontend_url') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $options['cancel_url'] ?? config('app.frontend_url') . '/checkout/cancel',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
                'customer_email' => $user->email,
            ]
        );

        // Update order with Stripe session ID
        $order->update([
            'stripe_session_id' => $checkoutSession->id,
        ]);

        return [
            'data' => [
                'session_id' => $checkoutSession->id,
                'session_url' => $checkoutSession->url,
                'order' => $order,
            ],
            'message' => 'Stripe session created successfully'
        ];
    }

    public function handleStripeWebhook(array $payload): array
    {
        $event = $payload['type'];
        $sessionData = $payload['data']['object'];

        switch ($event) {
            case 'checkout.session.completed':
                return $this->handlePaymentSuccess($sessionData);
            
            case 'checkout.session.expired':
                return $this->handlePaymentExpired($sessionData);
            
            default:
                return ['message' => 'Unhandled event type'];
        }
    }

    private function handlePaymentSuccess(array $sessionData): array
    {
        $order = Order::where('stripe_session_id', $sessionData['id'])->first();

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'stripe_payment_intent_id' => $sessionData['payment_intent'] ?? null,
                'payment_method' => 'stripe',
            ]);

            // Send order confirmation email here
            // Mail::to($order->user)->send(new OrderConfirmation($order));
        }

        return [
            'data' => $order,
            'message' => 'Payment processed successfully'
        ];
    }

    private function handlePaymentExpired(array $sessionData): array
    {
        $order = Order::where('stripe_session_id', $sessionData['id'])->first();

        if ($order && $order->payment_status === 'pending') {
            // Restore stock for expired orders
            foreach ($order->items as $item) {
                $item->product->increaseStock($item->quantity);
            }

            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]);
        }

        return [
            'data' => $order,
            'message' => 'Payment session expired'
        ];
    }
}
