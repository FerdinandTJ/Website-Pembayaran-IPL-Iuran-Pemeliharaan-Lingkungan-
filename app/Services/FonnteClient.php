<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class FonnteClient
{
    public function configured(): bool
    {
        return (bool) config('services.fonnte.enabled') && trim((string) config('services.fonnte.token')) !== '';
    }

    public function normalizePhone(?string $phone): ?string
    {
        // Reject lists, groups, variables and other provider target syntax.
        $phone = preg_replace('/[\s().-]/', '', $phone ?? '');
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }
        if (str_starts_with($phone, '08')) {
            $phone = '62'.substr($phone, 1);
        }

        return preg_match('/^628[0-9]{8,11}$/D', $phone) ? $phone : null;
    }

    /** Return only safe error codes; never persist tokens or response bodies. */
    public function send(string $phone, string $message): array
    {
        try {
            $response = Http::asForm()->withHeaders(['Authorization' => config('services.fonnte.token')])
                ->connectTimeout(5)->timeout(20)->withoutRedirecting()
                ->post('https://api.fonnte.com/send', [
                    'target' => $phone, 'message' => $message, 'countryCode' => '0',
                    'connectOnly' => 'true',
                ]);
        } catch (ConnectionException) {
            // The provider may have accepted the request before a timeout.
            return ['status' => 'uncertain', 'error' => 'connection_uncertain'];
        }

        if ($response->successful() && $response->json('status') === true) {
            return ['status' => 'accepted', 'error' => null];
        }
        if ($response->status() === 429 || ($response->successful() && $response->json('status') === false)) {
            return ['status' => 'pending', 'error' => $response->status() === 429 ? 'rate_limited' : 'provider_rejected'];
        }

        return [
            'status' => $response->clientError() ? 'failed' : 'uncertain',
            'error' => $response->clientError() ? 'http_'.$response->status() : 'response_uncertain',
        ];
    }
}
