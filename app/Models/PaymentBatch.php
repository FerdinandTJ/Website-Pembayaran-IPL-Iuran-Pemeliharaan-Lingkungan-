<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentBatch extends Model
{
    protected $fillable = ['housing_id', 'user_id', 'created_by', 'method', 'status', 'total_amount', 'snapshot', 'proof_path', 'note', 'rejection_reason', 'submitted_at', 'verified_at', 'verified_by'];

    protected function casts(): array
    {
        return ['status' => InvoiceStatus::class, 'snapshot' => 'array', 'total_amount' => 'decimal:2', 'submitted_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'payment_batch_invoice')->orderBy('invoices.id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function housing(): BelongsTo
    {
        return $this->belongsTo(Housing::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}
