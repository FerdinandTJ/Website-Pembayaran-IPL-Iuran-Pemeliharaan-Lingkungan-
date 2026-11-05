<div align="center">

# EnviroPay

**Lingkungan tertata. Iuran terkelola.**

Aplikasi pengelolaan Iuran Pemeliharaan Lingkungan (IPL): dari pendataan rumah dan warga, penagihan, pembayaran, hingga laporan kas perumahan.

![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Bootstrap 5](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)
[![License MIT](https://img.shields.io/badge/License-MIT-176b52)](LICENSE)

[Mulai instalasi](#instalasi-lokal) · [Fitur](#fitur-utama) · [Panduan penggunaan](docs/PANDUAN-PENGGUNA.md) · [Deployment](docs/DEPLOYMENT.md)

</div>

---

## Tentang EnviroPay

EnviroPay membantu pengurus mengelola administrasi lingkungan dalam satu aplikasi, sementara warga dapat memeriksa tagihan, mengunggah bukti transfer, mengunduh kuitansi, dan mengikuti pengumuman perumahan.

Satu instalasi mendukung beberapa perumahan. Akses pengurus dan warga dibatasi ke perumahan masing-masing; admin mengelola data perumahan dan akun pengurus.

## Preview

![Dashboard pengurus EnviroPay dengan ringkasan saldo, grafik arus kas, antrean verifikasi, dan kondisi rumah](docs/images/dashboard-pengurus.png)

*Preview menggunakan data demo fiktif. UI menyesuaikan desktop dan HP; dashboard tersedia untuk admin, pengurus, dan warga.*

## Fitur utama

| Area | Yang tersedia |
| --- | --- |
| **Dashboard** | Saldo lingkungan, grafik arus kas enam bulan, ringkasan tunggakan, antrean verifikasi, dan akses cepat sesuai peran. |
| **Rumah & warga** | Tambah manual, impor CSV dengan template dan pratinjau, hubungan akun ke rumah, penanggung jawab IPL, serta histori rumah. |
| **Kondisi rumah** | Dihuni, Dikontrakkan, Kosong, dan Renovasi sebagai status terpisah; aturan tarif rumah kosong dapat disesuaikan. |
| **Tagihan IPL** | Komponen biaya, periode, jatuh tempo, penerima semua warga aktif atau warga tertentu, dan beberapa tagihan pada periode yang sama. |
| **Pembayaran** | Transfer manual dan cash, untuk satu atau beberapa tagihan sekaligus; satu bukti dan satu pencatatan kas per pembayaran gabungan. |
| **Kuitansi** | Nomor tetap, salinan data saat penerbitan, unduh PDF A4, dan cetak dari browser. |
| **Kas & laporan** | Pemasukan otomatis dari pembayaran, kas manual, laporan tunggakan/tagihan/kas, filter, dan ekspor CSV. |
| **Pengumuman** | Draft, terbitkan, arsipkan, serta pemberitahuan kepada warga aktif. |
| **Notifikasi** | Lonceng dalam aplikasi, pengingat jatuh tempo otomatis, dan Web Push opsional ke perangkat yang memberikan izin. |
| **Akun & antarmuka** | Ubah password sendiri, format nominal rupiah saat mengetik, navigasi per peran, dan tabel ramah HP. |

### Hak akses

| Peran | Tanggung jawab |
| --- | --- |
| **Admin tunggal** | Mengelola perumahan dan akun pengurus; mengaktifkan atau menonaktifkan akses. |
| **Pengurus** | Mengelola rumah, warga, rekening, tagihan, pembayaran, kas, pengumuman, dan laporan dalam perumahannya. |
| **Warga** | Mengakses tagihan dan kuitansi sendiri, mengunggah bukti, serta melihat pengumuman dan transparansi kas lingkungannya. |

Tidak tersedia pendaftaran publik. Akun admin dibuat melalui CLI; pengurus dibuat admin, dan warga dibuat atau diimpor pengurus.

## Alur penggunaan

```mermaid
flowchart LR
    A[Admin: buat perumahan dan pengurus] --> B[Pengurus: impor atau tambah rumah]
    B --> C[Impor atau tambah warga dan penanggung jawab IPL]
    C --> D[Buat tagihan]
    D --> E[Warga: transfer dan unggah bukti]
    D --> F[Pengurus: terima cash]
    E --> G[Pengurus: verifikasi]
    F --> H[Kas tercatat dan kuitansi terbit]
    G --> H
```

Transfer yang ditolak dapat diunggah ulang. Pembayaran cash langsung terverifikasi setelah pengurus mengonfirmasi penerimaan uang. Pencatatan pembayaran, kas, kuitansi, dan notifikasi dilakukan dalam satu transaksi database.

## Teknologi

| Lapisan | Teknologi |
| --- | --- |
| Backend | Laravel 12, PHP 8.2+, Eloquent, policy, dan session database |
| Frontend | Blade, Bootstrap 5, Bootstrap Icons, JavaScript, dan Vite 6 |
| Database | SQLite untuk mulai lokal; MySQL/MariaDB dapat dikonfigurasi |
| Dokumen | Dompdf untuk kuitansi PDF |
| Notifikasi | Database notifications, Laravel Scheduler, Service Worker, dan Web Push VAPID |
| Pengujian | PHPUnit dengan SQLite in-memory dan Node.js test runner |

## Instalasi lokal

### 1. Siapkan kebutuhan

- PHP **8.2+** dan Composer 2, beserta ekstensi yang dibutuhkan dependensi. `composer check-platform-reqs` memeriksa kebutuhan PHP setelah instalasi.
- Node.js **22 LTS** dan npm untuk membangun frontend.
- SQLite dengan ekstensi PHP `pdo_sqlite`, atau server MySQL/MariaDB dengan `pdo_mysql`.

### 2. Ambil proyek dan pasang dependensi

```bash
git clone https://github.com/FerdinandTJ/Website-Pembayaran-IPL-Iuran-Pemeliharaan-Lingkungan-.git enviropay
cd enviropay
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

### 3. Atur database

Konfigurasi contoh menggunakan SQLite. Untuk instalasi baru, buat file databasenya jika belum tersedia:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

Pastikan `.env` menggunakan:

```dotenv
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=sqlite
SESSION_DRIVER=database
```

<details>
<summary><strong>Menggunakan MySQL/MariaDB, termasuk XAMPP</strong></summary>

Buat database kosong, lalu sesuaikan `.env` dengan kredensial lokal Anda:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=enviropay
DB_USERNAME=nama_user_database
DB_PASSWORD=password_database
```

Untuk MariaDB, `DB_CONNECTION=mariadb` juga tersedia. Pastikan PHP CLI yang menjalankan Artisan/Composer dan PHP web server sama-sama memenuhi kebutuhan versi dan ekstensi. Untuk Apache, arahkan document root ke folder `public/`.

</details>

### 4. Migrasi dan buat admin

```bash
php artisan migrate
php artisan app:create-admin
npm run build
```

Pembuatan admin meminta nama, username, email, dan password secara interaktif. Hanya satu admin diizinkan dan tidak ada kredensial bawaan.

### 5. Jalankan aplikasi

```bash
php artisan serve
```

Buka **http://127.0.0.1:8000**, masuk sebagai admin, buat perumahan dan pengurus, lalu lanjutkan pendataan dari akun pengurus.

Untuk pengembangan frontend, jalankan `npm run dev` di terminal lain. Jalankan `php artisan schedule:work` di terminal terpisah jika ingin mengaktifkan pengingat, pengiriman Web Push, dan pembersihan data sementara.

> Sudah memiliki data? Gunakan `php artisan migrate` untuk pembaruan. `migrate:fresh` menghapus seluruh tabel dan tidak diperlukan untuk instalasi ini.

## Aturan penting

### Impor: data sama diperbarui, data baru ditambahkan

Unduh template langsung dari **Data Rumah** atau **Data Warga**, unggah CSV, tinjau baris **Baru/Perbarui**, lalu konfirmasi. Tambah data manual tetap tersedia.

- **Rumah:** dicocokkan melalui kode unik dalam perumahan. Maksimal 1.000 baris per file.
- **Warga:** dicocokkan melalui username atau email. Identitas yang bertabrakan dengan akun lain ditolak. Maksimal 100 baris per file.
- File maksimal **2 MB**, mendukung pemisah koma/titik koma. Pratinjau berlaku **30 menit**.
- Satu baris bermasalah menahan seluruh impor. Konfirmasi memvalidasi ulang dan menyimpan satu batch secara atomik.
- Impor rumah terlebih dahulu, kemudian impor warga dengan `house_code` agar akun langsung terhubung ke rumahnya.

Rincian kolom, password, penanggung jawab, dan perlakuan sel kosong tersedia di [panduan penggunaan](docs/PANDUAN-PENGGUNA.md).

### Rumah dan tarif IPL

Satu akun warga terhubung ke satu rumah saat ini. Satu rumah dapat memiliki beberapa akun, dengan satu penanggung jawab IPL untuk tagihan baru.

| Kondisi rumah | Aturan tagihan baru |
| --- | --- |
| Dihuni | Komponen normal |
| Dikontrakkan | Komponen normal; status tetap berbeda dari Dihuni |
| Renovasi | Komponen normal |
| Kosong — tarif penuh | Komponen normal |
| Kosong — nominal khusus | Total komponen diganti nominal khusus positif per tagihan IPL |
| Kosong — bebas iuran baru | Dilewati saat pembuatan tagihan dengan aturan rumah kosong aktif |

Contoh: komponen normal **Rp 100.000** dan nominal khusus **Rp 25.000** menghasilkan tagihan **Rp 25.000**. Nominal khusus menggantikan total, bukan menjadi potongan. Perubahan tarif tidak menghitung ulang tagihan atau tunggakan lama.

### Pembayaran dan kuitansi

| Metode | Alur |
| --- | --- |
| Transfer | Warga memilih satu atau beberapa tagihan, transfer sesuai total, unggah satu JPG/PNG/WebP maksimal 5 MB, lalu pengurus memverifikasi atau menolak seluruh pembayaran dengan alasan. |
| Cash | Pengurus memilih warga dan satu atau beberapa tagihan `unpaid`/`rejected`, meninjau total, lalu mengonfirmasi uang diterima. |

Transfer dan cash mendukung pemilihan hingga 100 tagihan milik satu warga. Sistem otomatis mencatat satu pilihan sebagai pembayaran biasa, dan dua atau lebih sebagai pembayaran gabungan. Cicilan dan nominal sebagian belum tersedia. Tagihan `pending` perlu diproses terlebih dahulu. Pembayaran gabungan melunasi seluruh pilihan secara atomik, mencatat satu pemasukan kas, serta menerbitkan satu kuitansi berisi rincian per tagihan. Pemasukan dari pembayaran tagihan tidak dapat diedit atau dihapus melalui kas manual.

## Notifikasi aplikasi dan layar HP

**Lonceng aplikasi** menyimpan pemberitahuan tagihan, hasil verifikasi, pembayaran, pengumuman, dan pengingat. Jumlah belum dibaca diperbarui setiap 30 detik saat tab terlihat.

**Web Push** bersifat opsional dan dapat memunculkan notifikasi sistem meskipun tab aplikasi ditutup, sesuai dukungan browser/OS dan kondisi perangkat. Pengguna mengaktifkannya melalui **Profil → Notifikasi Perangkat** setelah server dikonfigurasi.

- Perangkat memerlukan HTTPS dan izin notifikasi.
- iPhone/iPad dengan iOS/iPadOS 16.4+ memerlukan aplikasi ditambahkan ke Home Screen.
- Pengingat tagihan berjalan setiap **08.00 WIB**, pada **H-3** dan **H+1** jatuh tempo.
- Pengiriman Web Push diproses scheduler setiap menit; pesan layar kunci tidak memuat nama atau nominal.

Lihat [panduan notifikasi dan Web Push](docs/NOTIFIKASI.md) untuk kunci VAPID, scheduler, pengujian perangkat, dan penanganan kegagalan.

## Development dan pengujian

```bash
# Backend: database SQLite in-memory, terpisah dari data aplikasi
php artisan test

# Frontend: format rupiah dan Service Worker/Web Push
npm run test:frontend

# Build aset produksi
npm run build

# Audit dependensi
composer audit --locked
npm audit
```

Pengujian mencakup isolasi perumahan, autentikasi, tagihan, verifikasi/cash, konsistensi kas, kuitansi, impor CSV, notifikasi, laporan, dan validasi keamanan. Catatan pengujian serta batas pemeriksaan ada di [SECURITY.md](SECURITY.md).

## Struktur proyek

```text
app/
├── Console/Commands/      # Admin, pengingat, dan pengiriman Web Push
├── Http/Controllers/     # Admin, pengurus, warga, dan layanan bersama
├── Models/               # Perumahan, rumah, warga, tagihan, kas, dan notifikasi
├── Notifications/        # Notifikasi database per perumahan
├── Policies/             # Otorisasi resource
└── Services/             # Pembayaran, impor, laporan, sesi, dan Web Push
database/migrations/      # Skema dan migrasi pembaruan
resources/
├── css/                  # Tema dan komponen UI responsif
├── js/                   # Interaksi, format rupiah, dan langganan push
└── views/                # Layout, dashboard, formulir, dan dokumen Blade
public/service-worker.js  # Penerimaan dan navigasi Web Push
tests/                    # Pengujian backend dan frontend
docs/                     # Panduan penggunaan, notifikasi, dan deployment
```

## Dokumentasi lanjutan

| Dokumen | Isi |
| --- | --- |
| [Panduan penggunaan](docs/PANDUAN-PENGGUNA.md) | Rumah dan tarif IPL, format CSV, aturan pembaruan, cash, kuitansi, pengumuman, password, dan laporan. |
| [Notifikasi & Web Push](docs/NOTIFIKASI.md) | Lonceng, pengingat, VAPID, izin browser, scheduler, dan retry. |
| [Deployment & pembaruan](docs/DEPLOYMENT.md) | Konfigurasi production, upgrade tanpa menghapus data, backup, dan troubleshooting. |
| [Keamanan](SECURITY.md) | Kontrol akses, perlindungan data, pemeriksaan dependensi, dan batas audit. |

## Cakupan saat ini

Pembayaran transfer diverifikasi manual oleh pengurus. Integrasi payment gateway, rekonsiliasi bank otomatis, email/WhatsApp, cicilan, dan percakapan chat belum tersedia. Web Push memerlukan konfigurasi server serta pengujian pada perangkat tujuan.

## Lisensi

Proyek ini menggunakan [lisensi MIT](LICENSE).
