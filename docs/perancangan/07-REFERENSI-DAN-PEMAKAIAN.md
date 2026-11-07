# Referensi dan Pemakaian dalam Naskah

[Bab III](../BAB-III-PERANCANGAN.md) · [Indeks dokumentasi](../README.md)

## 1. Referensi metodologis

Tanggal penelusuran sumber daring: **8 November 2026**. Gaya daftar pustaka berikut bersifat kerja; sesuaikan APA/IEEE atau gaya wajib kampus tanpa mengubah penulis, judul dan versi. Referensi dipakai untuk metode/perancangan, bukan menggantikan Bab II yang tidak diminta pada paket ini.

### R1

Royce, W. W. (1970). *Managing the Development of Large Software Systems*. Proceedings, IEEE WESCON, August 1970, pp. 1–9. [Salinan makalah pada repository arsip](https://github.com/tpn/pdfs/blob/master/Managing%20the%20Development%20of%20Large%20Software%20Systems%20-%201970%20%28waterfall%29.pdf).

Naskah proyek memakai adaptasi tahap kerja, bukan menyatakan diagram lima kotak proyek identik dengan diagram Royce. Sumber arsip tersebut adalah salinan makalah, bukan situs penerbit resmi. Pembahasan sumber mencakup umpan balik, dokumentasi, pengujian dan keterlibatan pelanggan; jangan mengutipnya sebagai anjuran proses sekali jalan tanpa revisi.

### R2

National Aeronautics and Space Administration. (tanpa tahun pada sitasi kerja halaman web). *System Engineering Handbook: Appendix*. [Halaman resmi NASA](https://www.nasa.gov/reference/system-engineering-handbook-appendix/), khusus Appendix D (Requirements Verification Matrix) dan Appendix E (Creating the Validation Plan with a Validation Requirements Matrix).

Digunakan secara proporsional untuk rancangan keterlacakan dan bukti verifikasi/validasi, bukan untuk menyatakan EnviroPay mematuhi persyaratan misi NASA. Jika mengutip edisi PDF handbook pada naskah final, periksa edisi/tahun/halaman PDF itu secara tersendiri.

### R3

International Software Testing Qualifications Board. (2024). *Certified Tester Foundation Level Syllabus*, version 4.0.1, 15 September 2024. [PDF resmi ISTQB](https://istqb.org/wp-content/uploads/2024/11/ISTQB_CTFL_Syllabus_v4.0.1.pdf), khusus bagian 4.2, hlm. tercetak 39–41.

Digunakan untuk pemilihan teknik partisi, batas, keputusan dan status. Tabel kasus dalam proyek merupakan rancangan khusus EnviroPay, bukan salinan kasus contoh dari syllabus. Tidak ada klaim sertifikasi atau hasil coverage dari sitasi ini.

## 2. Sumber teknis proyek

| Sumber | Dipakai untuk |
| --- | --- |
| [SRS](../SRS.md) dan [kebutuhan](../srs/01-KEBUTUHAN.md) | Aktor, FR/BR/NFR, batas sistem dan kontrak. |
| [ERD](../srs/02-MODEL-DATA.md) serta [PDM](../srs/03-PDM-KAMUS-DATA.md) | Kardinalitas, skema dan kamus fisik. |
| [DFD dan proses](../srs/04-DFD-DAN-PROSES.md) | Arus data, status dan sequence pembayaran/WhatsApp. |
| [Route](../../routes/web.php), [controller](../../app/Http/Controllers) dan [service](../../app/Services) | Kesesuaian langkah/otorisasi dengan implementasi. |
| [Migration](../../database/migrations) dan [model](../../app/Models) | Tipe, indeks, hubungan dan cast payload. |
| [Pengujian](../../tests/Feature), [frontend](../../tests/Frontend) dan [catatan SRS](../srs/05-VERIFIKASI.md) | Dasar kasus/bukti teknis, tanpa mengubahnya menjadi hasil UAT. |

Source code adalah bukti perilaku baseline dan sumber rancangan yang direkonstruksi; tidak menjadi bukti observasi lapangan. Versi yang diacu pada penyusunan paket adalah `94cda91`.

## 3. Daftar gambar kerja Bab III

| Nomor | Judul / sumber |
| --- | --- |
| Gambar 3.1 | Tahapan Waterfall yang diadaptasi untuk EnviroPay — Mermaid dalam naskah Bab III. |
| Gambar 3.2 | Alur utama sistem usulan — Mermaid dalam naskah Bab III. |
| Gambar 3.3 | Diagram use case pengurus — SVG pada `docs/images/perancangan/`. |
| Gambar 3.4 | Rancangan aktivitas transfer — Mermaid dalam naskah Bab III. |
| Gambar 3.5 | Arsitektur logis — Mermaid dalam naskah Bab III. |
| Gambar 3.6 | Wireframe laporan pengurus — SVG pada `docs/images/perancangan/`. |

Diagram tambahan pada lampiran dapat memperoleh nomor gambar lanjutan setelah dipilih untuk naskah. Nomor tersebut belum dipaksakan pada setiap diagram SRS, sehingga tidak ada dua caption yang diberi nomor 3.x sama dalam naskah utama. Setiap gambar model kandidat/fiktif tetap mempertahankan statusnya.

## 4. Daftar tabel kerja Bab III

| Nomor | Judul |
| --- | --- |
| Tabel 3.1 | Keluaran dan kriteria peninjauan tahap Waterfall. |
| Tabel 3.2 | Aktor utama sistem. |
| Tabel 3.3 | Pemetaan modul perancangan. |
| Tabel 3.4 | Relasi keuangan yang menentukan integritas. |
| Tabel 3.5 | Rancangan tingkat pengujian. |
| Tabel 3.6 | Indeks keluaran Bab III. |

Tabel instrumen/kasus uji lebih panjang dapat menjadi lampiran agar naskah utama tetap terbaca. Daftar tabel/gambar di pengolah kata dibuat otomatis setelah pemilihan materi final.

## 5. Cara menyiapkan naskah akademik

1. Gunakan naskah Bab III sebagai alur narasi dan pilih rincian lampiran yang diperlukan pembimbing.
2. Isi identitas objek, izin, data lapangan dan jadwal berdasarkan bukti asli; revisi analisis/proses berjalan setelah konfirmasi.
3. Selaraskan judul, rumusan kebutuhan, batasan dan tujuan penelitian pada bagian naskah yang disusun peneliti, tanpa menambah Bab I/II di paket ini.
4. Masukkan diagram sebagai SVG atau ekspor resolusi memadai. Mermaid adalah sumber diagram; viewer teks yang tidak mendukung Mermaid tidak merender gambar otomatis.
5. Gunakan style Heading, caption dan cross-reference sesuai pedoman kampus. Nomor halaman bergantung tata letak akhir, bukan nomor baris Markdown.
6. Pertahankan sumber/caption dan sitasi dekat klaim metode. Jangan mengutip hasil rancangan proyek sebagai kutipan dari referensi metodologis.
7. Tempatkan hasil aktual, analisis temuan dan pengukuran manfaat pada bagian hasil/pembahasan sesuai pedoman kampus; rencana pengujian Bab III tidak diubah menjadi hasil tanpa eksekusi.
8. Minta pembimbing/pemilik proses meninjau metode, kesesuaian kebutuhan dan kelengkapan bukti sebelum menyatakan naskah final.

Paket ini disediakan sebagai Markdown/Mermaid/SVG dalam repository. Belum dibuat file Word/PDF atau klaim format final kampus; bila dikonversi kemudian, lakukan pemeriksaan visual ulang terhadap halaman, tabel, gambar dan daftar pustaka.
