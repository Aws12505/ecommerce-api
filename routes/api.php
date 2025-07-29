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
use App\Http\Controllers\Api\V1\User\UserProfileController;
use App\Http\Controllers\Api\V1\User\FavoritesController;
use App\Http\Controllers\Api\V1\User\UserAddressController;
use App\Http\Controllers\Api\V1\User\NotificationSettingsController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ThemeController;
use App\Http\Controllers\Api\V1\GlobalAlertController;

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
    Route::get('global-alert', [GlobalAlertController::class, 'showActive']);

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
Route::get('themes', [ThemeController::class, 'index']);
    Route::get('themes/active', [ThemeController::class, 'active']);
    Route::get('themes/{theme}', [ThemeController::class, 'show']);
    /*
    |--------------------------------------------------------------------------
    | Protected Routes (auth:sanctum)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        
        // User Profile routes
        Route::prefix('profile')->group(function () {
            Route::get('/', [UserProfileController::class, 'show']);
            Route::post('update', [UserProfileController::class, 'update']);
            Route::post('avatar', [UserProfileController::class, 'uploadAvatar']);
            Route::delete('avatar', [UserProfileController::class, 'deleteAvatar']);
        });

        // Favorites routes
        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoritesController::class, 'index']);
            Route::post('products/{product}', [FavoritesController::class, 'store']);
            Route::delete('products/{product}', [FavoritesController::class, 'destroy']);
            Route::get('products/{product}/check', [FavoritesController::class, 'check']);
        });

        // User Addresses routes
        Route::prefix('addresses')->group(function () {
            Route::get('/', [UserAddressController::class, 'index']);
            Route::post('/', [UserAddressController::class, 'store']);
            Route::get('{address}', [UserAddressController::class, 'show']);
            Route::post('{address}/update', [UserAddressController::class, 'update']);
            Route::delete('{address}', [UserAddressController::class, 'destroy']);
            Route::post('{address}/set-default', [UserAddressController::class, 'setDefault']);
        });

        // Notification Settings routes
        Route::prefix('notification-settings')->group(function () {
            Route::get('/', [NotificationSettingsController::class, 'index']);
            Route::post('update', [NotificationSettingsController::class, 'update']);
        });

        
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

        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('statistics', [OrderController::class, 'statistics']);
            Route::get('{order}', [OrderController::class, 'show']);
            Route::post('{order}/cancel', [OrderController::class, 'cancel']);
            Route::post('{order}/reorder', [OrderController::class, 'reorder']);
            Route::get('{order}/invoice', [OrderController::class, 'invoice']);
        });

        // Notification routes
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('unread', [NotificationController::class, 'unread']);
            Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
            Route::delete('{id}', [NotificationController::class, 'delete']);
            Route::delete('/', [NotificationController::class, 'deleteAll']);
        });

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

            Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus']);

            Route::post('themes', [ThemeController::class, 'store']);
            Route::put('themes/{theme}', [ThemeController::class, 'update']);
            Route::delete('themes/{theme}', [ThemeController::class, 'destroy']);

            Route::prefix('global-alerts')->group(function () {
        Route::get('/', [GlobalAlertController::class, 'index']);
        Route::post('/', [GlobalAlertController::class, 'store']);
        Route::put('{globalAlert}', [GlobalAlertController::class, 'update']);
        Route::delete('{globalAlert}', [GlobalAlertController::class, 'destroy']);
    });
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook
    |--------------------------------------------------------------------------
    */
    Route::post('webhooks/stripe', [CheckoutController::class, 'webhook']);
});
