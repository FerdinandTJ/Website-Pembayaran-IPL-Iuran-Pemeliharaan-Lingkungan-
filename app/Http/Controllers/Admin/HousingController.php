<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Housing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HousingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Housing::class);

        $housings = Housing::withCount([
            'users as managers_count' => fn ($query) => $query->where('role', 'pengurus')->where('is_active', true),
            'users as residents_count' => fn ($query) => $query->where('role', 'warga')->where('is_active', true),
        ])
            ->when($request->string('search')->toString(), fn ($query, $search) => $query
                ->where(fn ($query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")))
            ->when($request->filled('status'), fn ($query) => $query
                ->where('is_active', $request->string('status')->toString() === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.housings.index', compact('housings'));
    }

    public function create(): View
    {
        $this->authorize('create', Housing::class);

        return view('admin.housings.form', ['housing' => new Housing]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Housing::class);
        Housing::create($this->validated($request));

        return redirect()->route('admin.housings.index')->with('success', 'Perumahan berhasil ditambahkan.');
    }

    public function edit(Housing $housing): View
    {
        $this->authorize('update', $housing);

        return view('admin.housings.form', compact('housing'));
    }

    public function update(Request $request, Housing $housing): RedirectResponse
    {
        $this->authorize('update', $housing);
        $housing->update($this->validated($request, $housing));

        return redirect()->route('admin.housings.index')->with('success', 'Perumahan berhasil diperbarui.');
    }

    public function destroy(Housing $housing): RedirectResponse
    {
        $this->authorize('delete', $housing);

        if ($housing->users()->exists() || $housing->invoices()->exists() || $housing->cashflows()->exists()) {
            return back()->with('error', 'Perumahan yang memiliki histori tidak dapat dihapus. Nonaktifkan melalui menu edit.');
        }

        $housing->delete();

        return back()->with('success', 'Perumahan berhasil dihapus.');
    }

    private function validated(Request $request, ?Housing $housing = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('housings')->ignore($housing)],
            'address' => ['required', 'string', 'max:1000'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
