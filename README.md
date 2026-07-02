# EnviroPay

EnviroPay adalah aplikasi web Laravel untuk mengelola Iuran Pemeliharaan Lingkungan (IPL), pembayaran transfer manual, verifikasi bukti, serta arus kas perumahan.

## Fitur

### Admin tunggal

- Mengelola data perumahan.
- Membuat dan mengelola beberapa akun pengurus untuk setiap perumahan.
- Menonaktifkan akun pengurus tanpa menghapus histori.
- Tidak dapat membuat admin tambahan.

### Pengurus

- Mengelola akun warga dalam perumahannya.
- Membuat tagihan periodik untuk seluruh warga aktif.
- Memverifikasi atau menolak bukti pembayaran.
- Mengelola pemasukan dan pengeluaran manual.
- Melihat pemasukan otomatis dari pembayaran terverifikasi.

### Warga

- Melihat tagihan dan jatuh tempo.
- Mengunggah bukti transfer JPG, PNG, atau WebP maksimal 5 MB.
- Mengunggah ulang bukti yang ditolak.
- Melihat status pembayaran dan arus kas lingkungan.

## Teknologi

- Laravel 11 dan PHP 8.2+
- MySQL/MariaDB
- Blade, Bootstrap 5, Bootstrap Icons, dan Vite
- PHPUnit dengan SQLite in-memory untuk testing

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Atur koneksi database pada `.env`, kemudian bangun database development:

```bash
php artisan migrate:fresh
php artisan app:create-admin
npm run build
```

Perintah `app:create-admin` meminta nama, username, email, dan password secara interaktif. Perintah akan gagal jika admin sudah ada.

Jalankan aplikasi:

```bash
php artisan serve
```

Buka `http://127.0.0.1:8000`. Tidak ada kredensial bawaan yang disimpan di repository.

## Alur Pembayaran

1. Pengurus membuat tagihan dengan periode, jatuh tempo, rekening tujuan, dan rincian komponen.
2. Sistem membuat satu invoice untuk setiap warga aktif.
3. Warga melakukan transfer manual dan mengunggah bukti.
4. Status berubah dari `unpaid` menjadi `pending`.
5. Pengurus dapat memverifikasi pembayaran atau menolaknya dengan alasan.
6. Verifikasi mengubah status menjadi `verified` dan membuat satu pemasukan kas yang tidak dapat diedit.
7. Bukti yang ditolak dapat diunggah ulang oleh warga.

Tidak ada payment gateway maupun notifikasi email/WhatsApp pada versi ini.

## Keamanan Data

- Setiap user selain admin terhubung ke `housing_id`.
- Policy mencegah pengurus dan warga mengakses data perumahan lain.
- Bukti pembayaran disimpan pada private storage dan hanya disajikan melalui route terotorisasi.
- Logout menggunakan POST dan login dibatasi lima percobaan per menit per identitas/IP.
- User dengan histori dinonaktifkan, bukan dihapus.

## Development

```bash
# Menjalankan test
php artisan test

# Menjalankan Vite development server
npm run dev

# Build production asset
npm run build
```

Test menggunakan SQLite in-memory dan tidak mengubah database pada `.env`.

## Struktur Utama

- `app/Http/Controllers/Admin` — perumahan dan akun pengurus.
- `app/Http/Controllers/Pengurus` — warga, tagihan, verifikasi, dan arus kas.
- `app/Http/Controllers/Warga` — dashboard, tagihan, dan upload pembayaran.
- `app/Policies` — pembatasan kepemilikan resource.
- `app/Enums/InvoiceStatus.php` — status pembayaran.
- `resources/views` — UI Blade berbasis layout dan komponen reusable.

## Catatan Deployment

- Gunakan PHP minimal 8.2 untuk Laravel 11.
- Arahkan document root web server ke direktori `public`.
- Gunakan `APP_DEBUG=false` pada production.
- Jalankan worker queue bila driver queue tidak menggunakan `sync`.
- Backup database dan `storage/app/private` secara berkala karena bukti pembayaran disimpan di sana.
