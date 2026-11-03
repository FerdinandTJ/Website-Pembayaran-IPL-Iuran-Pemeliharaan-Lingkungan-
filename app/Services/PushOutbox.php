<?php

namespace App\Services;

use App\Models\PushDelivery;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class PushOutbox
{
    public function enqueue(User $user, DatabaseNotification $notification): void
    {
        if (! app(WebPushKeys::class)->configured() || ! $user->is_active || ! $user->housing?->is_active) {
            return;
        }
        foreach (PushSubscription::where('user_id', $user->id)->where('housing_id', $user->housing_id)->when($notification->data['push_subscription_id'] ?? null, fn ($q, $id) => $q->whereKey($id))->cursor() as $subscription) {
            PushDelivery::firstOrCreate(['push_subscription_id' => $subscription->id, 'notification_id' => $notification->id], ['available_at' => now()]);
        }
    }
}
