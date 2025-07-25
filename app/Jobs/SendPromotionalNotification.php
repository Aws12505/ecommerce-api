<?php
// FILE: app/Jobs/SendPromotionalNotification.php

namespace App\Jobs;

use App\Models\User;
use App\Models\Coupon;
use App\Notifications\PromotionalOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPromotionalNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Coupon $coupon,
        public string $offerTitle,
        public string $offerDescription = '',
        public array $userIds = []
    ) {}

    public function handle(): void
    {
        $query = User::query();

        if (!empty($this->userIds)) {
            $query->whereIn('id', $this->userIds);
        }

        $query->chunk(100, function ($users) {
            foreach ($users as $user) {
                $user->notify(new PromotionalOffer(
                    $this->coupon,
                    $this->offerTitle,
                    $this->offerDescription
                ));
            }
        });
    }
}
