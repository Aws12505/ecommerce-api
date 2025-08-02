<?php
// app/Services/V1/Checkout/CheckoutService.php

namespace App\Services\V1\Checkout;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Services\V1\CartService;
use App\Services\V1\Currency\CurrencyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function validateCheckout($cart): array
    {
        if ($cart->is_empty) {
            throw ValidationException::withMessages([
                'cart' => ['Cart is empty.']
            ]);
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
        $cartService = new CartService($this->currencyService);
        $cart = $cartService->getCurrentCart();

        // Validate checkout
        $this->validateCheckout($cart);

        $order = null;
        $userCurrency = $this->currencyService->getUserCurrency();
        $exchangeRate = $this->currencyService->getExchangeRate('USD', $userCurrency); // assuming USD is base

        DB::transaction(function () use ($cart, $checkoutData, &$order, $userCurrency, $exchangeRate) {
            // Calculate totals in base currency (stored amounts)
            $subtotal = $cart->subtotal;
            $taxAmount = $cart->tax_amount;
            $shippingAmount = $checkoutData['shipping_amount'] ?? 0;
            $discountAmount = 0;

            // Calculate discount from applied coupons (in base currency)
            if ($cart->applied_coupons) {
                foreach ($cart->applied_coupons as $appliedCoupon) {
                    $discountAmount += $appliedCoupon['discount_amount'];
                }
            }

            $total = $subtotal + $taxAmount + $shippingAmount - $discountAmount;

            // Store original amounts in base currency for record keeping
            $originalAmounts = [
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'base_currency' => 'USD' // or your base currency
            ];

            // Convert amounts to user's currency for order display
            $convertedSubtotal = $this->currencyService->convertPrice($subtotal, 'USD', $userCurrency);
            $convertedTaxAmount = $this->currencyService->convertPrice($taxAmount, 'USD', $userCurrency);
            $convertedShippingAmount = $this->currencyService->convertPrice($shippingAmount, 'USD', $userCurrency);
            $convertedDiscountAmount = $this->currencyService->convertPrice($discountAmount, 'USD', $userCurrency);
            $convertedTotal = $this->currencyService->convertPrice($total, 'USD', $userCurrency);

            // Create order with converted amounts
            $order = Order::create([
                'user_id' => Auth::id(),
                'subtotal' => $convertedSubtotal,
                'tax_amount' => $convertedTaxAmount,
                'shipping_amount' => $convertedShippingAmount,
                'discount_amount' => $convertedDiscountAmount,
                'total' => $convertedTotal,
                'currency' => $userCurrency,
                'exchange_rate' => $exchangeRate,
                'original_amounts' => $originalAmounts,
                'billing_address' => $checkoutData['billing_address'],
                'shipping_address' => $checkoutData['shipping_address'] ?? $checkoutData['billing_address'],
                'applied_coupons' => $cart->applied_coupons,
                'notes' => $checkoutData['notes'] ?? null,
            ]);

            // Create order items with converted prices
            foreach ($cart->items as $cartItem) {
                $convertedPrice = $this->currencyService->convertPrice($cartItem->price, 'USD', $userCurrency);
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'product_sku' => $cartItem->product->sku,
                    'quantity' => $cartItem->quantity,
                    'price' => $convertedPrice, // Store in user's currency
                    'product_options' => $cartItem->product_options,
                    'product_snapshot' => $cartItem->product->toArray(),
                ]);
            }

            // Record coupon usage (with base currency amounts)
            if ($cart->applied_coupons) {
                foreach ($cart->applied_coupons as $appliedCoupon) {
                    $coupon = Coupon::find($appliedCoupon['id']);
                    if ($coupon) {
                        CouponUsage::create([
                            'coupon_id' => $coupon->id,
                            'user_id' => Auth::id(),
                            'order_id' => $order->id,
                            'discount_amount' => $appliedCoupon['discount_amount'], // Store in base currency
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

        // Create line items for the checkout session (using order currency)
        $lineItems = $order->items->map(function ($item) use ($order) {
            return [
                'price_data' => [
                    'currency' => strtolower($order->currency),
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
                    'currency' => strtolower($order->currency),
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
                    'currency' => strtolower($order->currency),
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
                ]
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
