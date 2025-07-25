<?php
// FILE: app/Services/V1/NotificationService.php

namespace App\Services\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    public function getUserNotifications(Request $request): array
    {
        $user = Auth::user();
        
        $query = $user->notifications();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by read status
        if ($request->has('unread_only') && $request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $perPage = min($request->get('per_page', 15), 50);
        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return [
            'data' => $notifications,
            'message' => 'Notifications retrieved successfully'
        ];
    }

    public function getUnreadNotifications(): array
    {
        $user = Auth::user();
        
        $unreadNotifications = $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $unreadCount = $user->unreadNotifications()->count();

        return [
            'data' => [
                'notifications' => $unreadNotifications,
                'unread_count' => $unreadCount,
            ],
            'message' => 'Unread notifications retrieved successfully'
        ];
    }

    public function markAsRead(string $id): array
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            throw ValidationException::withMessages([
                'notification' => ['Notification not found.']
            ]);
        }

        $notification->markAsRead();

        return [
            'data' => $notification,
            'message' => 'Notification marked as read'
        ];
    }

    public function markAllAsRead(): array
    {
        $user = Auth::user();
        
        $updatedCount = $user->unreadNotifications()->update(['read_at' => now()]);

        return [
            'data' => ['updated_count' => $updatedCount],
            'message' => 'All notifications marked as read'
        ];
    }

    public function deleteNotification(string $id): array
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            throw ValidationException::withMessages([
                'notification' => ['Notification not found.']
            ]);
        }

        $notification->delete();

        return [
            'data' => null,
            'message' => 'Notification deleted successfully'
        ];
    }

    public function deleteAllNotifications(): array
    {
        $user = Auth::user();
        
        $deletedCount = $user->notifications()->delete();

        return [
            'data' => ['deleted_count' => $deletedCount],
            'message' => 'All notifications deleted successfully'
        ];
    }
}
