<?php

namespace App\Console\Commands;

use App\Models\WhatsAppDelivery;
use App\Services\FonnteClient;
use App\Services\WhatsAppSender;
use Illuminate\Console\Command;

class SendWhatsApp extends Command
{
    protected $signature = 'app:send-whatsapp {--limit=20}';

    protected $description = 'Proses antrean pengingat WhatsApp melalui Fonnte.';

    public function handle(FonnteClient $client, WhatsAppSender $sender): int
    {
        if (! $client->configured()) {
            $this->warn('WhatsApp belum aktif. Atur FONNTE_ENABLED dan FONNTE_TOKEN.');

            return self::SUCCESS;
        }
        // A crashed sender may already have sent its request. Do not resend blindly.
        WhatsAppDelivery::where('status', 'processing')->where('claimed_until', '<=', now())
            ->update(['status' => 'uncertain', 'last_error' => 'worker_interrupted', 'claimed_until' => null]);
        $ids = WhatsAppDelivery::where('status', 'pending')->where('available_at', '<=', now())
            ->orderBy('id')->limit(max(1, min(100, (int) $this->option('limit'))))->pluck('id');
        foreach ($ids as $id) {
            $sender->send($id);
        }
        $this->info('Antrean diperiksa: '.$ids->count());
        $this->table(['Status', 'Jumlah'], WhatsAppDelivery::selectRaw('status, COUNT(*) as total')->groupBy('status')->get()->map(fn ($row) => [$row->status, $row->total])->all());

        return self::SUCCESS;
    }
}
