<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EnviroPay')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/js/app.js')
    @stack('styles')
</head>
<body>
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
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0 position-relative">
            @include('partials.sidebar', ['offcanvas' => true])
        </div>
    </div>

    <main class="app-main">
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="Buka navigasi"><i class="bi bi-list fs-5"></i></button>
                <div>
                    <div class="fw-semibold">@yield('page-title', 'Dashboard')</div>
                    @if($user->housing)<small class="text-secondary">{{ $user->housing->name }}</small>@endif
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <span class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    <span class="d-none d-sm-inline text-start"><span class="d-block small fw-semibold">{{ $user->name }}</span><span class="d-block text-secondary" style="font-size:.72rem">{{ ucfirst($user->role) }}</span></span>
                    <i class="bi bi-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person me-2"></i>Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><form action="{{ route('logout') }}" method="POST">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button></form></li>
                </ul>
            </div>
        </header>

        <div class="app-content">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 page-heading">
                <div><h1 class="page-title">@yield('page-title', 'Dashboard')</h1><p class="page-subtitle">@yield('page-subtitle')</p></div>
                <div>@yield('page-actions')</div>
            </div>
            <x-flash />
            @yield('content')
        </div>
    </main>
</div>
@stack('scripts')
</body>
</html>
