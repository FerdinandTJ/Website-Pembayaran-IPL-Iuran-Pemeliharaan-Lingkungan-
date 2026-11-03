<div id="notifikasi-perangkat" class="card mt-4" data-web-push data-config-url="{{ route('push.config') }}" data-status-url="{{ route('push.status') }}" data-save-url="{{ route('push.store') }}" data-delete-url="{{ route('push.destroy') }}" data-test-url="{{ route('push.test') }}" data-worker-url="{{ url('/service-worker.js') }}">
    <div class="card-header"><strong><i class="bi bi-phone me-2"></i>Notifikasi Layar HP / Komputer</strong></div>
    <div class="card-body"><p class="small text-secondary">Aktifkan pada perangkat ini agar pemberitahuan dapat muncul meskipun halaman aplikasi ditutup. Isi layar kunci dibuat umum; detail tagihan tetap perlu login.</p>
        <p class="small" role="status" aria-live="polite" data-push-status>Memeriksa dukungan notifikasi perangkat...</p>
        <div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-primary" data-push-enable disabled>Aktifkan Notifikasi Perangkat</button><button type="button" class="btn btn-outline-secondary d-none" data-push-disable>Nonaktifkan di Perangkat Ini</button><button type="button" class="btn btn-outline-primary d-none" data-push-test>Kirim Notifikasi Uji</button></div>
        <p class="small text-secondary mt-3 mb-0">iPhone/iPad: tambahkan ke Home Screen melalui menu Bagikan Safari, lalu buka dari ikon aplikasi dan aktifkan notifikasi (iOS/iPadOS 16.4+). Situs pada HP harus memakai HTTPS. Pengiriman dapat tertunda oleh koneksi, mode hemat daya, atau pengaturan notifikasi perangkat.</p>
        <noscript><p class="text-danger">Aktifkan JavaScript untuk mengelola izin notifikasi perangkat.</p></noscript>
    </div>
</div>
