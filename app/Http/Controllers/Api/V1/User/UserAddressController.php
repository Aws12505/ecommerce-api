<?php
// FILE: app/Http/Controllers/Api/V1/UserAddressController.php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\CreateAddressRequest;
use App\Http\Requests\V1\User\UpdateAddressRequest;
use App\Models\UserAddress;
use App\Services\V1\User\UserProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserProfileService $userProfileService) {}

    public function index(): JsonResponse
    {
        try {
            $result = $this->userProfileService->getUserAddresses(Auth::user());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve addresses', 500, $e->getMessage());
        }
    }

    public function store(CreateAddressRequest $request): JsonResponse
    {
        try {
            $result = $this->userProfileService->createAddress(Auth::user(), $request->validated());
            return $this->successResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create address', 422, $e->getMessage());
        }
    }

    public function show(UserAddress $address): JsonResponse
    {
        try {
            // Ensure user can only see their own addresses
            if ($address->user_id !== Auth::id()) {
                return $this->errorResponse('Address not found', 404);
            }

            return $this->successResponse($address, 'Address retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Address not found', 404, $e->getMessage());
        }
    }

    public function update(UpdateAddressRequest $request, UserAddress $address): JsonResponse
    {
        try {
            // Ensure user can only update their own addresses
            if ($address->user_id !== Auth::id()) {
                return $this->errorResponse('Address not found', 404);
            }

            $result = $this->userProfileService->updateAddress($address, $request->validated());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update address', 422, $e->getMessage());
        }
    }

    public function destroy(UserAddress $address): JsonResponse
    {
        try {
            // Ensure user can only delete their own addresses
            if ($address->user_id !== Auth::id()) {
                return $this->errorResponse('Address not found', 404);
            }

            $result = $this->userProfileService->deleteAddress($address);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete address', 500, $e->getMessage());
        }
    }

    public function setDefault(UserAddress $address): JsonResponse
    {
        try {
            // Ensure user can only set their own addresses as default
            if ($address->user_id !== Auth::id()) {
                return $this->errorResponse('Address not found', 404);
            }

            $result = $this->userProfileService->setDefaultAddress($address);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to set default address', 422, $e->getMessage());
        }
    }
}
