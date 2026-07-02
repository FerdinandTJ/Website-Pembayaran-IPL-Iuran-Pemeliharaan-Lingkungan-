<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Housing;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManagerController extends Controller
{
    public function index(Request $request): View
    {
        $managers = User::with('housing')
            ->where('role', 'pengurus')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query
                ->where(fn ($query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")))
            ->when($request->filled('housing_id'), fn ($query) => $query->where('housing_id', $request->integer('housing_id')))
            ->when($request->filled('status'), fn ($query) => $query
                ->where('is_active', $request->string('status')->toString() === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.managers.index', [
            'managers' => $managers,
            'housings' => Housing::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.managers.form', [
            'manager' => new User(['is_active' => true]),
            'housings' => Housing::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        User::create([...$data, 'role' => 'pengurus']);

        return redirect()->route('admin.managers.index')->with('success', 'Akun pengurus berhasil dibuat.');
    }

    public function edit(User $manager): View
    {
        $this->authorize('update', $manager);

        return view('admin.managers.form', [
            'manager' => $manager,
            'housings' => Housing::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $manager): RedirectResponse
    {
        $this->authorize('update', $manager);
        $manager->update($this->validated($request, $manager));

        return redirect()->route('admin.managers.index')->with('success', 'Akun pengurus berhasil diperbarui.');
    }

    public function destroy(User $manager): RedirectResponse
    {
        $this->authorize('delete', $manager);
        $manager->update(['is_active' => false]);

        return back()->with('success', 'Akun pengurus dinonaktifkan.');
    }

    public function resetPassword(Request $request, User $manager): RedirectResponse
    {
        $this->authorize('resetPassword', $manager);
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        $manager->update(['password' => $data['password']]);

        return back()->with('success', 'Password pengurus berhasil diubah.');
    }

    private function validated(Request $request, ?User $manager = null): array
    {
        $rules = [
            'housing_id' => ['required', Rule::exists('housings', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($manager)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($manager)],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ];

        if (! $manager) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules);
    }
}
