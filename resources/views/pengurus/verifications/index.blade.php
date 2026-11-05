@extends('layouts.app')
@section('title', 'Verifikasi - EnviroPay')
@section('page-title', 'Verifikasi Pembayaran')
@section('page-subtitle', 'Periksa bukti pembayaran sebelum memasukkannya ke arus kas.')

@section('content')
@if(request('invoice_id'))<div class="alert alert-info">Menampilkan tagihan dari notifikasi. <a href="{{ route('pengurus.verifications.index') }}">Lihat semua antrean</a></div>@endif
<form class="filter-bar row g-2 mb-3">@if(request('invoice_id'))<input type="hidden" name="invoice_id" value="{{ request('invoice_id') }}">@endif<div class="col"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama warga..."></div><div class="col-auto"><button class="btn btn-primary">Cari</button></div></form>
<div class="card"><div class="table-responsive"><table class="table table-mobile-cards"><thead><tr><th>Tagihan / Warga</th><th>Periode</th><th>Nominal</th><th>Dikirim</th><th>Bukti</th><th class="text-end">Keputusan</th></tr></thead><tbody>
@forelse($invoices as $invoice)
<tr>
    <td><div class="fw-semibold">{{ $invoice->payment_batch_id ? 'Pembayaran gabungan #'.$invoice->payment_batch_id : $invoice->title }}</div>@if($invoice->paymentBatch)<small class="d-block text-secondary">{{ count($invoice->paymentBatch->snapshot['invoices']) }} tagihan dalam satu transfer</small>@endif<small class="text-secondary">{{ $invoice->resident->name }} · {{ $invoice->resident->house_address }}</small></td>
    <td>{{ $invoice->payment_batch_id ? 'Beberapa tagihan' : $invoice->billing_period->translatedFormat('F Y') }}</td>
    <td>Rp {{ number_format($invoice->paymentBatch?->total_amount ?? $invoice->total_amount, 0, ',', '.') }}</td>
    <td>{{ $invoice->submitted_at?->format('d M Y H:i') }}</td>
    <td><a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('invoices.proof', $invoice) }}"><i class="bi bi-image me-1"></i>Lihat</a></td>
    <td class="text-end">
        @if($invoice->payment_batch_id)<a class="btn btn-sm btn-primary" href="{{ route('payment-batches.show',$invoice->payment_batch_id) }}">Periksa Seluruh Pembayaran</a>@else
        <form class="d-inline" method="POST" action="{{ route('pengurus.invoices.verify', $invoice) }}" data-confirm="Pastikan nominal dan bukti pembayaran sudah benar. Verifikasi bersifat final.">@csrf<button class="btn btn-sm btn-success">Verifikasi</button></form>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject-{{ $invoice->id }}">Tolak</button>
        <div class="modal fade text-start" id="reject-{{ $invoice->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('pengurus.invoices.reject', $invoice) }}">@csrf<div class="modal-header"><h5 class="modal-title">Tolak Bukti Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Alasan penolakan</label><textarea class="form-control" name="rejection_reason" rows="4" minlength="5" required placeholder="Jelaskan bagian bukti yang perlu diperbaiki..."></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Tolak Bukti</button></div></form></div></div></div>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="6"><x-empty-state icon="check2-circle" title="Semua pembayaran sudah diproses" message="Belum ada bukti baru yang perlu diverifikasi." /></td></tr>
@endforelse
</tbody></table></div>@if($invoices->hasPages())<div class="card-footer bg-white">{{ $invoices->links() }}</div>@endif</div>
@endsection
