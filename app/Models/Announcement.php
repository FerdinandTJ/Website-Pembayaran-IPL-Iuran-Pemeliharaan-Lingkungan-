<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['housing_id', 'created_by', 'title', 'body', 'status', 'published_at', 'archived_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'archived_at' => 'datetime'];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
