<?php
// FILE: app/Services/V1/CouponService.php

namespace App\Services\V1\Checkout;

use App\Models\Coupon;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Services\V1\CartService;
class CouponService
{
    public function getAllCoupons(Request $request): array
    {
        $query = Coupon::query();

        if ($request->has('active')) {
            $query->active();
        }

        if ($request->has('valid')) {
            $query->valid();
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = min($request->get('per_page', 15), 50);
        $coupons = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return [
            'data' => $coupons,
            'message' => 'Coupons retrieved successfully'
        ];
    }

    public function getCoupon(string $code): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->firstOrFail();

        return [
            'data' => $coupon,
            'message' => 'Coupon retrieved successfully'
        ];
    }

    public function createCoupon(array $data): array
    {
        $coupon = Coupon::create($data);

        return [
            'data' => $coupon,
            'message' => 'Coupon created successfully'
        ];
    }

    public function updateCoupon(Coupon $coupon, array $data): array
    {
        $coupon->update($data);

        return [
            'data' => $coupon,
            'message' => 'Coupon updated successfully'
        ];
    }

    public function deleteCoupon(Coupon $coupon): array
    {
        $coupon->delete();

        return [
            'data' => null,
            'message' => 'Coupon deleted successfully'
        ];
    }

    public function validateCoupon(string $code, Cart $cart): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            throw ValidationException::withMessages([
                'code' => ['Invalid coupon code.']
            ]);
        }

        $user = Auth::user();

        if (!$coupon->canBeUsedBy($user)) {
            throw ValidationException::withMessages([
                'code' => ['This coupon cannot be used.']
            ]);
        }

        $discount = $coupon->calculateDiscount($cart->subtotal);

        if ($discount <= 0) {
            throw ValidationException::withMessages([
                'code' => ['This coupon is not applicable to your cart.']
            ]);
        }

        return [
            'data' => [
                'coupon' => $coupon,
                'discount_amount' => $discount,
                'new_total' => max(0, $cart->total - $discount)
            ],
            'message' => 'Coupon is valid'
        ];
    }

    public function applyCouponToCart(string $code): array
    {
        $cartService = new CartService();
        $cart = $cartService->getCurrentCart();

        $validation = $this->validateCoupon($code, $cart);
        $coupon = $validation['data']['coupon'];
        $discountAmount = $validation['data']['discount_amount'];

        // Add coupon to cart's applied coupons
        $appliedCoupons = $cart->applied_coupons ?? [];
        
        // Check if coupon already applied
        if (collect($appliedCoupons)->contains('code', $coupon->code)) {
            throw ValidationException::withMessages([
                'code' => ['This coupon is already applied.']
            ]);
        }

        $appliedCoupons[] = [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'discount_amount' => $discountAmount
        ];

        // Recalculate cart totals with discount
        $newTotal = max(0, $cart->total - $discountAmount);

        $cart->update([
            'applied_coupons' => $appliedCoupons,
            'total' => $newTotal
        ]);

        return [
            'data' => [
                'cart' => $cart->load(['items.product']),
                'applied_coupon' => $coupon,
                'discount_amount' => $discountAmount
            ],
            'message' => 'Coupon applied successfully'
        ];
    }

    public function removeCouponFromCart(string $code): array
    {
        $cartService = new CartService();
        $cart = $cartService->getCurrentCart();

        $appliedCoupons = $cart->applied_coupons ?? [];
        $appliedCoupons = collect($appliedCoupons)->reject(function ($coupon) use ($code) {
            return $coupon['code'] === strtoupper($code);
        })->values()->toArray();

        $cart->update(['applied_coupons' => $appliedCoupons]);
        $cart->calculateTotals();

        return [
            'data' => $cart->load(['items.product']),
            'message' => 'Coupon removed successfully'
        ];
    }
}
