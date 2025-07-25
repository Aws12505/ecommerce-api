<?php
// FILE: app/Http/Controllers/Api/V1/NotificationSettingsController.php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UpdateNotificationSettingsRequest;
use App\Services\V1\User\UserProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationSettingsController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserProfileService $userProfileService) {}

    public function index(): JsonResponse
    {
        try {
            $result = $this->userProfileService->getNotificationSettings(Auth::user());
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve notification settings', 500, $e->getMessage());
        }
    }

    public function update(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        try {
            $result = $this->userProfileService->updateNotificationSettings(Auth::user(), $request->validated()['settings']);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update notification settings', 422, $e->getMessage());
        }
    }
}
