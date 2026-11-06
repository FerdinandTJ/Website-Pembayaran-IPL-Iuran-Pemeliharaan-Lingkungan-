@extends('layouts.app')
@section('title', 'Rekap Tahunan Rumah - EnviroPay')
@section('page-title', 'Laporan Pengurus')
@section('page-subtitle', 'Pantau tunggakan warga, realisasi tagihan, dan pergerakan kas.')
@section('page-actions')
<a class="btn btn-outline-primary" href="{{ route('pengurus.annual-report.pdf', ['year' => $year] + request()->only('search', 'payment_status')) }}"><i class="bi bi-file-earmark-pdf me-1"></i>Unduh PDF</a>
<a class="btn btn-primary" href="{{ route('pengurus.annual-report.export', ['year' => $year] + request()->only('search', 'payment_status')) }}"><i class="bi bi-download me-1"></i>Ekspor CSV</a>
@endsection
@section('content')
@php($money = fn($value) => 'Rp '.preg_replace('/,00$/', '', number_format((float)$value, 2, ',', '.')))
@include('pengurus.reports.tabs', ['activeReport' => 'annual'])
<form class="filter-bar row g-2 mb-4">
    <div class="col-md-2"><label class="form-label" for="annual-year">Tahun tagihan</label><select id="annual-year" class="form-select" name="year" required>@foreach($yearOptions as $optionYear)<option value="{{ $optionYear }}" @selected($optionYear === $year)>{{ $optionYear }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label" for="annual-search">Rumah atau nama warga</label><input id="annual-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Kode rumah, alamat, atau nama warga"></div>
    <div class="col-md-4"><label class="form-label" for="annual-status">Status tagihan</label><select id="annual-status" class="form-select" name="payment_status"><option value="">Semua status</option>@foreach(\App\Services\AnnualReportService::STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('payment_status') === $key)>{{ $key === 'paid' ? 'Seluruh tagihan terbit sudah lunas' : $label }}</option>@endforeach</select></div>
    <div class="col-auto align-self-end d-flex gap-2"><button class="btn btn-primary">Tampilkan</button><a class="btn btn-light" href="{{ route('pengurus.annual-report.index') }}">Reset</a></div>
</form>
<div class="row g-3 mb-4">@foreach(['billed' => 'Total Tagihan', 'paid' => 'Lunas', 'pending' => 'Menunggu Verifikasi', 'unpaid' => 'Belum Lunas'] as $key => $label)<div class="col-md-6 col-xl-3"><x-stat-card :label="$label" :value="$money($summary[$key])" icon="receipt" /></div>@endforeach</div>
<p class="small text-secondary mb-4">{{ $summary['groups'] }} baris · {{ $summary['invoice_count'] }} tagihan periode {{ $year }} · Sudah jatuh tempo dan belum dibayar: <strong>{{ $money($summary['overdue']) }}</strong>. Ringkasan mengikuti filter dan mencakup seluruh halaman.</p>
@if($summary['unlinked'])<div class="alert alert-warning mb-4">Ada {{ $summary['unlinked'] }} akun dengan tagihan tanpa rumah tercatat saat penerbitan. Tagihan tersebut ditampilkan dalam baris terpisah dan tidak dipindahkan ke rumah warga saat ini.</div>@endif
<div class="card mb-4"><div class="card-body py-3">
    <div class="row g-3">
        <div class="col-md-6 col-xl-3"><span class="badge text-bg-success mb-1">Lunas</span><p class="small text-secondary mb-0">Semua tagihan bulan itu terverifikasi.</p></div>
        <div class="col-md-6 col-xl-3"><span class="badge text-bg-info mb-1">Verifikasi</span><p class="small text-secondary mb-0">Sisa tagihan sedang diperiksa.</p></div>
        <div class="col-md-6 col-xl-3"><span class="badge text-bg-warning mb-1">Belum Lunas</span><p class="small text-secondary mb-0">Ada tagihan belum dibayar atau bukti ditolak.</p></div>
        <div class="col-md-6 col-xl-3"><span class="badge text-bg-secondary mb-1">Belum Ada Tagihan</span><p class="small text-secondary mb-0">Ditandai — pada tabel, bukan berarti lunas.</p></div>
    </div>
    <p class="small text-secondary mt-3 mb-0">Periode mengikuti bulan tagihan, dengan status pembayaran saat ini. Beberapa tagihan dalam bulan yang sama digabung. Nominal belum lunas tidak mencakup menunggu verifikasi.</p>
</div></div>
<div class="card annual-report-card"><div class="card-header"><strong>Rekap Pembayaran {{ $year }}</strong><small class="d-block text-secondary">Klik status untuk rincian. Geser tabel ke samping untuk melihat seluruh bulan dan nominal.</small></div><div class="table-responsive"><table class="table align-middle annual-report-table"><thead><tr>
    <th class="annual-house">Rumah<br><span class="fw-normal">Penanggung Jawab Saat Ini</span></th>
    @foreach(range(1,12) as $monthNumber)<th class="text-center annual-month">{{ \Carbon\Carbon::create($year, $monthNumber, 1)->translatedFormat('M') }}</th>@endforeach
    <th class="text-end">Lunas</th><th class="text-end">Verifikasi</th><th class="text-end">Belum Lunas</th>
</tr></thead><tbody>
@forelse($rows as $row)
<tr><td class="annual-house"><strong>{{ $row->group_type === 'house' ? $row->code : 'Tanpa Rumah Tercatat' }}</strong><small class="d-block text-secondary">{{ $row->address }}</small><small class="d-block">{{ $row->resident ?: 'Belum ada penanggung jawab' }}</small></td>
@foreach($row->months as $monthNumber => $cell)
<td class="text-center">
@if($cell['status'] === 'not_issued')<span class="text-secondary" title="Belum ada tagihan">—</span>
@else
@php($detailParams = ['year' => $year, 'detail_month' => $monthNumber, $row->group_type === 'house' ? 'detail_house_id' : 'detail_resident_id' => $row->group_id] + request()->only('search', 'payment_status', 'page'))
<a class="badge text-bg-{{ ['paid' => 'success', 'pending' => 'info', 'unpaid' => 'warning'][$cell['status']] }} text-decoration-none" href="{{ route('pengurus.annual-report.index', $detailParams) }}#rincian-bulan" title="{{ (int)$cell['invoice_count'] }} tagihan · Lunas {{ $money($cell['paid']) }} · Verifikasi {{ $money($cell['pending']) }} · Belum lunas {{ $money($cell['unpaid']) }}">{{ ['paid' => 'Lunas', 'pending' => 'Verifikasi', 'unpaid' => 'Belum Lunas'][$cell['status']] }}</a>
@if($cell['status'] === 'unpaid' && $cell['pending_count'] > 0)<small class="d-block text-info mt-1">+ {{ (int)$cell['pending_count'] }} verifikasi</small>@endif
@endif
</td>
@endforeach
<td class="text-nowrap text-end">{{ $money($row->totals['paid']) }}</td><td class="text-nowrap text-end">{{ $money($row->totals['pending']) }}</td><td class="text-nowrap text-end">{{ $money($row->totals['unpaid']) }}</td></tr>
@empty<tr><td colspan="16"><x-empty-state title="Tidak ada rumah atau tagihan yang sesuai filter" message="Coba ubah tahun, status, atau pencarian." /></td></tr>@endforelse
</tbody></table></div><div class="card-footer bg-white">{{ $groups->links() }}</div></div>
@if($details)
<section class="card mt-4" id="rincian-bulan"><div class="card-header"><strong>Rincian {{ \Carbon\Carbon::create($year, (int)$filters['detail_month'], 1)->translatedFormat('F Y') }}</strong><p class="small text-secondary mb-0">{{ !empty($filters['detail_house_id']) ? 'Rumah yang tercatat pada tagihan' : 'Akun dengan tagihan tanpa rumah tercatat' }}. Nama warga di bawah adalah pemilik masing-masing tagihan, termasuk penghuni sebelumnya.</p></div>
<div class="table-responsive"><table class="table mb-0"><thead><tr><th>Warga / Rumah pada Tagihan</th><th>Tagihan</th><th>Jatuh Tempo</th><th>Nominal</th><th>Status</th><th>Rincian</th></tr></thead><tbody>
@forelse($details as $invoice)<tr><td>{{ $invoice->resident->name }}<small class="d-block text-secondary">{{ $invoice->house_label ?: 'Rumah belum tercatat' }}</small></td><td>{{ $invoice->title }}</td><td>{{ $invoice->due_date->format('d/m/Y') }}</td><td class="text-nowrap">{{ $money($invoice->total_amount) }}</td><td><x-status-badge :status="$invoice->status" /></td><td>
@if($invoice->status === \App\Enums\InvoiceStatus::Verified)<a class="btn btn-sm btn-outline-primary" href="{{ route('receipts.show', $invoice) }}">Kuitansi</a>
@elseif($invoice->status === \App\Enums\InvoiceStatus::Pending)<a class="btn btn-sm btn-outline-primary" href="{{ route('pengurus.verifications.index', ['invoice_id' => $invoice->id]) }}">Verifikasi</a>
@else<a class="btn btn-sm btn-outline-primary" href="{{ route('pengurus.cash-payments.index', ['resident_id' => $invoice->user_id]) }}">Pembayaran Cash</a>@endif
</td></tr>@empty<tr><td colspan="6">Tidak ada tagihan untuk pilihan rumah/akun dan bulan ini.</td></tr>@endforelse
</tbody></table></div><div class="card-footer bg-white">{{ $details->links() }}</div></section>
@endif
@endsection
