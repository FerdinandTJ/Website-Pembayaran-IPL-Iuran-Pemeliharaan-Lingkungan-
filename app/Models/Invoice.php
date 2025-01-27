<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_number',
        'total_amount',
        'receiver_name',
        'is_paid',
        'is_verified',
        'payment_proof',
    ];

    public function components()
    {
        return $this->hasMany(InvoiceComponent::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_name', 'name'); // Assuming receiver_name refers to User's name
    }
}
