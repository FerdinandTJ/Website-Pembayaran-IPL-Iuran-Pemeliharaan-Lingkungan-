<?php

namespace App\Notifications\Channels;

use App\Notifications\HousingNotification;
use App\Services\WhatsAppOutbox;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

class HousingDatabaseChannel extends DatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        /** @var HousingNotification $notification */
        $stored = $notifiable->routeNotificationFor('database', $notification)->firstOrCreate([
            'deduplication_key' => hash('sha256', $notifiable->id.':'.$notification->eventKey),
        ], [
            'id' => $notification->id,
            'type' => $notification::class,
            'housing_id' => $notification->housingId,
            'data' => $notification->toArray($notifiable),
            'read_at' => null,
        ]);
        if ($stored->wasRecentlyCreated) {
            app(WhatsAppOutbox::class)->enqueue($notifiable, $stored);
        }

        return $stored;
    }
}
