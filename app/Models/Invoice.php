<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_template_run_id', 'payment_batch_id', 'house_id', 'house_label', 'payment_method', 'paid_at', 'payment_note',
        'housing_id', 'user_id', 'created_by', 'title',
        'billing_period', 'due_date', 'total_amount', 'status',
        'payment_proof_path', 'submitted_at', 'verified_at',
        'verified_by', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'billing_period' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
        ];
    }

    public function scopeForHousing(Builder $query, int $housingId): Builder
    {
        return $query->where('housing_id', $housingId);
    }

    public function scopePaymentRepresentatives(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('payment_batch_id')
            ->orWhereRaw('invoices.id = (SELECT MIN(pbi.invoice_id) FROM payment_batch_invoice pbi WHERE pbi.payment_batch_id = invoices.payment_batch_id)'));
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function paymentBatch(): BelongsTo
    {
        return $this->belongsTo(PaymentBatch::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function getPaymentLabelAttribute(): string
    {
        return $this->status === InvoiceStatus::Verified ? 'Lunas · '.($this->payment_method === 'cash' ? 'Cash' : 'Transfer') : ($this->payment_method === 'transfer' ? 'Transfer' : '—');
    }

    public function housing(): BelongsTo
    {
        return $this->belongsTo(Housing::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function components(): HasMany
    {
        return $this->hasMany(InvoiceComponent::class);
    }

    public function cashflow(): HasOne
    {
        return $this->hasOne(Cashflow::class);
    }

    public function canReceiveProof(): bool
    {
        return in_array($this->status, [InvoiceStatus::Unpaid, InvoiceStatus::Rejected], true);
    }
}
