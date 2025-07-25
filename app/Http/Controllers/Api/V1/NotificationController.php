<?php
// FILE: app/Http/Controllers/Api/V1/NotificationController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\V1\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(protected NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->notificationService->getUserNotifications($request);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve notifications', 500, $e->getMessage());
        }
    }

    public function unread(): JsonResponse
    {
        try {
            $result = $this->notificationService->getUnreadNotifications();
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve unread notifications', 500, $e->getMessage());
        }
    }

    public function markAsRead(string $id): JsonResponse
    {
        try {
            $result = $this->notificationService->markAsRead($id);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark notification as read', 404, $e->getMessage());
        }
    }

    public function markAllAsRead(): JsonResponse
    {
        try {
            $result = $this->notificationService->markAllAsRead();
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark all notifications as read', 500, $e->getMessage());
        }
    }

    public function delete(string $id): JsonResponse
    {
        try {
            $result = $this->notificationService->deleteNotification($id);
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete notification', 404, $e->getMessage());
        }
    }

    public function deleteAll(): JsonResponse
    {
        try {
            $result = $this->notificationService->deleteAllNotifications();
            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete all notifications', 500, $e->getMessage());
        }
    }
}
