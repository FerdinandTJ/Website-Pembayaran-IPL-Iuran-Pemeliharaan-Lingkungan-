<?php

namespace App\Services;

use App\Models\House;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceIssuer
{
    public function plan(User $actor, array $data, bool $lock = false): array
    {
        $period = Carbon::createFromFormat('!Y-m', $data['billing_period']);
        if (Carbon::parse($data['due_date'])->lt($period)) {
            throw ValidationException::withMessages(['due_date' => 'Jatuh tempo tidak boleh sebelum periode tagihan.']);
        }
        $total = collect($data['components'])->sum('amount');
        if ($total < 1 || $total > 9999999999999) {
            throw ValidationException::withMessages(['components' => 'Total nominal tagihan tidak valid atau terlalu besar.']);
        }
        $query = User::where('housing_id', $actor->housing_id)->where('role', 'warga')->where('is_active', true)
            ->when($data['recipient_mode'] === 'selected', fn ($q) => $q->whereIn('id', $data['resident_ids'] ?? []))->orderBy('id');
        $recipients = ($lock ? $query->lockForUpdate() : $query)->get();
        $rows = [];
        $skipped = 0;
        foreach ($recipients as $resident) {
            $houseQuery = House::where('housing_id', $actor->housing_id)->whereKey($resident->house_id);
            $house = $resident->house_id ? ($lock ? $houseQuery->lockForUpdate() : $houseQuery)->first() : null;
            if ($resident->house_id && ! $house) {
                $skipped++;

                continue;
            }
            if ($house && $house->responsible_user_id !== $resident->id) {
                $skipped++;

                continue;
            }
            $amount = $total;
            $components = $data['components'];
            if (($data['apply_house_tariff'] ?? true) && $house?->occupancy === 'vacant') {
                if ($house->vacant_billing === 'exempt') {
                    $skipped++;

                    continue;
                }
                if ($house->vacant_billing === 'custom') {
                    $amount = $house->vacant_amount;
                    $components = [['name' => 'Tarif khusus rumah kosong', 'amount' => $amount]];
                }
            }
            $rows[] = ['user_id' => $resident->id, 'resident' => $resident->name, 'house_id' => $house?->id,
                'house_label' => $house?->label ?? $resident->house_address, 'amount' => (string) $amount, 'components' => $components];
        }

        return ['data' => $data, 'rows' => $rows, 'skipped' => $skipped];
    }

    public function issue(User $actor, array $data, ?int $runId = null): array
    {
        return DB::transaction(function () use ($actor, $data, $runId) {
            return $this->issuePlan($actor, $this->plan($actor, $data, true), $runId);
        });
    }

    public function issuePlan(User $actor, array $plan, ?int $runId = null): array
    {
        $data = $plan['data'];
        foreach ($plan['rows'] as $row) {
            $invoice = Invoice::create(['housing_id' => $actor->housing_id, 'created_by' => $actor->id,
                'invoice_template_run_id' => $runId, 'house_id' => $row['house_id'], 'house_label' => $row['house_label'],
                'user_id' => $row['user_id'], 'title' => $data['title'], 'billing_period' => $data['billing_period'].'-01',
                'due_date' => $data['due_date'], 'total_amount' => $row['amount'], 'status' => 'unpaid']);
            $invoice->components()->createMany($row['components']);
            app(InvoiceNotifier::class)->send($invoice, 'issued');
        }

        return ['issued' => count($plan['rows']), 'skipped' => $plan['skipped']];
    }
}
