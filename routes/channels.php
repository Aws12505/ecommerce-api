<?php
// FILE: routes/channels.php

use Illuminate\Support\Facades\Broadcast;

// Private channel for user notifications
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for order updates
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    return $user->orders()->where('id', $orderId)->exists();
});

// Admin channel for order management
Broadcast::channel('admin.orders', function ($user) {
    return $user->hasRole('admin');
});
