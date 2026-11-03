<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushTransport
{
    public function send(PushSubscription $subscription, array $payload): string
    {
        if (! app(PushEndpoint::class)->allowed($subscription->endpoint)) {
            return 'expired';
        }
        $auth = app(WebPushKeys::class)->keys() + ['subject' => config('webpush.subject')];
        $client = new WebPush(['VAPID' => $auth], ['TTL' => 3600, 'urgency' => 'normal'], 15, [
            'connect_timeout' => 5, 'allow_redirects' => false, 'verify' => true,
        ]);
        $report = $client->sendOneNotification(Subscription::create([
            'endpoint' => $subscription->endpoint, 'publicKey' => $subscription->public_key,
            'authToken' => $subscription->auth_token, 'contentEncoding' => 'aes128gcm',
        ]), json_encode($payload, JSON_THROW_ON_ERROR));
        if ($report->isSubscriptionExpired()) {
            return 'expired';
        }

        return $report->isSuccess() ? 'sent' : 'retry';
    }
}
