<?php

namespace App\Services;

use App\Models\InvoiceTemplate;
use App\Models\InvoiceTemplateRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceTemplateService
{
    public function preview(User $actor, InvoiceTemplate $template, string $period, bool $lock = false): array
    {
        abort_unless($actor->isManager() && $actor->housing_id === $template->housing_id, 403);
        if (! $template->is_active) {
            throw ValidationException::withMessages(['template' => 'Template sedang dinonaktifkan.']);
        }
        if ($template->runs()->whereDate('billing_period', $period.'-01')->exists()) {
            throw ValidationException::withMessages(['period' => 'Template ini sudah diterbitkan untuk periode tersebut.']);
        }
        $due = Carbon::createFromFormat('!Y-m', $period)->addMonthsNoOverflow($template->due_month_offset);
        $due->day(min($template->due_day, $due->daysInMonth));
        $data = ['title' => $template->title, 'components' => $template->components, 'recipient_mode' => $template->recipient_mode,
            'resident_ids' => $template->resident_ids ?? [], 'apply_house_tariff' => $template->apply_house_tariff,
            'billing_period' => $period, 'due_date' => $due->toDateString()];

        return app(InvoiceIssuer::class)->plan($actor, $data, $lock);
    }

    public function token(InvoiceTemplate $template, array $plan): string
    {
        return hash_hmac('sha256', json_encode([$template->id, $plan], JSON_THROW_ON_ERROR), config('app.key'));
    }

    public function issue(User $actor, InvoiceTemplate $template, string $period, string $token): InvoiceTemplateRun
    {
        return DB::transaction(function () use ($actor, $template, $period, $token) {
            $template = InvoiceTemplate::where('housing_id', $actor->housing_id)->lockForUpdate()->findOrFail($template->id);
            $plan = $this->preview($actor, $template, $period, true);
            if (! hash_equals($this->token($template, $plan), $token)) {
                throw ValidationException::withMessages(['preview_token' => 'Penerima, tarif, atau template berubah. Buka kembali pratinjau sebelum menerbitkan.']);
            }
            if (! count($plan['rows'])) {
                throw ValidationException::withMessages(['template' => 'Tidak ada penerima yang memenuhi syarat.']);
            }
            $run = InvoiceTemplateRun::create(['housing_id' => $actor->housing_id, 'invoice_template_id' => $template->id,
                'created_by' => $actor->id, 'billing_period' => $period.'-01', 'snapshot' => $plan['data'],
                'issued_count' => count($plan['rows']), 'skipped_count' => $plan['skipped']]);
            app(InvoiceIssuer::class)->issuePlan($actor, $plan, $run->id);

            return $run;
        });
    }
}
