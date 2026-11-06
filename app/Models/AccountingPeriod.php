<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['period' => 'date', 'snapshot' => 'array', 'closed_at' => 'datetime'];
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
