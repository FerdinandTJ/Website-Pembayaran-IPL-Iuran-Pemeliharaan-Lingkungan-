<?php

namespace App\Services;

use App\Models\Receipt;
use Carbon\Carbon;

class WhatsAppReceiptMessage
{
    public function render(Receipt $receipt, string $url): string
    {
        // Use the issued receipt snapshot, including names and amounts, on every retry.
        $data = $receipt->snapshot;
        $confirmed = ! empty($data['confirmed_at'])
            ? Carbon::parse($data['confirmed_at'])->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB'
            : 'Tidak tercatat';
        $lines = [
            '*KUITANSI PEMBAYARAN IPL*',
            $this->text($data['housing']),
            '────────────────────',
            'No. kuitansi: '.$receipt->number,
            'Tanggal: '.$confirmed,
            'Status: *LUNAS*',
            '',
            '*DITERIMA DARI*',
            $this->text($data['resident']),
            'Rumah: '.$this->text($data['address'] ?: '—', 250),
            '',
            '*RINCIAN PEMBAYARAN*',
        ];
        if (! empty($data['invoices'])) {
            foreach ($data['invoices'] as $index => $item) {
                $lines[] = ($index + 1).'. '.$this->text($item['title']);
                $lines[] = 'Periode: '.$this->text($item['period']);
                $lines[] = 'Nominal: '.$this->money($item['amount']);
                $lines[] = '';
            }
        } else {
            $lines[] = $this->text($data['title']);
            $lines[] = 'Periode: '.$this->text($data['period']);
            foreach (array_slice($data['components'] ?? [], 0, 30) as $item) {
                $lines[] = '• '.$this->text($item['name']).': '.$this->money($item['amount']);
            }
            if (count($data['components'] ?? []) > 30) {
                $lines[] = 'Rincian lengkap tersedia pada tautan kuitansi.';
            }
            $lines[] = '';
        }
        array_push($lines,
            '────────────────────',
            '*TOTAL DIBAYAR*',
            '*'.$this->money($data['amount']).'*',
            '',
            'Metode: '.($data['method'] === 'cash' ? 'Tunai / Cash' : 'Transfer Bank'),
            'Dikonfirmasi oleh: '.$this->text($data['officer']),
        );
        if (! empty($data['note'])) {
            $lines[] = 'Catatan: '.$this->text($data['note'], 400);
        }
        array_push($lines,
            '',
            '*LIHAT KUITANSI*',
            $url,
            '',
            'Pembayaran telah diterima dan dicatat.',
            'Terima kasih atas partisipasi Anda dalam menjaga lingkungan.',
            '',
            '_Kuitansi elektronik diterbitkan oleh EnviroPay._',
        );

        return implode("\n", $lines);
    }

    private function text(string $value, int $limit = 160): string
    {
        // Keep user-supplied line breaks and WhatsApp markup out of the layout.
        $value = preg_replace('/[\p{C}\p{Z}\s]+/u', ' ', $value);
        $value = str_replace(['*', '_', '~', '`'], '', $value);

        return mb_strimwidth(trim($value), 0, $limit, '…');
    }

    private function money(string|int|float $amount): string
    {
        $fraction = explode('.', (string) $amount, 2)[1] ?? '';

        return 'Rp '.number_format((float) $amount, preg_match('/[1-9]/', $fraction) ? 2 : 0, ',', '.');
    }
}
