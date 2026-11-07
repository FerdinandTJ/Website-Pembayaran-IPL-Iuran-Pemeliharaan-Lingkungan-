# Verifikasi dan Kriteria Penerimaan

[Indeks SRS](../SRS.md) · [Kebutuhan](01-KEBUTUHAN.md)

## 1. Matriks ketertelusuran

Referensi menunjukkan lokasi pemeriksaan terkait, bukan jaminan seluruh kalimat kebutuhan sudah diuji otomatis. UAT, konfigurasi production, peninjauan UI dan kinerja memerlukan bukti tersendiri. Nama implementasi mengacu pada direktori `app/`; nama pengujian mengacu pada `tests/Feature/` kecuali disebut lain.

| Kebutuhan | Implementasi utama | Pengujian terkait |
| --- | --- | --- |
| FR-01–02 | AuthController, UserAccess, RequireActiveAccount dan policy | AuthenticationTest, HousingIsolationTest, SecurityHardeningTest |
| FR-03 | Console/Commands/CreateAdmin | AdminManagementTest (`test_create_admin_command_only_allows_one_admin`) |
| FR-04 | Admin/HousingController, Admin/ManagerController | AdminManagementTest, HousingIsolationTest |
| FR-05 | HouseController, ResidentController, HouseService | HousesAndResidentImportTest, HousingIsolationTest |
| FR-06 | HouseCsvService, ResidentCsvService, ImportIdentity, import controllers | HouseImportTest, HousesAndResidentImportTest, ImportUpsertTest |
| FR-07 | PaymentAccountController, PaymentAccountPolicy | PaymentAccountTest |
| FR-08 | InvoiceController, InvoiceIssuer | BillingWorkflowTest, HousesAndResidentImportTest |
| FR-09 | InvoiceTemplateController, InvoiceTemplateService, InvoiceIssuer | ManagerWorkflowsTest |
| FR-10–12, FR-14 | PaymentService, PaymentController, VerificationController, CashPaymentController, ReceiptController | BillingWorkflowTest, PaymentsAnnouncementsAndPasswordTest, SecurityHardeningTest |
| FR-13 | PaymentBatchController, BatchPaymentService, PaymentBatchPolicy | BatchPaymentsTest |
| FR-15 | CashflowController, CashflowPolicy, CashflowCategory, FinancialActivityObserver | CashflowCategoriesTest, ManagerWorkflowsTest |
| FR-16 | ReportController, ReportService, ReportPdf | ReportsTest, CashflowCategoriesTest, ReportsPdfTest |
| FR-17 | AnnualReportController, AnnualReportService | AnnualReportsTest |
| FR-18–20 | FollowUpController, AccountingPeriodService, FinancialActivityObserver | ManagerWorkflowsTest |
| FR-21 | AnnouncementController | PaymentsAnnouncementsAndPasswordTest |
| FR-22 | NotificationController, InvoiceNotifier, BatchPaymentNotifier | NotificationsTest, BatchPaymentsTest |
| FR-23–24 | WhatsAppOutbox, WhatsAppSender, FonnteClient, command dan scheduler | FonnteTest |
| FR-25 | WhatsAppHistoryController, WhatsAppDelivery | WhatsAppHistoryTest |
| FR-26 | DashboardMetrics dan dashboard controller per peran | DashboardMetricsTest, ViewSmokeTest |
| FR-27 | ProfileController, SessionRevoker | PaymentsAnnouncementsAndPasswordTest (`test_self_password_change_checks_old_password_ends_other_sessions_and_preserves_login`), SecurityHardeningTest |
| FR-28 | Admin/ManagerController, Pengurus/ResidentController, SessionRevoker | SecurityHardeningTest (`test_password_reset_revokes_existing_sessions_and_remember_token`); reset pengurus juga perlu AT-18. |
| FR-29 | bootstrap/app.php, resources/views/errors/page.blade.php dan errors/styles.blade.php | ErrorPagesTest; pratinjau HTML statis pada desktop/HP. |
| NFR-01–04, NFR-07 | Middleware, policy, transaksi, observer dan private download | HousingIsolationTest, SecurityHardeningTest, BatchPaymentsTest, ManagerWorkflowsTest, WhatsAppHistoryTest |
| NFR-05 | Blade, CSS, JavaScript dan pagination | ViewSmokeTest dan `tests/Frontend/`; perlu peninjauan visual HP/desktop. |
| NFR-06 | Carbon, config timezone dan routes/console.php | ReportsTest, AnnualReportsTest, FonnteTest; konfigurasi jam server diperiksa operator. |
| NFR-08 | phpunit.xml, composer.lock dan package-lock.json | Suite PHPUnit, Node frontend dan build aset. |
| NFR-09–10 | Environment dan prosedur operator | Pemeriksaan deployment, load test dan simulasi pemulihan belum direkam sebagai hasil lulus dalam SRS. |

Sumber dapat ditelusuri di [controllers](../../app/Http/Controllers), [services](../../app/Services), [middleware](../../app/Http/Middleware), [policies](../../app/Policies), [tests backend](../../tests/Feature) dan [tests frontend](../../tests/Frontend).

Penghapusan Web Push diverifikasi oleh `RetiredWebPushCleanupTest`: instalasi baru tidak menyisakan tabelnya, upgrade menghapus tabel berisi data tanpa menghapus notifikasi/WhatsApp/tagihan aktif, dan rollback mengembalikan struktur kosong. `tests/Frontend/browser-cleanup.test.mjs` memeriksa pencabutan worker yang tepat tanpa mengubah registrasi aplikasi lain.

## 2. Skenario penerimaan pengguna

Gunakan lingkungan uji, data fiktif dan nomor milik penguji. Tabel berikut berisi **hasil yang diharapkan**, bukan catatan UAT yang sudah disetujui.

| ID | Skenario | Hasil yang diharapkan | Kebutuhan |
| --- | --- | --- | --- |
| AT-01 | Login setiap peran; coba URL dan ID perumahan lain. | Akses sesuai peran; data asing tidak terbuka termasuk ekspor/file. | FR-01–02 |
| AT-02 | Impor CSV berisi data baru, data cocok dan satu baris salah. | Pratinjau membedakan tambah/perbarui; batch salah tidak tersimpan sebagian. | FR-05–06 |
| AT-03 | Terbitkan untuk rumah berpenghuni, kosong tarif khusus dan kosong bebas. | Penerima mengikuti penanggung jawab; tarif khusus mengganti total; rumah bebas dilewati bila aturan aktif. | FR-08 |
| AT-04 | Pratinjau template, ubah tarif/penerima lalu konfirmasi; ulangi periode yang sudah terbit. | Pratinjau berubah dan run ganda ditolak; tidak ada penerbitan ganda template/periode. | FR-09 |
| AT-05 | Unggah transfer, tolak dengan alasan, unggah ulang lalu setujui. | Pending → rejected → pending → verified; kas/kuitansi terbit sekali saat sah. | FR-10–11, FR-14 |
| AT-06 | Bayar cash tunggal/gabungan; ulangi keputusan pembayaran yang sudah sah. | Metode/total benar; satu kas/kuitansi per pembayaran; keputusan ulang ditolak. | FR-12–13 |
| AT-07 | Verifikasi batch setelah anggota berubah atau simulasikan kegagalan pencatatan. | Tidak ada pelunasan sebagian, kas ganda atau kuitansi tanpa pembayaran sah. | FR-13, NFR-04 |
| AT-08 | Catat pengeluaran berkategori dan nota; unduh nota dari perumahan lain. | Tanggal benar; nota privat; akses asing ditolak. | FR-15 |
| AT-09 | Filter pengeluaran Keamanan, ekspor CSV/PDF lalu bandingkan total. | Seluruh hasil filter diekspor; saldo keseluruhan terpisah; batas PDF tidak memotong diam-diam. | FR-16 |
| AT-10 | Dua tagihan dalam satu bulan, hanya satu lunas; pindahkan warga. | Bulan belum lunas seluruhnya; rumah historis tidak berpindah. | FR-17 |
| AT-11 | Catat janji bayar, lalu lunasi tagihan. | Catatan tetap ada; status hanya berubah melalui alur pembayaran. | FR-18 |
| AT-12 | Tutup bulan lampau; ubah nominal/tanggal/nota yang memengaruhinya; buka dengan alasan. | Perubahan ditolak sampai periode dibuka; buka terakhir dahulu; tindakan tercatat. | FR-19–20 |
| AT-13 | Buat tagihan saat Fonnte nonaktif, aktifkan lalu buat tagihan nomor uji. | Tidak retroaktif; antrean baru diproses scheduler; pembayaran tidak bergantung provider. | FR-23–24 |
| AT-14 | Simulasikan API diterima, 429, timeout dan nomor tidak valid dengan HTTP fake. | Status/retry sesuai; timeout tidak dikirim ulang otomatis; UI tidak mengklaim delivered/read. | FR-24–25 |
| AT-15 | Ubah nomor warga setelah percobaan; buka riwayat. | Tujuan terakhir terpisah dari nomor saat ini; halaman tidak mengirim pesan. | FR-25 |
| AT-16 | Ubah jam browser dengan server tetap; tinjau HP/desktop. | Waktu bisnis server tidak berubah; formulir/tabel dapat dipakai. Perubahan jam mesin server bukan perubahan jam browser. | NFR-05–06 |
| AT-17 | Pada database uji kosong buat admin, coba admin kedua; buat/nonaktifkan perumahan/pengurus. | Admin kedua ditolak; perubahan tersimpan; akun/perumahan nonaktif tidak dapat mengakses halaman bisnis. | FR-03–04 |
| AT-18 | Ubah password sendiri, coba password lama salah; reset pengurus oleh admin dan warga oleh pengurus. | Validasi/konfirmasi diterapkan; sesi terkait dicabut; target asing ditolak. | FR-27–28 |
| AT-19 | Tambah/edit/nonaktifkan rekening; tinjau pembayaran warga. | Hanya rekening aktif lingkungan dalam daftar tujuan; tagihan lama utuh. | FR-07 |
| AT-20 | Draft, publikasi, coba edit, arsipkan dan buka tautan lama sebagai warga. | Draft ditolak; publikasi memberi notifikasi; yang terbit tidak diedit langsung; arsip tetap dapat diakses sesuai izin. | FR-21 |
| AT-21 | Buat notifikasi dua akun, tandai satu/semua dibaca; coba ID asing. | Jumlah benar; hanya penerima dapat menandai; tautan tetap diotorisasi. | FR-22 |
| AT-22 | Dashboard tiga peran dengan data lebih dari satu lingkungan. | Ringkasan sesuai peran; data lingkungan asing tidak tampil; kewajiban warga hanya miliknya. | FR-26 |
| AT-23 | Buka URL yang tidak ada, akses terlarang, sesi formulir kedaluwarsa, rate limit dan kegagalan server; bandingkan permintaan HTML/JSON. | HTML memakai tampilan EnviroPay tanpa detail exception, respons tetap 4xx/5xx dan header seperti Allow/Retry-After dipertahankan. JSON/validasi form/redirect login tetap; halaman tetap dapat dirender tanpa database atau aset build. | FR-29 |

## 3. Cara verifikasi teknis

```bash
# SQLite in-memory dan fake HTTP Fonnte
php artisan test

# Interaksi dan format nominal frontend
npm run test:frontend

# Kompilasi aset
npm run build

# Format PHP dan diff
vendor/bin/pint --test
git diff --check
```

Pastikan PHPUnit memaksa `FONNTE_ENABLED=false` pada environment dasar; pengujian integrasi mengaktifkan konfigurasi uji dengan HTTP fake. Uji WhatsApp nyata hanya di lingkungan/nomor penguji, karena perintah pengirim memproses antrean yang memenuhi syarat.

Pemeriksaan dokumen meliputi tautan/anchor, kamus terhadap migration terisolasi, referensi pengujian serta diagram. Keberhasilan render tidak dengan sendirinya membuktikan makna bisnis, balancing DFD, hasil UAT atau kompatibilitas seluruh penampil Markdown.

## 4. Hal yang belum ditetapkan

| ID | Topik | Bukti atau keputusan yang diperlukan |
| --- | --- | --- |
| OPEN-01 | Persetujuan kebutuhan | Pengurus meninjau FR/BR, mencatat revisi dan menyetujui baseline. |
| OPEN-02 | Kinerja dan kapasitas | Tetapkan perumahan/akun, tagihan per tahun, pengguna bersamaan, persentil latensi serta skenario load test; rekam hasil runtime target. |
| OPEN-03 | Ketersediaan dan pemulihan | Sepakati uptime, backup/retensi dan RPO/RTO; simulasikan restore database, APP_KEY dan private storage. |
| OPEN-04 | Retensi dan privasi | Tetapkan masa simpan data warga, bukti, log/provider dan akses backup. Belum ada hapus otomatis untuk seluruh data bisnis. |
| OPEN-05 | UAT dan visual | Rekam versi aplikasi, browser/HP, penguji, tanggal, hasil AT-01–23 dan temuan. |
| OPEN-06 | Database production | Pilih SQLite atau MySQL/MariaDB; periksa tipe, constraint, transaksi/lock dan beban pada mesin pilihan. |
| OPEN-07 | Fonnte nyata | Rekam hasil uji nomor sendiri, perangkat/kuota, domain dan keterlambatan scheduler tanpa menyimpan token atau isi pesan pribadi. |

Daftar ini adalah pekerjaan spesifikasi/validasi selanjutnya, bukan komitmen fitur baru atau bukti perbaikan yang sudah selesai. Perubahan yang disepakati memperbarui versi SRS dan matriks ketertelusuran.

## 5. Hasil audit dokumen — 7 November 2026

| Pemeriksaan | Hasil dan batas bukti |
| --- | --- |
| Struktur database | Migration diterapkan ulang pada SQLite `:memory:` terisolasi; 29 tabel, 269 kolom, 52 FK dan 68 indeks cocok dengan kamus. Nama, tipe, nullability, default, PK/UQ/FK/auto increment, kolom indeks dan aksi ON DELETE/UPDATE diperiksa. Database aplikasi tidak diubah untuk audit ini. |
| ERD dan PDM | Hubungan konseptual dipisahkan dari constraint SQL. Penanggung jawab rumah paling banyak satu secara bisnis; FK fisiknya tidak unique. Referensi polymorphic/logis tidak dinyatakan sebagai FK SQL. |
| Diagram | Seluruh 19 blok Mermaid SRS berhasil diproses renderer lokal Mermaid CLI 12.2.0 ke SVG menggunakan Chrome headless. Sampel ERD, PDM dan DFD diperiksa lewat pratinjau PNG; DFD padat dipecah dan label diperpendek. Hasil renderer lokal belum merupakan pengujian seluruh penampil Markdown. |
| Arus DFD | Arus baca/tulis dibedakan, permintaan/hasil kuitansi dan transparansi dilengkapi. DFD level 1/2 dipisah ke beberapa tampilan yang tetap satu level; gabungannya mempertahankan ID dan arus proses. |
| Kebutuhan dan penerimaan | 28 FR, 25 BR, 10 NFR dan 22 skenario AT. FR memiliki pemetaan implementasi/pengujian dan sedikitnya satu skenario AT; ini merupakan kelengkapan pemetaan, belum hasil lulus UAT. |
| Tautan dan referensi | Tautan lokal/anchor serta nama file/metode pengujian yang disebut diperiksa. Tidak ada referensi dokumen yang sudah dihapus. |
| Pengujian aplikasi | Bukti sesi pembersihan sebelumnya: 152 tes backend, 12 tes frontend dan build berhasil. Suite aplikasi tidak diulang pada audit dokumentasi ini karena kode aplikasi tidak berubah; hasil tersebut tidak dipakai untuk mengklaim kinerja, hasil UAT atau pengiriman WhatsApp nyata. |

Status setelah audit: **baseline teknis ditinjau, masih draf untuk persetujuan kebutuhan**. Penyelesaian OPEN-01–07 memerlukan keputusan pengguna/operator atau pengukuran tersendiri. Tidak ada persentase kepatuhan standar, jaminan bebas bug atau target SLA yang dinyatakan tanpa bukti.

## 6. Verifikasi halaman error — versi 1.2

`ErrorPagesTest` menambahkan 21 kasus: 16 status spesifik, exception tak terduga saat debug aktif, route hilang tanpa migrasi database, header 405/429, kontrak JSON/validasi, serta redirect login. Suite backend setelah perubahan: **173 tes lulus, 1.714 assertion**.

Pratinjau HTML hasil Blade dirender tanpa server aplikasi pada ukuran desktop 1440 px dan HP 320/390 px. Sampel 404, 419, 500, 503 dan 504 diperiksa untuk overflow serta ukuran tombol; contoh 404 desktop dan 419 HP juga diperiksa visual. Ini bukan hasil UAT atau bukti seluruh browser/perangkat.

Penanganan ini berlaku untuk exception HTML yang mencapai Laravel. Error yang dihasilkan lebih dahulu oleh web server/proxy, atau maintenance yang sudah diprerender operator, memerlukan konfigurasi pada lapisan tersebut. Exception tetap mengikuti mekanisme pelaporan Laravel; detail teknis HTML disembunyikan juga saat APP_DEBUG aktif. Pemetaan terkini mencakup 29 FR dan 23 skenario AT; catatan audit versi 1.1 di atas tetap merupakan catatan versi sebelumnya.

## 7. Audit lintas dokumen — versi 1.3

Pada 8 November 2026, pemeriksaan ulang untuk baseline `94cda91` mencatat 173 tes backend/1.714 assertion dan 12 tes frontend lulus. Hasil skema, matriks FR/BR/NFR, seluruh diagram dan wireframe dicatat pada [Audit Dokumentasi](../AUDIT-DOKUMENTASI.md). Catatan versi 1.1/1.2 di atas adalah riwayat sesuai waktunya, bukan jumlah suite yang bersaing. Tidak ada hasil UAT, pengukuran beban atau persetujuan penelitian baru yang dinyatakan dari rerun teknis ini.
