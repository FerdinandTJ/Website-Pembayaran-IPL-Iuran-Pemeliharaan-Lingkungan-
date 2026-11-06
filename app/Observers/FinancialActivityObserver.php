<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Cashflow;
use App\Services\AccountingPeriodService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FinancialActivityObserver
{
    private const FIELDS = ['name', 'title', 'user_id', 'resident_id', 'house_id', 'invoice_id', 'payment_batch_id', 'invoice_template_id', 'invoice_template_run_id',
        'billing_period', 'due_date', 'components', 'recipient_mode', 'resident_ids', 'due_day', 'due_month_offset', 'apply_house_tariff', 'is_active',
        'type', 'source', 'category', 'total_cashflow', 'total_amount', 'transaction_date', 'status', 'paid_at', 'verified_at', 'verified_by',
        'rejection_reason', 'payment_note', 'payment_method', 'method', 'number', 'next_action_date', 'assigned_to', 'note', 'period', 'closed_at', 'closed_by', 'reopen_reason', 'issued_count', 'skipped_count'];

    public function creating(Model $model): void
    {
        if ($model instanceof Cashflow) {
            $model->transaction_date ??= ($model->created_at ?? now())->toDateString();
            app(AccountingPeriodService::class)->assertOpen($model->housing_id, $model->transaction_date->toDateString());
        }
    }

    public function updating(Model $model): void
    {
        if ($model instanceof Cashflow) {
            $book = app(AccountingPeriodService::class);
            $book->assertOpen($model->housing_id, substr($model->getRawOriginal('transaction_date') ?? $model->getRawOriginal('created_at'), 0, 10));
            $book->assertOpen($model->housing_id, $model->transaction_date->toDateString());
        }
    }

    public function deleting(Model $model): void
    {
        if ($model instanceof Cashflow) {
            app(AccountingPeriodService::class)->assertOpen($model->housing_id, $model->accounting_date->toDateString());
        }
    }

    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    private function record(Model $model, string $event): void
    {
        $housingId = $model->housing_id;
        if (! $housingId) {
            return;
        }
        $keys = array_intersect(self::FIELDS, array_keys($model->getAttributes()));
        $before = $event === 'created' ? [] : array_intersect_key($model->getRawOriginal(), array_flip($keys));
        $after = $event === 'deleted' ? [] : array_intersect_key($model->getAttributes(), array_flip($keys));
        if ($event === 'updated' && $before === $after && ! $model->isDirty('attachment_path')) {
            return;
        }
        if ($model instanceof Cashflow) {
            $before['has_attachment'] = (bool) $model->getRawOriginal('attachment_path');
            $after['has_attachment'] = $event !== 'deleted' && (bool) $model->attachment_path;
        }
        $actor = Auth::user();
        ActivityLog::create(['housing_id' => $housingId, 'actor_id' => $actor?->id, 'actor_name' => $actor?->name ?? 'Sistem / CLI',
            'entity_type' => class_basename($model), 'entity_id' => $model->id, 'event' => $event,
            'before' => $before, 'after' => $after, 'created_at' => now()]);
    }
}
