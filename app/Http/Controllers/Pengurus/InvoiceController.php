<?php

namespace App\Http\Controllers\Pengurus;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::with('resident')
            ->forHousing($request->user()->housing_id)
            ->when($request->string('search')->toString(), fn ($query, $search) => $query
                ->whereHas('resident', fn ($query) => $query->where('name', 'like', "%{$search}%")))
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
            'activeResidentCount' => User::where('housing_id', $request->user()->housing_id)
                ->where('role', 'warga')->where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_number' => ['required', 'string', 'max:255'],
            'billing_period' => ['required', 'date_format:Y-m'],
            'due_date' => ['required', 'date'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.name' => ['required', 'string', 'max:255'],
            'components.*.amount' => ['required', 'numeric', 'integer', 'min:1000'],
        ]);

        $period = Carbon::createFromFormat('Y-m', $data['billing_period'])->startOfMonth();
        if (Carbon::parse($data['due_date'])->lt($period)) {
            throw ValidationException::withMessages(['due_date' => 'Jatuh tempo tidak boleh sebelum periode tagihan.']);
        }

        $residents = User::where('housing_id', $request->user()->housing_id)
            ->where('role', 'warga')->where('is_active', true)->get();

        if ($residents->isEmpty()) {
            return back()->withInput()->with('error', 'Belum ada warga aktif yang dapat menerima tagihan.');
        }

        $duplicateCount = Invoice::whereIn('user_id', $residents->pluck('id'))
            ->whereDate('billing_period', $period)->count();
        if ($duplicateCount > 0) {
            throw ValidationException::withMessages([
                'billing_period' => 'Sebagian warga sudah memiliki tagihan pada periode ini.',
            ]);
        }

        $total = collect($data['components'])->sum('amount');

        DB::transaction(function () use ($request, $data, $period, $residents, $total) {
            foreach ($residents as $resident) {
                $invoice = Invoice::create([
                    'housing_id' => $request->user()->housing_id,
                    'user_id' => $resident->id,
                    'created_by' => $request->user()->id,
                    'account_number' => $data['account_number'],
                    'billing_period' => $period,
                    'due_date' => $data['due_date'],
                    'total_amount' => $total,
                    'status' => InvoiceStatus::Unpaid,
                ]);

                $invoice->components()->createMany($data['components']);
            }
        });

        return redirect()->route('pengurus.invoices.index')
            ->with('success', "Tagihan berhasil dikirim kepada {$residents->count()} warga.");
    }
}
