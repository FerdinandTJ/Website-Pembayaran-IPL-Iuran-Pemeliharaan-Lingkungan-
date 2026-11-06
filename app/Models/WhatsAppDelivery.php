<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppDelivery extends Model
{
    public const STATUSES = ['pending' => 'Antre', 'processing' => 'Sedang Diproses', 'accepted' => 'Diterima Fonnte',
        'failed' => 'Gagal', 'skipped' => 'Dilewati', 'uncertain' => 'Perlu Diperiksa'];

    public const EVENTS = ['issued' => 'Tagihan Baru', 'due_soon' => 'Pengingat H-3', 'overdue' => 'Pengingat H+1',
        'verified' => 'Kuitansi Transfer', 'cash_paid' => 'Kuitansi Tunai',
        'batch_verified' => 'Kuitansi Gabungan Transfer', 'batch_cash_paid' => 'Kuitansi Gabungan Tunai'];

    protected $table = 'whatsapp_deliveries';

    protected $guarded = [];

    protected $hidden = ['recipient_phone'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'available_at' => 'datetime', 'claimed_until' => 'datetime', 'accepted_at' => 'datetime', 'last_attempt_at' => 'datetime', 'recipient_phone' => 'encrypted'];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Status Tidak Dikenal';
    }

    public function getStatusColorAttribute(): string
    {
        return ['pending' => 'secondary', 'processing' => 'info', 'accepted' => 'success',
            'failed' => 'danger', 'skipped' => 'secondary', 'uncertain' => 'warning'][$this->status] ?? 'secondary';
    }

    public function getEventLabelAttribute(): string
    {
        return self::EVENTS[$this->event] ?? 'Pesan WhatsApp';
    }

    public function getIssueLabelAttribute(): string
    {
        if (! $this->last_error) {
            return match ($this->status) {
                'accepted' => 'Permintaan diterima oleh Fonnte.',
                'pending' => 'Menunggu jadwal pemrosesan.',
                'processing' => 'Permintaan sedang diproses.',
                default => 'Belum ada keterangan kendala.',
            };
        }

        return [
            'invalid_phone' => 'Nomor WhatsApp tidak valid saat pemeriksaan.',
            'recipient_inactive_or_moved' => 'Akun atau perumahan tidak aktif, atau penerima telah berubah.',
            'invoice_not_payable' => 'Tagihan tidak lagi memerlukan pengingat pembayaran.',
            'receipt_not_valid' => 'Kuitansi atau status pembayaran tidak lagi sesuai.',
            'reminder_expired_or_changed' => 'Antrean melewati batas waktu atau jatuh tempo berubah.',
            'rate_limited' => 'Batas permintaan Fonnte tercapai.',
            'provider_rejected' => 'Permintaan ditolak oleh Fonnte.',
            'connection_uncertain' => 'Koneksi terputus atau melewati batas waktu; hasil belum pasti.',
            'response_uncertain' => 'Respons Fonnte belum dapat dipastikan.',
            'worker_interrupted' => 'Proses pengiriman terhenti sebelum hasil tercatat.',
        ][$this->last_error] ?? (preg_match('/^http_(4[0-9]{2})$/D', $this->last_error, $matches)
            ? 'Permintaan ditolak (HTTP '.$matches[1].').' : 'Kendala pengiriman perlu diperiksa.');
    }

    public function getActionHintAttribute(): string
    {
        if ($this->status === 'accepted') {
            return 'Diterima Fonnte belum membuktikan pesan sampai atau dibaca warga. Periksa riwayat pada dashboard Fonnte bila warga belum menerima pesan.';
        }
        if ($this->status === 'uncertain') {
            return 'Permintaan mungkin sudah diterima Fonnte. Periksa riwayat Fonnte sebelum mempertimbangkan pengiriman ulang agar pesan tidak ganda.';
        }
        if ($this->last_error === 'invalid_phone') {
            return 'Periksa dan perbarui nomor melalui Data Warga. Perubahan nomor tidak mengirim ulang pesan ini secara otomatis.';
        }
        if ($this->status === 'skipped') {
            return 'Periksa status akun, tagihan, atau kuitansi terkait. Pesan dilewati sesuai kondisi saat pemrosesan; tidak dijadwalkan ulang otomatis.';
        }
        if ($this->status === 'pending') {
            return $this->last_error ? 'Permintaan akan diperiksa kembali sesuai jadwal, hingga batas percobaan dan masa berlaku antrean. Periksa perangkat dan kuota di Fonnte.'
                : 'Pesan menunggu pemrosesan. Pastikan pengiriman WhatsApp aktif dan scheduler berjalan.';
        }
        if ($this->status === 'processing') {
            return 'Tunggu hasil pemrosesan. Jika proses tidak selesai, periksa kembali riwayat setelah scheduler memeriksa antrean.';
        }

        return 'Periksa konfigurasi pengiriman, perangkat, serta kuota pada dashboard Fonnte. Tidak tersedia pengiriman ulang dari halaman ini.';
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
