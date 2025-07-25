<?php
// FILE: app/Http/Controllers/Api/V1/FavoritesController.php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\V1\User\UserProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FavoritesController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserProfileService $userProfileService) {}

    public function index(): JsonResponse
    {
        try {
            $result = $this->userProfileService->getUserFavorites(Auth::user());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve favorites', 500, $e->getMessage());
        }
    }

    public function store(Product $product): JsonResponse
    {
        try {
            $result = $this->userProfileService->addToFavorites(Auth::user(), $product);
            return $this->successResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add to favorites', 422, $e->getMessage());
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            $result = $this->userProfileService->removeFromFavorites(Auth::user(), $product);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to remove from favorites', 422, $e->getMessage());
        }
    }

    public function check(Product $product): JsonResponse
    {
        try {
            $isFavorite = Auth::user()->isFavorite($product);
            return $this->successResponse(['is_favorite' => $isFavorite], 'Favorite status retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check favorite status', 500, $e->getMessage());
        }
    }
}
