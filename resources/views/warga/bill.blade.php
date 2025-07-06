@extends('layouts.app')

@section('title', 'Tagihan Belum Dibayar')
@section('page-title', 'Tagihan')
@section('page-subtitle', 'Daftar tagihan yang belum dibayar')
@section('dashboard-url', url('/warga/dashboard'))

@section('nav-items')
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/warga/dashboard') }}">
            <i class="fas fa-tachometer-alt me-1"></i>
            Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ url('/warga/bills') }}">
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
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                        Tagihan Belum Dibayar
                    </h5>
                    <span class="badge bg-warning text-dark">{{ $invoices->count() }} Tagihan</span>
                </div>
                <div class="card-body">
                    @if($invoices->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem; margin-bottom: 1.5rem;"></i>
                            <h4 class="text-success mb-3">Tidak ada tagihan yang belum dibayar</h4>
                            <p class="text-muted mb-4">Selamat! Semua tagihan Anda sudah terbayar.</p>
                            <a href="{{ url('/warga/dashboard') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-1"></i>
                                Kembali ke Dashboard
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="fas fa-hashtag me-1"></i>ID Tagihan</th>
                                        <th><i class="fas fa-credit-card me-1"></i>No. Rekening</th>
                                        <th><i class="fas fa-money-bill-wave me-1"></i>Jumlah</th>
                                        <th><i class="fas fa-calendar me-1"></i>Periode</th>
                                        <th><i class="fas fa-clock me-1"></i>Jatuh Tempo</th>
                                        <th><i class="fas fa-tools me-1"></i>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $invoice)
                                        <tr>
                                            <td>
                                                <strong class="text-primary">Tagihan #{{ $invoice->id }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ $invoice->account_number }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-danger">
                                                    Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>{{ $invoice->created_at->format('F Y') }}</td>
                                            <td>
                                                @php
                                                    $dueDate = \Carbon\Carbon::parse($invoice->created_at)->addMonth()->day(10);
                                                    $isOverdue = $dueDate->isPast();
                                                @endphp
                                                <span class="badge {{ $isOverdue ? 'bg-danger' : 'bg-warning' }}">
                                                    {{ $dueDate->format('d M Y') }}
                                                    @if($isOverdue)
                                                        <i class="fas fa-exclamation-triangle ms-1"></i>
                                                    @endif
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ url('/warga/invoice/' . $invoice->id) }}" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-credit-card me-1"></i>
                                                    Bayar Sekarang
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Card -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="alert alert-warning d-flex align-items-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div>
                                        <strong>Total {{ $invoices->count() }} tagihan belum dibayar</strong><br>
                                        <small>Segera lakukan pembayaran untuk menghindari denda keterlambatan</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-calculator me-1"></i>
                                            Total yang Harus Dibayar
                                        </h6>
                                        <h4 class="text-danger mb-0">
                                            Rp {{ number_format($invoices->sum('total_amount'), 0, ',', '.') }}
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
