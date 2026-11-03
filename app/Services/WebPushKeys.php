<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class WebPushKeys
{
    public function keys(): array
    {
        if (config('webpush.public_key') && config('webpush.private_key')) {
            return ['publicKey' => config('webpush.public_key'), 'privateKey' => config('webpush.private_key')];
        }
        try {
            $disk = Storage::disk('local');
            if (! $disk->exists(config('webpush.key_file'))) {
                return [];
            }

            return json_decode($disk->get(config('webpush.key_file')), true) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function configured(): bool
    {
        $keys = $this->keys();

        return ! empty($keys['publicKey']) && ! empty($keys['privateKey']);
    }
}
