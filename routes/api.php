<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\VerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Products\ProductController;
use App\Http\Controllers\Api\V1\Products\CategoryController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\Checkout\CouponController;
use App\Http\Controllers\Api\V1\Checkout\CheckoutController;
use App\Http\Controllers\Api\V1\Checkout\OrderController;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Product & Category Routes
    |--------------------------------------------------------------------------
    */
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{slug}', [ProductController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes (Prefix: /api/v1/auth)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {

        // Public Auth Routes
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

        // Password Reset
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink']);
        Route::post('reset-password', [PasswordResetController::class, 'reset']);

        // Email Verification
        Route::post('resend-verification', [VerificationController::class, 'resend'])->middleware('throttle:3,1');
        Route::get('verify-email/{id}/{hash}', [VerificationController::class, 'verify'])
            ->middleware(['throttle:6,1'])
            ->name('verification.verify');

        // Protected Auth Routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-everywhere', [AuthController::class, 'logoutEverywhere']);
            Route::post('refresh-token', [AuthController::class, 'refreshToken']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (auth:sanctum)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // Cart Routes
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart', [CartController::class, 'store']);
        Route::put('cart/items/{cartItemId}', [CartController::class, 'update']);
        Route::delete('cart/items/{cartItemId}', [CartController::class, 'destroy']);
        Route::delete('cart/clear', [CartController::class, 'clear']);

        // Coupon Routes
        Route::post('coupons/apply', [CouponController::class, 'apply']);
        Route::post('coupons/remove', [CouponController::class, 'remove']);
        Route::get('coupons/{code}', [CouponController::class, 'show']);

        // Checkout Routes
        Route::post('checkout/validate', [CheckoutController::class, 'validate']);
        Route::post('checkout/process', [CheckoutController::class, 'process']);
        Route::post('orders/{order}/payment-session', [CheckoutController::class, 'createPaymentSession']);

        // Order Routes
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

        /*
        |--------------------------------------------------------------------------
        | Admin Routes (Requires role:admin)
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:admin')->group(function () {

            // Product Management
            Route::post('products', [ProductController::class, 'store']);
            Route::post('products/{product}', [ProductController::class, 'update']);
            Route::delete('products/{product}', [ProductController::class, 'destroy']);

            // Category Management
            Route::post('categories', [CategoryController::class, 'store']);
            Route::post('categories/{category}', [CategoryController::class, 'update']);
            Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

            // Coupon Management
            Route::get('coupons', [CouponController::class, 'index']);
            Route::post('coupons', [CouponController::class, 'store']);
            Route::post('coupons/{coupon}/update', [CouponController::class, 'update']);
            Route::delete('coupons/{coupon}', [CouponController::class, 'destroy']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook
    |--------------------------------------------------------------------------
    */
    Route::post('webhooks/stripe', [CheckoutController::class, 'webhook']);
});
