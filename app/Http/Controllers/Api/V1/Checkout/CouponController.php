<?php
// FILE: app/Http/Controllers/Api/V1/CouponController.php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Checkout\CreateCouponRequest;
use App\Http\Requests\V1\Checkout\UpdateCouponRequest;
use App\Http\Requests\V1\Checkout\ApplyCouponRequest;
use App\Models\Coupon;
use App\Services\V1\Checkout\CouponService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse;

    public function __construct(protected CouponService $couponService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->couponService->getAllCoupons($request);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve coupons', 500, $e->getMessage());
        }
    }

    public function show(string $code): JsonResponse
    {
        try {
            $result = $this->couponService->getCoupon($code);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Coupon not found', 404, $e->getMessage());
        }
    }

    public function store(CreateCouponRequest $request): JsonResponse
    {
        try {
            $result = $this->couponService->createCoupon($request->validated());
            return $this->successResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create coupon', 422, $e->getMessage());
        }
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        try {
            $result = $this->couponService->updateCoupon($coupon, $request->validated());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update coupon', 422, $e->getMessage());
        }
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        try {
            $result = $this->couponService->deleteCoupon($coupon);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete coupon', 500, $e->getMessage());
        }
    }

    public function apply(ApplyCouponRequest $request): JsonResponse
    {
        try {
            $result = $this->couponService->applyCouponToCart($request->code);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to apply coupon', 422, $e->getMessage());
        }
    }

    public function remove(Request $request): JsonResponse
    {
        try {
            $result = $this->couponService->removeCouponFromCart($request->code);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to remove coupon', 422, $e->getMessage());
        }
    }
}
