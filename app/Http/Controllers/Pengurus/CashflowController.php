<?php

namespace App\Http\Controllers\Pengurus;

use App\Enums\CashflowCategory;
use App\Http\Controllers\Controller;
use App\Models\Cashflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class CashflowController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['income', 'expense'])],
            'source' => ['nullable', Rule::in(['manual', 'invoice'])],
            'category' => ['nullable', Rule::enum(CashflowCategory::class)],
        ]);
        $base = Cashflow::forHousing($request->user()->housing_id);
        $income = (clone $base)->where('type', 'income')->sum('total_cashflow');
        $expense = (clone $base)->where('type', 'expense')->sum('total_cashflow');

        $cashflows = $base->with('invoice', 'paymentBatch')->matchingFilters($filters)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pengurus.cashflows.index', compact('cashflows', 'income', 'expense') + [
            'categories' => CashflowCategory::cases(), 'manualCategories' => CashflowCategory::manualCases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $path = $request->file('attachment')?->store('cashflow-attachments/'.$request->user()->housing_id, 'local');
        try {
            DB::transaction(fn () => Cashflow::create(array_diff_key($data, ['attachment' => true, 'remove_attachment' => true]) + [
                'housing_id' => $request->user()->housing_id, 'created_by' => $request->user()->id,
                'source' => 'manual', 'category' => $data['category'] ?? CashflowCategory::Uncategorized,
                'transaction_date' => $data['transaction_date'] ?? today('Asia/Jakarta')->toDateString(), 'attachment_path' => $path,
            ]));
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return back()->with('success', 'Transaksi kas berhasil ditambahkan.');
    }

    public function update(Request $request, Cashflow $cashflow): RedirectResponse
    {
        $this->authorize('update', $cashflow);
        $data = $this->validated($request);
        $path = $request->file('attachment')?->store('cashflow-attachments/'.$request->user()->housing_id, 'local');
        $oldPath = null;
        try {
            DB::transaction(function () use ($cashflow, $data, $path, $request, &$oldPath) {
                $locked = Cashflow::lockForUpdate()->findOrFail($cashflow->id);
                $oldPath = $locked->attachment_path;
                $values = array_diff_key($data, ['attachment' => true, 'remove_attachment' => true]);
                if ($path || $request->boolean('remove_attachment')) {
                    $values['attachment_path'] = $path;
                }
                $locked->update($values);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
        if ($oldPath && ($path || $request->boolean('remove_attachment'))) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'Transaksi kas berhasil diperbarui.');
    }

    public function destroy(Cashflow $cashflow): RedirectResponse
    {
        $this->authorize('delete', $cashflow);
        $path = DB::transaction(function () use ($cashflow) {
            $locked = Cashflow::lockForUpdate()->findOrFail($cashflow->id);
            $path = $locked->attachment_path;
            $locked->delete();

            return $path;
        });
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('success', 'Transaksi kas berhasil dihapus.');
    }

    public function attachment(Request $request, Cashflow $cashflow)
    {
        $this->authorize('update', $cashflow);
        abort_unless($cashflow->attachment_path && Storage::disk('local')->exists($cashflow->attachment_path), 404);
        $extension = pathinfo($cashflow->attachment_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($cashflow->attachment_path, 'bukti-transaksi-'.$cashflow->id.'.'.$extension,
            ['Content-Security-Policy' => "default-src 'none'; sandbox", 'Cache-Control' => 'private, no-store']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'transaction_date' => ['sometimes', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'attachment' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(5 * 1024)],
            'remove_attachment' => ['sometimes', 'boolean'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['sometimes', 'required', Rule::in(array_map(fn ($category) => $category->value, CashflowCategory::manualCases()))],
            'total_cashflow' => ['required', 'numeric', 'min:1', 'max:9999999999999', 'decimal:0,2'],
        ]);
    }
}
