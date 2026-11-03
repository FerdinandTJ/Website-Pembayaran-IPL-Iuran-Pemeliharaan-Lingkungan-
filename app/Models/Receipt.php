<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $fillable = ['invoice_id', 'number', 'snapshot'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
