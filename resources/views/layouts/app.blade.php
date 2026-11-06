<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
    <meta name="theme-color" content="#16784a">
    <link rel="apple-touch-icon" href="{{ url('/icons/app-192.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EnviroPay')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/js/app.js')
    @stack('styles')
</head>
<body class="role-{{ auth()->user()->role }}">
@php
    $user = auth()->user();
    $dashboardRoute = match($user->role) {
        'admin' => 'admin.dashboard',
        'pengurus' => 'pengurus.dashboard',
        default => 'warga.dashboard',
    };
@endphp
<div class="app-shell">
    <aside class="app-sidebar sidebar-desktop">
        @include('partials.sidebar', ['offcanvas' => false])
    </aside>

    <div class="offcanvas offcanvas-start app-sidebar" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header border-bottom border-white border-opacity-10">
            <a class="app-brand p-0" href="{{ route($dashboardRoute) }}"><span class="brand-mark"><i class="bi bi-leaf"></i></span> EnviroPay</a>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Tutup navigasi"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column">
            @include('partials.sidebar', ['offcanvas' => true])
        </div>
    </div>

    <main class="app-main">
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="Buka navigasi" aria-controls="mobileSidebar"><i class="bi bi-list fs-5"></i></button>
                <div>
                    <div class="topbar-breadcrumb"><span class="d-none d-sm-inline">EnviroPay <i class="bi bi-chevron-right" aria-hidden="true"></i></span><strong>@yield('page-title', 'Dashboard')</strong></div>
                    @if($user->housing)<small class="text-secondary">{{ $user->housing->name }}</small>@endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
            <span class="topbar-date d-none d-xl-inline"><i class="bi bi-calendar3 me-2" aria-hidden="true"></i>{{ today('Asia/Jakarta')->translatedFormat('d F Y') }}</span>
            @if(($user->isManager() || $user->isResident()) && $user->is_active && $user->housing?->is_active)
                @include('partials.notifications')
            @endif
            <div class="dropdown">
                <button class="btn profile-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-label="Menu akun" aria-expanded="false">
                    <span class="avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <span class="d-none d-sm-inline text-start"><span class="d-block small fw-semibold">{{ $user->name }}</span><span class="d-block text-secondary" style="font-size:.72rem">{{ ucfirst($user->role) }}</span></span>
                    <i class="bi bi-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person me-2"></i>Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><form action="{{ route('logout') }}" method="POST">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button></form></li>
                </ul>
            </div>
            </div>
        </header>

        <div class="app-content">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 page-heading">
                <div class="page-heading-copy"><div class="page-eyebrow">{{ $user->isAdmin() ? 'Administrasi' : ($user->isManager() ? 'Ruang Pengurus' : 'Ruang Warga') }}</div><h1 class="page-title">@yield('page-title', 'Dashboard')</h1><p class="page-subtitle">@yield('page-subtitle')</p></div>
                <div class="page-actions">@yield('page-actions')</div>
            </div>
            <x-flash />
            @yield('content')
        </div>
    </main>
</div>
@stack('scripts')
</body>
</html>
