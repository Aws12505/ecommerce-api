<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\VerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;

// Products & Categories
use App\Http\Controllers\Api\V1\Products\ProductController;
use App\Http\Controllers\Api\V1\Products\CategoryController;
use App\Http\Controllers\Api\V1\Products\LineController;

// Cart & Checkout
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\Checkout\CouponController;
use App\Http\Controllers\Api\V1\Checkout\CheckoutController;
use App\Http\Controllers\Api\V1\Checkout\OrderController;

// User
use App\Http\Controllers\Api\V1\User\UserProfileController;
use App\Http\Controllers\Api\V1\User\FavoritesController;
use App\Http\Controllers\Api\V1\User\UserAddressController;
use App\Http\Controllers\Api\V1\User\NotificationSettingsController;

// Other
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ThemeController;
use App\Http\Controllers\Api\V1\GlobalAlertController;
use App\Http\Controllers\Api\V1\SliderController;
use App\Http\Controllers\Api\V1\Legal\LegalDocumentController;
use App\Http\Controllers\Api\V1\SplashScreenController;
use App\Http\Controllers\Api\V1\Currency\CurrencyController;
use App\Http\Controllers\Api\V1\Currency\CurrencyRateController;


Route::prefix('v1')->group(function () {

    /**
     * Public Routes
     */
    Route::middleware('optionalSanctumAuth')->group(function(){
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{slug}', [ProductController::class, 'show']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);

    Route::get('lines', [LineController::class, 'index']);
    Route::get('lines/{slug}', [LineController::class, 'show']);

    Route::get('global-alert', [GlobalAlertController::class, 'showActive']);
    Route::get('themes', [ThemeController::class, 'index']);
    Route::get('themes/active', [ThemeController::class, 'active']);
    Route::get('themes/{theme}', [ThemeController::class, 'show']);
    Route::get('active-sliders', [SliderController::class, 'index']);

    Route::get('legal/terms-of-service', [LegalDocumentController::class, 'getTermsOfService']);
    Route::get('legal/privacy-policy', [LegalDocumentController::class, 'getPrivacyPolicy']);
    Route::get('legal/{type}', [LegalDocumentController::class, 'show']);

    Route::get('splash-screens/active', [SplashScreenController::class, 'getActive']);

    Route::get('currencies', [CurrencyController::class, 'index']);
    Route::get('currencies/{code}', [CurrencyController::class, 'show']);
    });
    /**
     * Authentication Routes
     */
    Route::prefix('auth')->group(function () {
        // Public
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink']);
        Route::post('reset-password', [PasswordResetController::class, 'reset']);

        Route::post('resend-verification', [VerificationController::class, 'resend'])->middleware('throttle:3,1');
        Route::get('verify-email/{id}/{hash}', [VerificationController::class, 'verify'])
            ->middleware('throttle:6,1')
            ->name('verification.verify');

        // Protected
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-everywhere', [AuthController::class, 'logoutEverywhere']);
            Route::post('refresh-token', [AuthController::class, 'refreshToken']);
            Route::get('profile/currency', [UserProfileController::class, 'getCurrencyPreference']);
            Route::post('profile/currency', [UserProfileController::class, 'updateCurrency']);
        });
    });

    /**
     * Protected Routes
     */
    Route::middleware('auth:sanctum')->group(function () {

        /**
         * User Profile
         */
        Route::prefix('profile')->group(function () {
            Route::get('/', [UserProfileController::class, 'show']);
            Route::post('update', [UserProfileController::class, 'update']);
            Route::post('avatar', [UserProfileController::class, 'uploadAvatar']);
            Route::delete('avatar', [UserProfileController::class, 'deleteAvatar']);
            Route::get('currency',[UserProfileController::class,'getCurrencyPreference']);
            Route::post('currency',[UserProfileController::class,'updateCurrency']);
        });

        /**
         * Favorites
         */
        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoritesController::class, 'index']);
            Route::post('products/{product}', [FavoritesController::class, 'store']);
            Route::delete('products/{product}', [FavoritesController::class, 'destroy']);
            Route::get('products/{product}/check', [FavoritesController::class, 'check']);
        });

        /**
         * User Addresses
         */
        Route::prefix('addresses')->group(function () {
            Route::get('/', [UserAddressController::class, 'index']);
            Route::post('/', [UserAddressController::class, 'store']);
            Route::get('{address}', [UserAddressController::class, 'show']);
            Route::post('{address}/update', [UserAddressController::class, 'update']);
            Route::delete('{address}', [UserAddressController::class, 'destroy']);
            Route::post('{address}/set-default', [UserAddressController::class, 'setDefault']);
        });

        /**
         * Notification Settings
         */
        Route::prefix('notification-settings')->group(function () {
            Route::get('/', [NotificationSettingsController::class, 'index']);
            Route::post('update', [NotificationSettingsController::class, 'update']);
        });

        /**
         * Cart
         */
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart', [CartController::class, 'store']);
        Route::put('cart/items/{cartItemId}', [CartController::class, 'update']);
        Route::delete('cart/items/{cartItemId}', [CartController::class, 'destroy']);
        Route::delete('cart/clear', [CartController::class, 'clear']);

        /**
         * Coupons
         */
        Route::post('coupons/apply', [CouponController::class, 'apply']);
        Route::post('coupons/remove', [CouponController::class, 'remove']);
        Route::get('coupons/{code}', [CouponController::class, 'show']);

        /**
         * Checkout & Orders
         */
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

        /**
         * Notifications
         */
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('unread', [NotificationController::class, 'unread']);
            Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
            Route::delete('{id}', [NotificationController::class, 'delete']);
            Route::delete('/', [NotificationController::class, 'deleteAll']);
        });

        /**
         * Admin Routes
         */
        Route::middleware('role:admin')->group(function () {

            // Products
            Route::post('products', [ProductController::class, 'store']);
            Route::post('products/{product}', [ProductController::class, 'update']);
            Route::delete('products/{product}', [ProductController::class, 'destroy']);

            // Categories
            Route::post('categories', [CategoryController::class, 'store']);
            Route::post('categories/{category}', [CategoryController::class, 'update']);
            Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

            // Coupons
            Route::get('coupons', [CouponController::class, 'index']);
            Route::post('coupons', [CouponController::class, 'store']);
            Route::post('coupons/{coupon}/update', [CouponController::class, 'update']);
            Route::delete('coupons/{coupon}', [CouponController::class, 'destroy']);

            // Orders
            Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus']);

            // Themes
            Route::post('themes', [ThemeController::class, 'store']);
            Route::put('themes/{theme}', [ThemeController::class, 'update']);
            Route::delete('themes/{theme}', [ThemeController::class, 'destroy']);

            // Global Alerts
            Route::prefix('global-alerts')->group(function () {
                Route::get('/', [GlobalAlertController::class, 'index']);
                Route::post('/', [GlobalAlertController::class, 'store']);
                Route::put('{globalAlert}', [GlobalAlertController::class, 'update']);
                Route::delete('{globalAlert}', [GlobalAlertController::class, 'destroy']);
            });

            // Product Lines
            Route::post('lines', [LineController::class, 'store']);
            Route::post('lines/{line}', [LineController::class, 'update']);
            Route::delete('lines/{line}', [LineController::class, 'destroy']);
            Route::post('lines/{line}/products', [LineController::class, 'attachProduct']);
            Route::delete('lines/{line}/products/{product}', [LineController::class, 'detachProduct']);

            // Sliders
            Route::prefix('sliders')->group(function () {
                Route::get('/', [SliderController::class, 'adminIndex']);
                Route::post('/', [SliderController::class, 'store']);
                Route::post('{slider}/update', [SliderController::class, 'update']);
                Route::delete('{slider}', [SliderController::class, 'destroy']);
            });

            Route::prefix('legal')->group(function () {
            Route::get('/', [LegalDocumentController::class, 'index']);
            Route::post('/', [LegalDocumentController::class, 'store']);
            Route::post('{legalDocument}', [LegalDocumentController::class, 'update']);
            Route::delete('{legalDocument}', [LegalDocumentController::class, 'destroy']);
            Route::post('{legalDocument}/publish', [LegalDocumentController::class, 'publish']);
            Route::post('{legalDocument}/unpublish', [LegalDocumentController::class, 'unpublish']);
        });

        Route::prefix('currencies')->group(function () {
        Route::post('/', [CurrencyController::class, 'store']);
        Route::post('{currency}', [CurrencyController::class, 'update']);
        Route::delete('{currency}', [CurrencyController::class, 'destroy']);
        Route::post('{currency}/activate', [CurrencyController::class, 'activate']);
        Route::post('{currency}/deactivate', [CurrencyController::class, 'deactivate']);
        Route::post('{currency}/set-default', [CurrencyController::class, 'setDefault']);
        Route::post('rates/recalculate', [CurrencyController::class, 'recalculateRates']);
    });

        Route::prefix('splash-screens')->group(function () {
        Route::get('/', [SplashScreenController::class, 'index']);
        Route::post('/', [SplashScreenController::class, 'store']);
        Route::post('{splashScreen}', [SplashScreenController::class, 'update']);
        Route::delete('{splashScreen}', [SplashScreenController::class, 'destroy']);
        Route::post('{splashScreen}/activate', [SplashScreenController::class, 'activate']);
        Route::post('{splashScreen}/deactivate', [SplashScreenController::class, 'deactivate']);
    });

    Route::prefix('currency-rates')->group(function () {
        Route::get('/', [CurrencyRateController::class, 'index']);
        Route::post('/', [CurrencyRateController::class, 'store']);
        Route::post('{rate}', [CurrencyRateController::class, 'update']);
        Route::delete('{rate}', [CurrencyRateController::class, 'destroy']);
    });
        });
    });

    /**
     * Stripe Webhook
     */
    Route::post('webhooks/stripe', [CheckoutController::class, 'webhook']);
});
