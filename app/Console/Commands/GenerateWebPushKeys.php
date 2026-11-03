<?php

namespace App\Console\Commands;

use App\Services\WebPushKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Minishlink\WebPush\VAPID;

class GenerateWebPushKeys extends Command
{
    protected $signature = 'app:web-push-keys';

    protected $description = 'Buat kunci VAPID sekali dan simpan privat tanpa menampilkannya.';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $path = config('webpush.key_file');
        if (app(WebPushKeys::class)->configured() || $disk->exists($path)) {
            $this->info('Kunci sudah tersedia. Tidak ada kunci yang diganti.');

            return self::SUCCESS;
        }
        if (! $disk->put($path, json_encode(VAPID::createVapidKeys(), JSON_THROW_ON_ERROR))) {
            $this->error('Kunci gagal disimpan.');

            return self::FAILURE;
        }
        chmod($disk->path($path), 0600);
        $this->info('Kunci VAPID dibuat pada private storage. Jangan publikasikan atau menggantinya sembarangan.');

        return self::SUCCESS;
    }
}
