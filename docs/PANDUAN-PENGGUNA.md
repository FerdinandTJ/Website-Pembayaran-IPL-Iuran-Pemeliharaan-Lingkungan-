# Panduan Penggunaan EnviroPay

[Kembali ke README](../README.md) · [Notifikasi](NOTIFIKASI.md) · [Deployment](DEPLOYMENT.md)

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

Username/email harus unik di file. Warga lama dicocokkan berdasarkan username atau email tanpa membedakan huruf besar/kecil, hanya dalam perumahan dan peran warga yang dikelola. Jika kedua identitas cocok dengan akun berbeda, identitas tidak unik, atau mengarah ke akun perumahan/peran lain, batch ditolak. Identitas yang belum ada membuat akun baru; akun yang cocok diperbarui tanpa mengganti ID, role, status aktif, atau kepemilikan tagihan lama. Password awal wajib untuk warga baru, minimal 8 dan maksimal 128 karakter. Untuk warga lama, password kosong mempertahankan hash lama; terisi mengganti password dan mencabut sesi/push perangkat. Password tidak ditampilkan pada pratinjau. Nomor telepon diperlakukan sebagai teks; pastikan spreadsheet tidak menghilangkan nol awal sebelum CSV dibuat. Setelah diimpor, warga dapat mengubah password melalui Profil.

Satu baris salah menahan seluruh impor. Pratinjau menandai Baru/Perbarui dan hasil konfirmasi menyebut jumlah masing-masing. Konfirmasi memeriksa ulang data akun dan rumah serta menyimpan seluruh batch dalam satu transaksi. Jika data berubah setelah pratinjau atau identitas baru dipakai proses lain, batch dibatalkan dan perlu diunggah ulang. Klik konfirmasi ulang tidak membuat akun ganda. Pratinjau disimpan terenkripsi dengan password yang sudah di-hash, terikat ke pengurus/perumahan, dan kedaluwarsa setelah 30 menit. Setelah selesai, isi batch dihapus. Scheduler membersihkan batch kedaluwarsa setiap hari pukul 02.00 waktu aplikasi.

## Pembayaran Cash dan Kuitansi

Buka **Pembayaran Cash → pilih warga → pilih tagihan → konfirmasi uang diterima**. Pengurus tidak mengetik ulang nominal. Pembayaran harus lunas per tagihan; cicilan dan pembayaran beberapa tagihan sekaligus belum tersedia. Waktu cash otomatis mengikuti waktu pencatatan, bukan tanggal mundur.

Hanya tagihan `unpaid`/`rejected` yang dapat dibayar cash. Tagihan `pending` harus diproses atau ditolak terlebih dahulu untuk mencegah pembayaran ganda. Akun warga nonaktif yang masih memiliki tagihan dapat dipilih oleh pengurus. Penguncian tagihan, perubahan status, pemasukan kas, kuitansi, dan notifikasi berjalan dalam satu transaksi. Pemasukan dari tagihan tetap terkunci terhadap edit/hapus manual.

Kuitansi tersedia pada detail tagihan lunas, daftar tagihan pengurus, dan setelah penerimaan cash. Kuitansi menggunakan nomor tetap `KW-{housing_id}-{invoice_id}` dan memuat salinan nama/alamat, komponen, nominal, metode pembayaran, tanggal konfirmasi, dan pengurus penerima/verifikator. Perubahan data berikutnya tidak mengubah salinan kuitansi. Warga hanya dapat membaca kuitansi miliknya; pengurus hanya dari perumahannya.

Kuitansi transfer diterbitkan saat verifikasi. Transfer lama yang sudah terverifikasi memperoleh kuitansi historis saat pertama dibuka, menggunakan data yang tersedia saat penerbitan; tanggal penerbitan dibedakan dari tanggal konfirmasi historis. Tidak ada tanggal pembayaran transfer yang ditebak dari gambar bukti. Tombol **Unduh PDF** menghasilkan PDF A4 di server menggunakan Dompdf, tanpa akses aset jarak jauh. Tombol **Cetak** menggunakan halaman cetak browser.

## Pengumuman dan Password

Pengurus dapat membuat draft pengumuman, memeriksanya, lalu menerbitkan kepada seluruh warga aktif. Draft tidak terlihat oleh warga. Penerbitan mengirim satu notifikasi per penerima dan menampilkan tiga pengumuman terbaru pada dashboard. Warga baru dapat membaca pengumuman yang masih terbit. Pengumuman terbit tidak dapat diedit; arsipkan dan buat pengumuman pengganti. Arsip hilang dari daftar aktif tetapi tautan notifikasi lama tetap dapat dibuka dengan label arsip. Isi berupa teks biasa, tanpa lampiran.

Semua peran dapat memakai **Profil → Ubah Password**, dengan password lama, password baru minimal 8 karakter, dan konfirmasi yang cocok. Password baru harus berbeda. Akun tetap masuk di perangkat saat ini, sesi database pada perangkat lain dihapus, dan remember token diganti. Deployment saat ini menggunakan `SESSION_DRIVER=database`; pertahankan driver ini untuk penghentian sesi perangkat lain. Reset password pengurus/admin yang sudah tersedia tetap dapat digunakan.

## Laporan Pengurus

Buka menu **Laporan** untuk melihat:

- **Tunggakan**: tagihan `unpaid`/`rejected` yang jatuh temponya sebelum hari ini (Asia/Jakarta), termasuk milik warga nonaktif. Tagihan `pending` tidak dihitung sebagai tunggakan. Umur tunggakan dibagi menjadi 1–30, 31–60, dan >60 hari. Filter umur memilih warga yang memiliki tunggakan pada kelompok itu; total tetap mencakup seluruh tunggakan warga tersebut. Tombol **Lihat** membuka tagihan penyusunnya.
- **Tagihan Bulanan**: berdasarkan `billing_period`, dengan jumlah/nominal setiap status dan persentase nominal terverifikasi. Status yang ditampilkan adalah kondisi saat ini, bukan rekonstruksi status pada akhir bulan.
- **Kas Bulanan**: berdasarkan tanggal pencatatan (`created_at`), dengan saldo awal, pemasukan manual/tagihan, pengeluaran, dan saldo akhir. Perubahan atau penghapusan transaksi manual dapat mengubah laporan bulan sebelumnya.

CSV mengikuti filter, mencakup seluruh halaman, menggunakan UTF-8, dan melindungi teks yang berpotensi menjadi formula spreadsheet. Bagian awal CSV memuat ringkasan; tabel rincian dimulai dari baris nama kolom. Semua laporan dibatasi ke perumahan pengurus yang login.
