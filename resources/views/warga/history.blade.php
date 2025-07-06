@extends('layouts.app')

@section('title', 'Riwayat Pembayaran')
@section('page-title', 'Riwayat Pembayaran')
@section('page-subtitle', 'Daftar transaksi pembayaran yang telah dilakukan')
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
        <a class="nav-link active" href="{{ url('/warga/invoice/history') }}">
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
                        <i class="fas fa-history me-2 text-success"></i>
                        Riwayat Pembayaran
                    </h5>
                    <span class="badge bg-success">{{ $invoices->count() }} Transaksi</span>
                </div>
                <div class="card-body">
                    @if($invoices->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-receipt text-muted" style="font-size: 4rem; margin-bottom: 1.5rem;"></i>
                            <h4 class="text-muted mb-3">Belum ada riwayat pembayaran</h4>
                            <p class="text-muted mb-4">Riwayat pembayaran Anda akan muncul di sini setelah melakukan transaksi.</p>
                            <a href="{{ url('/warga/bills') }}" class="btn btn-primary">
                                <i class="fas fa-file-invoice me-1"></i>
                                Lihat Tagihan
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
                                        <th><i class="fas fa-check-circle me-1"></i>Dibayar Pada</th>
                                        <th><i class="fas fa-shield-alt me-1"></i>Status</th>
                                        <th><i class="fas fa-image me-1"></i>Bukti Bayar</th>
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
                                                <span class="fw-bold text-success">
                                                    Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>{{ $invoice->created_at->format('F Y') }}</td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $invoice->updated_at->format('d M Y, H:i') }}
                                                </small>
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
                                            <td>
                                                @if($invoice->payment_proof)
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            data-bs-toggle="modal" data-bs-target="#proofModal-{{ $invoice->id }}">
                                                        <i class="fas fa-eye me-1"></i>
                                                        Lihat Bukti
                                                    </button>
                                                @else
                                                    <span class="text-muted">
                                                        <i class="fas fa-times me-1"></i>
                                                        Tidak ada
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>

                                        <!-- Modal for viewing proof -->
                                        @if($invoice->payment_proof)
                                        <div class="modal fade" id="proofModal-{{ $invoice->id }}" tabindex="-1" 
                                             aria-labelledby="proofModalLabel-{{ $invoice->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="proofModalLabel-{{ $invoice->id }}">
                                                            <i class="fas fa-image me-2"></i>
                                                            Bukti Pembayaran - Tagihan #{{ $invoice->id }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <img src="{{ asset('storage/' . $invoice->payment_proof) }}" 
                                                             alt="Bukti Pembayaran" class="img-fluid rounded shadow">
                                                        <div class="mt-3">
                                                            <p class="text-muted">
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                Klik gambar untuk memperbesar
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <a href="{{ asset('storage/' . $invoice->payment_proof) }}" 
                                                           target="_blank" class="btn btn-primary">
                                                            <i class="fas fa-external-link-alt me-1"></i>
                                                            Buka di Tab Baru
                                                        </a>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i>
                                                            Tutup
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

                        <!-- Summary Cards -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Total Transaksi
                                        </h6>
                                        <h4 class="mb-0">{{ $invoices->count() }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            Total Dibayar
                                        </h6>
                                        <h4 class="mb-0">Rp {{ number_format($invoices->sum('total_amount'), 0, ',', '.') }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-clock me-1"></i>
                                            Menunggu Verifikasi
                                        </h6>
                                        <h4 class="mb-0">{{ $invoices->where('is_verified', false)->count() }}</h4>
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
