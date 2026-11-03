<?php

namespace App\Services;

use App\Models\House;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class HouseService
{
    public function record(House $house, User $actor, array $before): void
    {
        $house->histories()->create(['changed_by' => $actor->id, 'data' => ['before' => $before, 'after' => $house->fresh()->only($house->getFillable())]]);
    }

    // Call inside a transaction. A user has exactly one current house;
    // changing it never updates old invoices or receipts.
    public function link(User $resident, ?int $houseId, bool $responsible, User $actor): void
    {
        $resident = User::where('housing_id', $actor->housing_id)->where('role', 'warga')->lockForUpdate()->findOrFail($resident->id);
        $house = $houseId ? House::where('housing_id', $actor->housing_id)->lockForUpdate()->findOrFail($houseId) : null;
        $oldHouses = House::where('housing_id', $actor->housing_id)->where('responsible_user_id', $resident->id)
            ->when($houseId, fn ($q) => $q->where('id', '!=', $houseId))->lockForUpdate()->get();
        foreach ($oldHouses as $old) {
            $before = $old->only($old->getFillable());
            $old->update(['responsible_user_id' => null]);
            $this->record($old, $actor, $before);
        }
        $resident->update(['house_id' => $house?->id, 'house_address' => $house?->address ?? $resident->house_address]);
        if ($house) {
            $before = $house->only($house->getFillable());
            if ($responsible) {
                $house->update(['responsible_user_id' => $resident->id]);
            } elseif ($house->responsible_user_id === $resident->id) {
                $house->update(['responsible_user_id' => null]);
            }
            $this->record($house, $actor, $before);
        }
    }

    public function save(House $house, array $data, User $actor): House
    {
        $before = $house->exists ? $house->only($house->getFillable()) : [];
        $payer = ! empty($data['responsible_user_id']) ? User::where('housing_id', $actor->housing_id)->where('role', 'warga')
            ->lockForUpdate()->findOrFail($data['responsible_user_id']) : null;
        if ($payer?->house_id && $payer->house_id !== $house->id) {
            throw ValidationException::withMessages(['responsible_user_id' => 'Warga masih terhubung ke rumah lain. Pindahkan melalui form Edit Warga terlebih dahulu.']);
        }
        $house->fill($data + ['housing_id' => $actor->housing_id])->save();
        if ($payer) {
            $payer->update(['house_id' => $house->id, 'house_address' => $house->address]);
        }
        $house->residents()->update(['house_address' => $house->address]);
        $this->record($house, $actor, $before);

        return $house;
    }
}
