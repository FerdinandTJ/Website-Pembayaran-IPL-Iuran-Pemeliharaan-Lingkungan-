# Pemeriksaan keamanan EnviroPay

Tanggal pemeriksaan: 3 November 2026 (Asia/Jakarta).

## Perbaikan

- Menutup akses bukti transfer, profil, dan home untuk akun/perumahan nonaktif, termasuk sesi login lama. Logout tetap diizinkan. Policy tagihan warga juga memeriksa kesesuaian perumahan.
- Mencabut sesi database dan remember token saat password direset, akun/perumahan dinonaktifkan, atau pengurus dipindahkan. Memeriksa hash password pada sesi terautentikasi untuk mencegah sesi lama hidup kembali setelah reset.
- Membatasi percobaan login per identitas/IP dan per IP; membatasi operasi password, pratinjau CSV, dan unduhan PDF. Identitas login berbentuk array atau terlalu panjang ditolak tanpa error server.
- Membatasi ukuran gambar dan dimensinya, menolak file palsu, menyajikan bukti dengan nosniff, sandbox, dan cache private/no-store. Bukti tetap berada pada private storage.
- Menambahkan batas nominal dan jumlah komponen untuk mencegah overflow dan beban request berlebih. Validasi nominal berada di server, terpisah dari format rupiah di browser.
- Menambahkan header penolakan framing, pembatasan object/base/form, dan referrer same-origin. Dokumen/halaman pribadi tidak disimpan di cache bersama. Ini bukan CSP penuh yang melarang seluruh inline script.
- Memaksa debug nonaktif serta cookie Secure/HttpOnly pada mode production; mengirim HSTS pada HTTPS production.
- Menambahkan pertahanan Apache pada root proyek terhadap akses langsung ke source, database, dan konfigurasi. Document root yang benar tetap harus `public/`.
- Memperbarui Laravel 11.39.1 ke 12.70.2 dan paket Composer/npm lainnya. Resolusi Composer ditargetkan ke PHP 8.2. Tidak menggunakan pengecualian advisory atau pengabaian pemeriksaan PHP untuk hasil akhir.

## Bukti pengujian

Suite `SecurityHardeningTest` memeriksa akun/perumahan nonaktif, isolasi resource, cache/header bukti, pencabutan sesi, pembatasan tebakan password, input login tidak valid, CSRF dengan bypass testing dimatikan, file gambar palsu, nominal berlebihan, eskalasi role/housing melalui request, dan konfigurasi production.

Suite lainnya memeriksa akses antarperumahan, akses kuitansi, CSV dan snapshot kuitansi, konsistensi pembayaran/kas, serta rendering teks pengumuman yang mengandung HTML. Pengujian frontend memeriksa bahwa format rupiah tetap mengirim angka mentah.

Audit awal dependensi menemukan 49 advisory pada 13 paket Composer dan 12 temuan npm. Setelah pembaruan, audit lock file final tidak melaporkan advisory Composer/npm yang diketahui pada waktu pemeriksaan. Hasil ini dapat berubah jika advisory baru diterbitkan; jalankan audit secara berkala.

## Batas pemeriksaan

Ini adalah review kode, audit dependensi, dan pengujian otomatis lokal, bukan sertifikasi atau jaminan aplikasi bebas celah. Belum dilakukan penetration test pada server production, pemeriksaan konfigurasi Apache/Nginx/TLS/firewall yang sedang berjalan, atau pengujian keamanan menyeluruh melalui browser pada server production. Tampilan lokal telah diperiksa melalui browser pada ukuran desktop dan HP dengan data demo terpisah. Pengujian runtime menggunakan PHP CLI 8.5 dengan lock file yang diselesaikan untuk PHP 8.2; runtime PHP 8.2 dan beban transaksi MySQL paralel belum diuji langsung.

Akun pengurus memang berwenang mencatat penerimaan cash dan mengelola kas manual. Aplikasi tidak dapat membuktikan uang fisik benar-benar diterima. Lindungi akun pengurus dan backup dengan izin akses serta prosedur operasional yang sesuai.

## Deployment

Gunakan document root `public/`, HTTPS, environment production, `SESSION_DRIVER=database`, cache persistent, dan akun database dengan hak minimum yang diperlukan aplikasi. Private storage, `.env`, `.git`, backup, dan database tidak boleh disajikan sebagai file publik. Jangan mengaktifkan Vite development server pada jaringan publik. Ikuti patch keamanan runtime, framework, dependensi, dan sistem operasi setelah deployment.

## Tambahan Web Push

Web Push menggunakan endpoint/kunci langganan terenkripsi, allowlist provider HTTPS, tanpa redirect, batas langganan per akun, CSRF dan autentikasi, serta pemeriksaan penerima ulang sebelum pengiriman. Kunci VAPID privat disimpan pada private storage atau environment, tidak dikirim ke browser. Pesan layar kunci bersifat umum, navigasi Service Worker dibatasi origin/scope aplikasi, dan tidak ada caching halaman pribadi. Pengiriman memakai outbox transaksional dan retry terbatas; izin browser tidak dapat dipaksakan. Pengujian provider dilakukan memakai mock, termasuk enkripsi/signature protokol nyata tanpa jaringan, bukan pengiriman end-to-end ke HP.
