<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\InvoiceTemplate;
use App\Models\User;
use App\Services\InvoiceTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceTemplateController extends Controller
{
    public function index(Request $request)
    {
        return view('pengurus.invoice-templates.index', ['templates' => InvoiceTemplate::where('housing_id', $request->user()->housing_id)->withCount('runs')->orderBy('name')->paginate(15)]);
    }

    public function create(Request $request)
    {
        return $this->form($request, new InvoiceTemplate(['is_active' => true, 'apply_house_tariff' => true, 'due_day' => 10, 'due_month_offset' => 0, 'recipient_mode' => 'all']));
    }

    public function edit(Request $request, InvoiceTemplate $invoiceTemplate)
    {
        $this->scope($request, $invoiceTemplate);

        return $this->form($request, $invoiceTemplate);
    }

    private function form(Request $request, InvoiceTemplate $template)
    {
        return view('pengurus.invoice-templates.form', ['template' => $template, 'residents' => User::where('housing_id', $request->user()->housing_id)->where('role', 'warga')->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(fn () => InvoiceTemplate::create($data + ['housing_id' => $request->user()->housing_id, 'created_by' => $request->user()->id]));

        return redirect()->route('pengurus.invoice-templates.index')->with('success', 'Template disimpan. Pilih periode untuk meninjau penerbitan.');
    }

    public function update(Request $request, InvoiceTemplate $invoiceTemplate)
    {
        $this->scope($request, $invoiceTemplate);
        $data = $this->validated($request);
        DB::transaction(fn () => $invoiceTemplate->update($data));

        return redirect()->route('pengurus.invoice-templates.index')->with('success', 'Template diperbarui. Tagihan yang sudah terbit tidak berubah.');
    }

    public function preview(Request $request, InvoiceTemplate $invoiceTemplate, InvoiceTemplateService $service)
    {
        $this->scope($request, $invoiceTemplate);
        $data = $request->validate(['period' => ['required', 'date_format:Y-m']]);
        $plan = $service->preview($request->user(), $invoiceTemplate, $data['period']);

        return view('pengurus.invoice-templates.preview', ['template' => $invoiceTemplate, 'period' => $data['period'], 'plan' => $plan, 'token' => $service->token($invoiceTemplate, $plan)]);
    }

    public function issue(Request $request, InvoiceTemplate $invoiceTemplate, InvoiceTemplateService $service)
    {
        $this->scope($request, $invoiceTemplate);
        $data = $request->validate(['period' => ['required', 'date_format:Y-m'], 'preview_token' => ['required', 'string', 'size:64'], 'confirmed' => ['accepted']]);
        $run = $service->issue($request->user(), $invoiceTemplate, $data['period'], $data['preview_token']);

        return redirect()->route('pengurus.invoices.index', ['period' => $data['period']])->with('success', $run->issued_count.' tagihan diterbitkan; '.$run->skipped_count.' penerima dilewati sesuai aturan rumah.');
    }

    private function scope(Request $request, InvoiceTemplate $template): void
    {
        abort_unless($template->housing_id === $request->user()->housing_id, 404);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'title' => ['required', 'string', 'max:255'],
            'components' => ['required', 'array', 'min:1', 'max:50'], 'components.*.name' => ['required', 'string', 'max:255'],
            'components.*.amount' => ['required', 'integer', 'min:1000', 'max:999999999999'],
            'due_day' => ['required', 'integer', 'between:1,31'], 'due_month_offset' => ['required', 'integer', 'between:0,1'],
            'recipient_mode' => ['required', Rule::in(['all', 'selected'])],
            'resident_ids' => ['exclude_unless:recipient_mode,selected', 'required', 'array', 'min:1', 'max:1000'],
            'resident_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('housing_id', $request->user()->housing_id)->where('role', 'warga')->where('is_active', true)],
            'apply_house_tariff' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'],
        ]) + ['resident_ids' => null];
        if (collect($data['components'])->sum('amount') > 9999999999999) {
            throw ValidationException::withMessages(['components' => 'Total nominal template terlalu besar.']);
        }

        return $data;
    }
}
