<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTemplate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['components' => 'array', 'resident_ids' => 'array', 'due_day' => 'integer', 'due_month_offset' => 'integer', 'apply_house_tariff' => 'boolean', 'is_active' => 'boolean'];
    }

    public function runs()
    {
        return $this->hasMany(InvoiceTemplateRun::class);
    }
}
