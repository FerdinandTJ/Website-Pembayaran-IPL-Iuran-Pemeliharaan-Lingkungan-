<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArrearsFollowUp extends Model
{
    public const STATUSES = ['contacted' => 'Sudah Dihubungi', 'promised' => 'Janji Bayar', 'scheduled' => 'Hubungi Kembali', 'closed' => 'Tindak Lanjut Selesai'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['next_action_date' => 'date'];
    }

    public function resident()
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
