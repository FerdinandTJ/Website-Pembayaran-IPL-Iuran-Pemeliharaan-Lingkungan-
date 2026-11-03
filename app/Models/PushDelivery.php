<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class PushDelivery extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['available_at' => 'datetime', 'claimed_until' => 'datetime'];
    }

    public function subscription()
    {
        return $this->belongsTo(PushSubscription::class, 'push_subscription_id');
    }

    public function notification()
    {
        return $this->belongsTo(DatabaseNotification::class);
    }
}
