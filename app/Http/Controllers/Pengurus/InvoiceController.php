<?php

namespace App\Http\Controllers\Pengurus;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::with('resident')
            ->forHousing($request->user()->housing_id)
            ->when($request->string('search')->toString(), fn ($query, $search) => $query
                ->where(fn ($query) => $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('resident', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('period'), fn ($query) => $query->whereDate('billing_period', $request->string('period').'-01'))
            ->latest('billing_period')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pengurus.invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('pengurus.invoices.create', [
            'residents' => User::with('house')->where('housing_id', $request->user()->housing_id)
                ->where('role', 'warga')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $housingId = $request->user()->housing_id;
        $data = $request->validate([
            'apply_house_tariff' => ['sometimes', 'boolean'],
            'title' => ['required', 'string', 'max:255'],
            'billing_period' => ['required', 'date_format:Y-m'],
            'due_date' => ['required', 'date'],
            'recipient_mode' => ['required', 'in:all,selected'],
            'resident_ids' => ['exclude_unless:recipient_mode,selected', 'required', 'array', 'min:1', 'max:1000'],
            'resident_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('housing_id', $housingId)
                    ->where('role', 'warga')
                    ->where('is_active', true)),
            ],
            'components' => ['required', 'array', 'min:1', 'max:50'],
            'components.*.name' => ['required', 'string', 'max:255'],
            'components.*.amount' => ['required', 'numeric', 'integer', 'min:1000', 'max:999999999999'],
        ]);

        $data['apply_house_tariff'] = $request->boolean('apply_house_tariff', true);
        $result = app(InvoiceIssuer::class)->issue($request->user(), $data);
        $sent = $result['issued'];
        $skipped = $result['skipped'];

        return redirect()->route('pengurus.invoices.index')
            ->with('success', "Tagihan berhasil dikirim kepada {$sent} warga.".($skipped ? " {$skipped} penerima dilewati sesuai penanggung jawab/tarif rumah." : ''));
    }
}
