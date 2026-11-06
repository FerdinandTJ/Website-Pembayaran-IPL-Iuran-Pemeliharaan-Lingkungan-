# Notifikasi dan WhatsApp Fonnte

[Kembali ke README](../README.md) · [Panduan penggunaan](PANDUAN-PENGGUNA.md) · [Deployment](DEPLOYMENT.md)

## Lonceng aplikasi

Lonceng tersedia untuk pengurus dan warga aktif. Dropdown menampilkan 10 notifikasi terbaru; **Lihat semua notifikasi** membuka daftar lengkap dan filter belum dibaca. Membuka notifikasi menandainya dibaca dan mengarahkan ke halaman terkait. Akses tetap dibatasi per akun dan perumahan. Pembayaran gabungan menghasilkan satu notifikasi per kejadian, bukan per tagihan anggota.

Notifikasi dan antrean WhatsApp disimpan dalam transaksi database yang sama dengan tagihan. Permintaan ke Fonnte dilakukan oleh scheduler setelah transaksi selesai. Gangguan Fonnte tidak membatalkan pembuatan tagihan atau pembayaran. Jumlah belum dibaca diperbarui setiap 30 detik saat tab terlihat.

Fitur aktivasi notifikasi layar HP / Web Push telah dihapus. Tidak ada permintaan izin notifikasi browser. Worker lama pada `public/service-worker.js` hanya mencabut langganan dan melepaskan dirinya saat browser memperbaruinya. Tabel push lama dipertahankan untuk kompatibilitas riwayat migrasi, tetapi tidak digunakan untuk pengiriman.

## Persiapan Fonnte

1. Buat akun di [Fonnte](https://fonnte.com/), tambahkan perangkat pengirim dan hubungkan nomor WhatsApp sesuai petunjuk dashboard.
2. Ambil **token perangkat** dari dashboard Fonnte. Pastikan perangkat terhubung dan paket/kuotanya mendukung pengiriman API.
3. Isi konfigurasi server pada `.env`:

```dotenv
FONNTE_ENABLED=true
FONNTE_TOKEN=token_perangkat_anda
APP_URL=https://ipl.domain-anda.id
```

Token hanya disimpan pada server. Jangan masukkan `.env` ke Git atau mengirim token melalui chat. Satu instalasi memakai satu token/nomor pengirim untuk seluruh perumahan. `FONNTE_ENABLED=false` menonaktifkan pembuatan dan pengiriman antrean WhatsApp; lonceng aplikasi tetap berjalan. Tagihan yang diterbitkan saat WhatsApp belum aktif tidak dikirim retroaktif.

4. Terapkan pembaruan dan jalankan scheduler:

```bash
php artisan config:clear
php artisan migrate
npm run build
php artisan schedule:work
```

Pada production, gunakan `php artisan config:cache` setelah konfigurasi diubah dan gunakan cron berikut sebagai pengganti `schedule:work` (sesuaikan path):

```cron
* * * * * cd /path/to/project && /path/to/php artisan schedule:run >> /path/to/project/storage/logs/scheduler.log 2>&1
```

Nomor WhatsApp warga diisi pengurus melalui **Data Warga**, atau kolom `phone_number` pada impor CSV. Gunakan format `081234567890`, `6281234567890`, atau `+6281234567890`. Spasi, tanda hubung, titik dan kurung dinormalisasi. Hanya satu nomor seluler Indonesia per warga yang diterima; daftar nomor, ID grup, dan sintaks variabel Fonnte ditolak. Nomor kosong/tidak valid dilewati dan dicatat sebagai `invalid_phone`.

Server lokal dapat menghubungi Fonnte selama terhubung internet. Tautan tagihan harus memakai `APP_URL` yang dapat dibuka warga; localhost di HP warga tidak menunjuk ke komputer server. Untuk pengingat otomatis, komputer/server dan scheduler perlu terus berjalan.

## Waktu dan isi pesan

WhatsApp dikirim hanya untuk:

- Tagihan baru (`issued`).
- H-3 sebelum jatuh tempo (`due_soon`).
- H+1 setelah jatuh tempo (`overdue`).
- Kuitansi pembayaran setelah transfer diverifikasi (`verified`) atau cash diterima (`cash_paid`), termasuk satu kuitansi untuk satu pembayaran gabungan (`batch_verified`/`batch_cash_paid`).

Pemeriksaan pengingat berjalan **pukul 08.00 WIB** setiap hari. Antrean pengiriman diproses **setiap menit**, maksimal 20 pesan per eksekusi bawaan, sehingga pengiriman bisa tertunda saat antrean besar. Pesan mencantumkan nama warga, perumahan, judul, periode, nominal, tanggal jatuh tempo dan tautan tagihan yang memerlukan login. Kuitansi dikirim sebagai pesan teks dengan format WhatsApp, tanpa lampiran. Nomor kuitansi, nama, alamat, rincian, total, metode, waktu konfirmasi WIB, catatan dan pengurus diambil dari snapshot kuitansi yang diterbitkan saat pembayaran disahkan. Satu kuitansi hanya memiliki satu antrean, termasuk pembayaran gabungan. Perubahan data setelah penerbitan tidak mengubah isi kuitansi. Tautan mengarah ke halaman kuitansi yang memerlukan login. Unggah bukti yang belum diverifikasi, penolakan pembayaran, pengumuman, dan kejadian lain tetap memakai lonceng aplikasi.

Untuk pengingat, hanya tagihan `unpaid`/`rejected` milik warga dan perumahan aktif yang memenuhi syarat. Kuitansi hanya dikirim untuk pembayaran `verified` yang memiliki kuitansi terbit dan tetap dimiliki penerima dalam perumahan yang sama. Pengirim memeriksa kembali kepemilikan, status tagihan, akun dan perumahan sebelum menghubungi Fonnte. Pengingat untuk tagihan lunas atau menunggu verifikasi, serta perubahan tanggal jatuh tempo, dilewati. Semua antrean berumur lebih dari satu hari dilewati. Kuitansi pembayaran lama sebelum fitur diaktifkan tidak dikirim secara retroaktif. Pengingat tanggal lampau yang terlewat tidak dikirim susulan secara massal. Setiap kejadian dideduplikasi; menjalankan pemeriksaan berulang tidak membuat pesan baru untuk kejadian yang sama.

## Pemeriksaan dan uji pengiriman

Periksa jadwal:

```bash
php artisan schedule:list
```

Untuk uji end-to-end, buat satu akun warga dengan nomor WhatsApp uji milik Anda, lalu terbitkan satu tagihan hanya untuk akun itu. Setelah itu proses antrean:

```bash
php artisan app:send-whatsapp --limit=1
```

Perintah ini mengirim pesan nyata ke antrean tertua yang memenuhi syarat. Gunakan database uji terpisah bila sudah ada antrean warga lain. Untuk menguji kuitansi, verifikasi pembayaran transfer akun uji atau catat pembayaran cash, lalu jalankan perintah pengiriman yang sama. Jangan menggunakan pembayaran warga nyata sebagai percobaan.

Untuk memproses pengingat yang sesuai tanggal hari ini secara manual:

```bash
php artisan app:send-invoice-reminders
php artisan app:send-whatsapp
```

Perintah pengiriman menampilkan ringkasan status. Riwayat teknis ada pada tabel `whatsapp_deliveries`, tanpa menyimpan token, isi pesan atau respons mentah provider:

| Status | Makna |
| --- | --- |
| `pending` | Menunggu pengiriman atau waktu percobaan ulang. |
| `processing` | Sedang diproses oleh satu pengirim. |
| `accepted` | API Fonnte menerima permintaan; belum membuktikan pesan sampai/dibaca warga. |
| `skipped` | Tidak memenuhi syarat, nomor salah, atau antrean kedaluwarsa. |
| `failed` | Kesalahan HTTP 4xx permanen atau batas percobaan tercapai. |
| `uncertain` | Timeout, respons tidak jelas, HTTP 5xx, atau proses pengirim terputus. Periksa riwayat di dashboard Fonnte sebelum tindakan lanjutan. |

Penolakan eksplisit dari provider (`status=false`) dan HTTP 429 dicoba ulang dengan jeda bertambah, maksimal 5 percobaan. Timeout/hasil tidak pasti tidak dikirim ulang otomatis untuk mengurangi risiko pesan ganda, karena Fonnte mungkin sudah menerima permintaan. Token salah, perangkat terputus, dan kuota habis perlu diperiksa melalui dashboard Fonnte. Setelah memperbaiki nomor/konfigurasi, operator dapat meninjau record gagal sebelum menyiapkan pengiriman ulang; tidak ada tombol kirim ulang massal.

Integrasi mengikuti [API pengiriman Fonnte](https://docs.fonnte.com/api-send-message/): form POST ke `https://api.fonnte.com/send`, header `Authorization` berisi token langsung tanpa `Bearer`. Pengujian otomatis memalsukan respons API, termasuk deduplikasi, rollback, status tagihan, isolasi perumahan, nomor, retry, dan timeout. Pengiriman nyata perlu diuji dengan akun/perangkat Fonnte Anda.

## Contoh format kuitansi WhatsApp

Contoh berikut menggunakan data fiktif. WhatsApp menampilkan teks di antara tanda `*` sebagai huruf tebal. Susunan vertikal menjaga pesan mudah dibaca pada layar HP tanpa tabel dengan spasi perataan.

```text
*KUITANSI PEMBAYARAN IPL*
Perumahan Melati
────────────────────
No. kuitansi: KW-1-12
Tanggal: 06/11/2026 09:30 WIB
Status: *LUNAS*

*DITERIMA DARI*
Budi Santoso
Rumah: A-01

*RINCIAN PEMBAYARAN*
IPL November
Periode: November 2026
• Keamanan: Rp 100.000
• Kebersihan: Rp 50.000

────────────────────
*TOTAL DIBAYAR*
*Rp 150.000*

Metode: Transfer Bank
Dikonfirmasi oleh: Pengurus Melati

*LIHAT KUITANSI*
https://ipl.domain-anda.id/invoices/12/receipt

Pembayaran telah diterima dan dicatat.
Terima kasih atas partisipasi Anda dalam menjaga lingkungan.

_Kuitansi elektronik diterbitkan oleh EnviroPay._
```
