<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppDelivery extends Model
{
    protected $table = 'whatsapp_deliveries';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'available_at' => 'datetime', 'claimed_until' => 'datetime', 'accepted_at' => 'datetime'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
