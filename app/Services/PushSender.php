<?php

namespace App\Services;

use App\Models\PushDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PushSender
{
    public function send(int $id): void
    {
        if (! app(WebPushKeys::class)->configured()) {
            return;
        }
        $delivery = DB::transaction(function () use ($id) {
            $row = PushDelivery::lockForUpdate()->find($id);
            if (! $row || $row->available_at->isFuture() || ! in_array($row->status, ['pending', 'processing'], true)) {
                return null;
            }
            if ($row->status === 'processing' && $row->claimed_until?->isFuture()) {
                return null;
            }
            if ($row->attempts >= 5 || $row->created_at->lt(now()->subDay())) {
                $row->update(['status' => 'failed', 'last_error' => 'expired_or_attempt_limit']);

                return null;
            }
            $row->update(['status' => 'processing', 'attempts' => $row->attempts + 1, 'claimed_until' => now()->addMinutes(2)]);

            return $row;
        });
        if (! $delivery) {
            return;
        }
        $subscription = $delivery->subscription;
        $user = $subscription?->user;
        $notification = $delivery->notification;
        if (! $subscription || ! $user || ! $user->is_active || ! $user->housing?->is_active
            || $subscription->housing_id !== $user->housing_id || ! $notification
            || $notification->housing_id !== $user->housing_id || $notification->notifiable_id !== $user->id
            || $notification->notifiable_type !== $user->getMorphClass()) {
            $delivery->update(['status' => 'canceled', 'claimed_until' => null]);

            return;
        }
        try {
            $result = app(WebPushTransport::class)->send($subscription, [
                'title' => 'EnviroPay', 'body' => 'Ada pemberitahuan baru. Buka aplikasi untuk melihat detail.',
                'tag' => 'enviropay-'.$notification->id, 'url' => route('notifications.index', [], false),
            ]);
            if ($result === 'expired') {
                $subscription->delete();

                return;
            }
            $delivery->update(['status' => $result === 'sent' ? 'sent' : 'pending', 'claimed_until' => null,
                'available_at' => now()->addMinutes(2 ** $delivery->attempts), 'last_error' => $result === 'sent' ? null : 'provider_rejected']);
        } catch (\Throwable) {
            // Do not expose endpoint capability URLs, subscription keys or HTTP bodies in logs.
            $delivery->update(['status' => 'pending', 'claimed_until' => null, 'available_at' => now()->addMinutes(2 ** $delivery->attempts), 'last_error' => 'transport_error']);
            Log::warning('Web push delivery failed', ['delivery_id' => $id]);
        }
    }
}
