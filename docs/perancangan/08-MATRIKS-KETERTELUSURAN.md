# Matriks Ketertelusuran Perancangan

[Bab III](../BAB-III-PERANCANGAN.md) · [Use case](02-USE-CASE-DAN-AKTIVITAS.md) · [Antarmuka](04-ANTARMUKA-MASUKAN-KELUARAN.md) · [AT SRS](../srs/05-VERIFIKASI.md)

Matriks ini menghubungkan kebutuhan ke tujuan aktor, kelompok layar dan rancangan penerimaan. RUC/UI/AT adalah sumber rancangan yang sudah didefinisikan, bukan endpoint baru atau hasil uji. Hubungan ke source code serta nama test otomatis tetap mengikuti matriks SRS. Memiliki pemetaan tidak berarti kebutuhan sudah disetujui pengguna atau setiap skenario telah lulus.

| Kebutuhan | Rumusan ringkas dari SRS | Kelompok use case | Kelompok antarmuka | Skenario penerimaan | Kasus black-box |
| --- | --- | --- | --- | --- | --- |
| FR-01 | Mengautentikasi username/email dan password, mengelola sesi dan logout. | `RUC-01` | `UI-01` | `AT-01` | `BB-01` |
| FR-02 | Membatasi akses menurut peran, keaktifan dan perumahan/kepemilikan data. | `RUC-01` | `UI-01` | `AT-01` | `BB-01` |
| FR-03 | Membatasi admin tunggal dan menyediakan pembuatan admin melalui CLI. | `RUC-01` | `UI-03` | `AT-17` | `BB-01` |
| FR-04 | Memungkinkan admin mengelola perumahan, akun pengurus, status aktif dan reset password pengurus. | `RUC-02` | `UI-03` | `AT-17` | `BB-02` |
| FR-05 | Memungkinkan pengurus mengelola rumah, kondisi hunian, tarif rumah kosong, warga dan penanggung jawab IPL. | `RUC-03` | `UI-04` | `AT-02` | `BB-03` |
| FR-06 | Mengimpor rumah/warga melalui CSV, validasi, pratinjau lalu konfirmasi atomik. | `RUC-03` | `UI-04` | `AT-02` | `BB-04` |
| FR-07 | Mengelola rekening pembayaran aktif per perumahan. | `RUC-03` | `UI-05` | `AT-19` | `BB-21` |
| FR-08 | Menerbitkan tagihan berkomponen untuk warga aktif sesuai penerima dan aturan rumah. | `RUC-04` | `UI-06` | `AT-03` | `BB-05` |
| FR-09 | Menyimpan template tagihan dan menerbitkannya setelah pratinjau serta konfirmasi. | `RUC-04` | `UI-06` | `AT-04` | `BB-06` |
| FR-10 | Menerima bukti transfer tunggal untuk tagihan `unpaid` atau `rejected` milik warga. | `RUC-05` | `UI-07` | `AT-05` | `BB-07` |
| FR-11 | Memungkinkan pengurus menyetujui atau menolak transfer dengan alasan penolakan. | `RUC-06` | `UI-08` | `AT-05` | `BB-09` |
| FR-12 | Mencatat pembayaran cash setelah konfirmasi uang diterima. | `RUC-06` | `UI-08` | `AT-06` | `BB-10` |
| FR-13 | Memproses 1–100 tagihan satu warga; lebih dari satu membentuk pembayaran gabungan. | `RUC-05`, `RUC-06` | `UI-07`, `UI-08` | `AT-06`, `AT-07` | `BB-08`, `BB-09`, `BB-10` |
| FR-14 | Menyediakan kuitansi bernomor tetap dengan snapshot dan unduh/cetak PDF. | `RUC-06` | `UI-08` | `AT-05` | `BB-10`, `BB-23` |
| FR-15 | Mengelola kas manual dengan jenis, kategori, nominal, tanggal transaksi dan nota privat. | `RUC-07` | `UI-09` | `AT-08` | `BB-11` |
| FR-16 | Menyediakan laporan tunggakan, tagihan bulanan dan kas dengan filter relevan serta CSV/PDF. | `RUC-08` | `UI-10` | `AT-09` | `BB-12`, `BB-24` |
| FR-17 | Menyediakan rekap tahunan per rumah dengan filter tahun, pencarian, status dan rincian bulanan. | `RUC-08` | `UI-10` | `AT-10` | `BB-13` |
| FR-18 | Mencatat riwayat tindak lanjut tunggakan, catatan, jadwal dan pengurus penanggung jawab. | `RUC-09` | `UI-11` | `AT-11` | `BB-14` |
| FR-19 | Menutup bulan kas yang sudah berakhir serta membuka kembali dengan alasan. | `RUC-10` | `UI-11` | `AT-12` | `BB-15` |
| FR-20 | Mencatat aktivitas keuangan terpilih, pelaku, waktu dan nilai sebelum/sesudah. | `RUC-10` | `UI-11` | `AT-12` | `BB-16` |
| FR-21 | Mengelola pengumuman draft, publikasi, arsip dan pemberitahuan warga aktif. | `RUC-11` | `UI-12` | `AT-20` | `BB-17` |
| FR-22 | Menyediakan daftar lonceng, jumlah belum dibaca dan penandaan dibaca. | `RUC-11` | `UI-12` | `AT-21` | `BB-17` |
| FR-23 | Mengantrekan WhatsApp untuk tagihan baru, H-3/H+1 dan kuitansi transfer/cash termasuk gabungan. | `RUC-12` | `UI-12` | `AT-13` | `BB-18`, `BB-25` |
| FR-24 | Memproses outbox melalui scheduler dengan deduplikasi, validasi ulang dan hasil pengiriman. | `RUC-12` | `UI-12` | `AT-13`, `AT-14` | `BB-18`, `BB-25` |
| FR-25 | Menampilkan riwayat WhatsApp per perumahan dengan filter, ringkasan dan detail kendala. | `RUC-12` | `UI-12` | `AT-14`, `AT-15` | `BB-19`, `BB-25` |
| FR-26 | Menyediakan dashboard sesuai peran dan lingkup data. | `RUC-08` | `UI-10` | `AT-22` | `BB-22` |
| FR-27 | Mengubah password sendiri dengan password saat ini dan konfirmasi password baru. | `RUC-01` | `UI-02` | `AT-18` | `BB-01` |
| FR-28 | Memungkinkan admin mereset password pengurus dan pengurus mereset password warga dalam lingkupnya. | `RUC-01` | `UI-02` | `AT-18` | `BB-01` |
| FR-29 | Menampilkan halaman error HTML beridentitas EnviroPay dengan pesan Indonesia dan jalan kembali yang aman. | `RUC-13` | `UI-01` | `AT-23` | `BB-20` |

## Interpretasi khusus

- FR-03 memiliki provisioning CLI, bukan layar pendaftaran. Hubungannya dengan UI-03 menunjukkan konteks administrasi dan batas operator, bukan form admin publik.
- UI-11/12 adalah kelompok beberapa menu; setiap tindakan tetap mengikuti otorisasinya. Fonnte hanya terkait pengiriman dalam RUC-12, sedangkan GET riwayat tidak memanggil provider.
- Dashboard termasuk RUC-08/UI-10 untuk semua role sesuai izin, tetapi laporan/ekspor pengurus tetap dibatasi perumahan.
- FR-29 merupakan penanganan lintas alur, bukan menu transaksi. UI-01 juga mencakup halaman error mandiri.
- Analisis penerapan NFR dan hubungan BR ke implementasi mengikuti SRS serta tabel kontrol risiko pada lampiran arsitektur. Tidak ada NFR kinerja yang dinyatakan terpenuhi hanya karena FR mempunyai pemetaan.

Saat perubahan kebutuhan disetujui, periksa ulang seluruh kolom hubungan dan kasus uji detailnya. ID lama yang telah dirujuk pada bukti/temuan tidak diganti tanpa catatan revisi.

## Pemetaan seluruh aturan bisnis

Definisi BR tetap berasal dari SRS. Kolom source/test menunjuk lokasi terkait, bukan menyatakan seluruh alternatif telah diuji. Algoritma dan kelompok UI/RUC merupakan rancangan proyek.

| Aturan | Pokok pemeriksaan | Rancangan | Source terkait | Pengujian teknis terkait | Kasus rancangan |
| --- | --- | --- | --- | --- | --- |
| BR-01 | Isolasi role/perumahan | UI-01–12; RUC-01–13 | Policy/middleware dan controller/service | HousingIsolationTest | AT-01; BB-01 |
| BR-02 | Rumah dan penanggung jawab | RUC-03/04; ALG-01 | HouseService, InvoiceIssuer | HousesAndResidentImportTest | AT-02/03; BB-03/05 |
| BR-03 | Kondisi dan tarif kosong | RUC-03/04; ALG-01 | House, HouseService, InvoiceIssuer | HousesAndResidentImportTest | AT-03; BB-05 |
| BR-04 | Invoice periode berulang dan run unik | RUC-04 | InvoiceTemplateService dan migration | BillingWorkflowTest, ManagerWorkflowsTest | AT-04; BB-06 |
| BR-05 | Status invoice dan kelayakan pengingat | RUC-05/06/12 | InvoiceStatus, Invoice, WhatsAppSender | BillingWorkflowTest, FonnteTest | AT-05/14; BB-07/18 |
| BR-06 | Pilihan, batas dan cash warga nonaktif | RUC-05/06; ALG-02 | BatchPaymentService, CashPaymentController | BatchPaymentsTest, PaymentsAnnouncementsAndPasswordTest | AT-06/07; BB-08/10 |
| BR-07 | Satu kas/kuitansi per pembayaran | RUC-06; ALG-02 | PaymentService, BatchPaymentService | BatchPaymentsTest | AT-06/07; BB-09/10 |
| BR-08 | Batch historis dan penunjuk saat ini | RUC-05/06 | PaymentBatch, BatchPaymentService dan pivot | BatchPaymentsTest | AT-07; BB-08/09 |
| BR-09 | Snapshot dan rumah historis | RUC-06/08; ALG-04 | PaymentService, AnnualReportService | PaymentsAnnouncementsAndPasswordTest, AnnualReportsTest | AT-05/10; BB-13/23 |
| BR-10 | Bukti/nota privat dan MIME/ukuran | RUC-05/07; UI-07/09 | PaymentController, CashflowController | SecurityHardeningTest, ManagerWorkflowsTest | AT-08; BB-07/11; BV-09 |
| BR-11 | CSV, pratinjau dan upsert atomik | RUC-03; UI-04 | HouseCsvService, ResidentCsvService | HouseImportTest, ImportUpsertTest | AT-02; BB-04; BV-11 |
| BR-12 | Komponen, due day dan offset | RUC-04; ALG-01 | InvoiceTemplateController, InvoiceTemplateService | ManagerWorkflowsTest | AT-04; BB-06; BV-02–05 |
| BR-13 | Tanggal akuntansi dan waktu catat | RUC-07/08; ALG-03 | Cashflow, FinancialActivityObserver | ManagerWorkflowsTest, ReportsTest | AT-08/09; BB-11/12; BV-08 |
| BR-14 | Dampak saldo awal dan guard buku | RUC-07/10; ALG-05 | AccountingPeriodService dan observer | ManagerWorkflowsTest | AT-12; BB-15 |
| BR-15 | Reopen beralasan dan urutan terakhir | RUC-10; ALG-05 | AccountingPeriodController, AccountingPeriodService | ManagerWorkflowsTest | AT-12; BB-15; BV-07 |
| BR-16 | Tindak lanjut tidak melunasi | RUC-09; UI-11 | FollowUpController, ArrearsFollowUp | ManagerWorkflowsTest | AT-11; BB-14; BV-08 |
| BR-17 | PDF dibatasi tanpa truncation | RUC-08; UI-10 | ReportController, AnnualReportController | ReportsPdfTest, AnnualReportsTest | AT-09/10; BB-12/13; BV-10 |
| BR-18 | Jadwal, deduplikasi dan kedaluwarsa | RUC-12; ALG-06 | routes/console.php, WhatsAppOutbox, WhatsAppSender | FonnteTest | AT-13/14; BB-18/25; BV-14/15 |
| BR-19 | Retry, batas dan hasil ambigu | RUC-12; ALG-06 | FonnteClient, WhatsAppSender, SendWhatsApp | FonnteTest | AT-14; BB-18/25 |
| BR-20 | Accepted dan tujuan terakhir | RUC-12; UI-12 | WhatsAppDelivery, WhatsAppHistoryController | WhatsAppHistoryTest | AT-15; BB-19 |
| BR-21 | Periode/due dan total kewajiban | RUC-04; ALG-01 | InvoiceIssuer, InvoiceController | BillingWorkflowTest, ManagerWorkflowsTest | AT-03/04; BB-05/06; BV-03 |
| BR-22 | Nominal dan kategori kas manual | RUC-07; UI-09 | CashflowController, CashflowCategory | CashflowCategoriesTest | AT-08; BB-11 |
| BR-23 | Draft/published/archived | RUC-11; UI-12 | AnnouncementController | PaymentsAnnouncementsAndPasswordTest | AT-20; BB-17 |
| BR-24 | Tahun dan matriks bukan rekonstruksi | RUC-08; ALG-04 | AnnualReportService, AnnualReportController | AnnualReportsTest | AT-10; BB-13; BV-06 |
| BR-25 | Kuitansi historis terbit sekali | RUC-06; UI-08 | PaymentService::receipt | PaymentsAnnouncementsAndPasswordTest | AT-05/06; BB-23 |

## Pemetaan seluruh kebutuhan nonfungsional

| Kebutuhan | Pokok | Rancangan / kontrol | Pengujian teknis terkait | Pemeriksaan lanjutan | Status dan batas bukti |
| --- | --- | --- | --- | --- | --- |
| NFR-01 | Otorisasi/isolasi | Policy, middleware, file dan ekspor | HousingIsolationTest, SecurityHardeningTest | AT-01/08; BB-01/11 | Baseline teknis; UAT dan cakupan tambahan tetap perlu bukti. |
| NFR-02 | Hash dan enkripsi terpilih | User, ResidentImport, HouseImport, WhatsAppDelivery | SecurityHardeningTest, WhatsAppHistoryTest | BB-01/04/19; OP-06 | Bukan enkripsi seluruh DB; inspeksi metadata/cast tidak membuktikan keamanan server. |
| NFR-03 | CSRF, validasi dan laju | AppServiceProvider, middleware, validator controller | SecurityHardeningTest, AuthenticationTest | BB-01/20 | Batas endpoint sesuai route; tidak semua endpoint dibatasi identik. |
| NFR-04 | Atomisitas bisnis | PaymentService, BatchPaymentService dan transaksi | BatchPaymentsTest, ManagerWorkflowsTest | BB-09/16; DT-01–05 | Perlu uji concurrency target DB; filesystem/provider bukan transaksi SQL yang sama. |
| NFR-05 | Responsif dan usability | UI-01–12, wireframe dan Blade | ViewSmokeTest, ErrorPagesTest; tests/Frontend | AT-16/23; OP-05 | Wireframe/pratinjau bukan hasil UAT atau sertifikasi aksesibilitas. |
| NFR-06 | Waktu server/WIB | Carbon, timezone dan scheduler | FonnteTest, ReportsTest | AT-16; BV-08/14/15; OP-02 | Jam server harus disinkronkan operator; bukan layanan waktu kebal perubahan operator. |
| NFR-07 | Audit terpilih | FinancialActivityObserver dan snapshot | ManagerWorkflowsTest, WhatsAppHistoryTest | BB-16/19; OP-06 | Tidak mengubah aktivitas lama menjadi log retroaktif atau mencegah SQL administrator. |
| NFR-08 | Build dan uji terisolasi | Lock file, phpunit.xml dan npm scripts | Suite PHPUnit dan tests/Frontend | Eksekusi suite/ build terpisah | Bukti eksekusi menyebut commit, environment, waktu dan log; bukan hasil UAT. |
| NFR-09 | Production dan pemulihan | HTTPS, config, private storage, backup konsisten | SecurityHardeningTest untuk konfigurasi aplikasi saja | OP-01/02/03/06 | Restore dan deployment produksi belum dinyatakan lulus; perlu keputusan OPEN-03/06/07. |
| NFR-10 | Target kapasitas/SLA | Instrumen kebutuhan dan rencana ukur | Belum ada hasil load test pada baseline | OP-04; OPEN-02/03 | Target belum disepakati; tidak mengisi CPU/RAM, latensi, RPO/RTO dengan tebakan. |
