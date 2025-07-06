@extends('layouts.app')

@section('title', 'Dashboard Warga')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang, ' . $user->name . '!')
@section('dashboard-url', url('/warga/dashboard'))

@section('nav-items')
    <li class="nav-item">
        <a class="nav-link active" href="{{ url('/warga/dashboard') }}">
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
        <a class="nav-link" href="{{ url('/warga/profile') }}">
            <i class="fas fa-user me-1"></i>
            Profil
        </a>
    </li>
@endsection

@section('content')
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #f44336, #d32f2f);">
                <div class="stats-number">{{ $unpaidInvoicesCount }}</div>
                <div class="stats-label">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Tagihan Belum Dibayar
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #4CAF50, #2E7D32);">
                <div class="stats-number">{{ $paidInvoicesCount }}</div>
                <div class="stats-label">
                    <i class="fas fa-check-circle me-1"></i>
                    Tagihan Terbayar
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #FF9800, #F57C00);">
                <div class="stats-number">{{ $totalInvoicesCount }}</div>
                <div class="stats-label">
                    <i class="fas fa-file-invoice me-1"></i>
                    Total Tagihan
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #2196F3, #1976D2);">
                <div class="stats-number">Rp {{ number_format($totalPaid, 0, ',', '.') }}</div>
                <div class="stats-label">
                    <i class="fas fa-wallet me-1"></i>
                    Total Dibayar
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Unpaid Invoices -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Tagihan Belum Dibayar
                    </h5>
                    <span class="badge bg-light text-dark">{{ $unpaidInvoicesCount }}</span>
                </div>
                <div class="card-body">
                    @if($unpaidInvoices->count() > 0)
                        <div class="scrollable-table">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jumlah</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unpaidInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <strong class="text-danger">
                                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                            </strong>
                                        </td>
                                        <td>
                                            <a href="{{ url('/warga/invoice/' . $invoice->id) }}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>
                                                Lihat
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ url('/warga/bills') }}" class="btn btn-outline-primary">
                                <i class="fas fa-list me-1"></i>
                                Lihat Semua Tagihan
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h5 class="text-success">Tidak ada tagihan yang belum dibayar</h5>
                            <p class="text-muted">Semua tagihan Anda sudah terbayar</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Pembayaran Terbaru
                    </h5>
                    <span class="badge bg-light text-dark">{{ $recentPaidInvoices->count() }}</span>
                </div>
                <div class="card-body">
                    @if($recentPaidInvoices->count() > 0)
                        <div class="scrollable-table">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Tanggal Bayar</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentPaidInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->payment_date ? $invoice->payment_date->format('d/m/Y') : '-' }}</td>
                                        <td>
                                            <strong class="text-success">
                                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                            </strong>
                                        </td>
                                        <td>
                                            @if($invoice->is_verified)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>
                                                    Terverifikasi
                                                </span>
                                            @else
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Menunggu Verifikasi
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ url('/warga/invoice/history') }}" class="btn btn-outline-primary">
                                <i class="fas fa-history me-1"></i>
                                Lihat Riwayat Lengkap
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-receipt text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h5 class="text-muted">Belum ada pembayaran</h5>
                            <p class="text-muted">Riwayat pembayaran akan muncul di sini</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Aksi Cepat
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ url('/warga/bills') }}" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                                <i class="fas fa-file-invoice fa-2x mb-2"></i>
                                <span>Lihat Tagihan</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ url('/warga/invoice/history') }}" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                                <i class="fas fa-history fa-2x mb-2"></i>
                                <span>Riwayat Pembayaran</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ url('/warga/profile') }}" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                                <i class="fas fa-user fa-2x mb-2"></i>
                                <span>Edit Profil</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                                <i class="fas fa-phone fa-2x mb-2"></i>
                                <span>Bantuan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
