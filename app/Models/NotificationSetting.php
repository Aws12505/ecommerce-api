<?php
// FILE: app/Models/NotificationSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'category',
        'enabled',
        'preferences',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'preferences' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Constants for notification types and categories
    const TYPES = [
        'email' => 'Email',
        'push' => 'Push Notification',
        'sms' => 'SMS',
    ];

    const CATEGORIES = [
        'order_status' => 'Order Status Updates',
        'offers' => 'Special Offers',
        'promotions' => 'Promotions & Sales',
        'product_alerts' => 'Product Alerts',
        'account_security' => 'Account Security',
        'newsletter' => 'Newsletter',
        'order_reminders' => 'Order Reminders',
        'stock_alerts' => 'Stock Availability',
    ];
}