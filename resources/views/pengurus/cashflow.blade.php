@extends('layouts.app')

@section('title', 'Manajemen Cash Flow')
@section('page-title', 'Manajemen Cash Flow')
@section('page-subtitle', 'Kelola pemasukan dan pengeluaran keuangan')
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
        <a class="nav-link active" href="{{ url('/pengurus/cashflow') }}">
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
    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Aksi Cepat
                        </h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCashflowModal">
                                <i class="fas fa-plus me-2"></i>Tambah Cash Flow
                            </button>
                            <a href="{{ url('/pengurus/invoice') }}" class="btn btn-primary">
                                <i class="fas fa-file-invoice me-2"></i>Buat Tagihan
                            </a>
                            <a href="{{ url('/pengurus/invoice/verification') }}" class="btn btn-warning">
                                <i class="fas fa-check-double me-2"></i>Verifikasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    @php
        $totalIncome = $cashflows->where('type', 'income')->sum('total_cashflow');
        $totalExpense = $cashflows->where('type', 'expense')->sum('total_cashflow');
        $netCashflow = $totalIncome - $totalExpense;
    @endphp

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-gradient-success text-white shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-1">
                                Total Pemasukan
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-white">
                                Rp {{ number_format($totalIncome, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-gradient-danger text-white shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-1">
                                Total Pengeluaran
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-white">
                                Rp {{ number_format($totalExpense, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-gradient-{{ $netCashflow >= 0 ? 'info' : 'warning' }} text-white shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-1">
                                Saldo Bersih
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-white">
                                Rp {{ number_format($netCashflow, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-gradient-primary text-white shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-1">
                                Total Transaksi
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-white">{{ $cashflows->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($cashflows->isEmpty())
        <!-- Empty State -->
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <div class="empty-state">
                    <i class="fas fa-chart-line fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">Belum ada data cash flow</h4>
                    <p class="text-muted mb-4">Mulai kelola keuangan dengan menambahkan catatan pemasukan atau pengeluaran</p>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCashflowModal">
                        <i class="fas fa-plus me-2"></i>Tambah Cash Flow Pertama
                    </button>
                </div>
            </div>
        </div>
    @else
        <!-- Cashflow Table -->
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-table me-2"></i>Riwayat Cash Flow
                </h6>
                <span class="badge bg-info">{{ $cashflows->count() }} Transaksi</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 8%;" class="text-center">#</th>
                                <th><i class="fas fa-tag me-2"></i>Nama Cash Flow</th>
                                <th class="text-center" style="width: 12%;"><i class="fas fa-exchange-alt me-2"></i>Tipe</th>
                                <th class="text-end" style="width: 18%;"><i class="fas fa-money-bill-wave me-2"></i>Nominal</th>
                                <th class="text-end" style="width: 18%;"><i class="fas fa-calculator me-2"></i>Total Terhitung</th>
                                <th class="text-center" style="width: 15%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cashflows as $index => $cashflow)
                                <tr>
                                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                @if($cashflow->type == 'income')
                                                    <i class="fas fa-arrow-up text-success fa-lg"></i>
                                                @else
                                                    <i class="fas fa-arrow-down text-danger fa-lg"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $cashflow->name }}</div>
                                                <small class="text-muted">{{ ucfirst($cashflow->type) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($cashflow->type == 'income')
                                            <span class="badge bg-success">
                                                <i class="fas fa-plus me-1"></i>Pemasukan
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-minus me-1"></i>Pengeluaran
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold {{ $cashflow->type == 'income' ? 'text-success' : 'text-danger' }}">
                                            {{ $cashflow->type == 'income' ? '+' : '-' }}Rp {{ number_format($cashflow->total_cashflow, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-info">
                                            Rp {{ number_format($cashflow->calculated_total, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editCashflowModal-{{ $cashflow->id }}"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="confirmDelete({{ $cashflow->id }}, '{{ $cashflow->name }}')"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Hidden delete form -->
                                        <form id="deleteForm{{ $cashflow->id }}" 
                                              action="{{ url('/pengurus/cashflow/delete', $cashflow->id) }}" 
                                              method="POST" 
                                              style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Cashflow Modal -->
                                <div class="modal fade" id="editCashflowModal-{{ $cashflow->id }}" 
                                     tabindex="-1" 
                                     aria-labelledby="editCashflowLabel-{{ $cashflow->id }}" 
                                     aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ url('/pengurus/cashflow/update', $cashflow->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header bg-warning text-white">
                                                    <h5 class="modal-title" id="editCashflowLabel-{{ $cashflow->id }}">
                                                        <i class="fas fa-edit me-2"></i>Edit Cash Flow
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" 
                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="name-{{ $cashflow->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-tag me-2 text-primary"></i>Nama Cash Flow
                                                        </label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               id="name-{{ $cashflow->id }}" 
                                                               name="name" 
                                                               value="{{ $cashflow->name }}" 
                                                               required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="type-{{ $cashflow->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-exchange-alt me-2 text-info"></i>Tipe
                                                        </label>
                                                        <select class="form-select" 
                                                                id="type-{{ $cashflow->id }}" 
                                                                name="type" 
                                                                required>
                                                            <option value="income" {{ $cashflow->type == 'income' ? 'selected' : '' }}>
                                                                Pemasukan
                                                            </option>
                                                            <option value="expense" {{ $cashflow->type == 'expense' ? 'selected' : '' }}>
                                                                Pengeluaran
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="total_cashflow-{{ $cashflow->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-money-bill-wave me-2 text-success"></i>Nominal
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">Rp</span>
                                                            <input type="number" 
                                                                   class="form-control" 
                                                                   id="total_cashflow-{{ $cashflow->id }}" 
                                                                   name="total_cashflow" 
                                                                   value="{{ $cashflow->total_cashflow }}" 
                                                                   min="1000"
                                                                   step="1000"
                                                                   required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-2"></i>Batal
                                                    </button>
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Add Cashflow Modal -->
<div class="modal fade" id="addCashflowModal" 
     tabindex="-1" 
     aria-labelledby="addCashflowLabel" 
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ url('/pengurus/cashflow/add') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addCashflowLabel">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Cash Flow
                    </h5>
                    <button type="button" class="btn-close btn-close-white" 
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">
                            <i class="fas fa-tag me-2 text-primary"></i>Nama Cash Flow
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="name" 
                               name="name" 
                               placeholder="Contoh: Pembayaran Listrik, Iuran Warga"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label fw-bold">
                            <i class="fas fa-exchange-alt me-2 text-info"></i>Tipe Transaksi
                        </label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Pilih tipe transaksi</option>
                            <option value="income">
                                <i class="fas fa-arrow-up"></i> Pemasukan
                            </option>
                            <option value="expense">
                                <i class="fas fa-arrow-down"></i> Pengeluaran
                            </option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="total_cashflow" class="form-label fw-bold">
                            <i class="fas fa-money-bill-wave me-2 text-success"></i>Nominal
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="total_cashflow" 
                                   name="total_cashflow" 
                                   placeholder="0"
                                   min="1000"
                                   step="1000"
                                   required>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Minimal Rp 1.000, kelipatan Rp 1.000
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Tambah Cash Flow
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.empty-state {
    padding: 3rem 2rem;
}

.table-responsive {
    border-radius: 0.5rem;
    overflow: hidden;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.fa-lg {
    font-size: 1.2em;
}
</style>

<script>
function confirmDelete(cashflowId, cashflowName) {
    if (confirm(`Apakah Anda yakin ingin menghapus cash flow "${cashflowName}"? Tindakan ini tidak dapat dibatalkan.`)) {
        document.getElementById('deleteForm' + cashflowId).submit();
    }
}
</script>
@endsection
