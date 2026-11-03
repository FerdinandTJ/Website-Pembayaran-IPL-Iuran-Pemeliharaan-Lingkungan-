<?php

namespace App\Notifications;

use App\Notifications\Channels\HousingDatabaseChannel;
use Illuminate\Notifications\Notification;

class HousingNotification extends Notification
{
    public function __construct(public int $housingId, public string $eventKey, public array $payload) {}

    public function via(object $notifiable): array
    {
        return [HousingDatabaseChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
