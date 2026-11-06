@extends('layouts.app')
@section('title', 'Laporan - EnviroPay')
@section('page-title', 'Laporan Pengurus')
@section('page-subtitle', 'Pantau tunggakan warga, realisasi tagihan, dan pergerakan kas.')
@section('page-actions')
<a class="btn btn-outline-primary" href="{{ route('pengurus.reports.pdf', ['report' => $report] + request()->only('month', 'search', 'age', 'resident_id', 'type', 'category', 'source')) }}"><i class="bi bi-file-earmark-pdf me-1"></i>Unduh PDF</a>
<a class="btn btn-primary" href="{{ route('pengurus.reports.export', ['report' => $report] + request()->only('month', 'search', 'age', 'resident_id', 'type', 'category', 'source')) }}"><i class="bi bi-download me-1"></i>Ekspor CSV</a>
@endsection
@section('content')
@php($money = fn($value) => 'Rp '.preg_replace('/,00$/', '', number_format((float)$value, 2, ',', '.')))
@include('pengurus.reports.tabs', ['activeReport' => $report])
<form class="filter-bar row g-2 mb-4">
    @if($report === 'arrears')
    <div class="col-md-6"><label class="form-label" for="report-search">Nama atau alamat warga</label><input id="report-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari warga..."></div>
    <div class="col-md-4"><label class="form-label" for="report-age">Memiliki tunggakan umur</label><select id="report-age" name="age" class="form-select"><option value="">Semua umur</option>@foreach(['1-30' => '1–30 hari', '31-60' => '31–60 hari', '61+' => 'Lebih dari 60 hari'] as $value => $label)<option value="{{ $value }}" @selected(request('age') === $value)>{{ $label }}</option>@endforeach</select></div>
    @if(request('resident_id'))<input type="hidden" name="resident_id" value="{{ request('resident_id') }}">@endif
    @else
    <div class="col-md-6"><label class="form-label" for="report-month">{{ $report === 'billing' ? 'Periode tagihan' : 'Bulan transaksi' }}</label><input type="month" id="report-month" name="month" class="form-control" value="{{ $month->format('Y-m') }}" required></div>
    @endif
    @if($report === 'cashflow')
    <div class="col-md-3"><label class="form-label" for="report-type">Jenis transaksi</label><select id="report-type" name="type" class="form-select"><option value="">Semua transaksi</option><option value="income" @selected(request('type') === 'income')>Pemasukan saja</option><option value="expense" @selected(request('type') === 'expense')>Pengeluaran saja</option></select></div>
    <div class="col-md-3"><label class="form-label" for="report-category">Kategori</label><select id="report-category" name="category" class="form-select"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->value }}" @selected(request('category') === $category->value)>{{ $category->label() }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label" for="cash-report-search">Nama transaksi</label><input id="cash-report-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Misalnya gaji satpam atau perbaikan lampu"></div>
    <div class="col-md-3"><label class="form-label" for="report-source">Sumber pencatatan</label><select id="report-source" class="form-select" name="source"><option value="">Semua sumber</option><option value="invoice" @selected(request('source') === 'invoice')>Pembayaran tagihan</option><option value="manual" @selected(request('source') === 'manual')>Transaksi manual</option></select></div>
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
<tr><td><strong>{{ $row->name }}</strong>@unless($row->is_active)<span class="badge text-bg-secondary ms-1">Nonaktif</span>@endunless<small class="d-block text-secondary">{{ $row->house_address }}</small></td><td>{{ $row->invoice_count }}</td><td class="text-nowrap">{{ $money($row->total) }}</td><td class="text-nowrap">{{ $money($row->age_30) }}</td><td class="text-nowrap">{{ $money($row->age_60) }}</td><td class="text-nowrap">{{ $money($row->age_over_60) }}</td><td>{{ (int) \Carbon\Carbon::parse($row->oldest_due, 'Asia/Jakarta')->diffInDays(today('Asia/Jakarta')) }} hari<small class="d-block text-secondary">{{ $row->oldest_due }}</small></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('pengurus.reports.index', ['report' => 'arrears', 'resident_id' => $row->id]) }}">Lihat</a> <a class="btn btn-sm btn-outline-primary" href="{{ route('pengurus.follow-ups.show',$row->id) }}">Tindak Lanjut</a></td></tr>
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
@if($bookPeriod?->status === 'closed')<div class="alert alert-success mb-4">Buku kas {{ $month->translatedFormat('F Y') }} sudah ditutup pada {{ $bookPeriod->closed_at->format('d/m/Y H:i') }} WIB. Transaksi periode ini dikunci.</div>@endif
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div><h2 class="h5 mb-1">{{ ['income' => 'Laporan Pemasukan', 'expense' => 'Laporan Pengeluaran'][request('type', '')] ?? 'Laporan Gabungan Pemasukan & Pengeluaran' }}</h2><p class="small text-secondary mb-0">{{ $month->translatedFormat('F Y') }} · {{ $summary['count'] }} transaksi{{ request('category') ? ' · '.\App\Enums\CashflowCategory::from(request('category'))->label() : '' }}</p></div>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('pengurus.cashflows.index', request()->only('type', 'category', 'source', 'search')) }}">Kelola transaksi kas</a>
</div>
<div class="row g-3 mb-4">
    @foreach(['income' => 'Pemasukan Sesuai Filter', 'expense' => 'Pengeluaran Sesuai Filter', 'net' => 'Selisih Pemasukan − Pengeluaran'] as $key => $label)
    <div class="col-md-4"><x-stat-card :label="$label" :value="$money($summary[$key])" icon="wallet2" /></div>
    @endforeach
</div>
<div class="card mb-4"><div class="card-header"><strong>Saldo Kas Seluruh Perumahan</strong></div><div class="card-body">
    <p class="small text-secondary">Saldo berikut mencakup seluruh transaksi pada bulan ini, terlepas dari filter jenis, kategori, sumber, atau pencarian.</p>
    <div class="row g-3">@foreach(['opening' => 'Saldo awal', 'period_income' => 'Pemasukan bulan ini', 'period_expense' => 'Pengeluaran bulan ini', 'closing' => 'Saldo akhir'] as $key => $label)<div class="col-6 col-lg-3"><small class="text-secondary d-block">{{ $label }}</small><strong>{{ $money($summary[$key]) }}</strong></div>@endforeach</div>
</div></div>
<div class="card mb-4"><div class="card-header"><strong>Rekap per Kategori</strong><small class="d-block text-secondary">Mengikuti filter yang dipilih; mencakup seluruh halaman transaksi.</small></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Kategori</th><th>Transaksi</th><th>Pemasukan</th><th>Pengeluaran</th><th>Selisih</th></tr></thead><tbody>
@forelse($categorySummary as $category)
<tr><td>{{ $category->category->label() }}</td><td>{{ $category->count }}</td><td class="text-nowrap">{{ $money($category->income) }}</td><td class="text-nowrap">{{ $money($category->expense) }}</td><td class="text-nowrap">{{ $money((float)$category->income - (float)$category->expense) }}</td></tr>
@empty<tr><td colspan="5" class="text-secondary">Tidak ada transaksi yang sesuai filter.</td></tr>@endforelse
</tbody></table></div></div>
<div class="alert alert-light border">Pemasukan manual sesuai filter: <strong>{{ $money($summary['manual']) }}</strong> · Pemasukan tagihan sesuai filter: <strong>{{ $money($summary['invoice']) }}</strong></div>
<p class="small text-secondary">Berdasarkan tanggal transaksi. Kategori pembayaran tagihan mencakup seluruh komponen dalam pembayaran, termasuk pembayaran gabungan. Transaksi manual lama ditandai Belum Dikategorikan dan dapat diperbarui melalui Arus Kas. Laporan mengikuti data terkini.</p>
<div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Tanggal Transaksi</th><th>Transaksi</th><th>Jenis</th><th>Kategori</th><th>Sumber / Metode</th><th>Nominal</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td class="text-nowrap">{{ $row->accounting_date?->format('d/m/Y') }}<small class="d-block text-secondary">Dicatat {{ $row->created_at->format('d/m/Y H:i') }}</small></td><td>{{ $row->name }}</td><td>{{ $row->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td><td>{{ $row->category->label() }}</td><td>{{ $row->isManual() ? 'Manual' : 'Tagihan' }}<small class="d-block">{{ $row->method_label }}</small></td><td class="text-nowrap text-{{ $row->type === 'income' ? 'success' : 'danger' }}">{{ $money($row->total_cashflow) }}</td></tr>@empty<tr><td colspan="6"><x-empty-state title="Tidak ada transaksi yang sesuai filter" message="Coba ubah bulan, jenis transaksi, kategori, atau pencarian." /></td></tr>@endforelse
</tbody></table></div><div class="card-footer bg-white">{{ $rows->links() }}</div></div>
@endif
@endsection
