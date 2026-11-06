# DFD dan Alur Proses

[Indeks SRS](../SRS.md) · [Kebutuhan](01-KEBUTUHAN.md) · [Verifikasi](05-VERIFIKASI.md)

## 1. Notasi dan batas sistem

DFD menggunakan kotak untuk entitas eksternal, bentuk membulat untuk proses, silinder untuk penyimpanan, serta panah berlabel nama data. Mermaid dipakai sebagai media gambar dengan notasi DFD yang disederhanakan. Panah menunjukkan perpindahan data, bukan urutan klik atau perpindahan uang fisik. Urutan peristiwa dijelaskan terpisah pada sequence diagram.

Konvensi level: **diagram konteks / proses 0** menampilkan satu EnviroPay; **level 1** menguraikan proses 0; **level 2** menguraikan proses 3.0 pembayaran. Scheduler berada dalam batas operasional EnviroPay. Fonnte di luar sistem; warga adalah penerima pesan akhir melalui Fonnte. Transfer uang berlangsung di luar aplikasi, yang menerima data/bukti pembayaran saja.

## 2. Diagram konteks

```mermaid
flowchart LR
    A[Admin] -->|Kredensial, data perumahan dan pengurus| P0([0. EnviroPay])
    P0 -->|Hasil autentikasi, daftar dan ringkasan administrasi| A
    M[Pengurus] -->|Kredensial, data administrasi, penagihan, pembayaran, kas, komunikasi dan filter| P0
    P0 -->|Hasil operasi, daftar, laporan, dokumen dan riwayat| M
    W[Warga] -->|Kredensial, pilihan tagihan, bukti dan permintaan data| P0
    P0 -->|Tagihan, rekening, status, kuitansi, transparansi kas dan komunikasi| W
    P0 -->|Target dan pesan WhatsApp| F[Fonnte]
    F -->|Respons API pengiriman| P0
```

WhatsApp diteruskan Fonnte kepada warga di luar batas aplikasi. Tidak ada arus balik status dibaca/delivered melalui webhook pada baseline.

## 3. Kamus penyimpanan DFD

| ID | Penyimpanan logis | Realisasi |
| --- | --- | --- |
| D1 | Identitas dan master lingkungan | housings, users, houses, house_histories, payment_accounts, sessions |
| D2 | Penagihan | invoices, invoice_components, invoice_templates, invoice_template_runs |
| D3 | Pembayaran dan kuitansi | payment_batches, payment_batch_invoice, receipts; atribut pembayaran pada invoices |
| D4 | Kas dan periode buku | cashflows, accounting_periods |
| D5 | Komunikasi | announcements, notifications, whatsapp_deliveries |
| D6 | Audit dan tindak lanjut | activity_logs, arrears_follow_ups |
| D7 | Dokumen privat | File bukti transfer dan nota kas; path pada database |
| D8 | Pratinjau impor | house_imports, resident_imports |

Pengelompokan bersifat logis. Atribut pembayaran dalam D2 dan referensi D3 tetap satu database fisik; diagram tidak menyatakan ada dua salinan tabel invoices.

## 4. DFD level 1

Diagram dibagi menjadi beberapa tampilan pada **level yang sama** agar label dapat dibaca. Node dengan ID yang sama adalah proses/aktor/penyimpanan yang sama; proses di luar fokus ditampilkan sebagai referensi. Gabungan tampilan berikut mempertahankan seluruh arus data pada level ini.

### Administrasi dan penagihan

```mermaid
flowchart TB
    A[Admin]
    D1[(D1 Master)]
    D2[(D2 Penagihan)]
    D8[(D8 Impor)]
    M[Pengurus]
    P1([1.0 Autentikasi dan master])
    P2([2.0 Penagihan])
    P5([5.0 Komunikasi dan scheduler])
    P6([6.0 Tindak lanjut dan audit])
    W[Warga]
    A -->|Kredensial dan administrasi| P1
    M -->|Kredensial, master dan CSV| P1
    W -->|Kredensial| P1
    P1 -->|Hasil autentikasi dan administrasi| A
    P1 -->|Hasil autentikasi dan master| M
    P1 -->|Hasil autentikasi| W
    D1 -->|Identitas, sesi dan master tersimpan| P1
    P1 -->|Master dan sesi diperbarui| D1
    P1 -->|Pratinjau dan hasil konfirmasi| D8
    D8 -->|Rows dan masa berlaku pratinjau| P1
    M -->|Komponen, penerima, template dan konfirmasi| P2
    D1 -->|Warga, rumah dan tarif| P2
    P2 -->|Tagihan, komponen, template dan run| D2
    D2 -->|Data penagihan tersimpan| P2
    W -->|Permintaan daftar dan detail tagihan sendiri| P2
    P2 -->|Daftar, detail dan status tagihan| W
    P2 -->|Hasil penerbitan dan pratinjau| M
    P2 -->|Kejadian terbit| P5
    P2 -->|Perubahan penagihan| P6
```

### Pembayaran

```mermaid
flowchart LR
    D1[(D1 Master)]
    D2[(D2 Penagihan)]
    D3[(D3 Pembayaran)]
    D4[(D4 Kas dan buku)]
    D7[(D7 File privat)]
    M[Pengurus]
    P3([3.0 Pembayaran])
    P5([5.0 Komunikasi dan scheduler])
    P6([6.0 Tindak lanjut dan audit])
    W[Warga]
    M -->|Permintaan bukti atau kuitansi, keputusan transfer dan cash| P3
    W -->|Pilihan tagihan, bukti, permintaan kuitansi| P3
    D1 -->|Identitas dan rekening pembayaran| P3
    D2 -->|Tagihan, pemilik, nominal dan status| P3
    P3 -->|Status dan atribut pembayaran diperbarui| D2
    P3 -->|Batch/anggota/kuitansi| D3
    D3 -->|Data pembayaran| P3
    P3 -->|File bukti transfer| D7
    D7 -->|Bukti tersimpan| P3
    D4 -->|Periode tertutup| P3
    P3 -->|Pemasukan pembayaran sah| D4
    P3 -->|Status, rekening dan kuitansi| W
    P3 -->|Antrean, bukti, hasil pembayaran dan kuitansi| M
    P3 -->|Kejadian pembayaran| P5
    P3 -->|Perubahan pembayaran| P6
```

### Kas dan pelaporan

```mermaid
flowchart TB
    D1[(D1 Master)]
    D2[(D2 Penagihan)]
    D3[(D3 Pembayaran)]
    D4[(D4 Kas dan buku)]
    D7[(D7 File privat)]
    M[Pengurus]
    P4([4.0 Kas dan pelaporan])
    P6([6.0 Tindak lanjut dan audit])
    W[Warga]
    M -->|Transaksi, nota, filter dan keputusan buku| P4
    D4 -->|Kas dan status periode| P4
    P4 -->|Kas dan keputusan penutupan| D4
    P4 -->|File nota| D7
    D7 -->|Nota tersimpan| P4
    D2 -->|Tagihan dan periode| P4
    D3 -->|Data pembayaran| P4
    D1 -->|Rumah dan identitas| P4
    P4 -->|Laporan, ekspor dan ringkasan| M
    W -->|Permintaan dashboard transparansi| P4
    P4 -->|Transparansi kas dashboard| W
    P4 -->|Perubahan kas dan buku| P6
```

### Komunikasi, audit dan tindak lanjut

```mermaid
flowchart TB
    D1[(D1 Master)]
    D2[(D2 Penagihan)]
    D3[(D3 Pembayaran)]
    D5[(D5 Komunikasi)]
    D6[(D6 Audit dan tindak lanjut)]
    F[Fonnte]
    M[Pengurus]
    P2([2.0 Penagihan])
    P3([3.0 Pembayaran])
    P4([4.0 Kas dan pelaporan])
    P5([5.0 Komunikasi dan scheduler])
    P6([6.0 Tindak lanjut dan audit])
    W[Warga]
    M -->|Pengumuman dan filter riwayat| P5
    W -->|Permintaan notifikasi dan pengumuman| P5
    P2 -->|Kejadian terbit| P5
    P3 -->|Kejadian pembayaran| P5
    D1 -->|Keaktifan dan nomor| P5
    D2 -->|Jatuh tempo dan status| P5
    D3 -->|Snapshot kuitansi| P5
    P5 -->|Pengumuman, notifikasi, tanda baca dan hasil outbox| D5
    D5 -->|Pengumuman, lonceng dan antrean tersimpan| P5
    P5 -->|Target dan pesan| F
    F -->|Respons API| P5
    P5 -->|Lonceng dan pengumuman| W
    P5 -->|Lonceng, pengumuman dan riwayat kirim| M
    M -->|Catatan tindak lanjut dan filter aktivitas| P6
    D1 -->|Pengurus dan warga| P6
    D2 -->|Tunggakan| P6
    P2 -->|Perubahan penagihan| P6
    P3 -->|Perubahan pembayaran| P6
    P4 -->|Perubahan kas dan buku| P6
    P6 -->|Catatan dan perubahan terpilih| D6
    D6 -->|Riwayat aktivitas dan tindak lanjut| P6
    P6 -->|Daftar tunggakan, catatan dan log| M
```

Hak akses, perubahan password dan validasi kepemilikan merupakan tanggung jawab lintas proses, dilambangkan oleh 1.0 dan diterapkan pada setiap endpoint. Proses 5.0 juga menerima pemicu internal scheduler; pemicu waktu bukan arus data dari aktor eksternal baru.

## 5. DFD level 2 — proses 3.0 pembayaran

Diagram dibagi menjadi beberapa tampilan pada **level yang sama** agar label dapat dibaca. Node dengan ID yang sama adalah proses/aktor/penyimpanan yang sama; proses di luar fokus ditampilkan sebagai referensi. Gabungan tampilan berikut mempertahankan seluruh arus data pada level ini.

### Pengajuan transfer

```mermaid
flowchart TB
    D1[(D1 Master)]
    D2[(D2 Penagihan)]
    D3[(D3 Pembayaran)]
    D7[(D7 File privat)]
    P31([3.1 Validasi pilihan dan unggahan])
    P32([3.2 Simpan pengajuan transfer])
    P5([5.0 Komunikasi])
    P6([6.0 Tindak lanjut dan audit])
    W[Warga]
    W -->|ID tagihan, total yang ditinjau dan bukti| P31
    D1 -->|Pemilik, keaktifan dan rekening| P31
    D2 -->|Status dan nominal server| P31
    P31 -->|Pilihan valid dan bukti| P32
    P31 -->|Validasi atau informasi rekening| W
    P32 -->|Status pending dan metadata| D2
    P32 -->|Batch transfer bila gabungan| D3
    P32 -->|File bukti| D7
    P32 -->|Kejadian pengajuan| P5
    P32 -->|Hasil pengajuan| W
    P32 -->|Perubahan pengajuan| P6
```

### Keputusan transfer dan cash

```mermaid
flowchart LR
    D1[(D1 Master)]
    D2[(D2 Penagihan)]
    D3[(D3 Pembayaran)]
    D4[(D4 Kas dan buku)]
    D7[(D7 File privat)]
    M[Pengurus]
    P33([3.3 Validasi keputusan dan lock data])
    P34([3.4 Sahkan atomik])
    P35([3.5 Tolak transfer])
    P5([5.0 Komunikasi])
    P6([6.0 Tindak lanjut dan audit])
    M -->|Permintaan bukti atau verifikasi, keputusan transfer atau cash| P33
    D2 -->|Status dan anggota saat ini| P33
    D3 -->|Batch dan snapshot| P33
    D1 -->|Pengurus dan perumahan| P33
    D7 -->|Bukti melalui endpoint privat| P33
    P33 -->|Pilihan sah, nominal dan metode| P34
    P33 -->|Alasan penolakan valid| P35
    D4 -->|Periode tertutup| P34
    P34 -->|Pemasukan pembayaran sah| D4
    P34 -->|Verified dan waktu konfirmasi| D2
    P34 -->|Batch sah dan snapshot kuitansi| D3
    P35 -->|Rejected dan alasan| D2
    P35 -->|Batch ditolak bila gabungan| D3
    P34 -->|Kejadian lunas| P5
    P35 -->|Kejadian ditolak| P5
    P34 -->|Perubahan keuangan| P6
    P35 -->|Perubahan status| P6
    P33 -->|Validasi, bukti dan antrean verifikasi| M
    P34 -->|Hasil pelunasan dan kuitansi| M
    P35 -->|Hasil penolakan| M
```

### Penyajian kuitansi

```mermaid
flowchart TB
    D2[(D2 Penagihan)]
    D3[(D3 Pembayaran)]
    M[Pengurus]
    P36([3.6 Otorisasi dan sajikan kuitansi])
    W[Warga]
    W -->|Permintaan kuitansi| P36
    M -->|Permintaan kuitansi lingkungan| P36
    P36 -->|Kuitansi HTML atau PDF| M
    D3 -->|Snapshot kuitansi| P36
    P36 -->|Kuitansi historis bila belum tersedia| D3
    D2 -->|Pemilik, status dan relasi| P36
    P36 -->|Kuitansi HTML atau PDF| W
```

Untuk menjaga diagram terfokus, permintaan kuitansi oleh pengurus mengikuti proses 3.6 yang sama dengan otorisasi perumahan. Dua arah pada penyimpanan telah dipisah menjadi data baca/tulis. Data lintas batas pada level 1 dipertahankan: pilihan/bukti warga, keputusan pengurus, rekening/status/kuitansi, perubahan D2/D3/D4/D7 dan kejadian ke proses 5.0/6.0. Cabang gagal validasi tidak mengubah status keuangan.

## 6. Use case utama

| ID | Aktor | Pemicu / prasyarat | Alur sukses | Pengecualian |
| --- | --- | --- | --- | --- |
| UC-01 | Admin | Login aktif | Buat perumahan dan akun pengurus | Identitas ganda atau data berelasi membatasi operasi. |
| UC-02 | Pengurus | Master siap | Unggah CSV, tinjau, konfirmasi tambah/perbarui | Baris salah, kedaluwarsa atau benturan saat konfirmasi menahan batch. |
| UC-03 | Pengurus | Warga/rumah valid | Buat manual atau tinjau template, lalu terbitkan | Template nonaktif, pratinjau berubah atau run ganda ditolak. |
| UC-04 | Warga | Tagihan dapat dibayar | Pilih, transfer di luar aplikasi, unggah bukti | Kepemilikan/status berubah atau file salah ditolak. |
| UC-05 | Pengurus | Transfer pending | Periksa bukti, sahkan seluruh pembayaran | Tolak dengan alasan; sudah diproses tidak disahkan ulang. |
| UC-06 | Pengurus | Cash diterima | Pilih tagihan, tinjau, konfirmasi penerimaan | Total/status berubah atau kas terkunci menggagalkan transaksi. |
| UC-07 | Pengurus | Data tersedia | Pilih laporan/filter, lihat ringkasan, unduh | Melebihi batas PDF diminta mempersempit filter/memakai CSV. |
| UC-08 | Pengurus | Bulan berakhir | Tinjau dan tutup buku; buka terakhir dengan alasan bila perlu | Bulan berjalan/masa depan atau buka bukan terakhir ditolak. |
| UC-09 | Pengurus | Ada riwayat outbox | Filter riwayat dan periksa detail/Fonnte | Halaman tidak mengirim ulang; hasil API bukan bukti pesan dibaca. |

## 7. Status tagihan

```mermaid
stateDiagram-v2
    [*] --> unpaid: tagihan terbit
    unpaid --> pending: bukti transfer
    rejected --> pending: bukti transfer baru
    pending --> rejected: pengurus menolak
    pending --> verified: transfer disahkan
    unpaid --> verified: cash diterima
    rejected --> verified: cash diterima
    verified --> [*]
```

Cash gabungan menggunakan status internal `pending` lalu `verified` dalam transaksi yang sama; tidak membentuk antrean verifikasi transfer. Batch transfer yang ditolak tetap menjadi histori ketika warga mengajukan pembayaran baru. Tidak tersedia alur pembatalan/refund tagihan lunas pada baseline.

## 8. Status outbox WhatsApp

```mermaid
stateDiagram-v2
    [*] --> pending: kejadian memenuhi syarat
    pending --> processing: klaim pengirim
    processing --> accepted: API menerima
    processing --> pending: penolakan eksplisit atau 429 dan batas belum tercapai
    processing --> failed: gagal permanen atau batas percobaan
    processing --> skipped: tidak layak atau kedaluwarsa
    processing --> uncertain: timeout, hasil ambigu atau klaim terhenti
    accepted --> [*]
    failed --> [*]
    skipped --> [*]
    uncertain --> [*]
```

Klaim yang terhenti diperiksa oleh perintah pengirim. Status akhir tidak memiliki transisi kirim ulang dari UI.

## 9. Sequence — pelunasan transfer dan kuitansi

```mermaid
sequenceDiagram
    actor M as Pengurus
    participant C as Controller
    participant S as PaymentService / BatchPaymentService
    participant DB as Database
    participant N as Notifier / Outbox
    M->>C: Sahkan transfer
    C->>S: Keputusan dengan aktor terautentikasi
    S->>DB: BEGIN, lock dan validasi status / pemilik / total
    S->>DB: Periksa periode kas, ubah verified, buat pemasukan
    S->>DB: Simpan snapshot kuitansi dan log aktivitas
    S->>N: Catat kejadian pembayaran
    N->>DB: Notifikasi dan outbox bila aktif
    S->>DB: COMMIT
    S-->>C: Hasil pembayaran
    C-->>M: Konfirmasi dan akses kuitansi
    Note over S,DB: Kegagalan membatalkan transaksi bisnis terkait
    Note over N,DB: Tidak menghubungi Fonnte dalam transaksi pelunasan
```

## 10. Sequence — scheduler WhatsApp

```mermaid
sequenceDiagram
    participant T as Scheduler
    participant S as WhatsAppSender
    participant DB as Database
    participant F as Fonnte
    T->>S: Proses antrean yang waktunya tersedia
    S->>DB: Transaksi singkat: klaim pending menjadi processing
    DB-->>S: Antrean terambil
    S->>DB: Validasi ulang penerima, status, kuitansi dan umur
    alt Tidak memenuhi syarat
        S->>DB: skipped dan kode kendala
    else Layak dikirim
        S->>DB: Catat tujuan terenkripsi dan waktu percobaan
        S->>F: Target dan pesan teks
        F-->>S: Respons API bila koneksi berhasil
        Note over S: Timeout ditangani klien sebagai hasil tidak pasti
        S->>DB: Catat accepted / pending / failed / uncertain
    end
    Note over S,F: accepted tidak membuktikan sampai atau dibaca
```

## 11. Detail use case pembayaran dan kontrol buku

### UC-04 — Pengajuan transfer

- **Prasyarat:** warga/perumahan aktif; 1–100 tagihan unik milik satu warga berstatus `unpaid`/`rejected`; rekening tujuan tersedia untuk ditinjau di aplikasi.
- **Alur:** warga memilih tagihan, memeriksa total server dan rekening, transfer di luar aplikasi, mengunggah bukti yang valid; server mengunci serta memvalidasi ulang pilihan, lalu menyimpan bukti/metadata dan status `pending`.
- **Alternatif/gagal:** pilihan berubah, ID asing, total salah atau unggahan tidak valid ditolak. Kegagalan penyimpanan bisnis membersihkan file baru sesuai jalur controller; tidak ada status pembayaran sebagian.
- **Kondisi akhir sukses:** satu pilihan memakai alur tunggal; lebih dari satu membuat batch dan pivot historis. Notifikasi pengajuan tersedia bagi pengurus; belum ada kas/kuitansi lunas atau WhatsApp kuitansi.
- **Verifikasi:** AT-05/07, BillingWorkflowTest dan BatchPaymentsTest.

### UC-05 — Keputusan transfer

- **Prasyarat:** pengurus aktif dalam perumahan; pembayaran transfer `pending`; batch harus tetap memiliki anggota dan nominal sesuai snapshot.
- **Alur:** pengurus memeriksa bukti, menyetujui pembayaran; server memvalidasi ulang di dalam transaksi, menyimpan status `verified`, satu pemasukan, snapshot kuitansi, log terpilih dan notifikasi/outbox yang memenuhi syarat.
- **Alternatif/gagal:** penolakan wajib alasan dan mengubah seluruh anggota batch menjadi `rejected`, tanpa pemasukan/kuitansi baru. Keputusan berulang, batch berubah atau periode kas tertutup menolak penyelesaian; perubahan dalam transaksi dibatalkan.
- **Kondisi akhir sukses:** pembayaran lunas hanya sekali; batch memperoleh satu kuitansi/satu kas. Bukti dan percobaan ditolak tetap dapat ditelusuri sesuai otorisasi.
- **Verifikasi:** AT-05/07/12, PaymentsAnnouncementsAndPasswordTest, BatchPaymentsTest dan ManagerWorkflowsTest.

### UC-06 — Penerimaan cash

- **Prasyarat:** uang benar-benar diterima pengurus; tagihan satu warga memenuhi syarat dan periode tanggal penerimaan server terbuka.
- **Alur:** pengurus memilih tagihan, meninjau total dan mengonfirmasi penerimaan; server memeriksa ulang status/total dan menyelesaikan pembayaran menggunakan metode `cash`.
- **Alternatif/gagal:** `pending` transfer atau tagihan lunas tidak dapat langsung diterima cash; nominal berubah, akun asing atau kas terkunci menolak penyelesaian.
- **Kondisi akhir sukses:** status `verified`, satu pemasukan, satu kuitansi dan pemberitahuan; tidak ada langkah persetujuan transfer tambahan. Tagihan lampau dapat dibayar di bulan berjalan.
- **Verifikasi:** AT-06/12, PaymentsAnnouncementsAndPasswordTest, BatchPaymentsTest dan ManagerWorkflowsTest.

### UC-08 — Tutup dan buka kembali buku kas

- **Prasyarat penutupan:** pengurus aktif memilih bulan yang sudah berakhir dan meninjau laporan kas lengkap, bukan total filter kategori saja.
- **Alur penutupan:** konfirmasi diterima, server mengunci koordinasi perumahan, memeriksa periode, menyimpan snapshot ringkasan/status `closed` serta log aktivitas.
- **Alternatif pembukaan:** pilih periode tertutup paling akhir, masukkan alasan 10–1.000 karakter; status berubah `open` dan log menyimpan perubahan. Bulan yang lebih awal belum dapat dibuka bila ada bulan tertutup setelahnya.
- **Kondisi akhir:** mutasi kas dengan tanggal yang memengaruhi periode tertutup ditolak; buka kembali mengizinkan perubahan sesuai sisa periode tertutup. Snapshot/status tagihan laporan tahunan tidak diubah menjadi kondisi historis akhir tahun.
- **Verifikasi:** AT-12 dan ManagerWorkflowsTest.
