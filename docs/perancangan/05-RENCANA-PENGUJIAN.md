# Rancangan Pengujian dan Evaluasi

[Bab III](../BAB-III-PERANCANGAN.md) · [AT dan matriks SRS](../srs/05-VERIFIKASI.md) · [Formulir hasil](06-INSTRUMEN-DAN-KELENGKAPAN.md#5-formulir-pengujian-dan-uat)

## 1. Tujuan dan kedudukan

Rencana ini memeriksa kesesuaian fungsi, integritas keuangan, otorisasi dan alur pengguna. **BB-01–25 serta kasus nilai batas di bawah adalah rancangan uji, bukan tabel hasil lulus.** Keberadaan nama test otomatis yang berkaitan tidak menyatakan setiap nilai pada rancangan sudah dieksekusi. Hasil aktual, status dan bukti diisi setelah pengujian penelitian.

Partisi ekuivalensi memisahkan kelas input yang diperlakukan sama; nilai batas menguji peralihan rentang; tabel keputusan menguji kombinasi aturan; transisi status memeriksa urutan yang diperbolehkan. Penggunaan teknik ini mengikuti [ISTQB CTFL v4.0.1, bagian 4.2](https://istqb.org/wp-content/uploads/2024/11/ISTQB_CTFL_Syllabus_v4.0.1.pdf). PHPUnit digunakan sebagai alat otomatisasi; tidak ada klaim seluruh path/branch kode telah tercakup tanpa pengukuran coverage.

## 2. Lingkungan dan data uji

| Elemen | Rancangan |
| --- | --- |
| Identitas build | Catat commit, konfigurasi tersensor, versi runtime/DB/browser, tanggal dan penguji. |
| Database | SQLite in-memory untuk suite teknis; staging terpisah untuk UAT/target DB production. |
| Perumahan | H-A dan H-B fiktif untuk menguji isolasi. |
| Akun | Satu admin; pengurus/warga pada masing-masing lingkungan; tambah penghuni bukan penanggung jawab untuk kasus rumah. |
| Kewajiban | Status unpaid/pending/rejected/verified, satu/banyak periode, tanpa rumah, serta anggota batch ditolak/digantikan. |
| Waktu | Catat tanggal acuan T0 dari server; simulasikan waktu pada lingkungan test, jangan mengubah jam server production. |
| Berkas | File gambar/PDF valid, MIME palsu, kosong, dan ukuran batas tanpa data warga. |
| Provider | Fake HTTP untuk accepted/429/4xx/5xx/timeout; uji nyata terpisah dengan nomor sendiri dan izin operator. |
| Backup/pemulihan | Salinan staging yang memuat DB, APP_KEY/environment dan private storage; jangan memulihkan di atas data aktif tanpa prosedur. |

## 3. Matriks black-box

Semua kasus memiliki status awal **belum dicatat sebagai eksekusi penelitian**. Setiap baris dapat dipecah ke subkasus per input. Kolom hasil aktual tersedia pada instrumen, bukan diisi dari hasil harapan.

| ID | Prasyarat dan masukan | Tindakan | Hasil yang diharapkan | Acuan |
| --- | --- | --- | --- | --- |
| BB-01 | Akun aktif/nonaktif, kredensial benar/salah dan target reset berbeda role/lingkup | Login, logout, ganti/reset password | Validasi benar, sesi terkait dicabut, akses asing ditolak | FR-01–03, FR-27–28; RUC-01; AT-01/17/18 |
| BB-02 | Admin dan pengurus; perumahan kosong/berhistori | Kelola/nonaktifkan/hapus master | Hanya admin berwenang, histori tidak terhapus, sesi nonaktif ditolak | FR-04; RUC-02; AT-17 |
| BB-03 | Rumah/warga satu lingkungan, penghuni dan penanggung jawab | Hubungkan/pindahkan warga dan tarif | Penanggung jawab sesuai, tagihan historis tetap | FR-05; RUC-03; AT-02/03 |
| BB-04 | CSV baru/cocok/salah, expired, benturan setelah pratinjau | Preview dan konfirmasi | Upsert sesuai, batch salah tidak parsial, konfirmasi ulang tidak menggandakan | FR-06; RUC-03; AT-02 |
| BB-05 | Rumah dihuni/kosong custom/exempt, komponen/total batas | Terbitkan manual | Penerima dan nominal sesuai keputusan ALG-01 | FR-08; RUC-04; AT-03 |
| BB-06 | Template aktif/nonaktif, periode baru/ulang, rencana berubah | Preview lalu issue | Tidak ada side effect preview, run ganda/stale ditolak | FR-09; RUC-04; AT-04 |
| BB-07 | Satu invoice sendiri unpaid/rejected dan bukti valid/salah | Unggah transfer | Valid menjadi pending, file privat; salah ditolak | FR-10; RUC-05; AT-05 |
| BB-08 | Jumlah ID batas, duplikat, pemilik campuran, total berubah | Ajukan batch | 1 memakai alur tunggal, 2–100 batch, pilihan ilegal ditolak | FR-13; RUC-05; AT-07 |
| BB-09 | Transfer pending tunggal/batch, status/anggota berubah | Sahkan/tolak dan ulangi keputusan | Atomik, alasan wajib, satu kas/kuitansi, keputusan ulang ditolak | FR-11/13; RUC-06; AT-05/07 |
| BB-10 | Cash atas kewajiban lampau/berjalan; kuitansi akses sendiri/asing | Konfirmasi uang dan unduh kuitansi | Cash verified langsung, snapshot tetap, tidak ada akses asing | FR-12–14; RUC-06; AT-06 |
| BB-11 | Transaksi manual/otomatis, tanggal/nominal/nota batas | Tambah/edit/hapus kas dan nota | Manual valid tersimpan; otomatis/future/file asing ditolak | FR-15; RUC-07; AT-08 |
| BB-12 | Saldo fixture dan filter kategori/jenis/sumber | Lihat kas, CSV dan PDF | Total filter konsisten, saldo keseluruhan terpisah, seluruh hasil sesuai filter | FR-16; RUC-08; AT-09 |
| BB-13 | Rumah multi-invoice, pindah penghuni dan tahun batas | Buka rekap/detail/ekspor | Lunas hanya jika semua verified; rumah historis tetap; tanpa invoice bukan lunas | FR-17; RUC-08; AT-10 |
| BB-14 | Petugas aktif/asing, status janji/jadwal, tanggal lampau | Catat tindak lanjut | Valid tersimpan, janji/jadwal wajib tanggal sah, tidak mengubah invoice | FR-18; RUC-09; AT-11 |
| BB-15 | Bulan lampau/berjalan, closed terbaru/lebih awal, alasan batas | Tutup/buka buku | Hanya keputusan sah; perubahan terdampak periode tertutup ditolak | FR-19; RUC-10; AT-12 |
| BB-16 | Mutasi data dan kegagalan simpan audit pada lingkungan test | Jalankan transaksi dan rollback | Log sesuai whitelist; kegagalan menggagalkan transaksi terkait | FR-20; RUC-10; AT-07/12 |
| BB-17 | Draft/published/archived dan notifikasi akun lain | Publish/arsip/baca/read-all | Status/otorisasi sesuai; tidak memicu WhatsApp publikasi | FR-21–22; RUC-11; AT-20/21 |
| BB-18 | Tagihan due T0+3, T0−1, status layak/tidak, provider fake | Periksa pengingat dan proses antrean | H-3/H+1 tepat, deduplikasi/retry/uncertain sesuai | FR-23–24; RUC-12; AT-13/14 |
| BB-19 | Riwayat lingkungan berbeda dan nomor warga diubah setelah percobaan | Filter/buka detail | Ringkasan filter benar, nomor terakhir tidak ditebak, GET tidak mengirim pesan | FR-25; RUC-12; AT-15 |
| BB-20 | HTML/JSON, route hilang, sesi/rate limit dan exception internal | Akses endpoint uji terkontrol | UI aman dengan kode/header benar; JSON/validasi/redirect tetap | FR-29; RUC-13; AT-23 |
| BB-21 | Rekening sendiri/asing, aktif/nonaktif, duplikat bank+nomor dalam perumahan | Tambah/edit/nonaktifkan dan tinjau rekening warga | UQ dan otorisasi diterapkan; hanya rekening aktif sebagai pilihan; invoice tidak diubah menjadi FK rekening | FR-07; RUC-03; AT-19 |
| BB-22 | Dashboard tiga role dan dua lingkungan | Buka ringkasan, grafik dan antrean sesuai role | Data sesuai peran/lingkup; kewajiban warga hanya sendiri, transparansi kas tetap level lingkungan | FR-26; RUC-08; AT-22 |
| BB-23 | Invoice terverifikasi lama tanpa kuitansi dan kuitansi baru yang sudah diterbitkan | Buka/cetak/PDF, ubah master lalu buka ulang | Legacy diterbitkan sekali dari data tersedia; snapshot terbit tetap; akses asing ditolak | FR-14; RUC-06; AT-05/06 |
| BB-24 | Tunggakan umur 1/30/31/60/61 hari dan beberapa bucket pada satu warga, termasuk warga nonaktif | Filter umur dan ekspor | Pending/due hari ini dikecualikan; filter memilih warga, total tetap seluruh tunggakan warga terpilih | FR-16; RUC-08; AT-09 |
| BB-25 | Antrean berumur batas, nomor salah, klaim terhenti, tanggal due berubah dan konfigurasi nonaktif | Jalankan sender dengan fake HTTP | skipped/uncertain sesuai sebab; tidak menebak nomor historis, mengulang hasil ambigu atau mengirim retroaktif | FR-23–25; RUC-12; AT-13–15 |

AT-19 dan AT-22 dipetakan secara eksplisit ke BB-21/22. Seluruh FR tetap dipetakan ke AT pada SRS dan UI pada rancangan antarmuka.

## 4. Partisi ekuivalensi dan nilai batas

| ID | Parameter | Partisi valid / tidak valid | Nilai kandidat di batas | Harapan utama |
| --- | --- | --- | --- | --- |
| BV-01 | Jumlah invoice dipilih | 1..100 unik / 0, >100, duplikat | 0, 1, 2, 99, 100, 101 | 1 tunggal; 2..100 batch; sisanya/duplikat ditolak. |
| BV-02 | Komponen baru | 1..50 / 0, >50 | 0, 1, 2, 49, 50, 51 | Tidak ada komponen kosong atau melewati batas. |
| BV-03 | Nominal komponen baru | Integer 1.000..999.999.999.999 / di luar/fraction | 999, 1.000, 1.001; max−1, max, max+1; 1.000,50 | Rentang/format diterima atau ditolak sesuai aturan server. |
| BV-04 | Hari jatuh tempo template | 1..31 / 0, >31 | 0, 1, 2, 30, 31, 32 | 31 dijepit bila bulan lebih pendek, bukan tanggal melompat ke bulan lain. |
| BV-05 | Offset bulan template | 0..1 / di luar | −1, 0, 1, 2 | Hanya bulan periode/berikutnya. |
| BV-06 | Tahun rekap | 2000..2100 / di luar atau noninteger | 1999, 2000, 2001, 2099, 2100, 2101 | Validasi eksplisit; opsi dropdown tetap dalam rentang. |
| BV-07 | Alasan buka buku | 10..1.000 karakter / pendek/panjang | 9, 10, 11, 999, 1.000, 1.001 | Alasan sah tersimpan bersama perubahan status. |
| BV-08 | Tanggal transaksi/aksi | Kas <=T0; janji/jadwal >=T0 | T0−1, T0, T0+1 | Validasi bergantung jenis tanggal, bukan disamakan. |
| BV-09 | File bukti/nota | Format sah <=5 MiB / format salah atau lebih | 5.120 KiB−1 byte, 5.120 KiB, +1 byte; MIME palsu | Batas aplikasi/MIME diterapkan bila request mencapai Laravel. |
| BV-10 | PDF laporan | <=2.000 baris biasa atau <=500 tahunan | 1.999/2.000/2.001; 499/500/501 | Lewat batas ditolak, tanpa truncation diam-diam. |
| BV-11 | CSV | <=2 MiB dan batas baris / melebihi | 2.048 KiB−1 byte, tepat, +1; rumah 999/1.000/1.001, warga 99/100/101 | Ukuran/baris berlebih ditolak. |
| BV-12 | Password sendiri | 8..128 karakter, konfirmasi dan berbeda / lainnya | 7/8/9, 127/128/129; salah konfirmasi/identik lama | Aturan endpoint password sendiri diterapkan; jangan menganggap semua endpoint reset memiliki batas maksimum identik. |
| BV-13 | Umur tunggakan | Lewat jatuh tempo / hari ini/masa depan/pending | 0, 1, 29, 30, 31, 59, 60, 61 hari | Filter bucket memilih warga, tidak memangkas invoice warga itu ke bucket saja. |
| BV-14 | Tanggal pengingat | Due T0+3 dan T0−1 / tanggal lain | T0+4, +3, +2, T0, T0−1, T0−2 | Hanya H-3/H+1 pada tanggal pemeriksaan; tidak catch-up massal. |
| BV-15 | Umur antrean / klaim | <=24 jam / >24 jam; klaim berakhir | Freeze waktu test: 24 jam−1 detik, tepat 24 jam, +1 detik; claimed_until tepat T0 | Antrean lebih tua dilewati; klaim processing yang berakhir menjadi uncertain tanpa kirim ulang otomatis. |

Nilai nominal dikirim sebagai nilai numerik yang sesuai, bukan string berformat ribuan dari tabel. Ukuran file ditentukan dalam byte; tulis nilai aktual, MIME serta batas PHP/proxy pada bukti uji. Batas jumlah komponen/nominal, PDF dan input lainnya tidak disamakan dengan kapasitas beban serentak.

## 5. Tabel keputusan pelunasan

| Kasus | Aktor berwenang | Status sesuai metode | Total/anggota sesuai | Buku terdampak terbuka | Keputusan |
| --- | --- | --- | --- | --- | --- |
| DT-01 | Ya | Ya | Ya | Ya | Commit verified, satu kas dan kuitansi. |
| DT-02 | Tidak | — | — | — | Tolak; tidak memberikan akses data asing. |
| DT-03 | Ya | Tidak | — | — | Tolak keputusan/pembayaran ulang atau perubahan status. |
| DT-04 | Ya | Ya | Tidak | — | Tolak total/anggota berubah, tidak parsial. |
| DT-05 | Ya | Ya | Ya | Tidak | Tolak dan rollback mutasi bisnis terkait. |

Tanda — berarti kondisi tidak diperlukan untuk menyimpulkan keputusan, bukan diisi “Ya”. Untuk transfer pengesahan memerlukan pending; cash memerlukan unpaid/rejected. Penolakan transfer memakai jalur berbeda yang tidak membuat kas/kuitansi lunas.

## 6. Kriteria awal, akhir dan pencatatan hasil

Kriteria awal: baseline/lingkungan dapat diidentifikasi, fixture fiktif tersedia, fake HTTP aktif untuk pengujian provider, dan scope eksekusi disepakati. UAT dilaksanakan sesudah kasus kritis teknis tidak memiliki temuan penghalang terbuka.

Kriteria akhir yang diusulkan: semua kasus yang disepakati tercatat, seluruh kasus kritis keuangan/akses sesuai harapan, tidak ada temuan kritis yang belum ditutup, temuan tinggi diselesaikan atau keputusan penundaan/penerimaan risikonya terdokumentasi, serta pengguna menandatangani keputusan UAT per lingkup. Kriteria ini perlu disetujui pembimbing/pemilik; belum menyatakan G4 telah tercapai.

Status hasil: **Lulus**, **Gagal**, **Terhalang**, **Belum dijalankan**. Tulis langkah, input, hasil harapan, aktual, log/screenshot tersensor, pelaksana, waktu, commit dan ID temuan. Kasus yang diperbaiki mempunyai ID rerun dan hubungan ke temuan awal.

## 7. Evaluasi dan perhitungan

```text
persentase_kasus_lulus = kasus_lulus / kasus_dijalankan * 100%
kasus_dijalankan = kasus_lulus + kasus_gagal
```

Kasus terhalang/belum dijalankan dilaporkan terpisah. Bila belum ada kasus dijalankan, persentase tidak didefinisikan, bukan 0% atau 100%. Persentase kasus lulus tidak mengukur persentase bebas bug, kepuasan pengguna, maupun coverage kode.

Jika hendak membandingkan durasi tugas sebelum/sesudah, definisikan tugas, volume/kelengkapan data, peserta dan cara pencatatan waktu yang sebanding. Rumus penurunan durasi adalah `(durasi_awal − durasi_baru) / durasi_awal * 100%`, hanya ketika durasi_awal >0 dan data benar-benar dikumpulkan. Tidak ada angka efektivitas fiktif yang diberikan.

UAT dapat mencatat keberhasilan tugas, jumlah bantuan, kesalahan dan komentar. Paket ini tidak menyisipkan skor SUS atau kuesioner terstandar tanpa instrumen/sumber dan prosedur yang benar.

## 8. Bukti otomatis dan batasnya

Suite proyek mencakup PHPUnit SQLite in-memory dan Node test runner. Catatan historis setelah fitur error adalah 173 tes backend/1.714 assertion; angka ini merujuk [rekaman SRS](../srs/05-VERIFIKASI.md#6-verifikasi-halaman-error--versi-12), bukan eksekusi penelitian baru tanggal dokumen ini. Rerun dengan perintah pada SRS saat mengumpulkan bukti penelitian; lampirkan log asli.

Pemeriksaan dokumen meliputi konsistensi ID, tautan, sumber diagram dan skema. Ini tidak menggantikan UAT, load test, uji provider nyata, simulasi restore atau pengujian seluruh kombinasi browser. Bab III berisi rencana; hasil aktual dan pembahasannya ditempatkan sesuai struktur hasil penelitian yang ditetapkan kampus.

## 9. Rencana uji nonfungsional dan operasional

Semua OP berikut berstatus **rencana**, bukan hasil lulus. Target angka/lingkungan ditetapkan sebelum eksekusi; form hasil menyimpan nilai aktual dan bukti. Pemeriksaan aplikasi yang sudah tersedia hanya menjadi bagian bukti, bukan pengganti uji operasional.

| ID | Lingkup dan langkah rancangan | Data yang dicatat | Kriteria yang ditinjau |
| --- | --- | --- | --- |
| OP-01 | Restore di staging terisolasi dari DB, APP_KEY/environment dan private storage yang konsisten | Versi backup, waktu proses, jumlah/nominal sampel, akses bukti/nota dan dekripsi data | Integritas/akses pulih; RPO/RTO dinilai terhadap target yang sudah disepakati, belum diisi di sini. |
| OP-02 | Periksa cron, timezone, hak file, cache bersama dan proses setelah deploy; simulasikan jadwal dalam test | Schedule list, log tersensor, T0, queue depth dan status | Tidak ada pemicu ganda yang menghasilkan pembayaran/pesan ganda; jadwal sesuai WIB. |
| OP-03 | Uji nomor sendiri pada Fonnte, tagihan khusus uji dan kuitansi; telaah dashboard provider | Izin operator, status perangkat/kuota, waktu/nomor tersensor, status lokal/provider | Tautan valid dan pemetaan status benar; accepted tetap tidak dianggap read/delivered. |
| OP-04 | Pilih DB production, volume fixture dan profil beban berdasarkan kebutuhan yang disepakati; jalankan di staging | Jumlah akun/data/concurrency, konfigurasi, latensi persentil, error rate, resource dan kasus lock | Hasil dibandingkan dengan target tertulis; hasil SQLite memory/localhost bukan bukti kapasitas production. |
| OP-05 | Jalankan tugas per role pada browser/ukuran layar yang ditetapkan, termasuk keyboard, empty/error, tabel dan ekspor | Perangkat, versi, viewport, keberhasilan tugas, bantuan dan masalah | Hasil tugas dan fokus/label dapat dinilai; tidak mengklaim seluruh browser atau skor SUS. |
| OP-06 | Inspeksi akses environment/backup, private files, token/log whitelist, TLS dan konfigurasi debug/cookie | Konfigurasi tersensor, hasil akses yang diizinkan/ditolak, pihak pemeriksa | Sesuai rancangan akses dan retensi yang disetujui; bukan sertifikasi keamanan atau pentest menyeluruh. |
