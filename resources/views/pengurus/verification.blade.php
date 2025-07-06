@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran')
@section('page-title', 'Verifikasi Pembayaran')
@section('page-subtitle', 'Kelola verifikasi pembayaran tagihan warga')
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
        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-file-invoice me-1"></i>
            Tagihan
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ url('/pengurus/invoice') }}">
                <i class="fas fa-plus me-1"></i>
                Buat Tagihan
            </a></li>
            <li><a class="dropdown-item active" href="{{ url('/pengurus/invoice/verification') }}">
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

    @if($invoices->isEmpty())
        <!-- Empty State -->
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <div class="empty-state">
                    <i class="fas fa-clipboard-check fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">Tidak ada tagihan menunggu verifikasi</h4>
                    <p class="text-muted mb-4">Semua pembayaran sudah diverifikasi atau belum ada pembayaran masuk</p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ url('/pengurus/invoice') }}" class="btn btn-success">
                            <i class="fas fa-plus-circle me-2"></i>Buat Tagihan Baru
                        </a>
                        <a href="{{ url('/pengurus/dashboard') }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-gradient-warning text-white shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-1">
                                    Menunggu Verifikasi
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-white">{{ $invoices->count() }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-gradient-info text-white shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-1">
                                    Total Nominal
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-white">
                                    Rp {{ number_format($invoices->sum('total_amount'), 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-money-bill-wave fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-gradient-success text-white shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-1">
                                    Ada Bukti Bayar
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-white">
                                    {{ $invoices->whereNotNull('payment_proof')->count() }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-image fa-2x text-white-50"></i>
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
                                    Tanpa Bukti
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-white">
                                    {{ $invoices->whereNull('payment_proof')->count() }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-warning">
                    <i class="fas fa-list me-2"></i>Daftar Tagihan Menunggu Verifikasi
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 8%;">ID</th>
                                <th><i class="fas fa-credit-card me-2"></i>No. Akun</th>
                                <th><i class="fas fa-money-bill-wave me-2"></i>Total</th>
                                <th class="text-center"><i class="fas fa-image me-2"></i>Bukti Bayar</th>
                                <th class="text-center" style="width: 20%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">#{{ $invoice->id }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-university text-primary me-2"></i>
                                            <span class="fw-bold">{{ $invoice->account_number }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">
                                            Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($invoice->payment_proof && file_exists(storage_path('app/public/' . $invoice->payment_proof)))
                                            <button type="button" 
                                                    class="btn btn-info btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#proofModal-{{ $invoice->id }}"
                                                    title="Lihat bukti pembayaran">
                                                <i class="fas fa-image me-1"></i>Lihat Bukti
                                            </button>
                                        @elseif($invoice->payment_proof)
                                            <span class="badge bg-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>File Hilang
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>Tidak Ada
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" 
                                                    class="btn btn-success" 
                                                    onclick="confirmVerification({{ $invoice->id }}, 'verify')"
                                                    title="Verifikasi pembayaran"
                                                    {{ !$invoice->payment_proof ? 'disabled' : '' }}>
                                                <i class="fas fa-check"></i> Verifikasi
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-danger" 
                                                    onclick="confirmVerification({{ $invoice->id }}, 'unverify')"
                                                    title="Tolak pembayaran">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </div>
                                        
                                        <!-- Hidden forms -->
                                        <form id="verifyForm{{ $invoice->id }}" 
                                              action="{{ url('/pengurus/invoice/verify', $invoice->id) }}" 
                                              method="POST" 
                                              style="display: none;">
                                            @csrf
                                        </form>
                                        <form id="unverifyForm{{ $invoice->id }}" 
                                              action="{{ url('/pengurus/invoice/unverify', $invoice->id) }}" 
                                              method="POST" 
                                              style="display: none;">
                                            @csrf
                                        </form>
                                    </td>
                                </tr>

                                @if($invoice->payment_proof && file_exists(storage_path('app/public/' . $invoice->payment_proof)))
                                    <!-- Modal for viewing proof -->
                                    <div class="modal fade" id="proofModal-{{ $invoice->id }}" 
                                         tabindex="-1" 
                                         aria-labelledby="proofModalLabel-{{ $invoice->id }}" 
                                         aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header bg-info text-white">
                                                    <h5 class="modal-title" id="proofModalLabel-{{ $invoice->id }}">
                                                        <i class="fas fa-image me-2"></i>Bukti Pembayaran - Tagihan #{{ $invoice->id }}
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" 
                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center p-4">
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between text-start">
                                                            <div>
                                                                <strong>No. Akun:</strong> {{ $invoice->account_number }}
                                                            </div>
                                                            <div>
                                                                <strong>Total:</strong> Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                                            </div>
                                                        </div>
                                                        <div class="mt-2">
                                                            <small class="text-muted">
                                                                File: {{ $invoice->payment_proof }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="border rounded p-2 bg-light">
                                                        @if($invoice->payment_proof && file_exists(storage_path('app/public/' . $invoice->payment_proof)))
                                                            <img src="{{ asset('storage/' . $invoice->payment_proof) }}" 
                                                                 alt="Bukti Pembayaran" 
                                                                 class="img-fluid rounded shadow"
                                                                 style="max-height: 400px;"
                                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                            <div class="alert alert-warning d-none">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                Gambar tidak dapat dimuat
                                                            </div>
                                                        @else
                                                            <div class="alert alert-danger">
                                                                <i class="fas fa-times-circle me-2"></i>
                                                                File bukti pembayaran tidak ditemukan
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-2"></i>Tutup
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-success" 
                                                            onclick="confirmVerification({{ $invoice->id }}, 'verify'); 
                                                                     bootstrap.Modal.getInstance(document.getElementById('proofModal-{{ $invoice->id }}')).hide();">
                                                        <i class="fas fa-check me-2"></i>Verifikasi Pembayaran
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

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

.modal-body img {
    transition: transform 0.2s;
}

.modal-body img:hover {
    transform: scale(1.02);
}
</style>

<script>
function confirmVerification(invoiceId, action) {
    let message, formId;
    
    if (action === 'verify') {
        message = 'Apakah Anda yakin ingin memverifikasi pembayaran ini?';
        formId = 'verifyForm' + invoiceId;
    } else {
        message = 'Apakah Anda yakin ingin menolak pembayaran ini?';
        formId = 'unverifyForm' + invoiceId;
    }
    
    if (confirm(message)) {
        document.getElementById(formId).submit();
    }
}
</script>
@endsection
