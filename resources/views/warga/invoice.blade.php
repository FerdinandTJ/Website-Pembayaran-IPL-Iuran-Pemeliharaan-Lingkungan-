@extends('layouts.app')

@section('title', 'Detail Tagihan')
@section('page-title', 'Detail Tagihan #' . $invoice->id)
@section('page-subtitle', 'Informasi lengkap tagihan dan proses pembayaran')
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
        <a class="nav-link" href="{{ url('/warga/profile') }}">
            <i class="fas fa-user me-1"></i>
            Profil
        </a>
    </li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Status Banner -->
            @if($invoice->is_paid && $invoice->is_verified)
                <div class="alert alert-success d-flex align-items-center mb-4">
                    <i class="fas fa-check-circle me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <h6 class="mb-1">Tagihan Telah Dibayar dan Terverifikasi</h6>
                        <small>Pembayaran Anda telah berhasil dan sudah diverifikasi oleh admin.</small>
                    </div>
                </div>
            @elseif($invoice->is_paid && !$invoice->is_verified)
                <div class="alert alert-warning d-flex align-items-center mb-4">
                    <i class="fas fa-clock me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <h6 class="mb-1">Menunggu Verifikasi</h6>
                        <small>Pembayaran Anda sedang dalam proses verifikasi oleh admin.</small>
                    </div>
                </div>
            @else
                <div class="alert alert-danger d-flex align-items-center mb-4">
                    <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <h6 class="mb-1">Tagihan Belum Dibayar</h6>
                        <small>Silakan lakukan pembayaran sebelum tanggal jatuh tempo.</small>
                    </div>
                </div>
            @endif

            <!-- Invoice Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-invoice me-2"></i>
                            Detail Tagihan #{{ $invoice->id }}
                        </h5>
                        @if($invoice->is_paid)
                            <span class="badge bg-success">
                                <i class="fas fa-check me-1"></i>
                                LUNAS
                            </span>
                        @else
                            <span class="badge bg-warning">
                                <i class="fas fa-clock me-1"></i>
                                BELUM BAYAR
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Invoice Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Informasi Tagihan
                            </h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>ID Tagihan:</strong></td>
                                    <td>#{{ $invoice->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>No. Rekening:</strong></td>
                                    <td>{{ $invoice->account_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nama Penerima:</strong></td>
                                    <td>{{ $invoice->receiver_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Dibuat:</strong></td>
                                    <td>{{ $invoice->created_at->format('d F Y') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Informasi Pembayaran
                            </h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Periode:</strong></td>
                                    <td>{{ $invoice->created_at->format('F Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Jatuh Tempo:</strong></td>
                                    <td>
                                        @php
                                            $dueDate = \Carbon\Carbon::parse($invoice->created_at)->addMonth()->day(10);
                                            $isOverdue = $dueDate->isPast() && !$invoice->is_paid;
                                        @endphp
                                        <span class="badge {{ $isOverdue ? 'bg-danger' : 'bg-info' }}">
                                            {{ $dueDate->format('d F Y') }}
                                        </span>
                                    </td>
                                </tr>
                                @if($invoice->is_paid)
                                <tr>
                                    <td><strong>Dibayar Pada:</strong></td>
                                    <td>{{ $invoice->payment_date ? $invoice->payment_date->format('d F Y H:i') : '-' }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <!-- Invoice Components -->
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-list me-1"></i>
                        Rincian Tagihan
                    </h6>
                    @if($components->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Tidak ada komponen tagihan ditemukan.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Nama Item</th>
                                        <th class="text-end" style="width: 150px;">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($components as $index => $component)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $component->name }}</td>
                                            <td class="text-end">
                                                Rp {{ number_format($component->amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <th colspan="2" class="text-end">Total Tagihan:</th>
                                        <th class="text-end">
                                            Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif

                    <!-- Payment Actions -->
                    <div class="text-center mt-4">
                        @if($invoice->is_paid)
                            <button type="button" class="btn btn-success btn-lg" disabled>
                                <i class="fas fa-check-circle me-2"></i>
                                Tagihan Sudah Dibayar
                            </button>
                            @if($invoice->payment_proof)
                                <button type="button" class="btn btn-outline-primary ms-2" 
                                        data-bs-toggle="modal" data-bs-target="#proofModal">
                                    <i class="fas fa-eye me-1"></i>
                                    Lihat Bukti Bayar
                                </button>
                            @endif
                        @else
                            <button type="button" class="btn btn-primary btn-lg" 
                                    data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i class="fas fa-credit-card me-2"></i>
                                Bayar Sekarang
                            </button>
                            <a href="{{ url('/warga/bills') }}" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-arrow-left me-1"></i>
                                Kembali ke Tagihan
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    @if(!$invoice->is_paid)
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ url('/warga/invoice/' . $invoice->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="paymentModalLabel">
                                <i class="fas fa-upload me-2"></i>
                                Upload Bukti Transfer
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Petunjuk Pembayaran:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Lakukan transfer ke rekening yang telah ditentukan</li>
                                    <li>Upload bukti transfer dalam format gambar (JPG, PNG, dsb)</li>
                                    <li>Tunggu verifikasi dari admin</li>
                                </ol>
                            </div>
                            <div class="mb-3">
                                <label for="proof" class="form-label">
                                    <i class="fas fa-image me-1"></i>
                                    Bukti Transfer <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="proof" name="proof" 
                                       accept="image/*" required>
                                <div class="form-text">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Format yang didukung: JPG, PNG, GIF. Maksimal 5MB.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                Batal
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i>
                                Submit Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Proof Modal -->
    @if($invoice->is_paid && $invoice->payment_proof)
        <div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="proofModalLabel">
                            <i class="fas fa-image me-2"></i>
                            Bukti Pembayaran - Tagihan #{{ $invoice->id }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="{{ asset('storage/' . $invoice->payment_proof) }}" 
                             alt="Bukti Pembayaran" class="img-fluid rounded shadow">
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
@endsection