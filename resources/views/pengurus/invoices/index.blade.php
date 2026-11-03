@extends('layouts.app')
@section('title', 'Tagihan - EnviroPay')
@section('page-title', 'Daftar Tagihan')
@section('page-subtitle', 'Pantau seluruh tagihan warga berdasarkan periode dan status.')
@section('page-actions')<a class="btn btn-primary" href="{{ route('pengurus.invoices.create') }}"><i class="bi bi-plus-lg me-1"></i>Buat Tagihan</a>@endsection

@section('content')
<form class="filter-bar row g-2 mb-3">
    <div class="col-md"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari judul atau nama warga..."></div>
    <div class="col-md-3"><input type="month" class="form-control" name="period" value="{{ request('period') }}"></div>
    <div class="col-md-3"><select class="form-select" name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
</form>
<div class="card">
    <div class="table-responsive"><table class="table table-mobile-cards"><thead><tr><th>Tagihan / Warga</th><th>Periode</th><th>Jatuh Tempo</th><th>Nominal</th><th>Status</th></tr></thead><tbody>
        @forelse($invoices as $invoice)
            <tr>
                <td><div class="fw-semibold">{{ $invoice->title }}</div><small class="text-secondary">{{ $invoice->resident->name }} · {{ $invoice->house_label }} · #{{ $invoice->id }}</small></td>
                <td>{{ $invoice->billing_period->translatedFormat('F Y') }}</td>
                <td class="{{ $invoice->due_date->isPast() && $invoice->status->value === 'unpaid' ? 'text-danger fw-semibold' : '' }}">{{ $invoice->due_date->format('d M Y') }}</td>
                <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                <td><x-status-badge :status="$invoice->status" />@if($invoice->status->value === 'verified')<a class="d-block small mt-1" href="{{ route('receipts.show',$invoice) }}">{{ $invoice->payment_label }} · Kuitansi</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state icon="receipt" title="Belum ada tagihan" /></td></tr>
        @endforelse
    </tbody></table></div>
    @if($invoices->hasPages())<div class="card-footer bg-white">{{ $invoices->links() }}</div>@endif
</div>
@endsection
