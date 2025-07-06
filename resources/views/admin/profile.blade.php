@extends('layouts.app')

@section('title', 'Profil Administrator')
@section('page-title', 'Profil Administrator')
@section('page-subtitle', 'Informasi akun dan data administrator sistem')
@section('dashboard-url', url('/admin/dashboard'))

@section('nav-items')
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/admin/dashboard') }}">
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
        <a class="nav-link active" href="{{ url('/admin/profile') }}">
            <i class="fas fa-user me-1"></i>
            Profil
        </a>
    </li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Admin Profile Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-crown me-2"></i>
                        Profil Administrator
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Profile Header -->
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <div class="profile-avatar mb-3">
                                <i class="fas fa-user-shield text-danger" style="font-size: 8rem;"></i>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h3 class="text-danger mb-2">{{ $user->name }}</h3>
                            <p class="text-muted mb-1">
                                <i class="fas fa-envelope me-2"></i>
                                {{ $user->email }}
                            </p>
                            <p class="text-muted mb-1">
                                <i class="fas fa-crown me-2"></i>
                                Role: <span class="badge bg-danger">{{ ucfirst($user->role) }}</span>
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-shield-alt me-2"></i>
                                Akses Penuh Sistem EnviroPay
                            </p>
                        </div>
                    </div>

                    <!-- Admin Privileges -->
                    <div class="alert alert-danger d-flex align-items-center mb-4">
                        <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                        <div>
                            <strong>Administrator Privileges:</strong> 
                            Anda memiliki akses penuh ke semua fitur sistem termasuk mengelola pengurus, 
                            monitoring keseluruhan sistem, dan konfigurasi tingkat lanjut.
                        </div>
                    </div>

                    <!-- Profile Details -->
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Detail Informasi
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <tbody>
                                @foreach($user->getAttributes() as $key => $value)
                                    @if(!in_array($key, ['id', 'password', 'created_at', 'updated_at']) && !is_null($value))
                                        <tr>
                                            <td class="fw-bold" style="width: 200px;">
                                                @switch($key)
                                                    @case('name')
                                                        <i class="fas fa-user me-2 text-danger"></i>
                                                        Nama Lengkap
                                                        @break
                                                    @case('username')
                                                        <i class="fas fa-at me-2 text-danger"></i>
                                                        Username
                                                        @break
                                                    @case('email')
                                                        <i class="fas fa-envelope me-2 text-danger"></i>
                                                        Email
                                                        @break
                                                    @case('role')
                                                        <i class="fas fa-crown me-2 text-danger"></i>
                                                        Role
                                                        @break
                                                    @case('phone_number')
                                                        <i class="fas fa-phone me-2 text-danger"></i>
                                                        No. Telepon
                                                        @break
                                                    @case('housing_name')
                                                        <i class="fas fa-building me-2 text-danger"></i>
                                                        Sistem Perumahan
                                                        @break
                                                    @case('housing_address')
                                                        <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                                        Alamat Sistem
                                                        @break
                                                    @default
                                                        <i class="fas fa-info me-2 text-danger"></i>
                                                        {{ ucfirst(str_replace('_', ' ', $key)) }}
                                                @endswitch
                                            </td>
                                            <td>
                                                @if($key === 'role')
                                                    <span class="badge bg-danger fs-6">
                                                        <i class="fas fa-crown me-1"></i>
                                                        {{ ucfirst($value) }}
                                                    </span>
                                                @elseif($key === 'email')
                                                    <a href="mailto:{{ $value }}" class="text-decoration-none">{{ $value }}</a>
                                                @elseif($key === 'phone_number')
                                                    <a href="tel:{{ $value }}" class="text-decoration-none">{{ $value }}</a>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Admin Actions -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-cogs me-1"></i>
                            Aksi Administrator
                        </h6>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-outline-danger w-100" disabled>
                                    <i class="fas fa-edit me-1"></i>
                                    Edit Profil Admin
                                </button>
                                <small class="text-muted">Fitur akan segera tersedia</small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-outline-warning w-100" disabled>
                                    <i class="fas fa-key me-1"></i>
                                    Ubah Password
                                </button>
                                <small class="text-muted">Fitur akan segera tersedia</small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-outline-info w-100" disabled>
                                    <i class="fas fa-cog me-1"></i>
                                    Pengaturan Sistem
                                </button>
                                <small class="text-muted">Fitur akan segera tersedia</small>
                            </div>
                        </div>
                    </div>

                    <!-- Back to Dashboard -->
                    <div class="text-center mt-4">
                        <a href="{{ url('/admin/dashboard') }}" class="btn btn-danger">
                            <i class="fas fa-arrow-left me-1"></i>
                            Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Admin Privileges Card -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Hak Akses Administrator
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">
                                <i class="fas fa-check-circle me-1"></i>
                                Manajemen Pengguna
                            </h6>
                            <ul class="text-muted small">
                                <li>Menambah pengurus baru</li>
                                <li>Mengelola akses pengurus</li>
                                <li>Monitor aktivitas pengguna</li>
                                <li>Reset password pengguna</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">
                                <i class="fas fa-check-circle me-1"></i>
                                Kontrol Sistem
                            </h6>
                            <ul class="text-muted small">
                                <li>Konfigurasi sistem global</li>
                                <li>Backup dan restore data</li>
                                <li>Monitor performa sistem</li>
                                <li>Akses log aktivitas</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lock me-2"></i>
                        Keamanan Akun
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <h5 class="text-success mb-1">
                                    <i class="fas fa-shield-check"></i>
                                </h5>
                                <small class="text-muted">Status Keamanan</small>
                                <p class="mb-0 fw-bold text-success">Aman</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <h5 class="text-info mb-1">
                                    <i class="fas fa-calendar-check"></i>
                                </h5>
                                <small class="text-muted">Admin Sejak</small>
                                <p class="mb-0 fw-bold">{{ $user->created_at->format('M Y') }}</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <h5 class="text-warning mb-1">
                                <i class="fas fa-key"></i>
                            </h5>
                            <small class="text-muted">Akses Level</small>
                            <p class="mb-0 fw-bold text-danger">SUPER ADMIN</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection