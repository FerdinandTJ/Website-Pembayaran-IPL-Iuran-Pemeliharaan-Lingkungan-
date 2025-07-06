@extends('layouts.app')

@section('title', 'Profil Pengurus')
@section('page-title', 'Profil Pengurus')
@section('page-subtitle', 'Informasi akun dan data pengurus perumahan')
@section('dashboard-url', url('/pengurus/dashboard'))

@section('nav-items')
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/pengurus/dashboard') }}">
            <i class="fas fa-tachometer-alt me-1"></i>
            Dashboard
        </a>
    </li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-users me-1"></i>
            Kelola Warga
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ url('/pengurus/register') }}">
                <i class="fas fa-user-plus me-1"></i>
                Tambah Warga
            </a></li>
            <li><a class="dropdown-item" href="{{ url('/pengurus/members') }}">
                <i class="fas fa-list me-1"></i>
                Daftar Warga
            </a></li>
        </ul>
    </li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-file-invoice me-1"></i>
            Tagihan
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ url('/pengurus/invoice') }}">
                <i class="fas fa-plus me-1"></i>
                Buat Tagihan
            </a></li>
            <li><a class="dropdown-item" href="{{ url('/pengurus/invoice/verification') }}">
                <i class="fas fa-check-double me-1"></i>
                Verifikasi Pembayaran
            </a></li>
        </ul>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/pengurus/cashflow') }}">
            <i class="fas fa-chart-line me-1"></i>
            Cash Flow
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ url('/pengurus/profile') }}">
            <i class="fas fa-user me-1"></i>
            Profil
        </a>
    </li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Pengurus Profile Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-user-tie me-2"></i>
                        Profil Pengurus
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Profile Header -->
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <div class="profile-avatar mb-3">
                                <i class="fas fa-user-tie text-warning" style="font-size: 8rem;"></i>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h3 class="text-warning mb-2">{{ $user->name }}</h3>
                            <p class="text-muted mb-1">
                                <i class="fas fa-envelope me-2"></i>
                                {{ $user->email }}
                            </p>
                            <p class="text-muted mb-1">
                                <i class="fas fa-user-tag me-2"></i>
                                Role: <span class="badge bg-warning text-dark">{{ ucfirst($user->role) }}</span>
                            </p>
                            @if($user->housing_name)
                                <p class="text-muted mb-0">
                                    <i class="fas fa-building me-2"></i>
                                    Pengurus {{ $user->housing_name }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Pengurus Responsibilities -->
                    <div class="alert alert-warning d-flex align-items-center mb-4">
                        <i class="fas fa-clipboard-list me-3 fs-4"></i>
                        <div>
                            <strong>Tanggung Jawab Pengurus:</strong> 
                            Mengelola warga, membuat tagihan bulanan, memverifikasi pembayaran, 
                            dan mengelola cash flow keuangan perumahan.
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
                                                        <i class="fas fa-user me-2 text-warning"></i>
                                                        Nama Lengkap
                                                        @break
                                                    @case('username')
                                                        <i class="fas fa-at me-2 text-warning"></i>
                                                        Username
                                                        @break
                                                    @case('email')
                                                        <i class="fas fa-envelope me-2 text-warning"></i>
                                                        Email
                                                        @break
                                                    @case('role')
                                                        <i class="fas fa-user-tag me-2 text-warning"></i>
                                                        Role
                                                        @break
                                                    @case('phone_number')
                                                        <i class="fas fa-phone me-2 text-warning"></i>
                                                        No. Telepon
                                                        @break
                                                    @case('housing_name')
                                                        <i class="fas fa-building me-2 text-warning"></i>
                                                        Nama Perumahan
                                                        @break
                                                    @case('housing_address')
                                                        <i class="fas fa-map-marker-alt me-2 text-warning"></i>
                                                        Alamat Perumahan
                                                        @break
                                                    @default
                                                        <i class="fas fa-info me-2 text-warning"></i>
                                                        {{ ucfirst(str_replace('_', ' ', $key)) }}
                                                @endswitch
                                            </td>
                                            <td>
                                                @if($key === 'role')
                                                    <span class="badge bg-warning text-dark fs-6">
                                                        <i class="fas fa-user-tie me-1"></i>
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

                    <!-- Pengurus Actions -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-cogs me-1"></i>
                            Aksi Pengurus
                        </h6>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-outline-warning w-100" disabled>
                                    <i class="fas fa-edit me-1"></i>
                                    Edit Profil
                                </button>
                                <small class="text-muted">Fitur akan segera tersedia</small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-outline-danger w-100" disabled>
                                    <i class="fas fa-key me-1"></i>
                                    Ubah Password
                                </button>
                                <small class="text-muted">Fitur akan segera tersedia</small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="{{ url('/pengurus/members') }}" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-users me-1"></i>
                                    Kelola Warga
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Back to Dashboard -->
                    <div class="text-center mt-4">
                        <a href="{{ url('/pengurus/dashboard') }}" class="btn btn-warning">
                            <i class="fas fa-arrow-left me-1"></i>
                            Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pengurus Responsibilities Card -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>
                        Tugas dan Tanggung Jawab
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-success">
                                <i class="fas fa-check-circle me-1"></i>
                                Manajemen Warga
                            </h6>
                            <ul class="text-muted small">
                                <li>Mendaftarkan warga baru</li>
                                <li>Mengelola data warga</li>
                                <li>Update informasi warga</li>
                                <li>Hapus warga tidak aktif</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-info">
                                <i class="fas fa-check-circle me-1"></i>
                                Manajemen Keuangan
                            </h6>
                            <ul class="text-muted small">
                                <li>Membuat tagihan bulanan</li>
                                <li>Verifikasi pembayaran</li>
                                <li>Mengelola cash flow</li>
                                <li>Laporan keuangan</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Aksi Cepat
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 col-md-3 mb-2">
                            <a href="{{ url('/pengurus/register') }}" class="btn btn-outline-success btn-sm w-100">
                                <i class="fas fa-user-plus d-block mb-1"></i>
                                <small>Tambah Warga</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <a href="{{ url('/pengurus/invoice') }}" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-file-invoice d-block mb-1"></i>
                                <small>Buat Tagihan</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <a href="{{ url('/pengurus/invoice/verification') }}" class="btn btn-outline-warning btn-sm w-100">
                                <i class="fas fa-check-double d-block mb-1"></i>
                                <small>Verifikasi</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <a href="{{ url('/pengurus/cashflow') }}" class="btn btn-outline-info btn-sm w-100">
                                <i class="fas fa-chart-line d-block mb-1"></i>
                                <small>Cash Flow</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Summary -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Ringkasan Akun
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <h5 class="text-primary mb-1">
                                    <i class="fas fa-calendar-check"></i>
                                </h5>
                                <small class="text-muted">Bergabung Sejak</small>
                                <p class="mb-0 fw-bold">{{ $user->created_at->format('M Y') }}</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <h5 class="text-success mb-1">
                                    <i class="fas fa-user-check"></i>
                                </h5>
                                <small class="text-muted">Status Akun</small>
                                <p class="mb-0 fw-bold text-success">Aktif</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <h5 class="text-warning mb-1">
                                <i class="fas fa-shield-alt"></i>
                            </h5>
                            <small class="text-muted">Level Akses</small>
                            <p class="mb-0 fw-bold text-warning">PENGURUS</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection