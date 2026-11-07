# Dokumentasi EnviroPay

[Kembali ke README proyek](../README.md)

## Bahan Bab III skripsi

Mulai dari [BAB III — Metode dan Perancangan Sistem](BAB-III-PERANCANGAN.md). Kerangka menggunakan **Waterfall**, berisi narasi metode/analisis, rancangan proses, arsitektur, basis data, antarmuka, logika, keamanan serta rencana uji/penerapan. Tidak mencakup Bab I atau Bab II.

| Lampiran perancangan | Isi |
| --- | --- |
| [Metodologi dan analisis](perancangan/01-METODOLOGI-DAN-ANALISIS.md) | Tahapan/gate Waterfall, sumber bukti, model kandidat proses berjalan dan rencana kegiatan. |
| [Use case dan aktivitas](perancangan/02-USE-CASE-DAN-AKTIVITAS.md) | Diagram UML use case per aktor, 13 kelompok spesifikasi RUC dan aktivitas tambahan. |
| [Arsitektur, data dan logika](perancangan/03-ARSITEKTUR-DATA-DAN-LOGIKA.md) | Kelas, sequence, deployment, normalisasi, algoritma dan pengendalian risiko. |
| [Antarmuka, input/output](perancangan/04-ANTARMUKA-MASUKAN-KELUARAN.md) | 12 kelompok UI, wireframe SVG, validasi dan keluaran. |
| [Rencana pengujian](perancangan/05-RENCANA-PENGUJIAN.md) | 25 kelompok black-box, 15 rancangan nilai batas, tabel keputusan dan kriteria evaluasi. |
| [Instrumen dan kelengkapan](perancangan/06-INSTRUMEN-DAN-KELENGKAPAN.md) | Formulir observasi, wawancara, validasi kebutuhan, uji, UAT dan daftar kelengkapan. |
| [Referensi dan pemakaian](perancangan/07-REFERENSI-DAN-PEMAKAIAN.md) | Sumber metodologi, daftar gambar/tabel kerja dan cara memakai bahan pada naskah. |
| [Matriks ketertelusuran](perancangan/08-MATRIKS-KETERTELUSURAN.md) | Pemetaan seluruh 29 FR ke kelompok use case, antarmuka dan skenario penerimaan. |
| [Keselarasan dan audit](perancangan/09-KONSISTENSI-DAN-AUDIT.md) | Hierarki acuan, aturan lintas dokumen, cakupan dan batas kelengkapan. |

## Spesifikasi teknis dan penggunaan

- [Software Requirements Specification](SRS.md): kebutuhan dan batas sistem; rincian pada `srs/`.
- [ERD](srs/02-MODEL-DATA.md), [PDM/kamus data](srs/03-PDM-KAMUS-DATA.md), [DFD](srs/04-DFD-DAN-PROSES.md) serta [verifikasi](srs/05-VERIFIKASI.md): lampiran teknis acuan perancangan.
- [Panduan pengguna](PANDUAN-PENGGUNA.md): penggunaan fitur aplikasi.
- [Instalasi dan deployment](../README.md#instalasi-lokal) serta [keamanan](../SECURITY.md): pengelolaan lingkungan aplikasi.

Source rancangan mengikuti baseline kode, tanpa data warga nyata. Objek penelitian, bukti lapangan, keputusan kebutuhan, hasil UAT serta target kinerja/pemulihan tetap perlu dilengkapi dari penelitian sebenarnya. Materi tersebut tidak diisi dengan nama, jawaban informan atau hasil ukur fiktif.

## Pemeriksaan paket perancangan

Pemeriksaan keselarasan 8 November 2026 mencakup seluruh 29 FR, 25 BR dan 10 NFR, matriks RUC/UI/AT/BB, 30 blok Mermaid di dokumentasi proyek, 3 diagram use case SVG dan 14 wireframe SVG. Rincian hasil dan batas bukti dicatat pada [Audit Dokumentasi](AUDIT-DOKUMENTASI.md). Pemeriksaan dapat diulang dengan [alat dokumentasi](tools/README.md). Data lapangan dan keputusan penerimaan tidak dihasilkan otomatis oleh pemeriksa ini.
