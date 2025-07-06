@extends('layouts.app')

@section('title', 'Profil Pengguna')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Informasi akun dan data pribadi')
@section('dashboard-url', url('/warga/dashboard'))

@section('nav-items')
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/warga/dashboard') }}">
            <i class="fas fa-tachometer-alt me-1"></i>
            Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/warga/bills') }}">
            <i class="fas fa-file-invoice me-1"></i>
            Tagihan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/warga/invoice/history') }}">
            <i class="fas fa-history me-1"></i>
            Riwayat
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ url('/warga/profile') }}">
            <i class="fas fa-user me-1"></i>
            Profil
        </a>
    </li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        Informasi Profil
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Profile Header -->
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <div class="profile-avatar mb-3">
                                <i class="fas fa-user-circle text-muted" style="font-size: 8rem;"></i>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h4 class="text-primary mb-2">{{ $user->name }}</h4>
                            <p class="text-muted mb-1">
                                <i class="fas fa-envelope me-2"></i>
                                {{ $user->email }}
                            </p>
                            <p class="text-muted mb-1">
                                <i class="fas fa-user-tag me-2"></i>
                                Role: <span class="badge bg-info">{{ ucfirst($user->role) }}</span>
                            </p>
                            @if($user->housing_name)
                                <p class="text-muted mb-0">
                                    <i class="fas fa-home me-2"></i>
                                    {{ $user->housing_name }}
                                </p>
                            @endif
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
                                                        <i class="fas fa-user me-2 text-primary"></i>
                                                        Nama Lengkap
                                                        @break
                                                    @case('email')
                                                        <i class="fas fa-envelope me-2 text-primary"></i>
                                                        Email
                                                        @break
                                                    @case('role')
                                                        <i class="fas fa-user-tag me-2 text-primary"></i>
                                                        Role
                                                        @break
                                                    @case('account_number')
                                                        <i class="fas fa-credit-card me-2 text-primary"></i>
                                                        No. Rekening
                                                        @break
                                                    @case('housing_name')
                                                        <i class="fas fa-home me-2 text-primary"></i>
                                                        Nama Perumahan
                                                        @break
                                                    @case('phone')
                                                        <i class="fas fa-phone me-2 text-primary"></i>
                                                        No. Telepon
                                                        @break
                                                    @case('address')
                                                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                                        Alamat
                                                        @break
                                                    @default
                                                        <i class="fas fa-info me-2 text-primary"></i>
                                                        {{ ucfirst(str_replace('_', ' ', $key)) }}
                                                @endswitch
                                            </td>
                                            <td>
                                                @if($key === 'role')
                                                    <span class="badge bg-{{ $value === 'admin' ? 'danger' : ($value === 'pengurus' ? 'warning' : 'info') }}">
                                                        {{ ucfirst($value) }}
                                                    </span>
                                                @elseif($key === 'email')
                                                    <a href="mailto:{{ $value }}" class="text-decoration-none">{{ $value }}</a>
                                                @elseif($key === 'phone')
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

                    <!-- Account Actions -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-cogs me-1"></i>
                            Aksi Akun
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <button class="btn btn-outline-primary w-100" disabled>
                                    <i class="fas fa-edit me-1"></i>
                                    Edit Profil
                                </button>
                                <small class="text-muted">Fitur akan segera tersedia</small>
                            </div>
                            <div class="col-md-6 mb-2">
                                <button class="btn btn-outline-warning w-100" disabled>
                                    <i class="fas fa-key me-1"></i>
                                    Ubah Password
                                </button>
                                <small class="text-muted">Fitur akan segera tersedia</small>
                            </div>
                        </div>
                    </div>

                    <!-- Back to Dashboard -->
                    <div class="text-center mt-4">
                        <a href="{{ url('/warga/dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Account Summary Card -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
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
                                <p class="mb-0 fw-bold">Aktif</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <h5 class="text-info mb-1">
                                <i class="fas fa-shield-alt"></i>
                            </h5>
                            <small class="text-muted">Keamanan</small>
                            <p class="mb-0 fw-bold">Terjamin</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection