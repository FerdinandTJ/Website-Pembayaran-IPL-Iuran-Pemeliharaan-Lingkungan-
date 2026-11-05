@extends('layouts.app')
@section('title', 'Pilih Tagihan - EnviroPay')
@section('page-title', 'Bayar Tagihan')
@section('page-subtitle', 'Pilih tagihan yang ingin dilunasi dengan satu transfer dan satu bukti pembayaran.')
@section('page-actions')<a class="btn btn-light" href="{{ route('warga.invoices.index') }}">Kembali ke Tagihan</a>@endsection
@section('content')
<div class="alert alert-info">Menampilkan hingga 100 tagihan belum dibayar atau ditolak, mulai dari jatuh tempo terlama. Pembayaran satu tagihan juga tetap bisa dilakukan.</div>
<form method="GET" action="{{ route('warga.payment-batches.create') }}" data-payment-selection>
<div class="card"><div class="card-header"><label class="form-check mb-0"><input type="checkbox" class="form-check-input" data-payment-all><span class="form-check-label">Pilih semua tagihan di daftar ini</span></label></div><div class="table-responsive"><table class="table table-mobile-cards"><thead><tr><th>Pilih</th><th>Tagihan</th><th>Periode / Jatuh tempo</th><th>Nominal</th><th>Status</th></tr></thead><tbody>
@forelse($invoices as $invoice)<tr><td><input class="form-check-input" type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" data-payment-invoice data-amount="{{ $invoice->total_amount }}" aria-label="Pilih {{ $invoice->title }} periode {{ $invoice->billing_period->translatedFormat('F Y') }}"></td><td><a class="fw-semibold text-decoration-none" href="{{ route('warga.invoices.show',$invoice) }}">{{ $invoice->title }}</a></td><td>{{ $invoice->billing_period->translatedFormat('F Y') }}<small class="d-block text-secondary">Jatuh tempo {{ $invoice->due_date->format('d/m/Y') }}</small></td><td>Rp {{ number_format($invoice->total_amount,0,',','.') }}</td><td><x-status-badge :status="$invoice->status" /></td></tr>@empty<tr><td colspan="5"><x-empty-state icon="check2-circle" title="Tidak ada tagihan yang dapat dipilih" message="Tagihan lunas dan menunggu verifikasi tidak dapat dibayar lagi." /></td></tr>@endforelse
</tbody></table></div></div><x-payment-selection-summary />
</form>
@endsection
