@php
    $user = auth()->user();
    $dashboardRoute = match($user->role) {'admin' => 'admin.dashboard', 'pengurus' => 'pengurus.dashboard', default => 'warga.dashboard'};
@endphp
@unless($offcanvas)
<a class="app-brand" href="{{ route($dashboardRoute) }}"><span class="brand-mark"><i class="bi bi-leaf"></i></span><span>EnviroPay</span></a>
@endunless
<div class="sidebar-label">Menu utama</div>
<nav class="sidebar-nav nav flex-column">
    <a class="nav-link {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}" href="{{ route($dashboardRoute) }}"><i class="bi bi-grid"></i>Dashboard</a>
    @if($user->isAdmin())
        <a class="nav-link {{ request()->routeIs('admin.housings.*') ? 'active' : '' }}" href="{{ route('admin.housings.index') }}"><i class="bi bi-buildings"></i>Perumahan</a>
        <a class="nav-link {{ request()->routeIs('admin.managers.*') ? 'active' : '' }}" href="{{ route('admin.managers.index') }}"><i class="bi bi-person-badge"></i>Pengurus</a>
    @elseif($user->isManager())
        <a class="nav-link {{ request()->routeIs('pengurus.residents.*') ? 'active' : '' }}" href="{{ route('pengurus.residents.index') }}"><i class="bi bi-people"></i>Warga</a>
        <a class="nav-link {{ request()->routeIs('pengurus.invoices.*') && !request()->routeIs('pengurus.invoices.verify', 'pengurus.invoices.reject') ? 'active' : '' }}" href="{{ route('pengurus.invoices.index') }}"><i class="bi bi-receipt"></i>Tagihan</a>
        <a class="nav-link {{ request()->routeIs('pengurus.verifications.*') ? 'active' : '' }}" href="{{ route('pengurus.verifications.index') }}"><i class="bi bi-check2-circle"></i>Verifikasi</a>
        <a class="nav-link {{ request()->routeIs('pengurus.cashflows.*') ? 'active' : '' }}" href="{{ route('pengurus.cashflows.index') }}"><i class="bi bi-wallet2"></i>Arus Kas</a>
    @else
        <a class="nav-link {{ request()->routeIs('warga.invoices.*') ? 'active' : '' }}" href="{{ route('warga.invoices.index') }}"><i class="bi bi-receipt"></i>Tagihan Saya</a>
    @endif
    <a class="nav-link {{ request()->routeIs('profile.show') ? 'active' : '' }}" href="{{ route('profile.show') }}"><i class="bi bi-person"></i>Profil</a>
</nav>
@unless($offcanvas)
<div class="sidebar-user">
    <div class="small fw-semibold text-truncate">{{ $user->name }}</div>
    <div class="small text-white-50 text-truncate">{{ $user->housing?->name ?? 'Administrator' }}</div>
</div>
@endunless
