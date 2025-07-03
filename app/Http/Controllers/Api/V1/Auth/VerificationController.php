<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\ResendVerificationRequest;
use App\Services\V1\Auth\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VerificationController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService) {}

    public function resend(ResendVerificationRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->resendVerification($request->validated());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to resend verification', 422, $e->getMessage());
        }
    }

    public function verify(Request $request, $id, $hash): JsonResponse
    {
        try {
            $result = $this->authService->verifyEmail($request, $id, $hash);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Email verification failed', 403, $e->getMessage());
        }
    }
}
