<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function index(Request $request): View
    {
        $residents = User::where('housing_id', $request->user()->housing_id)
            ->where('role', 'warga')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query
                ->where(fn ($query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('house_address', 'like', "%{$search}%")))
            ->when($request->filled('status'), fn ($query) => $query
                ->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('pengurus.residents.index', compact('residents'));
    }

    public function create(): View
    {
        return view('pengurus.residents.form', ['resident' => new User(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create([
            ...$this->validated($request),
            'housing_id' => $request->user()->housing_id,
            'role' => 'warga',
        ]);

        return redirect()->route('pengurus.residents.index')->with('success', 'Warga berhasil ditambahkan.');
    }

    public function edit(User $resident): View
    {
        $this->authorize('update', $resident);

        return view('pengurus.residents.form', compact('resident'));
    }

    public function update(Request $request, User $resident): RedirectResponse
    {
        $this->authorize('update', $resident);
        $resident->update($this->validated($request, $resident));

        return redirect()->route('pengurus.residents.index')->with('success', 'Data warga berhasil diperbarui.');
    }

    public function destroy(User $resident): RedirectResponse
    {
        $this->authorize('delete', $resident);
        $resident->update(['is_active' => false]);

        return back()->with('success', 'Akun warga dinonaktifkan tanpa menghapus histori.');
    }

    public function resetPassword(Request $request, User $resident): RedirectResponse
    {
        $this->authorize('resetPassword', $resident);
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        $resident->update(['password' => $data['password']]);

        return back()->with('success', 'Password warga berhasil diubah.');
    }

    private function validated(Request $request, ?User $resident = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($resident)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($resident)],
            'phone_number' => ['required', 'string', 'max:20'],
            'house_address' => ['required', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];

        if (! $resident) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules);
    }
}
