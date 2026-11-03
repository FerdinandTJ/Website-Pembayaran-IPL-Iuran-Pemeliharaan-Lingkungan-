@extends('layouts.app')
@section('title', 'Laporan - EnviroPay')
@section('page-title', 'Laporan Pengurus')
@section('page-subtitle', 'Pantau tunggakan warga, realisasi tagihan, dan pergerakan kas.')
@section('page-actions')
<a class="btn btn-primary" href="{{ route('pengurus.reports.export', ['report' => $report] + request()->only('month', 'search', 'age', 'resident_id')) }}"><i class="bi bi-download me-1"></i>Ekspor CSV</a>
@endsection
@section('content')
@php($money = fn($value) => 'Rp '.number_format($value, 0, ',', '.'))
<nav class="nav nav-pills gap-2 mb-4" aria-label="Jenis laporan">
    @foreach(['arrears' => 'Tunggakan', 'billing' => 'Tagihan Bulanan', 'cashflow' => 'Kas Bulanan'] as $key => $label)
    <a class="nav-link {{ $report === $key ? 'active' : '' }}" href="{{ route('pengurus.reports.index', ['report' => $key, 'month' => $month->format('Y-m')]) }}">{{ $label }}</a>
    @endforeach
</nav>
<form class="filter-bar row g-2 mb-4">
    @if($report === 'arrears')
    <div class="col-md-6"><label class="form-label" for="report-search">Nama atau alamat warga</label><input id="report-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari warga..."></div>
    <div class="col-md-4"><label class="form-label" for="report-age">Memiliki tunggakan umur</label><select id="report-age" name="age" class="form-select"><option value="">Semua umur</option>@foreach(['1-30' => '1–30 hari', '31-60' => '31–60 hari', '61+' => 'Lebih dari 60 hari'] as $value => $label)<option value="{{ $value }}" @selected(request('age') === $value)>{{ $label }}</option>@endforeach</select></div>
    @if(request('resident_id'))<input type="hidden" name="resident_id" value="{{ request('resident_id') }}">@endif
    @else
    <div class="col-md-6"><label class="form-label" for="report-month">{{ $report === 'billing' ? 'Periode tagihan' : 'Bulan pencatatan' }}</label><input type="month" id="report-month" name="month" class="form-control" value="{{ $month->format('Y-m') }}" required></div>
    @endif
    <div class="col-auto align-self-end"><button class="btn btn-primary">Tampilkan</button></div>
    <div class="col-auto align-self-end"><a class="btn btn-light" href="{{ route('pengurus.reports.index', $report) }}">Reset</a></div>
</form>
@if($report === 'arrears')
<p class="small text-secondary">Kondisi per {{ today('Asia/Jakarta')->translatedFormat('d F Y') }}. Pembayaran menunggu verifikasi tidak termasuk tunggakan. Filter umur memilih warga; nominal tetap mencakup seluruh tunggakannya.</p>
<div class="row g-3 mb-4">
    @foreach(['total' => 'Total Tunggakan', 'residents' => 'Warga Menunggak', 'invoices' => 'Tagihan Menunggak', 'age_30' => 'Umur 1–30 Hari', 'age_60' => 'Umur 31–60 Hari', 'age_over_60' => 'Umur >60 Hari'] as $key => $label)
    <div class="col-md-6 col-xl-4"><x-stat-card :label="$label" :value="in_array($key, ['residents', 'invoices']) ? $summary[$key] : $money($summary[$key])" icon="receipt" /></div>
    @endforeach
</div>
<div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Warga / Alamat</th><th>Tagihan</th><th>Total</th><th>1–30 Hari</th><th>31–60 Hari</th><th>>60 Hari</th><th>Keterlambatan Tertua</th><th>Rincian</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td><strong>{{ $row->name }}</strong>@unless($row->is_active)<span class="badge text-bg-secondary ms-1">Nonaktif</span>@endunless<small class="d-block text-secondary">{{ $row->house_address }}</small></td><td>{{ $row->invoice_count }}</td><td class="text-nowrap">{{ $money($row->total) }}</td><td class="text-nowrap">{{ $money($row->age_30) }}</td><td class="text-nowrap">{{ $money($row->age_60) }}</td><td class="text-nowrap">{{ $money($row->age_over_60) }}</td><td>{{ (int) \Carbon\Carbon::parse($row->oldest_due, 'Asia/Jakarta')->diffInDays(today('Asia/Jakarta')) }} hari<small class="d-block text-secondary">{{ $row->oldest_due }}</small></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('pengurus.reports.index', ['report' => 'arrears', 'resident_id' => $row->id]) }}">Lihat</a></td></tr>
@empty<tr><td colspan="8"><x-empty-state icon="check2-circle" title="Tidak ada tunggakan" message="Tidak ada warga menunggak yang sesuai filter." /></td></tr>@endforelse
</tbody></table></div><div class="card-footer bg-white">{{ $rows->links() }}</div></div>
@if($details)
<div class="card mt-4"><div class="card-header"><strong>Rincian Tagihan Tertunggak</strong></div><div class="table-responsive"><table class="table"><thead><tr><th>Tagihan</th><th>Periode</th><th>Jatuh Tempo</th><th>Nominal</th><th>Status</th></tr></thead><tbody>
@forelse($details as $invoice)<tr><td>{{ $invoice->title }}</td><td>{{ $invoice->billing_period->translatedFormat('F Y') }}</td><td>{{ $invoice->due_date->format('d/m/Y') }}</td><td>{{ $money($invoice->total_amount) }}</td><td><x-status-badge :status="$invoice->status" /><small class="d-block">{{ $invoice->payment_label }}</small><small class="d-block text-secondary">{{ $invoice->house_label }}</small></td></tr>@empty<tr><td colspan="5">Tidak ada tagihan tertunggak.</td></tr>@endforelse
</tbody></table></div><div class="card-footer bg-white">{{ $details->links() }}</div></div>
@endif
@elseif($report === 'billing')
<div class="row g-3 mb-4">
    <div class="col-md-6"><x-stat-card label="Total Nominal Tagihan" :value="$money($summary['total'])" icon="receipt" /></div>
    <div class="col-md-6"><x-stat-card label="Nominal Terverifikasi" :value="$summary['percentage'].'%'" icon="check2-circle" /></div>
    @foreach(\App\Enums\InvoiceStatus::cases() as $status)
    <div class="col-md-6 col-xl-3"><x-stat-card :label="$status->label().' ('.$summary['statuses'][$status->value]->count.')'" :value="$money($summary['statuses'][$status->value]->amount)" icon="receipt" /></div>
    @endforeach
</div>
<p class="small text-secondary">Berdasarkan periode tagihan {{ $month->translatedFormat('F Y') }}, dengan status pembayaran saat ini.</p>
<div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Warga</th><th>Tagihan</th><th>Jatuh Tempo</th><th>Nominal</th><th>Status</th></tr></thead><tbody>
@forelse($rows as $invoice)<tr><td>{{ $invoice->resident->name }}<small class="d-block text-secondary">{{ $invoice->resident->house_address }}</small></td><td>{{ $invoice->title }}</td><td>{{ $invoice->due_date->format('d/m/Y') }}</td><td class="text-nowrap">{{ $money($invoice->total_amount) }}</td><td><x-status-badge :status="$invoice->status" /><small class="d-block">{{ $invoice->payment_label }}</small><small class="d-block text-secondary">{{ $invoice->house_label }}</small></td></tr>@empty<tr><td colspan="5"><x-empty-state title="Belum ada tagihan pada periode ini" /></td></tr>@endforelse
</tbody></table></div><div class="card-footer bg-white">{{ $rows->links() }}</div></div>
@else
<div class="row g-3 mb-4">
    @foreach(['opening' => 'Saldo Awal', 'income' => 'Pemasukan', 'expense' => 'Pengeluaran', 'closing' => 'Saldo Akhir'] as $key => $label)
    <div class="col-md-6 col-xl-3"><x-stat-card :label="$label" :value="$money($summary[$key])" icon="wallet2" /></div>
    @endforeach
</div>
<div class="alert alert-light border">Pemasukan manual: <strong>{{ $money($summary['manual']) }}</strong> · Pemasukan tagihan terverifikasi: <strong>{{ $money($summary['invoice']) }}</strong></div>
<p class="small text-secondary">Bulan pencatatan {{ $month->translatedFormat('F Y') }}. Laporan dapat berubah jika transaksi manual diedit atau dihapus.</p>
<div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Tanggal Pencatatan</th><th>Transaksi</th><th>Jenis</th><th>Sumber</th><th>Nominal</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td class="text-nowrap">{{ $row->created_at->format('d/m/Y H:i') }}</td><td>{{ $row->name }}</td><td>{{ $row->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td><td>{{ $row->isManual() ? 'Manual' : 'Tagihan' }}<small class="d-block">{{ $row->invoice?->payment_label }}</small></td><td class="text-nowrap text-{{ $row->type === 'income' ? 'success' : 'danger' }}">{{ $money($row->total_cashflow) }}</td></tr>@empty<tr><td colspan="5"><x-empty-state title="Belum ada transaksi pada bulan ini" /></td></tr>@endforelse
</tbody></table></div><div class="card-footer bg-white">{{ $rows->links() }}</div></div>
@endif
@endsection
