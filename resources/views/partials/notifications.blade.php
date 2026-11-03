<div class="dropdown" data-notification-widget data-list-url="{{ route('notifications.index') }}" data-count-url="{{ route('notifications.count') }}" data-read-all-url="{{ route('notifications.read-all') }}">
    <button class="btn btn-light position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Buka notifikasi" data-notification-toggle>
        <i class="bi bi-bell fs-5"></i><span class="position-absolute top-0 start-100 translate-middle badge text-bg-danger d-none" data-notification-badge aria-live="polite"></span>
    </button>
    <div class="dropdown-menu dropdown-menu-end shadow notification-menu">
        <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-2 border-bottom"><strong>Notifikasi</strong><button class="btn btn-link btn-sm p-0" data-notification-read-all>Tandai semua dibaca</button></div>
        <div class="notification-items" data-notification-items><p class="text-secondary p-3 mb-0">Buka untuk memuat notifikasi.</p></div>
        <a class="dropdown-item text-center border-top py-3" href="{{ route('notifications.index') }}">Lihat semua notifikasi</a>
        <a class="dropdown-item text-center py-2 small" href="{{ route('profile.show') }}#notifikasi-perangkat">Aktifkan notifikasi layar HP</a>
    </div>
</div>
