# Kebutuhan Sistem

[Indeks SRS](../SRS.md) · [Model data](02-MODEL-DATA.md) · [Verifikasi](05-VERIFIKASI.md)

## 1. Aktor dan hak akses

| Kapabilitas | Admin | Pengurus | Warga |
| --- | --- | --- | --- |
| Kelola perumahan dan akun pengurus | Ya | Tidak | Tidak |
| Kelola rumah, warga, rekening dan tarif | Tidak | Perumahannya | Tidak |
| Terbitkan tagihan / template | Tidak | Perumahannya | Tidak |
| Lihat tagihan dan bukti | Tidak melalui menu pengurus/warga | Perumahannya | Miliknya |
| Unggah bukti transfer | Tidak | Tidak sebagai warga | Tagihan miliknya yang memenuhi syarat |
| Verifikasi / tolak / terima tunai | Tidak | Perumahannya | Tidak |
| Lihat kuitansi | Tidak melalui endpoint kuitansi | Perumahannya | Miliknya |
| Kelola kas, nota, tutup buku, tindak lanjut | Tidak | Perumahannya | Tidak |
| Laporan pengurus, ekspor dan log aktivitas | Tidak | Perumahannya | Tidak |
| Transparansi kas pada dashboard warga | Tidak | Ringkasan pengurus | Lingkungannya melalui dashboard |
| Kelola pengumuman | Tidak | Perumahannya | Daftar yang terbit; detail arsip tetap dapat diakses sesuai otorisasi |
| Lonceng notifikasi | Tidak melalui menu notifikasi | Miliknya | Miliknya |
| Riwayat WhatsApp | Tidak | Perumahannya, hanya baca | Tidak |
| Ubah password sendiri | Ya | Ya | Ya |

Aktor pendukung: operator server menjalankan CLI/deployment/backup; scheduler memicu tugas; Fonnte menerima permintaan pengiriman. Operator dan scheduler bukan peran login tambahan. Semua menu memerlukan akun aktif; akun pengurus/warga juga memerlukan perumahan aktif.

## 2. Kebutuhan fungsional

Kebutuhan berikut dirumuskan dari jalur kode yang tersedia pada baseline. Status ini belum menyatakan seluruh kriteria sudah diterima melalui UAT. Kolom penerimaan merangkum hasil yang dapat diperiksa; rincian skenario dan referensi pengujian ada pada [verifikasi](05-VERIFIKASI.md).

| ID | Sistem harus | Kriteria penerimaan |
| --- | --- | --- |
| FR-01 | Mengautentikasi username/email dan password, mengelola sesi dan logout. | Kredensial salah ditolak; pengguna dialihkan sesuai peran; sesi logout tidak dapat dipakai. |
| FR-02 | Membatasi akses menurut peran, keaktifan dan perumahan/kepemilikan data. | Percobaan akses akun/perumahan lain ditolak tanpa memberikan data tersebut. |
| FR-03 | Membatasi admin tunggal dan menyediakan pembuatan admin melalui CLI. | `app:create-admin` menolak admin kedua; tidak ada pendaftaran publik. |
| FR-04 | Memungkinkan admin mengelola perumahan, akun pengurus, status aktif dan reset password pengurus. | Perubahan berlaku pada sumber data; pembatasan penghapusan data berelasi diterapkan. |
| FR-05 | Memungkinkan pengurus mengelola rumah, kondisi hunian, tarif rumah kosong, warga dan penanggung jawab IPL. | Rumah/warga terhubung hanya dalam perumahannya; kode rumah unik per perumahan. |
| FR-06 | Mengimpor rumah/warga melalui CSV, validasi, pratinjau lalu konfirmasi atomik. | Data cocok diperbarui; data baru ditambah; baris bermasalah menahan batch; pratinjau kedaluwarsa ditolak. |
| FR-07 | Mengelola rekening pembayaran aktif per perumahan. | Warga memperoleh pilihan rekening lingkungan; perubahan rekening tidak mengubah tagihan menjadi relasi rekening historis. |
| FR-08 | Menerbitkan tagihan berkomponen untuk warga aktif sesuai penerima dan aturan rumah. | Total dihitung server; rumah memakai penanggung jawab; aturan rumah kosong diterapkan bila dipilih. |
| FR-09 | Menyimpan template tagihan dan menerbitkannya setelah pratinjau serta konfirmasi. | Pratinjau berubah ditolak; satu template/periode hanya satu run; menyimpan template tidak menerbitkan tagihan. |
| FR-10 | Menerima bukti transfer tunggal untuk tagihan `unpaid` atau `rejected` milik warga. | Bukti valid disimpan privat; status menjadi `pending`; nominal tagihan tidak berasal dari klien. |
| FR-11 | Memungkinkan pengurus menyetujui atau menolak transfer dengan alasan penolakan. | Persetujuan menghasilkan lunas, kas dan kuitansi; penolakan memungkinkan unggah ulang. |
| FR-12 | Mencatat pembayaran cash setelah konfirmasi uang diterima. | Tagihan yang memenuhi syarat langsung `verified`; metode cash tersimpan dan kuitansi terbit. |
| FR-13 | Memproses 1–100 tagihan satu warga; lebih dari satu membentuk pembayaran gabungan. | Total/status/keanggotaan divalidasi ulang; keputusan gabungan tidak diproses sebagian; satu kas dan satu kuitansi. |
| FR-14 | Menyediakan kuitansi bernomor tetap dengan snapshot dan unduh/cetak PDF. | Pengulangan akses memakai kuitansi yang sama; akses dibatasi; perubahan master tidak mengubah snapshot terbit. |
| FR-15 | Mengelola kas manual dengan jenis, kategori, nominal, tanggal transaksi dan nota privat. | Pemasukan tagihan otomatis terkunci; nota hanya diunduh pengurus yang berhak. |
| FR-16 | Menyediakan laporan tunggakan, tagihan bulanan dan kas dengan filter relevan serta CSV/PDF. | Ekspor mengikuti filter dan seluruh hasil; total filter dipisahkan dari saldo seluruh perumahan. |
| FR-17 | Menyediakan rekap tahunan per rumah dengan filter tahun, pencarian, status dan rincian bulanan. | Seluruh tagihan per bulan diperhitungkan; rumah historis dipertahankan; bulan tanpa tagihan tidak ditandai lunas. |
| FR-18 | Mencatat riwayat tindak lanjut tunggakan, catatan, jadwal dan pengurus penanggung jawab. | Janji/jadwal wajib tanggal hari ini atau selanjutnya; tidak mengubah status tagihan atau mengirim pesan. |
| FR-19 | Menutup bulan kas yang sudah berakhir serta membuka kembali dengan alasan. | Transaksi yang memengaruhi periode tertutup ditolak; buka kembali periode tertutup terakhir lebih dahulu. |
| FR-20 | Mencatat aktivitas keuangan terpilih, pelaku, waktu dan nilai sebelum/sesudah. | Log tercatat bersama transaksi; tidak ada endpoint edit/hapus log; rahasia tidak disalin ke log. |
| FR-21 | Mengelola pengumuman draft, publikasi, arsip dan pemberitahuan warga aktif. | Warga hanya melihat pengumuman yang dapat diakses di lingkungannya. |
| FR-22 | Menyediakan daftar lonceng, jumlah belum dibaca dan penandaan dibaca. | Data hanya milik akun; pembacaan notifikasi tidak membuka resource tanpa izin. |
| FR-23 | Mengantrekan WhatsApp untuk tagihan baru, H-3/H+1 dan kuitansi transfer/cash termasuk gabungan. | Fitur terkonfigurasi dan penerima/kejadian memenuhi syarat; nomor diperiksa saat pengiriman; satu kuitansi satu antrean, jenis notifikasi lain tetap melalui lonceng. |
| FR-24 | Memproses outbox melalui scheduler dengan deduplikasi, validasi ulang dan hasil pengiriman. | Gangguan provider tidak membatalkan pembayaran; hasil tidak pasti tidak dicoba ulang otomatis. |
| FR-25 | Menampilkan riwayat WhatsApp per perumahan dengan filter, ringkasan dan detail kendala. | Halaman hanya baca; nomor percobaan terakhir dibedakan dari nomor warga saat ini; tidak ada tombol kirim ulang. |
| FR-26 | Menyediakan dashboard sesuai peran dan lingkup data. | Admin melihat administrasi, pengurus melihat kondisi lingkungan, warga melihat tagihan sendiri dan transparansi lingkungannya. |
| FR-27 | Mengubah password sendiri dengan password saat ini dan konfirmasi password baru. | Password baru berbeda, 8–128 karakter; sesi perangkat lain dicabut, sesi saat ini diregenerasi. |
| FR-28 | Memungkinkan admin mereset password pengurus dan pengurus mereset password warga dalam lingkupnya. | Target sesuai peran/perumahan; password baru dikonfirmasi; sesi target dicabut tanpa menghapus histori akun. |

## 3. Aturan bisnis dan validasi

| ID | Aturan |
| --- | --- |
| BR-01 | Setiap akses bisnis harus memastikan perumahan dan kepemilikan. FK tunggal tidak cukup untuk menjamin pasangan lintas tabel berada di perumahan sama. |
| BR-02 | Satu warga memiliki paling banyak satu rumah saat ini dan boleh belum dihubungkan; satu rumah dapat memiliki banyak warga dan paling banyak satu penanggung jawab. Satu warga juga paling banyak menjadi penanggung jawab satu rumah melalui alur aplikasi. `responsible_user_id` boleh kosong di skema, tetapi penagihan rumah memerlukan kecocokan penanggung jawab. |
| BR-03 | Status rumah: `occupied`, `rented`, `vacant`, `renovation`. Tarif kosong: `full`, `custom`, `exempt`. Nominal khusus mengganti total tagihan baru, bukan potongan. |
| BR-04 | Beberapa tagihan untuk warga dan bulan yang sama diperbolehkan; tidak ada UQ pada pasangan warga/periode. Penerbitan ulang template yang sama/periode yang sama ditolak. Perlindungan duplikasi pembayaran mengikuti BR-07. |
| BR-05 | Tagihan memakai `unpaid`, `pending`, `verified`, `rejected`. Hanya `verified` berarti lunas. Pengingat hanya untuk `unpaid`/`rejected`. |
| BR-06 | Pembayaran tidak mendukung sebagian nominal. Satu pilihan tagihan memakai alur tunggal; beberapa memakai batch, maksimal 100 ID unik milik satu warga. |
| BR-07 | Satu pembayaran sah menghasilkan satu pencatatan kas otomatis dan satu kuitansi; batch tidak menambah kas per tagihan anggotanya. |
| BR-08 | `payment_batch_invoice` mempertahankan keanggotaan historis; `invoices.payment_batch_id` menunjuk batch saat ini. Batch ditolak dapat digantikan percobaan pembayaran baru tanpa menghapus batch lama. |
| BR-09 | Snapshot kuitansi/batch menyimpan rincian historis. Rekap tahunan memakai `invoices.house_id` saat penerbitan, bukan rumah warga saat ini. |
| BR-10 | Bukti transfer JPG/PNG/WebP maksimal 5 MB. Nota kas juga menerima PDF maksimal 5 MB. Path tidak boleh menjadi tautan publik. |
| BR-11 | CSV maksimal 2 MB; rumah maksimal 1.000 baris, warga maksimal 100 baris; pratinjau berlaku 30 menit dan divalidasi ulang saat konfirmasi. |
| BR-12 | Template: 1–50 komponen; nominal komponen bilangan bulat Rp 1.000–Rp 999.999.999.999; hari jatuh tempo 1–31, offset bulan 0/1; hari melampaui akhir bulan disesuaikan. |
| BR-13 | Kas manual memakai tanggal tidak melebihi hari ini. Kas otomatis memakai tanggal server. `created_at` tetap waktu pencatatan; tanggal lama memiliki fallback untuk laporan. |
| BR-14 | Penutupan kas menghalangi perubahan pada bulan tersebut dan transaksi lebih lama yang memengaruhi saldo awal. Perubahan tanggal dicek pada tanggal asal dan tujuan. Bulan berjalan/masa depan belum dapat ditutup. |
| BR-15 | Pembukaan kembali memerlukan alasan 10–1.000 karakter; periode tertutup paling akhir dibuka dahulu. Tutup buku kas tidak membekukan status tagihan pada laporan. |
| BR-16 | Catatan tindak lanjut 5–2.000 karakter; pengurus penanggung jawab wajib aktif dalam perumahan. Status `contacted`, `promised`, `scheduled`, `closed` tidak menyatakan pembayaran lunas. |
| BR-17 | PDF laporan biasa maksimal 2.000 baris, rekap tahunan 500 baris. Batas dilewati ditolak secara eksplisit; CSV dipakai untuk hasil lebih besar. |
| BR-18 | H-3/H+1 diperiksa pukul 08.00 WIB; outbox diproses setiap menit, bawaan maksimal 20 pesan. Antrean lebih tua dari satu hari dilewati; jadwal lampau tidak dikirim massal susulan. |
| BR-19 | Respons HTTP 2xx dengan `status=false` atau HTTP 429 dicoba ulang maksimal lima pemrosesan, jeda `2^attempts` menit. Timeout/HTTP 5xx/hasil ambigu menjadi `uncertain`; klaim terhenti juga perlu diperiksa. |
| BR-20 | Status `accepted` berarti API menerima permintaan, tidak membuktikan pesan diterima/dibaca. Riwayat belum memiliki daftar terpisah setiap percobaan atau rekaman isi pesan lama. |
| BR-21 | Periode tagihan disimpan sebagai awal bulan; jatuh tempo tidak boleh sebelum awal periode. Komponen tagihan manual mengikuti batas jumlah/nominal pada template; total maksimum Rp 9.999.999.999.999. |
| BR-22 | Kas manual memiliki nominal minimal Rp 1, maksimal Rp 9.999.999.999.999 dan paling banyak dua desimal. Kategori `ipl` tidak dapat dipilih sebagai kategori manual. |
| BR-23 | Daftar pengumuman warga hanya memuat `published`. Detail `archived` masih dapat dibuka lewat tautan yang sah; draft ditolak. Pengumuman terbit tidak diedit langsung: arsipkan lalu buat pengganti. |
| BR-24 | Dropdown tahun rekap mencakup tahun server minus lima hingga plus satu, diperluas oleh periode tagihan dan tahun terpilih, dengan batas 2000–2100. Laporan tahun bukan rekonstruksi kondisi pembayaran pada akhir tahun. |
| BR-25 | Pembayaran terverifikasi dari sebelum fitur kuitansi dapat memperoleh kuitansi historis saat pertama diminta, memakai data yang tersedia saat penerbitan. Tidak ada rekonstruksi data lama yang tidak tersimpan. |

## 4. Kebutuhan nonfungsional

| ID | Kebutuhan | Dasar verifikasi / batas |
| --- | --- | --- |
| NFR-01 | Otorisasi harus berlaku pada halaman, ekspor, bukti, nota dan kuitansi, dengan isolasi perumahan. | Policy, middleware, scoping controller dan pengujian lintas perumahan. |
| NFR-02 | Password harus disimpan sebagai hash; data sensitif tertentu harus terenkripsi dan token tidak ditampilkan. | Cast/model, konfigurasi environment, pengujian. Nomor tujuan WhatsApp dan rows impor dienkripsi; ini bukan enkripsi seluruh database. |
| NFR-03 | Mutasi berbasis sesi harus memakai perlindungan CSRF dan validasi server; endpoint tertentu dibatasi laju. | Middleware Laravel, validasi controller, RateLimiter. Login: 5/menit per identitas+IP serta 30/menit per IP; operasi sensitif 5/menit per akun+IP, impor 5/menit per akun, dokumen 30/menit per akun. |
| NFR-04 | Penyelesaian pembayaran harus menjaga atomisitas status, kas, kuitansi dan notifikasi/outbox. | Transaksi database, lock dan UQ; kegagalan harus membatalkan perubahan bisnis terkait. Tidak menjamin transaksi atomik antara filesystem dan API eksternal. |
| NFR-05 | Antarmuka harus mendukung desktop/HP, format rupiah dan pagination. | Pemeriksaan UI dan pengujian frontend. Belum ada klaim sertifikasi aksesibilitas. |
| NFR-06 | Waktu otoritatif harus berasal dari server; jadwal pengingat menggunakan WIB. | Penggunaan Carbon/server dan Scheduler; sinkronisasi NTP dan hak ubah jam menjadi tanggung jawab operator. |
| NFR-07 | Riwayat bisnis terpilih harus dapat ditelusuri tanpa menyimpan password, token atau respons mentah provider pada log aktivitas. | Observer whitelist, snapshot dan pengujian rollback/log. Bukan audit trail yang kebal perubahan administrator database. |
| NFR-08 | Aplikasi harus dapat dibangun dan diuji dari lock file dengan database uji terisolasi serta HTTP Fonnte palsu. | Composer/npm, PHPUnit SQLite in-memory dan Node test runner. |
| NFR-09 | Production harus memakai HTTPS, debug nonaktif, cookie sesi aman, private storage dan backup konsisten. | Konfigurasi dan pemeriksaan deployment operator; belum ada hasil uji pemulihan/SLA dalam SRS ini. |
| NFR-10 | Target latensi, pengguna bersamaan, ukuran data, uptime, RPO dan RTO harus ditetapkan sebelum menjanjikan kapasitas operasional. | Belum disepakati/diukur; lihat daftar tindak lanjut verifikasi. Batas ekspor bukan bukti hasil load test. |

## 5. Antarmuka dan kebutuhan eksternal

| Antarmuka | Masukan | Keluaran / kontrak |
| --- | --- | --- |
| Browser | Form HTML, sesi, pencarian, filter, unggahan | Blade/Bootstrap, redirect/validasi, unduhan privat, CSV/PDF. |
| Database | Query Eloquent dan transaksi | Skema migrasi; relasi rinci pada PDM. SQLite dan MySQL berbeda pada representasi tipe dan perilaku lock. |
| File CSV | Template unduhan, koma/titik koma, kolom rumah/warga | Pratinjau dan hasil tambah/perbarui; detail kolom di panduan pengguna. |
| Fonnte | HTTPS POST `/send`, header Authorization token langsung, target dan message | Hasil dipetakan ke status lokal. Endpoint `https://api.fonnte.com/send`; tidak ada webhook penerimaan pada baseline. |
| CLI / scheduler | `app:create-admin`, `app:send-invoice-reminders`, `app:send-whatsapp`, `schedule:run` | Admin atau hasil pekerjaan terjadwal. Perintah pengiriman dapat mengirim pesan nyata. |
| PDF / printer | Data yang sudah diotorisasi, filter laporan atau snapshot kuitansi | PDF/cetak; laporan A4 landscape, kuitansi A4. |

Daftar route aktual berada di [routes/web.php](../../routes/web.php), jadwal di [routes/console.php](../../routes/console.php). Sistem berbasis web dengan sesi; belum menyediakan kontrak REST API publik untuk aplikasi pihak ketiga.
