<?php

namespace App\Http\Controllers\Pengurus;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceNotifier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        $period = Carbon::createFromFormat('Y-m', $data['billing_period'])->startOfMonth();
        if (Carbon::parse($data['due_date'])->lt($period)) {
            throw ValidationException::withMessages(['due_date' => 'Jatuh tempo tidak boleh sebelum periode tagihan.']);
        }

        $recipients = User::with('house')->where('housing_id', $housingId)
            ->where('role', 'warga')
            ->where('is_active', true)
            ->when(
                $data['recipient_mode'] === 'selected',
                fn ($query) => $query->whereIn('id', array_unique($data['resident_ids']))
            )
            ->get();

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('error', 'Belum ada warga aktif yang dapat menerima tagihan.');
        }

        $total = collect($data['components'])->sum('amount');

        if ($total > 9999999999999) {
            throw ValidationException::withMessages(['components' => 'Total nominal tagihan terlalu besar.']);
        }
        $sent = 0;
        $skipped = 0;
        DB::transaction(function () use ($request, $data, $period, $recipients, $total, &$sent, &$skipped) {
            foreach ($recipients as $resident) {
                $resident = User::lockForUpdate()->findOrFail($resident->id);
                if (! $resident->is_active) {
                    $skipped++;

                    continue;
                }
                $house = $resident->house_id ? House::lockForUpdate()->findOrFail($resident->house_id) : null;
                if ($house && $house->responsible_user_id !== $resident->id) {
                    $skipped++;

                    continue;
                }
                $components = $data['components'];
                $amount = $total;
                if ($request->boolean('apply_house_tariff', true) && $house?->occupancy === 'vacant') {
                    if ($house->vacant_billing === 'exempt') {
                        $skipped++;

                        continue;
                    }
                    if ($house->vacant_billing === 'custom') {
                        $amount = $house->vacant_amount;
                        $components = [['name' => 'Tarif khusus rumah kosong', 'amount' => $amount]];
                    }
                }
                $invoice = Invoice::create([
                    'house_id' => $house?->id,
                    'house_label' => $house?->label ?? $resident->house_address,
                    'housing_id' => $request->user()->housing_id,
                    'user_id' => $resident->id,
                    'created_by' => $request->user()->id,
                    'title' => $data['title'],
                    'billing_period' => $period,
                    'due_date' => $data['due_date'],
                    'total_amount' => $amount,
                    'status' => InvoiceStatus::Unpaid,
                ]);

                $invoice->components()->createMany($components);
                $sent++;
                app(InvoiceNotifier::class)->send($invoice, 'issued');
            }
        });

        return redirect()->route('pengurus.invoices.index')
            ->with('success', "Tagihan berhasil dikirim kepada {$sent} warga.".($skipped ? " {$skipped} penerima dilewati sesuai penanggung jawab/tarif rumah." : ''));
    }
}
