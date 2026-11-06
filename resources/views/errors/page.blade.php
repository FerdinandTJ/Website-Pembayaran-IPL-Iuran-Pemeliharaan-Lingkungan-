@php
    $pages = [
        400 => ['Permintaan belum bisa diproses', 'Ada informasi dalam permintaan yang belum sesuai. Buka kembali halaman yang dituju, lalu coba lagi.', 'Periksa kembali permintaan Anda'],
        401 => ['Silakan masuk terlebih dahulu', 'Masuk ke akun Anda untuk melanjutkan ke halaman ini.', 'Akses akun diperlukan'],
        403 => ['Halaman ini dibatasi', 'Akun Anda tidak memiliki akses ke halaman ini atau aksesnya sedang dinonaktifkan. Hubungi pengurus jika Anda memerlukan bantuan.', 'Akses tidak tersedia'],
        404 => ['Sepertinya Anda salah jalan', 'Halaman yang Anda cari tidak ditemukan. Tautannya mungkin sudah berubah atau halaman tersebut tidak tersedia.', 'Mari kembali ke halaman utama'],
        405 => ['Permintaan tidak sesuai', 'Halaman ini tidak dapat dibuka dengan cara tersebut. Buka kembali melalui menu aplikasi untuk melanjutkan.', 'Gunakan menu aplikasi'],
        408 => ['Permintaan melewati batas waktu', 'Permintaan belum selesai dalam waktu yang tersedia. Periksa koneksi Anda, lalu buka kembali halaman yang dituju.', 'Coba kembali beberapa saat lagi'],
        409 => ['Data sudah berubah', 'Data yang Anda gunakan mungkin sudah diperbarui. Buka kembali halaman terkait dan periksa informasi terbaru sebelum melanjutkan.', 'Periksa informasi terbaru'],
        410 => ['Halaman ini sudah tidak tersedia', 'Halaman yang Anda tuju sudah tidak digunakan. Anda dapat melanjutkan melalui menu di halaman utama.', 'Temukan informasi melalui menu aplikasi'],
        413 => ['Ukuran berkas terlalu besar', 'Berkas yang diunggah melebihi batas permintaan. Pilih berkas yang lebih kecil sesuai petunjuk pada formulir.', 'Gunakan berkas yang lebih kecil'],
        419 => ['Sesi halaman sudah berakhir', 'Halaman mungkin terlalu lama terbuka. Masuk kembali, lalu buka formulir dari menu aplikasi sebelum melanjutkan.', 'Saatnya membuka sesi baru'],
        422 => ['Informasi belum sesuai', 'Ada informasi yang perlu diperiksa kembali. Buka formulir melalui menu aplikasi dan ikuti petunjuk pengisiannya.', 'Periksa kembali isian Anda'],
        429 => ['Sebentar, beri jeda dahulu', 'Ada terlalu banyak permintaan dalam waktu berdekatan. Tunggu beberapa saat sebelum mencoba kembali.', 'Silakan coba lagi setelah jeda'],
        500 => ['Ada kendala di sisi kami', 'Aplikasi belum dapat menyelesaikan permintaan Anda. Coba lagi beberapa saat lagi. Jika kendala berlanjut, hubungi pengurus.', 'Kami belum bisa memuat halaman ini'],
        502 => ['Layanan belum merespons', 'Aplikasi belum memperoleh respons yang diperlukan. Silakan coba lagi beberapa saat lagi.', 'Layanan sementara belum tersedia'],
        503 => ['Kami sedang bersiap kembali', 'Aplikasi sedang dalam pemeliharaan atau sementara belum tersedia. Silakan kembali beberapa saat lagi.', 'Terima kasih sudah menunggu'],
        504 => ['Layanan membutuhkan waktu lebih lama', 'Layanan belum merespons dalam waktu yang tersedia. Silakan coba lagi beberapa saat lagi.', 'Coba kembali setelah beberapa saat'],
    ];
    [$heading, $description, $hint] = $pages[$statusCode] ?? ($statusCode >= 500 ? $pages[500] : $pages[400]);
    $signIn = in_array($statusCode, [401, 419], true);
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#176b52">
    <title>{{ $statusCode }} · {{ $heading }} — EnviroPay</title>
    @include('errors.styles')
</head>
<body>
    <div class="error-shell">
        <header class="error-header">
            <a class="brand" href="{{ url('/') }}" aria-label="EnviroPay, halaman utama">
                <span class="brand-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M19 4C9 3 4 8 6 14c2 5 10 6 12-2 1-3 1-6 1-8Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m5 20 9-10M10 15l-1-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                <span>EnviroPay<small>Iuran lingkungan, terkelola.</small></span>
            </a>
            <span class="header-label">Ruang bersama lingkungan Anda</span>
        </header>
        <main class="error-card" aria-labelledby="error-heading">
            <section class="error-art" aria-hidden="true">
                <div class="art-top"><span class="small-dot"></span> TERHUBUNG DALAM SATU LINGKUNGAN</div>
                <div class="scene">
                    <span class="error-code">{{ $statusCode }}</span>
                    <svg class="neighborhood" viewBox="0 0 440 285" fill="none">
                        <circle cx="340" cy="65" r="26" fill="#d8e8ba"/>
                        <path d="M38 236c42-27 78-16 123-25 58-12 82-16 131 3 37 15 66 4 109 24" fill="#dce9d8"/>
                        <path d="M26 243h388" stroke="#a6bea6" stroke-width="2" stroke-linecap="round"/>
                        <path d="M85 242v-88l71-54 70 54v88" fill="#f8fbf3" stroke="#6c9480" stroke-width="2" stroke-linejoin="round"/>
                        <path d="m70 160 86-67 85 67" stroke="#245d48" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M137 242v-59a19 19 0 0 1 38 0v59" fill="#c5d9b4" stroke="#6c9480" stroke-width="2"/>
                        <path d="M100 169h22v26h-22zM190 169h22v26h-22z" fill="#e1ead3" stroke="#7a9c83" stroke-width="2"/>
                        <path d="M264 242v-65l43-35 44 35v65" fill="#e3eddc" stroke="#7a9c83" stroke-width="2" stroke-linejoin="round"/>
                        <path d="m254 181 53-45 54 45" stroke="#547e64" stroke-width="5" stroke-linecap="round"/>
                        <path d="M296 242v-33a11 11 0 0 1 22 0v33" fill="#b9d0a7" stroke="#7a9c83" stroke-width="2"/>
                        <path d="M50 240v-46m0 21-12-10m12 0 13-12M387 241v-42m0 19-12-11m12-1 10-9" stroke="#7c9a75" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="50" cy="185" r="21" fill="#adc797"/><circle cx="387" cy="190" r="18" fill="#c4d7ad"/>
                        <path d="M180 277c9-14 21-22 39-34M220 277c9-17 14-25 13-34" stroke="#c4d3bf" stroke-width="2" stroke-linecap="round"/>
                        <path d="M228 98h45m-22-16v32" stroke="#b0c99d" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <p class="art-caption">Lingkungan tertata.<br><strong>Iuran terkelola.</strong></p>
            </section>
            <section class="error-copy">
                <span class="status-pill"><span class="small-dot"></span> HALAMAN {{ $statusCode }}</span>
                <h1 id="error-heading">{{ $heading }}</h1>
                <p class="description">{{ $description }}</p>
                <a class="primary-action" href="{{ url('/') }}">
                    {{ $signIn ? 'Masuk kembali' : 'Ke halaman utama' }}
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <div class="help-note">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 11v6m0-10v.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <p>{{ $hint }}. Untuk bantuan lebih lanjut, silakan hubungi pengurus lingkungan Anda.</p>
                </div>
            </section>
        </main>
        <footer class="error-footer"><span>EnviroPay · Sistem Pembayaran IPL</span><span>Lingkungan yang baik dimulai dari kita.</span></footer>
    </div>
</body>
</html>
