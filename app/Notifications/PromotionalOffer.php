<?php
// FILE: app/Notifications/PromotionalOffer.php

namespace App\Notifications;

use App\Models\Coupon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PromotionalOffer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Coupon $coupon, 
        public string $offerTitle,
        public string $offerDescription = ''
    ) {}

    public function via($notifiable): array
    {
        $channels = ['database'];
        
        if ($notifiable->hasNotificationEnabled('email', 'offers')) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->hasNotificationEnabled('push', 'offers')) {
            $channels[] = 'broadcast';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->offerTitle)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('We have a special offer just for you!')
            ->line($this->offerDescription ?: $this->coupon->description)
            ->line('**Coupon Code: ' . $this->coupon->code . '**')
            ->line('Discount: ' . $this->coupon->value . ($this->coupon->type === 'percentage' ? '%' : ' USD'))
            ->when($this->coupon->minimum_amount, function ($message) {
                return $message->line('Minimum order amount: $' . number_format($this->coupon->minimum_amount, 2));
            })
            ->when($this->coupon->expires_at, function ($message) {
                return $message->line('Expires on: ' . $this->coupon->expires_at->format('M d, Y'));
            })
            ->action('Shop Now', url('/'))
            ->line('Hurry! Don\'t miss out on this amazing deal.');
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'coupon_id' => $this->coupon->id,
            'coupon_code' => $this->coupon->code,
            'offer_title' => $this->offerTitle,
            'title' => $this->offerTitle,
            'message' => $this->offerDescription ?: $this->coupon->description,
            'discount_value' => $this->coupon->value,
            'discount_type' => $this->coupon->type,
            'expires_at' => $this->coupon->expires_at?->toISOString(),
            'icon' => '🎉',
            'url' => '/?coupon=' . $this->coupon->code,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'promotional_offer',
            'coupon_id' => $this->coupon->id,
            'coupon_code' => $this->coupon->code,
            'offer_title' => $this->offerTitle,
            'title' => $this->offerTitle,
            'message' => $this->offerDescription ?: $this->coupon->description,
            'discount_value' => $this->coupon->value,
            'discount_type' => $this->coupon->type,
            'expires_at' => $this->coupon->expires_at?->toISOString(),
            'icon' => '🎉',
            'url' => '/?coupon=' . $this->coupon->code,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('user.' . $this->notifiable->id),
        ];
    }
}
