<?php
// FILE: app/Notifications/StockAlert.php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class StockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via($notifiable): array
    {
        $channels = ['database'];
        
        if ($notifiable->hasNotificationEnabled('email', 'stock_alerts')) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->hasNotificationEnabled('push', 'stock_alerts')) {
            $channels[] = 'broadcast';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Back in Stock: ' . $this->product->name)
            ->greeting('Great news, ' . $notifiable->name . '!')
            ->line('The product you were waiting for is back in stock!')
            ->line('**' . $this->product->name . '**')
            ->line('Price: $' . number_format($this->product->current_price, 2))
            ->when($this->product->is_on_sale, function ($message) {
                return $message->line('🏷️ On Sale! Save ' . $this->product->discount_percentage . '%');
            })
            ->action('Shop Now', url('/products/' . $this->product->slug))
            ->line('Hurry! Limited stock available.');
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_slug' => $this->product->slug,
            'product_price' => $this->product->current_price,
            'is_on_sale' => $this->product->is_on_sale,
            'title' => 'Back in Stock!',
            'message' => $this->product->name . ' is now available!',
            'icon' => '📦',
            'url' => '/products/' . $this->product->slug,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'stock_alert',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_slug' => $this->product->slug,
            'product_price' => $this->product->current_price,
            'is_on_sale' => $this->product->is_on_sale,
            'title' => 'Back in Stock!',
            'message' => $this->product->name . ' is now available!',
            'icon' => '📦',
            'url' => '/products/' . $this->product->slug,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('user.' . $this->notifiable->id),
        ];
    }
}
