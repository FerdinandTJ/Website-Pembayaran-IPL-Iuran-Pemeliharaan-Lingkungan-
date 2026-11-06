<?php

namespace App\Models;

use App\Enums\CashflowCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_batch_id', 'housing_id', 'invoice_id', 'created_by', 'name',
        'type', 'source', 'total_cashflow', 'category', 'transaction_date', 'attachment_path',
    ];

    protected function casts(): array
    {
        return ['total_cashflow' => 'decimal:2', 'category' => CashflowCategory::class, 'transaction_date' => 'date'];
    }

    public const ACCOUNTING_DATE_SQL = 'COALESCE(transaction_date, DATE(created_at))';

    public function scopeAccountingBefore(Builder $query, $date): Builder
    {
        return $query->whereRaw(self::ACCOUNTING_DATE_SQL.' < ?', [$date->toDateString()]);
    }

    public function scopeAccountingRange(Builder $query, $start, $end): Builder
    {
        return $query->whereRaw(self::ACCOUNTING_DATE_SQL.' >= ? AND '.self::ACCOUNTING_DATE_SQL.' < ?', [$start->toDateString(), $end->toDateString()]);
    }

    public function getAccountingDateAttribute()
    {
        return $this->transaction_date ?? $this->created_at?->copy()->startOfDay();
    }

    public function scopeMatchingFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['type'] ?? null, fn ($q, $value) => $q->where('type', $value))
            ->when($filters['category'] ?? null, fn ($q, $value) => $q->where('category', $value))
            ->when($filters['source'] ?? null, fn ($q, $value) => $q->where('source', $value))
            ->when(isset($filters['search']) && $filters['search'] !== '', fn ($q) => $q->where('name', 'like', '%'.$filters['search'].'%'));
    }

    public function paymentBatch(): BelongsTo
    {
        return $this->belongsTo(PaymentBatch::class);
    }

    public function getMethodLabelAttribute(): string
    {
        if ($this->isManual()) {
            return 'Manual';
        }

        return match ($this->paymentBatch?->method ?? $this->invoice?->payment_method) {
            'cash' => 'Tunai / Cash',
            'transfer' => 'Transfer Bank',
            default => 'Tidak tercatat',
        };
    }

    public function scopeForHousing(Builder $query, int $housingId): Builder
    {
        return $query->where('housing_id', $housingId);
    }

    public function housing(): BelongsTo
    {
        return $this->belongsTo(Housing::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isManual(): bool
    {
        return $this->source === 'manual';
    }
}
