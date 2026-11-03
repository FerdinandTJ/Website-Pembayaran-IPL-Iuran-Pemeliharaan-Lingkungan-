# Deployment dan Pembaruan

[Kembali ke README](../README.md) · [Panduan penggunaan](PANDUAN-PENGGUNA.md) · [Notifikasi](NOTIFIKASI.md) · [Keamanan](../SECURITY.md)

## Konfigurasi production

Gunakan PHP minimal 8.2, ekstensi yang memenuhi Composer, database yang dikonfigurasi pada `.env`, serta web server HTTPS. Arahkan document root ke **`public/`**.

Contoh nilai environment:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ipl.domain-anda.id
APP_TIMEZONE=Asia/Jakarta
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
```

Sesuaikan koneksi database, kredensial, dan kontak VAPID dengan deployment Anda. Simpan `.env` di server, di luar version control. Gunakan `APP_KEY` yang tetap untuk instalasi yang sudah berjalan; mengganti kunci dapat membuat data terenkripsi, termasuk pratinjau impor dan langganan push, tidak dapat dibaca.

- Private storage, `.env`, `.git`, backup, dan database harus tidak dapat diunduh melalui web server.
- Berikan izin tulis untuk user proses web dan scheduler pada `storage/` serta `bootstrap/cache/`; hindari izin tulis publik untuk seluruh proyek.
- `.htaccess` root menambahkan pertahanan pada Apache dengan mod_rewrite dan AllowOverride yang sesuai. Konfigurasi document root tetap harus benar; server lain memerlukan aturan aksesnya sendiri.
- Pertahankan `SESSION_DRIVER=database` agar pencabutan sesi perangkat lain bekerja.
- Gunakan cache persistent bersama untuk proses scheduler dan web yang memakai database/environment sama.
- Jangan membuka Vite development server sebagai server publik production.

## Instalasi baru

Setelah source dan `.env` tersedia di server, jalankan:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan migrate --force
php artisan app:create-admin
php artisan optimize
```

`key:generate` dan pembuatan admin ditujukan untuk instalasi baru. Perintah admin menolak pembuatan admin kedua. Build frontend dapat dilakukan di pipeline lalu hasil `public/build` disertakan pada artefak deployment; Node.js tidak harus berjalan sebagai proses server aplikasi.

Jika Web Push dipakai, buat kunci VAPID dan set kontak operator sesuai [panduan notifikasi](NOTIFIKASI.md). Jalankan cron scheduler untuk pengingat dan pengiriman push.

## Pembaruan instalasi yang sudah berjalan

Backup **database, `.env`, dan seluruh `storage/app/private/`** sebelum memperbarui aplikasi. Simpan backup di tempat dengan akses terbatas dan verifikasi proses pemulihannya.

Pada deployment sederhana, gunakan maintenance window untuk mencegah transaksi berlangsung saat migrasi:

```bash
php artisan down
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan up
```

Jalankan `up` sesudah seluruh langkah berhasil. Jika ada langkah gagal, periksa penyebabnya sebelum mengaktifkan kembali aplikasi. Perintah-perintah tersebut harus dijalankan dari direktori proyek dan menggunakan PHP CLI yang sesuai.

**Jangan gunakan `migrate:fresh` pada data yang ingin dipertahankan.** Tidak perlu membuat ulang `APP_KEY` atau menjalankan seeder untuk pembaruan. Gunakan `composer install` dengan lock file, tanpa mengabaikan pemeriksaan platform.

Setelah deploy, periksa login, dashboard, halaman tagihan, dan koneksi scheduler. Restart proses scheduler/queue jangka panjang agar memuat kode baru; jika menggunakan queue worker, jalankan `php artisan queue:restart` sesuai process manager deployment. Notifikasi database dan pengiriman Web Push saat ini tidak memerlukan worker queue tambahan.

### Peninjauan data rumah dari versi lama

Migrasi menghubungkan alamat warga lama yang sama persis dalam perumahan yang sama ke satu rumah. Rumah migrasi menggunakan kode sementara `LEGACY-{id}`. Pengurus perlu meninjau kode blok/nomor, kondisi rumah, serta penanggung jawab IPL awal; akun pertama dapat saja nonaktif. Tagihan lama mempertahankan kepemilikan akun dan salinan alamat.

Pratinjau impor yang dibuat sebelum perubahan aturan tambah/perbarui perlu diunggah ulang agar pencocokan identitas dan kondisi data dapat diverifikasi.

## Scheduler

Tambahkan cron setiap menit. Ganti lokasi proyek dan executable PHP berikut dengan milik server:

```cron
* * * * * cd /path/to/project && /path/to/php artisan schedule:run >> /path/to/project/storage/logs/scheduler.log 2>&1
```

Pastikan cron menggunakan database, konfigurasi, dan izin file yang sama dengan proses web. Atur rotasi log scheduler agar file tidak tumbuh tanpa batas.

| Tugas | Jadwal |
| --- | --- |
| Pengiriman Web Push | Setiap menit |
| Pengingat jatuh tempo H-3/H+1 | 08.00 WIB setiap hari |
| Pembersihan pratinjau impor warga kedaluwarsa | 02.00 waktu aplikasi |
| Pembersihan pratinjau impor rumah kedaluwarsa | 02.05 waktu aplikasi |
| Pembersihan antrean push lama | 02.15 waktu aplikasi |

Periksa jadwal dengan:

```bash
php artisan schedule:list
```

Untuk development, gunakan `php artisan schedule:work` di terminal terpisah. Detail pengiriman dan perintah manual tersedia di [panduan notifikasi](NOTIFIKASI.md).

## Backup

| Data | Alasan |
| --- | --- |
| Database | Akun, rumah, tagihan, kas, kuitansi, pengumuman, dan notifikasi |
| `.env` / secret environment | `APP_KEY`, konfigurasi database, serta kunci VAPID bila diatur melalui environment |
| `storage/app/private/` | Bukti pembayaran dan kunci VAPID lokal |

Bukti transfer sengaja berada di private storage dan diakses melalui route terotorisasi; jangan memindahkannya ke folder publik. Perintah `storage:link` tidak diperlukan untuk membuka bukti pembayaran. Kunci VAPID lokal berada di `storage/app/private/web-push/vapid.json`; jangan masukkan file ini ke Git.

## Pemeriksaan rutin

```bash
composer check-platform-reqs
composer audit --locked
npm audit
php artisan test
npm run test:frontend
npm run build
```

Jalankan pengujian pada lingkungan development/CI dengan dependensi development terpasang. PHPUnit memakai SQLite in-memory dan tidak mengubah database aplikasi. Lock file Composer menargetkan PHP 8.2; tetap periksa runtime yang digunakan deployment. Hasil audit bergantung pada advisory yang tersedia saat pemeriksaan.

## Troubleshooting

| Gejala | Yang diperiksa |
| --- | --- |
| `Vite manifest not found` atau aset 404 | Jalankan `npm ci` dan `npm run build`; pastikan `public/build` ikut dideploy. |
| Koneksi database gagal | Periksa `.env`, ekstensi PDO, kredensial, serta izin file SQLite; bersihkan/bangun ulang config cache setelah mengubah environment. |
| Error izin tulis atau halaman 500 | Periksa ownership `storage/`, `bootstrap/cache/`, dan `storage/logs/laravel.log`; jangan membuka debug production. |
| Tidak dapat membuat admin | Aplikasi membatasi satu admin. Periksa apakah admin sudah ada; jangan menghapus data hanya untuk melewati pembatasan. |
| Tidak ada pengingat | Periksa cron, `schedule:list`, tanggal jatuh tempo, status tagihan, dan keaktifan akun/perumahan. |
| Tombol push tidak tersedia di HP | Periksa HTTPS, dukungan browser, izin, kunci VAPID, dan pemasangan Home Screen pada iPhone/iPad. |
| Push tertunda atau tidak tampil | Periksa scheduler, log, respons provider, jaringan, serta pengaturan notifikasi/hemat daya OS. Penerimaan provider tidak membuktikan perangkat menampilkan pesan. |
