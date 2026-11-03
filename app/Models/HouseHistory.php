<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HouseHistory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
