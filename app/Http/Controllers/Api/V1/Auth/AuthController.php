<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Services\V1\Auth\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());
            return $this->successResponse($result['data'], $result['message'], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed', 422, $e->getMessage());
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Login failed', 401, $e->getMessage());
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();
            return $this->successResponse(null, 'Successfully logged out');
        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed', 500, $e->getMessage());
        }
    }

    public function logoutEverywhere(): JsonResponse
    {
        try {
            $this->authService->logoutEverywhere();
            return $this->successResponse(null, 'Successfully logged out from all devices');
        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed', 500, $e->getMessage());
        }
    }

    public function me(): JsonResponse
    {
        $user = Auth::user()->load(['roles.permissions', 'preferredCurrency']);
        return $this->successResponse([
            'user' => $user,
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles' => $user->getRoleNames(),
            'currency_details' => $user->getCurrencyDetails(),
        ], 'User profile retrieved successfully');
    }

    public function refreshToken(): JsonResponse
    {
        try {
            $result = $this->authService->refreshToken();
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Token refresh failed', 401, $e->getMessage());
        }
    }
}
