<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\User;
use App\Services\HouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HouseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['occupancy' => ['nullable', Rule::in(array_keys(House::OCCUPANCIES))], 'search' => ['nullable', 'string', 'max:255']]);
        $houses = House::with('responsible')->where('housing_id', $request->user()->housing_id)
            ->when($request->input('search'), fn ($q, $s) => $q->where(fn ($q) => $q->where('code', 'like', "%{$s}%")->orWhere('address', 'like', "%{$s}%")))
            ->when($request->input('occupancy'), fn ($q, $s) => $q->where('occupancy', $s))->orderBy('code')->paginate(15)->withQueryString();

        return view('pengurus.houses.index', compact('houses'));
    }

    public function create(Request $request)
    {
        return $this->form($request, new House(['occupancy' => 'occupied', 'vacant_billing' => 'full']));
    }

    public function edit(Request $request, House $house)
    {
        abort_unless($house->housing_id === $request->user()->housing_id, 403);

        return $this->form($request, $house);
    }

    private function form(Request $request, House $house)
    {
        $residents = User::where('housing_id', $request->user()->housing_id)->where('role', 'warga')
            ->where(fn ($q) => $q->whereNull('house_id')->when($house->exists, fn ($q) => $q->orWhere('house_id', $house->id)))->orderBy('name')->get();
        $histories = $house->exists ? $house->histories()->with('actor')->paginate(10) : null;

        return view('pengurus.houses.form', compact('house', 'residents', 'histories'));
    }

    public function store(Request $request, HouseService $service)
    {
        return $this->persist($request, new House, $service);
    }

    public function update(Request $request, House $house, HouseService $service)
    {
        abort_unless($house->housing_id === $request->user()->housing_id, 403);

        return $this->persist($request, $house, $service);
    }

    private function persist(Request $request, House $house, HouseService $service)
    {
        $request->merge(['code' => mb_strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('houses')->where('housing_id', $request->user()->housing_id)->ignore($house)],
            'address' => ['required', 'string', 'max:1000'], 'occupancy' => ['required', Rule::in(array_keys(House::OCCUPANCIES))],
            'owner_name' => ['nullable', 'string', 'max:255'], 'occupant_name' => ['nullable', 'string', 'max:255'],
            'responsible_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('housing_id', $request->user()->housing_id)->where('role', 'warga')],
            'vacant_billing' => ['exclude_unless:occupancy,vacant', 'required', Rule::in(array_keys(House::BILLING))],
            'vacant_amount' => ['exclude_unless:occupancy,vacant', 'exclude_unless:vacant_billing,custom', 'required', 'integer', 'min:1', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['occupancy'] === 'vacant') {
            $data['vacant_amount'] = $data['vacant_billing'] === 'custom' ? $data['vacant_amount'] : null;
        } elseif (! $house->exists) {
            $data['vacant_billing'] = 'full';
            $data['vacant_amount'] = null;
        }
        // Non-vacant houses retain earlier vacancy settings without applying
        // or accepting changes to those settings until the house is vacant.

        DB::transaction(function () use ($house, $data, $request, $service) {
            if ($house->exists) {
                $house = House::lockForUpdate()->findOrFail($house->id);
            }
            $service->save($house, $data, $request->user());
        });

        return redirect()->route('pengurus.houses.index')->with('success', 'Data rumah dan histori berhasil disimpan.');
    }
}
