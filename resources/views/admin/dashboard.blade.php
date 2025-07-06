@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Admin')
@section('page-subtitle', 'Selamat datang, ' . $user->name . '!')
@section('dashboard-url', url('/admin/dashboard'))

@section('nav-items')
    <li class="nav-item">
        <a class="nav-link active" href="{{ url('/admin/dashboard') }}">
            <i class="fas fa-tachometer-alt me-1"></i>
            Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/admin/register') }}">
            <i class="fas fa-user-plus me-1"></i>
            Tambah Pengurus
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/admin/profile') }}">
            <i class="fas fa-user me-1"></i>
            Profil
        </a>
    </li>
@endsection

@section('content')
    <!-- Admin Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient" style="background: linear-gradient(135deg, #e91e63, #9c27b0);">
                <div class="card-body text-white">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-crown me-2"></i>
                                Selamat Datang, Administrator!
                            </h2>
                            <p class="lead mb-0">
                                Kelola sistem EnviroPay dengan akses penuh sebagai admin utama
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-shield-alt" style="font-size: 4rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Stats -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #e91e63, #ad1457);">
                <div class="stats-number">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="stats-label">
                    <i class="fas fa-shield-alt me-1"></i>
                    Admin Aktif
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #9c27b0, #7b1fa2);">
                <div class="stats-number">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stats-label">
                    <i class="fas fa-clipboard-list me-1"></i>
                    Total Pengurus
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #3f51b5, #303f9f);">
                <div class="stats-number">
                    <i class="fas fa-home"></i>
                </div>
                <div class="stats-label">
                    <i class="fas fa-building me-1"></i>
                    Total Warga
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #00bcd4, #0097a7);">
                <div class="stats-number">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="stats-label">
                    <i class="fas fa-tools me-1"></i>
                    Sistem Aktif
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Functions -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        Manajemen Pengurus
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Tambah pengurus baru dan kelola akses mereka ke sistem EnviroPay
                    </p>
                    
                    <div class="d-grid gap-2">
                        <a href="{{ url('/admin/register') }}" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>
                            Tambah Pengurus Baru
                        </a>
                        <button class="btn btn-outline-primary" disabled>
                            <i class="fas fa-list me-2"></i>
                            Lihat Daftar Pengurus
                            <small class="ms-2 text-muted">(Segera Hadir)</small>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Laporan Sistem
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Monitor aktivitas sistem dan generate laporan komprehensif
                    </p>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" disabled>
                            <i class="fas fa-chart-line me-2"></i>
                            Laporan Keuangan Global
                            <small class="ms-2 text-muted">(Segera Hadir)</small>
                        </button>
                        <button class="btn btn-outline-success" disabled>
                            <i class="fas fa-users me-2"></i>
                            Laporan Aktivitas Pengguna
                            <small class="ms-2 text-muted">(Segera Hadir)</small>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informasi Sistem
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><i class="fas fa-calendar me-2 text-info"></i> Versi:</td>
                            <td><strong>EnviroPay v1.0</strong></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-server me-2 text-info"></i> Status:</td>
                            <td><span class="badge bg-success">Online</span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-clock me-2 text-info"></i> Uptime:</td>
                            <td><strong>24/7</strong></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-shield-alt me-2 text-info"></i> Keamanan:</td>
                            <td><span class="badge bg-success">Aktif</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Status Backup
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <i class="fas fa-database text-warning" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <h6>Backup Otomatis</h6>
                        <p class="text-muted small">Last backup: Hari ini</p>
                        <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>
                            Aktif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-danger btn-sm" disabled>
                            <i class="fas fa-sync-alt me-1"></i>
                            Restart System
                        </button>
                        <button class="btn btn-outline-warning btn-sm" disabled>
                            <i class="fas fa-download me-1"></i>
                            Export Data
                        </button>
                        <button class="btn btn-outline-info btn-sm" disabled>
                            <i class="fas fa-cog me-1"></i>
                            Settings
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Fitur akan segera tersedia
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Aktivitas Terbaru
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-history text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h6 class="text-muted">Log Aktivitas</h6>
                        <p class="text-muted">Fitur monitoring aktivitas akan segera tersedia</p>
                        <button class="btn btn-outline-secondary" disabled>
                            <i class="fas fa-eye me-1"></i>
                            Lihat Log Detail
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
