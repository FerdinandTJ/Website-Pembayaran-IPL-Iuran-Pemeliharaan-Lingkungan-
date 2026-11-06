@php
    $user = auth()->user();
    $dashboardRoute = match($user->role) {'admin' => 'admin.dashboard', 'pengurus' => 'pengurus.dashboard', default => 'warga.dashboard'};
    $items = $user->isAdmin() ? [
        'Administrasi' => [['admin.housings.*','admin.housings.index','buildings','Perumahan'],['admin.managers.*','admin.managers.index','person-badge','Pengurus']],
    ] : ($user->isManager() ? [
        'Data lingkungan' => [['pengurus.houses.*,pengurus.house-imports.*','pengurus.houses.index','houses','Data Rumah'],['pengurus.residents.*,pengurus.imports.*','pengurus.residents.index','people','Data Warga']],
        'Keuangan' => [['pengurus.invoice-templates.*','pengurus.invoice-templates.index','files','Template Tagihan'],['pengurus.follow-ups.*','pengurus.follow-ups.index','telephone','Tindak Lanjut'],['pengurus.invoices.*','pengurus.invoices.index','receipt','Tagihan'],['pengurus.cash-payments.*','pengurus.cash-payments.index','cash-coin','Pembayaran Cash'],['pengurus.verifications.*','pengurus.verifications.index','patch-check','Verifikasi'],['pengurus.cashflows.*','pengurus.cashflows.index','wallet2','Arus Kas'],['pengurus.accounting-periods.*','pengurus.accounting-periods.index','journal-check','Tutup Buku'],['pengurus.activity-logs.*','pengurus.activity-logs.index','clock-history','Riwayat Aktivitas'],['pengurus.payment-accounts.*','pengurus.payment-accounts.index','bank','Rekening'],['pengurus.reports.*,pengurus.annual-report.*','pengurus.reports.index','bar-chart-line','Laporan']],
        'Informasi' => [['pengurus.whatsapp-history.*','pengurus.whatsapp-history.index','whatsapp','Riwayat WhatsApp'],['announcements.*,pengurus.announcements.*','announcements.index','megaphone','Pengumuman']],
    ] : ['Layanan warga' => [['warga.invoices.*','warga.invoices.index','receipt','Tagihan Saya'],['announcements.*','announcements.index','megaphone','Pengumuman']]]);
@endphp
@unless($offcanvas)
<a class="app-brand" href="{{ route($dashboardRoute) }}"><span class="brand-mark"><i class="bi bi-leaf" aria-hidden="true"></i></span><span>EnviroPay<small>Iuran lingkungan, lebih mudah.</small></span></a>
@endunless
<div class="sidebar-scroll">
<nav class="sidebar-nav" aria-label="Navigasi utama">
    <div class="sidebar-label">Workspace</div>
    <a class="nav-link {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}" href="{{ route($dashboardRoute) }}" @if(request()->routeIs($dashboardRoute)) aria-current="page" @endif><i class="bi bi-grid-1x2" aria-hidden="true"></i><span>Dashboard</span></a>
    @foreach($items as $group => $links)
    <div class="sidebar-label">{{ $group }}</div>
    @foreach($links as [$patterns,$route,$icon,$label])
    @php($active = request()->routeIs(...explode(',', $patterns)))
    <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ $route === 'pengurus.reports.index' ? route($route,'arrears') : route($route) }}" @if($active) aria-current="page" @endif><i class="bi bi-{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span></a>
    @endforeach
    @endforeach
    <div class="sidebar-label">Akun</div>
    <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif><i class="bi bi-person-circle" aria-hidden="true"></i><span>Profil & Pengaturan</span></a>
</nav>
</div>
@unless($offcanvas)
<div class="sidebar-user"><span class="sidebar-avatar">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</span><div class="min-w-0"><strong>{{ $user->name }}</strong><small>{{ $user->housing?->name ?? 'Administrator' }}</small></div><span class="sidebar-online" title="Akun aktif"></span></div>
@endunless
