# Pemeriksaan Dokumentasi

[Indeks docs](../README.md) · [Audit dan batas bukti](../perancangan/09-KONSISTENSI-DAN-AUDIT.md)

Alat ini memeriksa artefak dokumentasi. Ia tidak memutuskan apakah jawaban informan benar, kebutuhan sudah disetujui, seluruh diagram benar secara semantik, aplikasi bebas bug atau UAT lulus.

## Pemeriksaan struktur dan ketertelusuran

Dari root proyek, dengan Python 3.9+:

```bash
python3 docs/tools/verify_docs.py
```

Pemeriksaan mencakup tautan/anchor lokal, jumlah kolom tabel Markdown, code fence, nama file/metode test, ID yang dirujuk, definisi FR yang disalin ke matriks, hubungan FR ke RUC/UI/AT/BB, kehadiran seluruh BR/NFR pada matriks, serta struktur XML/title/viewBox pada SVG. Ia tidak mengunduh sumber daring atau menjalankan server aplikasi.

## Perbandingan PDM dengan seluruh migration

Dengan dependency PHP proyek dan ekstensi SQLite tersedia:

```bash
php docs/tools/export_schema.php /tmp/enviropay-schema.json
python3 docs/tools/verify_docs.py --schema /tmp/enviropay-schema.json
```

Exporter memaksa koneksi khusus `documentation_audit` dengan database `:memory:`, session/cache array dan Fonnte nonaktif, lalu menjalankan migration hanya pada koneksi tersebut. Export berisi struktur kolom/indeks/FK tanpa isi tabel. Database aplikasi tidak di-reset atau dimigrasikan. Jangan mengganti koneksi ini menjadi database aplikasi untuk melakukan audit.

Kamus diperiksa terhadap nama/tipe fisik SQLite, nullable/default, PK/UQ/FK/auto increment, urutan/kolom indeks, target dan aksi FK, serta total ringkasan. Ini bukan validasi DDL MySQL/MariaDB atau setiap CHECK/trigger yang mungkin ada di mesin berbeda.

## Validasi renderer Mermaid opsional

Bila Mermaid CLI sudah tersedia di lingkungan pemeriksa:

```bash
python3 docs/tools/verify_docs.py --schema /tmp/enviropay-schema.json \
  --mermaid-cli /path/to/mmdc \
  --puppeteer-config /path/to/puppeteer.json
```

Flag tersebut benar-benar menjalankan renderer untuk setiap sumber Mermaid dan memeriksa hasil SVG dalam direktori sementara. Tidak memasang dependency ke proyek. Path Chrome/konfigurasi mengikuti komputer pemeriksa dan tidak dianggap konfigurasi production aplikasi.

Tanpa flag renderer, hasil hanya menghitung blok Mermaid dan memeriksa sumber/rujukannya; jangan mengutipnya sebagai bukti sintaks/render Mermaid berhasil. Pemeriksaan batas teks SVG pada browser dan peninjauan visual juga harus dicatat tersendiri. Hasil proses ulang disimpan bersama commit dan versi alat pada laporan pemeriksaan.
