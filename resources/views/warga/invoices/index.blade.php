@extends('layouts.app')
@section('title', 'Tagihan Saya - EnviroPay')
@section('page-title', 'Tagihan Saya')
@section('page-subtitle', 'Lihat tagihan aktif, status verifikasi, dan riwayat pembayaran.')

@section('content')
<form class="filter-bar row g-2 mb-3">
    <div class="col-md"><input type="month" class="form-control" name="period" value="{{ request('period') }}"></div>
    <div class="col-md-4"><select class="form-select" name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
</form>
<div class="card">
    <div class="table-responsive"><table class="table table-mobile-cards"><thead><tr><th>Tagihan</th><th>Periode</th><th>Jatuh Tempo</th><th>Nominal</th><th>Status</th><th class="text-end">Detail</th></tr></thead><tbody>
        @forelse($invoices as $invoice)
            <tr>
                <td class="fw-semibold">{{ $invoice->title }}</td>
                <td>{{ $invoice->billing_period->translatedFormat('F Y') }}</td>
                <td class="{{ $invoice->due_date->isPast() && $invoice->status->value === 'unpaid' ? 'text-danger fw-semibold' : '' }}">{{ $invoice->due_date->format('d M Y') }}</td>
                <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                <td><x-status-badge :status="$invoice->status" /></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('warga.invoices.show', $invoice) }}">Buka</a></td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty-state icon="receipt" title="Tidak ada tagihan" message="Tagihan yang dibuat pengurus akan muncul di sini." /></td></tr>
        @endforelse
    </tbody></table></div>
    @if($invoices->hasPages())<div class="card-footer bg-white">{{ $invoices->links() }}</div>@endif
</div>
@endsection
