<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'housing_id', 'invoice_id', 'created_by', 'name',
        'type', 'source', 'total_cashflow',
    ];

    protected function casts(): array
    {
        return ['total_cashflow' => 'decimal:2'];
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
