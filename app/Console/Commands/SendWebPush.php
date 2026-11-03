<?php

namespace App\Console\Commands;

use App\Models\PushDelivery;
use App\Services\PushSender;
use App\Services\WebPushKeys;
use Illuminate\Console\Command;

class SendWebPush extends Command
{
    protected $signature = 'app:send-web-push {--limit=100}';

    protected $description = 'Kirim antrean Web Push tanpa mengganggu transaksi tagihan.';

    public function handle(PushSender $sender): int
    {
        if (! app(WebPushKeys::class)->configured()) {
            $this->warn('Kunci Web Push belum dikonfigurasi.');

            return self::SUCCESS;
        }
        $ids = PushDelivery::whereIn('status', ['pending', 'processing'])->where('available_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('claimed_until')->orWhere('claimed_until', '<=', now()))
            ->orderBy('id')->limit(max(1, min(1000, (int) $this->option('limit'))))->pluck('id');
        foreach ($ids as $id) {
            $sender->send($id);
        }
        $this->info('Pemeriksaan antrean Web Push selesai: '.$ids->count());

        return self::SUCCESS;
    }
}
