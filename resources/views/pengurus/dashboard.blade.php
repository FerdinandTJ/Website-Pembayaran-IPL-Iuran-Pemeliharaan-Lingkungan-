@extends('layouts.app')

@section('title', 'Dashboard Pengurus')
@section('page-title', 'Dashboard Pengurus')
@section('page-subtitle', 'Selamat datang, ' . $user->name . '!')
@section('dashboard-url', url('/pengurus/dashboard'))

@section('nav-items')
    <li class="nav-item">
        <a class="nav-link active" href="{{ url('/pengurus/dashboard') }}">
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
        <a class="nav-link" href="{{ url('/pengurus/profile') }}">
            <i class="fas fa-user me-1"></i>
            Profil
        </a>
    </li>
@endsection

@section('content')
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #4CAF50, #2E7D32);">
                <div class="stats-number">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
                <div class="stats-label">
                    <i class="fas fa-arrow-up me-1"></i>
                    Total Pemasukan
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #f44336, #d32f2f);">
                <div class="stats-number">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
                <div class="stats-label">
                    <i class="fas fa-arrow-down me-1"></i>
                    Total Pengeluaran
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #2196F3, #1976D2);">
                <div class="stats-number">Rp {{ number_format($totalAmount, 0, ',', '.') }}</div>
                <div class="stats-label">
                    <i class="fas fa-calculator me-1"></i>
                    Saldo Bersih
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #FF9800, #F57C00);">
                <div class="stats-number">{{ $unpaidCount }}</div>
                <div class="stats-label">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Belum Dibayar
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Summary Card -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Ringkasan Keuangan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-circle text-success me-1"></i> Pemasukan</span>
                            <strong class="text-success">Rp {{ number_format($totalIncome, 0, ',', '.') }}</strong>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $totalIncome > 0 ? ($totalIncome / ($totalIncome + $totalExpense)) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-circle text-danger me-1"></i> Pengeluaran</span>
                            <strong class="text-danger">Rp {{ number_format($totalExpense, 0, ',', '.') }}</strong>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-danger" style="width: {{ ($totalIncome + $totalExpense) > 0 ? ($totalExpense / ($totalIncome + $totalExpense)) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><strong>Saldo Bersih</strong></span>
                        <strong class="{{ $totalAmount >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format($totalAmount, 0, ',', '.') }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices to Verify -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Pembayaran Menunggu Verifikasi
                    </h5>
                    <span class="badge bg-warning text-dark">{{ $oldestInvoices->count() }}</span>
                </div>
                <div class="card-body">
                    @if($oldestInvoices->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h6 class="text-success">Semua pembayaran sudah diverifikasi</h6>
                            <p class="text-muted">Tidak ada pembayaran yang menunggu verifikasi</p>
                        </div>
                    @else
                        <div class="scrollable-table">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID Tagihan</th>
                                        <th>Jumlah</th>
                                        <th>Tanggal Bayar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($oldestInvoices as $invoice)
                                    <tr>
                                        <td>
                                            <strong class="text-primary">#{{ $invoice->id }}</strong>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">
                                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $invoice->payment_date ? $invoice->payment_date->format('d/m/Y H:i') : '-' }}
                                            </small>
                                        </td>
                                        <td>
                                            <a href="{{ url('/pengurus/invoice/verification') }}" 
                                               class="btn btn-sm btn-warning">
                                                <i class="fas fa-check me-1"></i>
                                                Verifikasi
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ url('/pengurus/invoice/verification') }}" class="btn btn-warning">
                                <i class="fas fa-list me-1"></i>
                                Lihat Semua Verifikasi
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Cashflow History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Riwayat Cash Flow
                    </h5>
                    <a href="{{ url('/pengurus/cashflow') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Tambah Cash Flow
                    </a>
                </div>
                <div class="card-body">
                    @if($cashflowsHistory->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-chart-line text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h6 class="text-muted">Belum ada riwayat cash flow</h6>
                            <p class="text-muted">Mulai kelola keuangan dengan menambah cash flow</p>
                            <a href="{{ url('/pengurus/cashflow') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>
                                Tambah Cash Flow
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Cash Flow</th>
                                        <th>Tipe</th>
                                        <th>Jumlah</th>
                                        <th>Total Kalkulasi</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cashflowsHistory as $cashflow)
                                        <tr>
                                            <td>{{ $cashflow->name }}</td>
                                            <td>
                                                <span class="badge {{ $cashflow->type === 'expense' ? 'bg-danger' : 'bg-success' }}">
                                                    <i class="fas fa-{{ $cashflow->type === 'expense' ? 'arrow-down' : 'arrow-up' }} me-1"></i>
                                                    {{ ucfirst($cashflow->type) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-bold {{ $cashflow->type === 'expense' ? 'text-danger' : 'text-success' }}">
                                                    Rp {{ number_format($cashflow->total_cashflow, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-bold">
                                                    Rp {{ number_format($cashflow->calculated_total, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $cashflow->created_at->format('d/m/Y H:i') }}
                                                </small>
                                            </td>
                                            <td>
                                                <form action="{{ url('/pengurus/cashflow/delete/' . $cashflow->id) }}" method="POST" 
                                                      style="display: inline;" 
                                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus cash flow ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash me-1"></i>
                                                        Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
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
                            <a href="{{ url('/pengurus/register') }}" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                                <i class="fas fa-user-plus fa-2x mb-2"></i>
                                <span>Tambah Warga</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ url('/pengurus/invoice') }}" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                                <i class="fas fa-file-invoice fa-2x mb-2"></i>
                                <span>Buat Tagihan</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ url('/pengurus/invoice/verification') }}" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                                <i class="fas fa-check-double fa-2x mb-2"></i>
                                <span>Verifikasi Pembayaran</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ url('/pengurus/cashflow') }}" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                                <i class="fas fa-chart-line fa-2x mb-2"></i>
                                <span>Kelola Cash Flow</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
