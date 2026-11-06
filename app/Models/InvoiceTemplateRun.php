<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTemplateRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['billing_period' => 'date', 'snapshot' => 'array'];
    }
}
