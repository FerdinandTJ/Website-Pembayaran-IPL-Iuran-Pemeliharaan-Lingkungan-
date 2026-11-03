<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\User;
use App\Services\HouseService;
use App\Services\SessionRevoker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function index(Request $request): View
    {
        $residents = User::with('house')->where('housing_id', $request->user()->housing_id)
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

    public function create(Request $request): View
    {
        return view('pengurus.residents.form', ['resident' => new User(['is_active' => true]), 'houses' => House::where('housing_id', $request->user()->housing_id)->orderBy('code')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data) {
            $resident = User::create(array_diff_key($data, ['house_id' => true, 'is_responsible' => true]) + ['housing_id' => $request->user()->housing_id, 'role' => 'warga']);
            if ($request->filled('house_id')) {
                app(HouseService::class)->link($resident, (int) $data['house_id'], $request->boolean('is_responsible'), $request->user());
            }
        });

        return redirect()->route('pengurus.residents.index')->with('success', 'Warga berhasil ditambahkan.');
    }

    public function edit(User $resident): View
    {
        $this->authorize('update', $resident);

        return view('pengurus.residents.form', ['resident' => $resident, 'houses' => House::where('housing_id', $resident->housing_id)->orderBy('code')->get()]);
    }

    public function update(Request $request, User $resident): RedirectResponse
    {
        $this->authorize('update', $resident);
        $data = $this->validated($request, $resident);
        DB::transaction(function () use ($request, $resident, $data) {
            $resident->update(array_diff_key($data, ['house_id' => true, 'is_responsible' => true]));
            if (! $resident->is_active) {
                app(SessionRevoker::class)->revoke($resident);
            }
            if ($request->exists('house_id')) {
                app(HouseService::class)->link($resident, ! empty($data['house_id']) ? (int) $data['house_id'] : null, $request->boolean('is_responsible'), $request->user());
            }
        });

        return redirect()->route('pengurus.residents.index')->with('success', 'Data warga berhasil diperbarui.');
    }

    public function destroy(User $resident): RedirectResponse
    {
        $this->authorize('delete', $resident);
        DB::transaction(function () use ($resident) {
            $resident->update(['is_active' => false]);
            app(SessionRevoker::class)->revoke($resident);
        });

        return back()->with('success', 'Akun warga dinonaktifkan tanpa menghapus histori.');
    }

    public function resetPassword(Request $request, User $resident): RedirectResponse
    {
        $this->authorize('resetPassword', $resident);
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        DB::transaction(function () use ($resident, $data) {
            $resident->update(['password' => $data['password']]);
            app(SessionRevoker::class)->revoke($resident);
        });

        return back()->with('success', 'Password warga berhasil diubah.');
    }

    private function validated(Request $request, ?User $resident = null): array
    {
        $rules = [
            'house_id' => ['nullable', 'integer', Rule::exists('houses', 'id')->where('housing_id', $request->user()->housing_id)],
            'is_responsible' => ['sometimes', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($resident)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($resident)],
            'phone_number' => ['required', 'string', 'max:20'],
            'house_address' => ['nullable', 'required_without:house_id', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];

        if (! $resident) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules);
    }
}
