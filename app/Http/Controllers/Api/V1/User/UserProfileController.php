<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UpdateProfileRequest;
use App\Http\Requests\V1\User\UploadAvatarRequest;
use App\Services\V1\User\UserProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserProfileService $userProfileService) {}

    public function show(): JsonResponse
    {
        try {
            $user = Auth::user()->load(['roles.permissions', 'preferredCurrency']);
            
            return $this->successResponse([
                'user' => $user,
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'roles' => $user->getRoleNames(),
                'avatar_url' => $user->avatar_url,
                'currency_details' => $user->getCurrencyDetails(),
            ], 'Profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve profile', 500, $e->getMessage());
        }
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $result = $this->userProfileService->updateProfile(Auth::user(), $request->validated());
            
            // Add currency details to response
            $responseData = $result['data'];
            $responseData['currency_details'] = $responseData->getCurrencyDetails();
            
            return $this->successResponse($responseData, $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update profile', 422, $e->getMessage());
        }
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        try {
            $result = $this->userProfileService->uploadAvatar(Auth::user(), $request->file('avatar'));
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload avatar', 422, $e->getMessage());
        }
    }

    public function deleteAvatar(): JsonResponse
    {
        try {
            $result = $this->userProfileService->deleteAvatar(Auth::user());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete avatar', 500, $e->getMessage());
        }
    }

    public function getCurrencyPreference(): JsonResponse
    {
        try {
            $result = $this->userProfileService->getUserCurrencyPreference(Auth::user());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve currency preferences', 500, $e->getMessage());
        }
    }

    public function updateCurrency(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'required|string|size:3|exists:currencies,code'
        ]);

        try {
            $result = $this->userProfileService->updateUserCurrency(Auth::user(), $request->currency);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update currency', 422, $e->getMessage());
        }
    }
}
