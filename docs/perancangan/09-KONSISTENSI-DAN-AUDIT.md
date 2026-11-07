# Keselarasan Dokumen dan Hasil Audit

[Bab III](../BAB-III-PERANCANGAN.md) · [SRS](../SRS.md) · [Matriks lengkap](08-MATRIKS-KETERTELUSURAN.md) · [Pemeriksaan otomatis](../tools/README.md)

## 1. Acuan dan hierarki dokumen

Tanggal peninjauan: **8 November 2026**. Baseline kode `94cda91`; perubahan pada paket ini menambah/memperjelas dokumentasi dan alat pemeriksaan dokumen, bukan perilaku transaksi aplikasi.

| Dokumen | Tanggung jawab | Yang tidak boleh disimpulkan darinya |
| --- | --- | --- |
| SRS kebutuhan | Definisi FR-01–29, BR-01–25 dan NFR-01–10 serta kriteria | Persetujuan pengguna atau bukti semua jalur sudah diuji. |
| ERD/PDM/kamus | Makna hubungan dan skema hasil migration | Semua aturan aplikasi sudah menjadi constraint SQL. |
| DFD/state/sequence | Arus data, status dan batas proses/transaksi | Seluruh proses adalah langkah sinkron atau satu query. |
| Bab III | Alur akademik Waterfall dan ringkasan rancangan | Riwayat pengembangan telah linear atau penelitian lapangan selesai. |
| Lampiran rancangan | RUC, kelas/deployment, logika, UI, pengujian dan instrumen | Fitur di luar baseline otomatis ditambahkan. |
| Panduan pengguna | Cara memakai menu dan arti hasil | Spesifikasi seluruh detail internal/API. |
| README/SECURITY | Instalasi, operasi dan kontrol yang diimplementasikan | Klaim lulus pentest, SLA atau restore production. |
| Source dan pengujian | Bukti perilaku yang dapat diamati pada baseline | Kebutuhan normatif dapat diam-diam diubah untuk menutupi ketidaksesuaian kode. |

Jika kebutuhan yang disepakati berbeda dari implementasi, catat perbedaan sebagai temuan/perubahan dan putuskan dengan pemilik proses. Memperbaiki salah uraian dokumen berbeda dari menerima perilaku yang menyimpang dari kebutuhan. Bukti pengujian teknis, hasil UAT dan rencana uji dipisahkan.

## 2. Daftar aturan yang harus sama antarbagian

| ID | Pokok | Interpretasi baseline yang diselaraskan | Acuan implementasi / dokumen |
| --- | --- | --- | --- |
| KS-01 | Tenant dan peran | Admin/pengurus/warga berbeda kewenangan; pengurus/warga aktif memerlukan lingkungan aktif. Otorisasi detail/file/ekspor diperiksa sesuai resource. | Middleware, policy; FR-01–05; AT-01. |
| KS-02 | Rumah sekarang/historis | Warga paling banyak satu rumah saat ini; invoice tetap memakai rumah ketika terbit; penanggung jawab konseptual tidak diasumsikan unique fisik. | HouseService, InvoiceIssuer; BR-02/09; ERD/PDM. |
| KS-03 | Tarif kosong | Custom mengganti total, exempt dilewati bila aturan aktif; opsi tarif tidak menghapus pembatasan penanggung jawab. | InvoiceIssuer; BR-03; ALG-01. |
| KS-04 | Periode/tagihan berulang | Beberapa invoice satu bulan boleh; run template unik per template/periode. Preview/simpan template tidak menerbitkan. | InvoiceTemplateService, migration; BR-04/12/21. |
| KS-05 | Nilai uang | Komponen tagihan baru integer Rp 1.000–Rp 999.999.999.999; total kewajiban baru maksimum Rp 9.999.999.999.999. Kas manual maksimum sama dengan dua desimal. Total pembayaran gabungan maksimum Rp 9.999.999.999.999,99. Ini tiga konteks validasi berbeda. | InvoiceController, InvoiceIssuer, CashflowController, BatchPaymentService. |
| KS-06 | Satu/gabungan | 1 ID memakai alur tunggal, 2–100 ID unik satu warga memakai batch. Tidak ada cicilan, sebagian keputusan atau nominal bebas. | BatchPaymentService; BR-06–08. |
| KS-07 | Cash dan akun nonaktif | Pengurus dapat menerima cash untuk warga nonaktif berutang; akun itu tetap tidak bisa login dan WhatsApp tidak memenuhi syarat. Pending tidak langsung diterima cash. | CashPaymentController, PaymentService, sender; panduan pembayaran. |
| KS-08 | Kuitansi | KW untuk tunggal, KWG untuk batch; satu kas/kuitansi per pembayaran; snapshot tidak mengikuti master. Legacy tanpa kuitansi diterbitkan ketika diminta dari data tersedia. | PaymentService, BatchPaymentService; BR-07/09/25. |
| KS-09 | Ukuran berkas | Batas aplikasi `5*1024` KiB = 5.242.880 byte (5 MiB) untuk bukti/nota; CSV 2.048 KiB = 2.097.152 byte (2 MiB). UI/panduan memakai label ringkas 5 MB/2 MB. Batas PHP/proxy dapat lebih rendah. | Validator controller; BR-10/11; BV-09/11. |
| KS-10 | Isi dan izin berkas | Bukti hanya JPG/PNG/WebP; nota juga PDF; akses privat. Riwayat/bukti tidak dibuka melalui storage publik. | PaymentProofController, CashflowController; UI-07/09. |
| KS-11 | Tanggal | Periode kewajiban, tanggal transaksi kas dan waktu catat berbeda. Waktu bisnis berasal dari server/WIB, bukan jam browser. | Cashflow::ACCOUNTING_DATE_SQL, Carbon, scheduler; BR-13. |
| KS-12 | Filter kas | Hasil filter/kategori terpisah dari saldo seluruh kas; source pembayaran mencakup semua komponen, bukan distribusi biaya per komponen. | ReportService::cashflow; panduan kategori; ALG-03. |
| KS-13 | Umur tunggakan | Unpaid/rejected due sebelum hari ini; termasuk warga nonaktif, tidak termasuk pending. Filter bucket memilih warga dan total tetap seluruh tunggakan warga itu. | ReportService::arrears; panduan laporan; ALG-03A; BB-24. |
| KS-14 | Rekap tahunan | Bulan tanpa invoice bukan lunas; unpaid/rejected didahulukan dari pending dalam status gabungan; seluruh verified baru lunas. House historis, status terkini, tahun 2000–2100. | AnnualReportService; BR-09/24; ALG-04. |
| KS-15 | Batas PDF | 2.000 baris hasil query biasa, 500 baris rumah/akun rekap tahunan. Baris tunggakan adalah agregat warga, bukan jumlah invoice detail. CSV tidak memakai batas PDF. | ReportController, AnnualReportController; BR-17. |
| KS-16 | Tutup/buka buku | Guard termasuk transaksi lebih lama yang memengaruhi saldo awal; edit cek tanggal lama/baru. Buka closed terakhir dahulu, alasan 10–1.000 karakter. | AccountingPeriodService dan observer; BR-14/15. |
| KS-17 | Tindak lanjut | Catatan dan status follow-up bukan bukti bayar dan tidak otomatis mengirim WhatsApp. Petugas aktif lingkungannya; janji/jadwal tidak lampau. | FollowUpController; BR-16. |
| KS-18 | Publikasi | Draft editable, published tidak diedit langsung, arsip hilang dari daftar aktif tetapi detail sah tetap dapat dibuka. Bukan WhatsApp publikasi. | AnnouncementController; BR-23; panduan pengumuman. |
| KS-19 | Waktu pengingat | H-3/H+1 diperiksa 08.00 WIB; outbox per menit bawaan 20 per eksekusi. Tidak catch-up massal kejadian lampau. | routes/console.php, SendInvoiceReminders, SendWhatsApp. |
| KS-20 | Umur antrean/retry | Lebih dari 24 jam dilewati; klaim 5 menit yang terhenti menjadi uncertain. Respons 2xx false/429 dapat dijadwalkan ulang maksimal lima pemrosesan; timeout/5xx tidak dikirim ulang otomatis. | WhatsAppSender, FonnteClient, SendWhatsApp; BR-18/19. |
| KS-21 | Makna status dan nomor | Accepted bukan delivered/read. Nomor disaring saat kirim, bukan dijamin valid mobile ketika form master disimpan. Tujuan terakhir terenkripsi; attempts bukan jumlah pasti HTTP. GET riwayat tidak mengirim pesan. | WhatsAppDelivery, WhatsAppHistoryController; BR-20. |
| KS-22 | Password/reset | Password sendiri memeriksa password saat ini, 8–128 karakter; reset pengurus oleh admin, reset warga oleh pengurus. Tidak ada reset admin oleh warga/pengurus atau email lupa password aktif. | ProfileController, ManagerController, ResidentController. |
| KS-23 | Error | Exception HTML yang mencapai Laravel memakai desain EnviroPay; JSON/validasi/redirect tetap. Proxy/error sebelum aplikasi perlu konfigurasi sendiri. | bootstrap/app.php, ErrorPagesTest; FR-29. |
| KS-24 | Historis dan bukti | Data lama tidak dihapus hanya karena dianggap usang; Web Push aktif sudah dihapus melalui migration. Log aktivitas bukan retroaktif/tamper-proof terhadap operator SQL. | migration 000006, FinancialActivityObserver; ERD/PDM, SECURITY. |

## 3. Cakupan rancangan dan kelengkapan

| Artefak | Cakupan baseline | Cara memeriksa |
| --- | --- | --- |
| Kebutuhan | 29 FR, 25 BR, 10 NFR | Definisi SRS dan matriks 08. |
| Tujuan pengguna | 13 kelompok RUC, termasuk alternatif/gagal/kondisi akhir | Spesifikasi RUC dan diagram use case per aktor. |
| Layar | 12 kelompok UI; WF-01–14 mencakup kelompok-kelompok tersebut | Matriks UI dan wireframe; bukan klaim seluruh modal memiliki gambar sendiri. |
| Data | 29 tabel, 269 kolom, 52 FK, 68 indeks pada SQLite hasil migration | Export memory-only dan pemeriksa PDM. |
| Proses | DFD konteks/level 1/2, status, aktivitas, sequence, kelas dan deployment | Diagram SRS dan tambahan rancangan, dibedakan notasinya. |
| Logika | Penerbitan, pembayaran, kas, aging, rekap tahunan, periode dan outbox | ALG-01–06 beserta ALG-03A. |
| Uji | 25 kelompok BB, 15 BV, 5 DT, 6 OP serta AT-01–23 | Matriks ID dan instrumen hasil; ini kasus rancangan, bukan seluruhnya hasil lulus. |
| Penelitian | Form identitas/izin, OBS, WAW, DOK, VAL, UAT, temuan dan gate | Lampiran instrumen; isian nyata memerlukan pelaksanaan. |
| Referensi | Sumber metode, teknik uji dan matriks verifikasi/validasi | Metadata dan pemakaian sumber, tanpa Bab I/II. |

## 4. Temuan penyelarasan pada 8 November 2026

- Pemetaan FR ke RUC/UI/AT dilengkapi dengan hubungan langsung ke kasus BB. Seluruh BR dan NFR mendapat tabel kontrol, source/pengujian dan batas bukti masing-masing.
- Kasus rekening/dashboard tidak lagi hanya disebut sebagai tambahan AT; tersedia BB-21/22. Kuitansi historis, semantik aging dan umur antrean diuraikan pada BB-23–25 serta BV-13–15.
- Penjelasan reset password pada panduan diperbaiki: admin mereset pengurus, pengurus mereset warga; bukan pengurus mereset admin.
- Perbedaan maksimum nominal tagihan, kas manual dan total pembayaran diperjelas; label ukuran file dipadankan dengan byte pada validator.
- Wireframe ditambah untuk seluruh kelompok UI, tanpa menambahkan menu/fitur baru pada kode aplikasi. Diagram pengurus memisahkan pemantauan riwayat dari tujuan pengiriman Fonnte.
- Pemeriksaan dokumen dibuat dapat dijalankan ulang melalui `docs/tools/` sehingga pembuktian tautan, ID, matriks dan skema tidak bergantung file sementara dari satu sesi.

Hasil perintah dan pengujian terkini dicatat dalam [laporan pemeriksaan](../AUDIT-DOKUMENTASI.md). Jumlah artefak adalah pemeriksaan kelengkapan struktur, bukan bukti semua kalimat benar atau kebutuhan sudah disahkan.

## 5. Bagian yang tetap membutuhkan bukti penelitian

| Kelengkapan akhir | Yang sudah disediakan | Yang harus berasal dari penelitian/keputusan nyata |
| --- | --- | --- |
| Objek dan proses berjalan | Model kandidat, pertanyaan dan lembar observasi | Nama lokasi, izin, tanggal, catatan dan konfirmasi proses. |
| Persetujuan kebutuhan | FR/BR/NFR serta form VAL/gate | Pengesahan pemilik proses dan keputusan revisi. |
| UAT | AT/BB, langkah/harapan dan formulir | Peserta, hasil aktual, temuan, keputusan dan persetujuan. |
| Kinerja/operasi | OP, indikator dan cara pencatatan | Target kapasitas/SLA, hasil beban, restore, Fonnte nyata dan konfigurasi production. |
| Format kampus | Narasi Bab III, caption kerja, sumber gambar dan referensi | Ketentuan pembimbing, format halaman/daftar pustaka dan pemeriksaan hasil konversi Word/PDF. |

Dokumentasi rancangan dapat dinilai lengkap terhadap baseline yang didefinisikan di atas, sementara kelengkapan empiris/naskah final dinilai dari bukti pada tabel ini. Tidak ada jawaban informan, tanggal kegiatan atau hasil penerimaan yang diisi fiktif untuk menutup bagian yang masih terbuka.
