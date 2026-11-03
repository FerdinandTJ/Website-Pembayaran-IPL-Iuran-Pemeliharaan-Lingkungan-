<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class House extends Model
{
    public const OCCUPANCIES = ['occupied' => 'Dihuni', 'rented' => 'Dikontrakkan', 'vacant' => 'Kosong', 'renovation' => 'Renovasi'];

    public const OCCUPANCY_COLORS = ['occupied' => 'success', 'rented' => 'info', 'vacant' => 'warning', 'renovation' => 'secondary'];

    public const BILLING = ['full' => 'Tarif penuh', 'custom' => 'Nominal khusus', 'exempt' => 'Bebas iuran baru'];

    protected $fillable = ['housing_id', 'code', 'address', 'occupancy', 'owner_name', 'occupant_name', 'responsible_user_id', 'vacant_billing', 'vacant_amount', 'notes'];

    protected function casts(): array
    {
        return ['vacant_amount' => 'decimal:2'];
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function residents()
    {
        return $this->hasMany(User::class);
    }

    public function histories()
    {
        return $this->hasMany(HouseHistory::class)->latest('id');
    }

    public function getLabelAttribute(): string
    {
        return $this->code.' · '.$this->address;
    }
}
