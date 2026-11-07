# Panduan Penggunaan EnviroPay

[Kembali ke README](../README.md) · [Indeks dokumentasi](README.md) · [Notifikasi](../README.md#whatsapp-fonnte) · [Deployment](../README.md#deployment-dan-pembaruan)

Panduan ini menjelaskan aturan data rumah, impor, pembayaran, dan laporan untuk pengurus serta warga.

## Data Rumah dan Tarif IPL

Menu **Data Rumah** menyimpan kode unik dalam perumahan, alamat, kondisi (Dihuni/Dikontrakkan/Kosong/Renovasi), nama pemilik dan penghuni, serta akun penanggung jawab IPL. Rumah boleh belum memiliki akun. Satu akun warga hanya memiliki satu rumah saat ini; beberapa akun dapat terhubung ke satu rumah tetapi hanya satu yang menjadi penanggung jawab tagihan baru. Pemilik tetap dapat memiliki akun aktif meskipun rumahnya kosong.

Saat upgrade, alamat warga lama yang sama persis dalam perumahan yang sama dihubungkan ke satu rumah. Rumah hasil migrasi memakai kode sementara `LEGACY-{id}`; tinjau dan ganti dengan kode blok/nomor sebenarnya. Akun pertama menjadi penanggung jawab awal; pengurus perlu meninjau penetapan tersebut, terutama jika akun itu nonaktif. Tidak ada penebakan nama pemilik/penghuni. Tagihan lama mempertahankan pemilik akun dan salinan alamatnya.

Pada **Edit Warga**, pilih rumah dan centang **Penanggung jawab IPL** bila akun itu menerima tagihan baru. Pemindahan penanggung jawab mengosongkan penanggung jawab rumah sebelumnya. Tagihan dan kuitansi lama tidak dipindahkan ke akun atau rumah baru. Histori perubahan dapat dibaca pada **Edit Rumah**.

Saat membuat tagihan, opsi **Terapkan aturan tarif rumah kosong** aktif secara default:

- Rumah dihuni, dikontrakkan, dan renovasi: memakai komponen yang diisi pada formulir tagihan.
- Rumah kosong dengan tarif penuh: memakai komponen yang sama.
- Rumah kosong dengan nominal khusus: komponen diganti menjadi satu komponen **Tarif khusus rumah kosong**, sejumlah nominal rumah tersebut.
- Rumah kosong bebas iuran baru: dilewati, tanpa membuat tagihan nol atau menghapus tunggakan.
- Akun terhubung yang bukan penanggung jawab IPL: dilewati, termasuk ketika dipilih manual.

Matikan opsi tarif rumah kosong untuk tagihan khusus di luar IPL jika memang perlu menerapkan komponen normal. Form tetap mendukung akun lama yang belum dihubungkan ke rumah; akun tersebut memakai komponen normal. Kondisi rumah tidak menonaktifkan akun secara otomatis.
Status **Dikontrakkan** ditampilkan tersendiri pada Data Rumah, filter, histori, dan ringkasan dashboard; tarifnya tetap komponen normal seperti rumah Dihuni. Rumah tanpa penghuni dipilih sebagai Kosong, termasuk yang sedang ditawarkan untuk disewa.

Form aturan rumah kosong hanya dapat diubah ketika kondisi **Kosong**. Nominal hanya dapat diisi jika **Nominal khusus** dipilih, harus berupa rupiah bulat positif, dan menggantikan total komponen normal per tagihan IPL (bukan nilai potongan). Untuk nominal nol gunakan **Bebas iuran baru**. Ketika rumah berubah ke Dihuni/Dikontrakkan/Renovasi, aturan kosong sebelumnya tetap tersimpan tetapi tidak aktif; server mengabaikan perubahan isian tarif kosong pada kondisi tersebut. Tagihan baru memakai nominal normal kecuali kondisi Kosong dan opsi penerapan aturan dicentang. Tagihan/tunggakan lama tidak dihitung ulang.

## Impor CSV Rumah

Untuk pendataan awal: **Data Rumah → Impor CSV Rumah → konfirmasi**, kemudian **Warga → Impor CSV** memakai kode rumah yang sama. Tombol **Tambah Rumah** tetap tersedia untuk rumah tambahan satu per satu. Impor rumah tidak membuat akun warga atau menentukan penanggung jawab; penetapan otomatis dilakukan pada impor warga melalui `house_code` dan `is_responsible`.

Unduh template dari halaman impor rumah. Header lengkap:

```csv
code,address,occupancy,owner_name,occupant_name,vacant_billing,vacant_amount,notes
```

Hanya `code`, `address`, dan `occupancy` yang wajib ada sebagai kolom. Kolom lainnya boleh dihilangkan atau dikosongkan. Pemisah koma/titik koma dan UTF-8 BOM didukung. Maksimal 1.000 rumah dan 2 MB per file.

- `code`: kode unik dalam perumahan, dinormalisasi huruf besar; misalnya `A-01`. Kode yang sama di perumahan lain boleh digunakan.
- `occupancy`: Dihuni/Dikontrakkan/Kosong/Renovasi, atau occupied/rented/vacant/renovation. Huruf besar/kecil dan spasi tepi dinormalisasi.
- `owner_name`, `occupant_name`: nama kontak biasa, bukan akun login.
- `vacant_billing`: Tarif penuh/Nominal khusus/Bebas iuran baru, atau full/custom/exempt. Kosong berarti Tarif penuh.
- `vacant_amount`: hanya untuk kondisi Kosong + Nominal khusus, berupa angka rupiah bulat positif seperti `25000` tanpa Rp/titik/koma. Kondisi lain harus menggunakan Tarif penuh dengan nominal kosong; untuk bebas iuran pilih exempt dan kosongkan nominal.
- `notes`: catatan opsional.

Kode rumah yang sudah ada di perumahan tersebut memperbarui baris rumah yang sama; kode baru membuat rumah baru. Penanggung jawab, hubungan warga, tagihan, dan kuitansi lama tetap dipertahankan; perubahan rumah dicatat pada histori. Kolom opsional yang tidak disertakan mempertahankan nilai lama, sedangkan sel kosong pada kolom yang disertakan mengosongkan nilainya (aturan kosong berarti Tarif penuh). Kode berulang pada file yang sama tetap ditolak. Satu baris bermasalah menahan seluruh impor. Pratinjau terenkripsi, terikat ke pengurus/perumahannya, dan berlaku 30 menit. Konfirmasi memvalidasi ulang dan menyimpan satu batch secara atomik; rumah yang dibuat atau diubah proses lain setelah pratinjau membuat seluruh batch dibatalkan agar tidak menimpa perubahan yang belum ditinjau. Konfirmasi ulang tidak membuat rumah ganda. Data batch dikosongkan sesudah selesai; scheduler membersihkan batch kedaluwarsa pukul 02.05 waktu aplikasi.

## Impor CSV Warga

1. Impor CSV rumah atau buat/tinjau Data Rumah dan kode rumah dahulu.
2. Buka **Warga → Impor CSV → Unduh Template**.
3. Isi CSV UTF-8 dengan pemisah koma atau titik koma. Maksimal 100 warga dan 2 MB per file.
4. Unggah, periksa setiap baris pada pratinjau, lalu konfirmasi dalam 30 menit.

Header wajib:

```csv
name,username,email,phone_number,house_code,password,is_responsible
```

`house_code` harus sudah ada di perumahan pengurus. Alamat akun mengikuti Data Rumah sehingga tidak perlu mengulang kolom alamat pada CSV. `is_responsible=1` menetapkan warga sebagai penanggung jawab IPL rumah tersebut, termasuk menggantikan penanggung jawab lama; pergantian ditampilkan pada pratinjau. Gunakan `0` untuk akun penghuni lain. Satu rumah hanya boleh memiliki satu baris penanggung jawab bernilai 1 dalam file. Tunggakan lama tetap milik akun sebelumnya.

Username/email harus unik di file. Warga lama dicocokkan berdasarkan username atau email tanpa membedakan huruf besar/kecil, hanya dalam perumahan dan peran warga yang dikelola. Jika kedua identitas cocok dengan akun berbeda, identitas tidak unik, atau mengarah ke akun perumahan/peran lain, batch ditolak. Identitas yang belum ada membuat akun baru; akun yang cocok diperbarui tanpa mengganti ID, role, status aktif, atau kepemilikan tagihan lama. Password awal wajib untuk warga baru, minimal 8 dan maksimal 128 karakter. Untuk warga lama, password kosong mempertahankan hash lama; terisi mengganti password dan mencabut sesi perangkat. Password tidak ditampilkan pada pratinjau. Nomor telepon diperlakukan sebagai teks; pastikan spreadsheet tidak menghilangkan nol awal sebelum CSV dibuat. Setelah diimpor, warga dapat mengubah password melalui Profil.

Satu baris salah menahan seluruh impor. Pratinjau menandai Baru/Perbarui dan hasil konfirmasi menyebut jumlah masing-masing. Konfirmasi memeriksa ulang data akun dan rumah serta menyimpan seluruh batch dalam satu transaksi. Jika data berubah setelah pratinjau atau identitas baru dipakai proses lain, batch dibatalkan dan perlu diunggah ulang. Klik konfirmasi ulang tidak membuat akun ganda. Pratinjau disimpan terenkripsi dengan password yang sudah di-hash, terikat ke pengurus/perumahan, dan kedaluwarsa setelah 30 menit. Setelah selesai, isi batch dihapus. Scheduler membersihkan batch kedaluwarsa setiap hari pukul 02.00 waktu aplikasi.

## Pembayaran Satu atau Beberapa Tagihan

Sistem menentukan jenis pembayaran secara otomatis dari jumlah tagihan yang valid: satu tagihan dicatat melalui pembayaran biasa dengan kuitansi `KW-...`; dua atau lebih dicatat sebagai pembayaran gabungan dengan kuitansi `KWG-...`. Warga dan pengurus cukup memilih tagihan yang ingin dilunasi, tanpa memilih mode tunggal/gabungan.

Warga dapat mencentang tagihan langsung pada **Tagihan Saya**, lalu menekan **Tinjau Pembayaran**. Pilihan pada daftar paginasi berlaku pada halaman tersebut. Tombol **Bayar Tagihan** membuka hingga 100 tagihan yang memenuhi syarat, mulai dari jatuh tempo terlama, termasuk tagihan bulan sebelumnya.

1. Pilih satu atau beberapa tagihan belum dibayar/ditolak milik satu warga dalam perumahan yang sama.
2. Tinjau rincian dan total yang dihitung server; nominal tidak dapat diketik bebas.
3. Untuk transfer, kirim uang sesuai total lalu unggah satu bukti. Untuk cash, pengurus mengonfirmasi uang penuh telah diterima.
4. Seluruh tagihan transfer berubah menjadi menunggu verifikasi. Antrean pengurus menampilkan pembayaran biasa untuk satu tagihan atau satu baris gabungan untuk beberapa tagihan.
5. Pengurus memeriksa rincian serta total, kemudian memverifikasi atau menolak seluruh pembayaran. Tidak ada keputusan sebagian.
6. Setelah disahkan, seluruh tagihan pilihan lunas, satu pemasukan kas tercatat, dan kuitansi diterbitkan sesuai jumlah pilihan: `KW-{housing_id}-{invoice_id}` untuk satu tagihan, atau `KWG-{housing_id}-{payment_batch_id}` untuk gabungan.

Tagihan lunas atau menunggu verifikasi tidak dapat dipilih. Maksimal 100 tagihan per pembayaran, dengan total sampai Rp 9.999.999.999.999,99. Server memeriksa ulang kepemilikan, status, nominal, dan keanggotaan saat konfirmasi, serta mengunci tagihan dalam transaksi untuk mencegah pembayaran ganda. Jika total berubah sejak pratinjau, pembayaran tidak disimpan dan pengguna harus meninjau ulang. Bukti yang sudah tersimpan sebelum kegagalan transaksi dibersihkan.

Penolakan mengembalikan seluruh tagihan ke status Ditolak. Warga dapat memilih ulang tagihan dan mengirim bukti baru, baik gabungan maupun per tagihan; percobaan pembayaran sebelumnya beserta buktinya tetap menjadi histori. Catatan pembayaran lama tetap berstatus Ditolak meskipun tagihannya kemudian lunas melalui percobaan lain. Buka tagihan terkait untuk status terkini.

Kuitansi gabungan dapat dibuka dari halaman pembayaran atau dari setiap tagihan anggotanya. Isinya menyimpan rincian per tagihan, periode, alamat, komponen, nominal, metode, dan pengurus verifikator. Salinan tidak berubah ketika data warga/rumah kemudian diperbarui. Pembayaran dan kuitansi per tagihan dari versi lama tetap berlaku.

## Pembayaran Cash dan Kuitansi

Buka **Pembayaran Cash → pilih warga → centang satu atau beberapa tagihan → Tinjau Pembayaran → konfirmasi uang diterima**. Pengurus tidak mengetik ulang nominal. Pembayaran harus lunas sesuai total pilihan; cicilan dan pembayaran sebagian belum tersedia. Waktu cash otomatis mengikuti waktu pencatatan, bukan tanggal mundur.

Hanya tagihan `unpaid`/`rejected` yang dapat dibayar cash. Tagihan `pending` harus diproses atau ditolak terlebih dahulu untuk mencegah pembayaran ganda. Akun warga nonaktif yang masih memiliki tagihan dapat dipilih oleh pengurus. Penguncian tagihan, perubahan status, pemasukan kas, kuitansi, dan notifikasi berjalan dalam satu transaksi. Pemasukan dari tagihan tetap terkunci terhadap edit/hapus manual.

Kuitansi tersedia pada detail tagihan lunas, daftar tagihan pengurus, dan setelah penerimaan cash. Kuitansi menggunakan nomor tetap `KW-{housing_id}-{invoice_id}` dan memuat salinan nama/alamat, komponen, nominal, metode pembayaran, tanggal konfirmasi, dan pengurus penerima/verifikator. Perubahan data berikutnya tidak mengubah salinan kuitansi. Warga hanya dapat membaca kuitansi miliknya; pengurus hanya dari perumahannya.

Kuitansi transfer diterbitkan saat verifikasi. Transfer lama yang sudah terverifikasi memperoleh kuitansi historis saat pertama dibuka, menggunakan data yang tersedia saat penerbitan; tanggal penerbitan dibedakan dari tanggal konfirmasi historis. Tidak ada tanggal pembayaran transfer yang ditebak dari gambar bukti. Tombol **Unduh PDF** menghasilkan PDF A4 di server menggunakan Dompdf, tanpa akses aset jarak jauh. Tombol **Cetak** menggunakan halaman cetak browser.

## Pengumuman dan Password

Pengurus dapat membuat draft pengumuman, memeriksanya, lalu menerbitkan kepada seluruh warga aktif. Draft tidak terlihat oleh warga. Penerbitan mengirim satu notifikasi per penerima dan menampilkan tiga pengumuman terbaru pada dashboard. Warga baru dapat membaca pengumuman yang masih terbit. Pengumuman terbit tidak dapat diedit; arsipkan dan buat pengumuman pengganti. Arsip hilang dari daftar aktif tetapi tautan notifikasi lama tetap dapat dibuka dengan label arsip. Isi berupa teks biasa, tanpa lampiran.

Semua peran dapat memakai **Profil → Ubah Password**, dengan password lama, password baru minimal 8 karakter, dan konfirmasi yang cocok. Password baru harus berbeda. Akun tetap masuk di perangkat saat ini, sesi database pada perangkat lain dihapus, dan remember token diganti. Deployment saat ini menggunakan `SESSION_DRIVER=database`; pertahankan driver ini untuk penghentian sesi perangkat lain. Admin dapat mereset password pengurus; pengurus dapat mereset password warga perumahannya. Tidak ada menu reset password admin oleh pengurus atau warga.

## Laporan Pengurus

Buka menu **Laporan** untuk melihat:

- **Tunggakan**: tagihan `unpaid`/`rejected` yang jatuh temponya sebelum hari ini (Asia/Jakarta), termasuk milik warga nonaktif. Tagihan `pending` tidak dihitung sebagai tunggakan. Umur tunggakan dibagi menjadi 1–30, 31–60, dan >60 hari. Filter umur memilih warga yang memiliki tunggakan pada kelompok itu; total tetap mencakup seluruh tunggakan warga tersebut. Tombol **Lihat** membuka tagihan penyusunnya.
- **Tagihan Bulanan**: berdasarkan `billing_period`, dengan jumlah/nominal setiap status dan persentase nominal terverifikasi. Status yang ditampilkan adalah kondisi saat ini, bukan rekonstruksi status pada akhir bulan.
- **Kas Bulanan**: berdasarkan tanggal transaksi (tanggal pencatatan untuk data lama), dengan saldo awal, pemasukan manual/tagihan, pengeluaran, dan saldo akhir. Perubahan atau penghapusan transaksi manual dapat mengubah laporan bulan sebelumnya.

### Laporan pemasukan, pengeluaran, dan kategori

1. Buka **Laporan → Kas Bulanan** dan pilih bulan transaksi.
2. Pada **Jenis transaksi**, pilih **Semua transaksi**, **Pemasukan saja**, atau **Pengeluaran saja**.
3. Pilih kategori bila diperlukan, misalnya **Keamanan**, **Kebersihan**, atau **Perbaikan & Pemeliharaan**.
4. Saring sumber tagihan/manual atau cari nama transaksi, lalu tekan **Tampilkan**.
5. Periksa total pemasukan, pengeluaran, selisih dan **Rekap per Kategori**. Semuanya mengikuti filter dan seluruh halaman transaksi.
6. Tekan **Unduh PDF** untuk laporan siap cetak atau **Ekspor CSV** untuk pengolahan spreadsheet. Kedua format mengikuti filter dan mencakup seluruh transaksi yang cocok, bukan hanya halaman saat ini.

**Saldo Kas Seluruh Perumahan** menampilkan saldo awal, seluruh pemasukan/pengeluaran bulan itu, serta saldo akhir tanpa filter detail. Selisih hasil filter bukan saldo kas. Contoh: filter pengeluaran Keamanan dapat menunjukkan Rp500.000 pengeluaran dengan selisih -Rp500.000, sementara saldo perumahan tetap dihitung dari semua transaksi.

Untuk memberi kategori, buka **Arus Kas → Tambah Transaksi** atau **Edit** transaksi manual. Pilihan yang tersedia: Keamanan, Kebersihan, Listrik & Air, Perbaikan & Pemeliharaan, Administrasi, Kegiatan Warga, Donasi & Sumbangan, Lainnya, serta Belum Dikategorikan. Kategori dapat dipakai pada pemasukan maupun pengeluaran sesuai tujuan transaksi. Tombol **Laporan Bulanan** di Arus Kas membawa filter saat ini ke laporan; pilih kembali bulan yang diinginkan.

Kategori **Pembayaran Tagihan** diisi otomatis untuk pembayaran transfer/cash tunggal dan gabungan. Totalnya mencakup seluruh komponen pembayaran; kategori ini tidak membagi satu pembayaran ke keamanan/kebersihan. Pencatatan otomatis tetap terkunci dan tidak dapat dikategorikan ulang lewat edit kas manual. Transaksi manual lama ditandai **Belum Dikategorikan** hingga pengurus meninjaunya, tanpa menebak kategori dari nama transaksi.

### Unduh laporan PDF

Tombol **Unduh PDF** tersedia pada laporan Tunggakan, Tagihan Bulanan, dan Kas Bulanan. Pilih filter dan tekan **Tampilkan** sebelum mengunduh. PDF A4 landscape mencantumkan nama/alamat perumahan, periode atau tanggal kondisi tunggakan, filter aktif, nama pengurus, waktu unduh WIB, ringkasan, tabel rincian, dan nomor halaman. Untuk laporan kas, saldo seluruh perumahan ditampilkan terpisah dari total hasil filter. Judul kolom tabel diulang ketika berlanjut ke halaman berikutnya.

Batas PDF adalah 2.000 baris hasil filter; jika lebih, persempit filter atau gunakan CSV. Laporan kosong tetap dapat diunduh dan menampilkan keterangan tidak ada data. Laporan menggambarkan data saat diunduh. Periode kas yang ditutup ditandai pada halaman dan PDF; transaksi yang memengaruhi periode tersebut dikunci. File diunduh langsung dari aplikasi dengan autentikasi pengurus dan tidak dikirim sebagai lampiran WhatsApp.

CSV mengikuti filter, mencakup seluruh halaman, menggunakan UTF-8, dan melindungi teks yang berpotensi menjadi formula spreadsheet. Bagian awal CSV memuat ringkasan; tabel rincian dimulai dari baris nama kolom. Semua laporan dibatasi ke perumahan pengurus yang login.

## Rekap Tahunan per Rumah

1. Buka **Laporan → Rekap Tahunan Rumah** dan pilih tahun dari dropdown. Daftar mencakup lima tahun sebelumnya, tahun berjalan, dan satu tahun berikutnya; rentangnya diperluas bila ada tagihan lebih lama/lebih jauh atau tahun lain sedang dipilih. Daftar memakai data perumahan sendiri dan dibatasi 2000-2100.
2. Cari kode rumah, alamat, atau nama penanggung jawab saat ini bila diperlukan.
3. Pilih filter status lalu tekan **Tampilkan**. Filter **Belum Lunas** memilih baris dengan tagihan belum dibayar/bukti ditolak; **Menunggu Verifikasi** memilih baris yang punya bukti belum diproses. Satu rumah bisa cocok dengan kedua filter tersebut. Filter **Seluruh tagihan terbit sudah lunas** tidak mencakup rumah yang belum punya tagihan sama sekali.
4. Baca status Januari-Desember. **Lunas** berarti seluruh tagihan bulan itu sudah terverifikasi. **Verifikasi** berarti seluruh sisa tagihan sedang diperiksa. **Belum Lunas** berarti masih ada yang belum dibayar/bukti ditolak; jika ada juga pembayaran pending, jumlahnya ditampilkan di bawah status. Tanda **—** berarti belum ada tagihan, bukan lunas.
5. Klik status suatu bulan untuk membuka daftar tagihan milik rumah tersebut. Nama warga pada rincian adalah pemilik masing-masing tagihan; bisa berbeda dari penanggung jawab rumah saat ini. Tersedia akses ke verifikasi, pembayaran cash, atau kuitansi sesuai status.
6. **Ekspor CSV** dan **Unduh PDF** mengikuti filter dan mencakup seluruh halaman. PDF memakai A4 landscape, kode L/V/B/B/V dengan legenda, dan dibatasi 500 baris. CSV tidak dibatasi jumlah baris PDF.

Periode mengikuti bulan pada tagihan, bukan tanggal pembayaran. Beberapa tagihan dalam satu bulan, termasuk tagihan khusus, dirangkum menjadi satu status. Ringkasan total tagihan, lunas, menunggu verifikasi, belum lunas, dan sudah jatuh tempo mengikuti filter. Nominal belum lunas tidak mencakup menunggu verifikasi, dan tagihan yang belum jatuh tempo belum masuk nominal sudah jatuh tempo.

Pergantian penghuni atau penanggung jawab tidak memindahkan tagihan historis antar-rumah. Rumah mengikuti `house_id` pada saat tagihan diterbitkan; nama penanggung jawab pada matriks menunjukkan data saat ini. Tagihan lama yang tidak memiliki rumah tercatat ditampilkan dalam baris terpisah per akun. Rumah kosong atau bebas iuran tanpa tagihan tetap bertanda belum ada tagihan; laporan tidak menganggapnya sudah membayar atau mengarang tagihan baru. Akun warga nonaktif tetap diperhitungkan bila memiliki tagihan. Seluruh akses dibatasi ke perumahan pengurus aktif.

## Template Tagihan Rutin

1. Buka **Template Tagihan → Tambah Template**. Isi nama, judul, komponen, penerima dan tanggal jatuh tempo. Pilih bulan tagihan atau bulan berikutnya untuk jatuh tempo. Jika tanggal tidak tersedia (misalnya 31 Februari), sistem memakai hari terakhir bulan tersebut.
2. Tentukan apakah tarif khusus/bebas iuran rumah kosong diterapkan. Warga aktif dan penanggung jawab rumah dinilai kembali pada saat penerbitan, bukan dibekukan saat menyimpan template.
3. Simpan, pilih periode pada daftar template, lalu tekan **Tinjau**. Periksa penerima, rumah, komponen, nominal, penerima yang dilewati, dan jatuh tempo.
4. Centang konfirmasi dan tekan **Terbitkan Tagihan**. Tagihan, komponen, riwayat dan notifikasi disimpan bersama dalam satu transaksi. Pengiriman WhatsApp tetap diproses scheduler.
5. Template yang sama tidak bisa diterbitkan dua kali pada periode yang sama, meskipun diedit. Bila penerima/tarif berubah setelah pratinjau, tinjau ulang. Tagihan susulan dibuat manual. Pencegahan ganda ini berlaku per template, bukan lintas semua template/tagihan manual.

Template dapat dinonaktifkan melalui Edit. Mengubah template tidak mengubah tagihan yang sudah terbit. Menyimpan template atau membuka pratinjau tidak mengirim tagihan. Penerbitan tetap membutuhkan tindakan pengurus; belum ada penagihan otomatis bulanan.

## Tindak Lanjut Tunggakan

Buka **Tindak Lanjut** atau tombol **Tindak Lanjut** pada laporan tunggakan. Pilih warga, lalu catat status komunikasi, catatan dan pengurus penanggung jawab. **Janji Bayar** dan **Hubungi Kembali** wajib memiliki tanggal hari ini atau sesudahnya. Catatan bersifat internal, tersimpan sebagai riwayat baru dan tidak mengubah pembayaran atau mengirim pesan WhatsApp. Riwayat terbaru tetap dapat ditemukan setelah warga melunasi tunggakan. **Tindak Lanjut Selesai** berarti komunikasi selesai, bukan tagihan otomatis lunas.

## Tanggal Transaksi dan Nota

Pada **Arus Kas → Tambah/Edit**, pilih tanggal transaksi: tanggal uang diterima/dikeluarkan, paling lambat hari ini. Waktu pencatatan tetap diisi server dan tidak dapat diketik pengguna. Pembayaran tagihan terverifikasi/cash selalu dicatat memakai tanggal server saat disahkan. Laporan dan grafik kas mengikuti tanggal transaksi; data lama memakai tanggal pencatatan sebagai tanggal transaksi awal.

Unggah satu JPG/PNG/WebP atau PDF maksimal 5 MB pada **Nota / bukti transaksi**. Bukti disimpan privat dan hanya dapat diunduh pengurus perumahan. Mengunggah file baru mengganti lampiran lama; centang **Hapus lampiran lama** untuk melepasnya. Lampiran terhapus ketika transaksi manual dihapus. Kas otomatis dari tagihan tetap tidak dapat diedit atau dihapus lewat kas manual.

## Riwayat Aktivitas Keuangan

Menu **Riwayat Aktivitas** mencatat pembuatan/perubahan/penghapusan kas, penerbitan dan status tagihan, pembayaran gabungan, template, catatan tindak lanjut serta penutupan/pembukaan buku. Filter jenis aktivitas dan tanggal, lalu buka **Lihat nilai sebelum / sesudah**. Nama pelaku dan waktu server dicatat; operasi CLI tanpa akun login ditandai Sistem / CLI. Log tidak menyimpan token, password, isi file atau path privat lampiran. Tidak tersedia edit/hapus log melalui aplikasi. Riwayat dimulai sejak fitur diaktifkan dan tidak direkonstruksi untuk data lama; akses administrator database tetap berada di luar pembatasan aplikasi.

## Tutup Buku Kas

1. Buka **Tutup Buku**, pilih bulan yang sudah berakhir, dan periksa saldo awal, pemasukan, pengeluaran dan saldo akhir. Buka **Periksa Laporan Kas** untuk memeriksa rincian.
2. Setelah angka dan bukti benar, centang konfirmasi lalu tekan **Tutup Buku Bulan Ini**. Ringkasan penutupan, pelaku dan waktu server disimpan.
3. Transaksi dalam periode tertutup tidak bisa ditambah, diubah, dipindahkan tanggal, diganti nota atau dihapus. Transaksi lebih lama juga dikunci bila memengaruhi saldo awal periode tertutup. Ini mencegah laporan yang telah disahkan berubah melalui tanggal mundur.
4. Jika perlu koreksi, buka kembali **periode tertutup paling akhir** lebih dulu dan tulis alasan minimal 10 karakter. Untuk mengoreksi bulan lebih lama, buka juga periode tertutup sesudahnya secara berurutan. Setelah koreksi, tinjau dan tutup kembali periode yang diperlukan.

Bulan berjalan/masa depan belum dapat ditutup. Penguncian berlaku untuk buku kas berdasarkan tanggal transaksi, bukan melarang pembayaran tagihan lama: tagihan lama tetap bisa dibayar sekarang dan masuk kas bulan berjalan. Laporan tagihan dan rekap tahunan tetap mencerminkan status pembayaran terkini. Membuka kembali dan menutup ulang tercatat pada riwayat aktivitas.

## Riwayat WhatsApp

Buka menu **Riwayat WhatsApp** untuk memantau pengingat dan kuitansi. Pilih tanggal, jenis pesan, status, nama warga atau rumah, lalu tekan **Tampilkan**. Default tanggal adalah bulan berjalan hingga hari ini; kosongkan keduanya untuk seluruh riwayat. Ringkasan mengikuti seluruh filter dan seluruh halaman.

Klik **Detail** untuk melihat kendala dan saran, nomor tujuan percobaan terakhir, nomor akun saat ini, waktu proses, tagihan atau kuitansi terkait. Riwayat lama mungkin belum mencatat nomor tujuan/waktu percobaan dan tidak ditebak dari data sekarang. Perubahan nomor warga tidak mengirim ulang pesan yang sudah gagal/dilewati. Nomor tujuan baru disimpan terenkripsi.

**Diterima Fonnte** berarti permintaan diterima API, belum membuktikan pesan sampai atau dibaca. **Perlu Diperiksa** berarti hasil tidak pasti; periksa dashboard Fonnte sebelum mempertimbangkan pengiriman ulang. Halaman ini tidak mengirim pesan dan belum menyediakan tombol kirim ulang. Hanya pengurus aktif perumahan yang dapat mengakses riwayatnya. [Panduan status dan penanganan kendala](../README.md#jadwal-dan-pemeriksaan-pengiriman).
