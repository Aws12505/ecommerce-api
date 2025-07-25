<?php
// FILE: app/Notifications/OrderStatusUpdated.php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order, 
        public string $previousStatus
    ) {}

    public function via($notifiable): array
    {
        $channels = ['database']; // Always store in database
        
        if ($notifiable->hasNotificationEnabled('email', 'order_status')) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->hasNotificationEnabled('push', 'order_status')) {
            $channels[] = 'broadcast';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $statusMessages = [
            'pending' => 'Your order has been received and is being processed.',
            'processing' => 'Your order is currently being prepared.',
            'shipped' => 'Your order has been shipped and is on its way!',
            'delivered' => 'Your order has been delivered successfully.',
            'cancelled' => 'Your order has been cancelled.',
            'refunded' => 'Your order has been refunded.',
        ];

        return (new MailMessage)
            ->subject('Order Status Update - #' . $this->order->order_number)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($statusMessages[$this->order->status] ?? 'Your order status has been updated.')
            ->line('Order Number: #' . $this->order->order_number)
            ->line('Order Total: $' . number_format($this->order->total, 2))
            ->when($this->order->shipped_at, function ($message) {
                return $message->line('Shipped on: ' . $this->order->shipped_at->format('M d, Y'));
            })
            ->when($this->order->tracking_number ?? false, function ($message) {
                return $message->line('Tracking Number: ' . $this->order->tracking_number);
            })
            ->action('View Order Details', url('/orders/' . $this->order->id))
            ->line('Thank you for shopping with us!');
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'previous_status' => $this->previousStatus,
            'current_status' => $this->order->status,
            'title' => 'Order Status Updated',
            'message' => "Your order #{$this->order->order_number} status changed to " . ucfirst($this->order->status),
            'icon' => $this->getStatusIcon(),
            'url' => '/orders/' . $this->order->id,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'order_status_updated',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'previous_status' => $this->previousStatus,
            'current_status' => $this->order->status,
            'title' => 'Order Status Updated',
            'message' => "Your order #{$this->order->order_number} status changed to " . ucfirst($this->order->status),
            'icon' => $this->getStatusIcon(),
            'url' => '/orders/' . $this->order->id,
        ];
    }

    private function getStatusIcon(): string
    {
        return match($this->order->status) {
            'pending' => '⏳',
            'processing' => '🔄',
            'shipped' => '🚚',
            'delivered' => '✅',
            'cancelled' => '❌',
            'refunded' => '💰',
            default => '📦',
        };
    }

    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('user.' . $this->order->user_id),
        ];
    }
}
