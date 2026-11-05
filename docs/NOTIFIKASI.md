# Notifikasi dan Web Push

[Kembali ke README](../README.md) · [Panduan penggunaan](PANDUAN-PENGGUNA.md) · [Deployment](DEPLOYMENT.md)

## Notifikasi dan Pengingat

Lonceng tersedia untuk pengurus dan warga aktif. Buka lonceng untuk melihat 10 notifikasi terbaru atau **Lihat semua notifikasi** untuk daftar lengkap dan filter belum dibaca. Menekan satu notifikasi menandainya dibaca dan membuka halaman terkait. Notifikasi pengurus dari perumahan sebelumnya tidak dapat diakses setelah ia dipindahkan.

Pembayaran gabungan mengirim satu notifikasi per pembayaran/kejadian kepada tiap penerima, bukan satu notifikasi untuk setiap tagihan anggotanya. Tautan membuka rincian pembayaran gabungan.

Notifikasi database disimpan dalam transaksi yang sama dengan perubahan tagihan/pembayaran, tanpa queue worker. Pengiriman ganda untuk kejadian yang sama dicegah oleh unique key; unggah ulang bukti dihitung sebagai kejadian baru. Pemberitahuan bukti pembayaran dikirim kepada semua pengurus aktif dalam perumahan tersebut. Notifikasi tagihan baru hanya berlaku untuk tagihan yang dibuat setelah fitur dipasang.

Jumlah belum dibaca diperbarui setiap 30 detik ketika tab browser terlihat. Isi dropdown dimuat saat dibuka dan diperbarui selama tetap terbuka. Web Push opsional tersedia melalui pengaturan Profil; tidak menggunakan WebSocket, email, atau WhatsApp.

Pengingat otomatis dijadwalkan **setiap hari pukul 08.00 WIB**, pada H-3 dan H+1 jatuh tempo, hanya untuk tagihan `unpaid`/`rejected` milik warga dan perumahan aktif. Setiap jenis pengingat hanya dikirim sekali per tagihan. Pengingat tanggal lampau yang terlewat tidak dikirim ulang secara massal. Tagihan lama dapat menerima pengingat jika jadwalnya sesuai.

Untuk development, biarkan proses scheduler berjalan di terminal:

```bash
php artisan schedule:work
```

Untuk production, tambahkan cron yang menjalankan scheduler setiap menit. Ganti `/path/to/project` dan `/path/to/php` dengan lokasi deployment dan PHP CLI yang dipakai:

```cron
* * * * * cd /path/to/project && /path/to/php artisan schedule:run >> /path/to/project/storage/logs/scheduler.log 2>&1
```

Verifikasi jadwal dan jalankan pemeriksaan pengingat secara manual bila diperlukan:

```bash
php artisan schedule:list
php artisan app:send-invoice-reminders
```

Perintah manual mengirim pengingat nyata yang sesuai tanggal hari ini; aman diulang karena deduplikasi. Periksa `storage/logs/laravel.log` dan output scheduler jika pengingat gagal. Pastikan proses scheduler memakai konfigurasi database yang sama dengan aplikasi web. Backup database dan private storage secara berkala.

## Notifikasi Layar HP / Web Push

Fitur Web Push mengirim pemberitahuan sistem ke perangkat yang memberikan izin, terpisah dari lonceng dalam aplikasi. Profil menyediakan **Aktifkan Notifikasi Perangkat**, **Nonaktifkan di Perangkat Ini**, dan **Kirim Notifikasi Uji**. Lonceng memiliki tautan ke pengaturan tersebut. Tidak ada dialog izin otomatis saat membuka aplikasi; browser meminta izin setelah pengguna menekan tombol aktifkan.

Persiapan server:

```bash
composer install
php artisan migrate --force
php artisan app:web-push-keys
npm run build
php artisan schedule:work
```

Pada production gunakan cron scheduler yang sudah didokumentasikan di atas. Pengiriman antrean berjalan setiap menit, sehingga biasanya ada jeda sekitar satu menit sebelum provider menerima permintaan. Tidak membutuhkan queue worker tambahan. Untuk memproses antrean secara manual:

```bash
php artisan app:send-web-push
```

Set `WEB_PUSH_VAPID_SUBJECT` ke alamat kontak operator, misalnya `mailto:pengurus@domain-anda.id`. Perintah pembuat kunci menyimpan pasangan VAPID pada `storage/app/private/web-push/vapid.json` dengan izin file 0600 dan tidak mencetak kunci privat. Pastikan user proses web/scheduler dapat membacanya. Perintah bersifat idempotent dan tidak mengganti kunci yang sudah ada. Alternatifnya gunakan pasangan `WEB_PUSH_VAPID_PUBLIC_KEY`/`WEB_PUSH_VAPID_PRIVATE_KEY` melalui environment deployment. Backup kunci bersama private storage; jangan memasukkannya ke Git atau mengganti pasangan tanpa rencana pendaftaran ulang perangkat.

Syarat perangkat:

- Akses HP harus melalui HTTPS. `http://localhost`/loopback hanya berguna untuk development pada perangkat yang menjalankan server; `http://192.168...` dari HP bukan secure context.
- Android/desktop: browser yang menyediakan Service Worker, Push API, dan Notifications API. Jika tidak didukung, pengaturan menampilkan penjelasan.
- iPhone/iPad iOS/iPadOS 16.4+: tambahkan situs ke Home Screen melalui Bagikan Safari, buka dari ikon aplikasi, lalu aktifkan notifikasi. Manifest dan ikon aplikasi sudah tersedia.
- Izin yang ditolak harus diubah melalui pengaturan browser/sistem; aplikasi tidak dapat memaksanya.

Notifikasi baru untuk tagihan, bukti transfer, hasil verifikasi, cash, pengingat, serta pengumuman membuat antrean push bagi perangkat terdaftar. Antrean disimpan dalam transaksi database yang sama dengan notifikasi; kegagalan provider tidak membatalkan pembayaran. Notifikasi lama sebelum perangkat diaktifkan tidak dikirim secara retroaktif. Pesan di layar kunci bersifat umum tanpa nama warga, nominal, atau isi pengumuman. Ketukan membuka pusat notifikasi dengan autentikasi dan policy normal.

Langganan disimpan terenkripsi dan terikat ke akun/perumahan. Endpoint dibatasi ke layanan push browser yang diizinkan, HTTPS/443, tanpa userinfo/fragment; redirect HTTP keluar dimatikan untuk membatasi SSRF. Kunci langganan divalidasi, jumlah perangkat dibatasi 10 per akun, dan endpoint akun lain tidak dapat diambil alih. Data endpoint/kunci tidak dicetak pada log. Opt-out menghapus langganan perangkat dan antreannya; logout menghapus langganan sesi perangkat tersebut. Reset password/penonaktifan akun/perumahan mencabut langganan bersama sesi terkait. Masa kedaluwarsa sesi login sendiri tidak otomatis menghentikan langganan, sehingga push tetap dapat diterima ketika aplikasi lama ditutup.

Pengirim memeriksa kembali status akun/perumahan dan kepemilikan sebelum mengirim, menghapus langganan 404/410, serta mencoba ulang kegagalan sementara dengan jeda bertambah (maksimal 5 percobaan, umur antrean maksimal satu hari). Antrean lama dibersihkan setelah 7 hari. Satu notifikasi/perangkat memiliki satu record dan tag tetap; percobaan ulang masih dapat terjadi jika proses berhenti setelah provider menerima permintaan. Keberhasilan provider berarti diterima untuk diproses, bukan bukti bahwa HP menampilkannya. Koneksi, mode hemat daya, Focus/Do Not Disturb, atau penghentian browser oleh OS dapat menunda atau mencegah tampilan.

Pengujian otomatis memalsukan respons provider: pengujian backend menguji registrasi, isolasi, opt-out/logout, deduplikasi/rollback, retry, dan enkripsi/signature VAPID; pengujian frontend menguji pembatasan navigasi Service Worker serta teks layar kunci. Pengiriman end-to-end pada HP dan izin browser pengguna harus diuji pada situs HTTPS nyata. Service Worker tidak melakukan caching halaman atau dokumen pribadi.
