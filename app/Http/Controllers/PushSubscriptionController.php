<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\HousingNotification;
use App\Services\PushEndpoint;
use App\Services\WebPushKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PushSubscriptionController extends Controller
{
    public function config(WebPushKeys $keys)
    {
        return response()->json(['configured' => $keys->configured(), 'public_key' => $keys->keys()['publicKey'] ?? null]);
    }

    public function status(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2048']]);
        $active = PushSubscription::where('user_id', $request->user()->id)->where('housing_id', $request->user()->housing_id)
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))->exists();

        return response()->json(['active' => $active]);
    }

    public function store(Request $request, WebPushKeys $keys, PushEndpoint $endpoints)
    {
        abort_unless($keys->configured(), 503, 'Web Push belum dikonfigurasi.');
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:128'], 'keys.auth' => ['required', 'string', 'max:64'],
        ]);
        if (! $endpoints->allowed($data['endpoint'])) {
            throw ValidationException::withMessages(['endpoint' => 'Layanan push browser tidak didukung.']);
        }
        foreach (['p256dh' => 65, 'auth' => 16] as $key => $length) {
            $encoded = $data['keys'][$key];
            $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
            if (! preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $encoded) || $decoded === false || strlen($decoded) !== $length
                || ($key === 'p256dh' && ord($decoded[0]) !== 4)) {
                throw ValidationException::withMessages(['keys.'.$key => 'Kunci langganan tidak valid.']);
            }
        }
        DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $hash = hash('sha256', $data['endpoint']);
            $existing = PushSubscription::where('endpoint_hash', $hash)->lockForUpdate()->first();
            abort_if($existing && $existing->user_id !== $request->user()->id, 409, 'Perangkat perlu didaftarkan ulang.');
            abort_if(! $existing && PushSubscription::where('user_id', $request->user()->id)->count() >= 10, 422, 'Maksimal 10 perangkat. Nonaktifkan perangkat lama terlebih dahulu.');
            $attributes = [
                'user_id' => $request->user()->id, 'housing_id' => $request->user()->housing_id,
                'endpoint' => $data['endpoint'], 'public_key' => $data['keys']['p256dh'], 'auth_token' => $data['keys']['auth'],
                'session_hash' => hash('sha256', $request->session()->getId()),
            ];
            $record = $existing ?? PushSubscription::createOrFirst(['endpoint_hash' => $hash], $attributes);
            abort_if($record->user_id !== $request->user()->id, 409, 'Perangkat perlu didaftarkan ulang.');
            $record->update($attributes);
        });

        return response()->json(['active' => true], 201);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2048']]);
        PushSubscription::where('user_id', $request->user()->id)->where('endpoint_hash', hash('sha256', $data['endpoint']))->delete();

        return response()->json(['active' => false]);
    }

    public function test(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2048']]);
        $subscription = PushSubscription::where('user_id', $request->user()->id)->where('housing_id', $request->user()->housing_id)
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))->firstOrFail();
        DB::transaction(fn () => $request->user()->notify(new HousingNotification($request->user()->housing_id, 'push-test:'.Str::uuid(), [
            'event' => 'push_test', 'title' => 'Notifikasi uji perangkat', 'message' => 'Permintaan notifikasi uji telah masuk antrean Web Push.',
            'push_subscription_id' => $subscription->id,
        ])));

        return response()->json(['message' => 'Notifikasi uji masuk antrean. Tunggu sekitar satu menit; pengiriman bergantung pada browser dan koneksi perangkat.']);
    }
}
