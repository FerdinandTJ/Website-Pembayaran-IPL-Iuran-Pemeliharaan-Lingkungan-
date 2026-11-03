<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentImport extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['rows'];

    protected function casts(): array
    {
        return ['rows' => 'encrypted:array', 'expires_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
