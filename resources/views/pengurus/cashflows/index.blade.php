@extends('layouts.app')
@section('title', 'Arus Kas - EnviroPay')
@section('page-title', 'Arus Kas')
@section('page-subtitle', 'Pemasukan terverifikasi dan transaksi kas manual perumahan.')
@section('page-actions')
<a class="btn btn-outline-primary" href="{{ route('pengurus.reports.index', ['report' => 'cashflow'] + request()->only('type', 'category', 'source', 'search')) }}">Laporan Bulanan</a>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCashflow"><i class="bi bi-plus-lg me-1"></i>Tambah Transaksi</button>
@endsection
@section('content')
@php($money = fn($value) => 'Rp '.preg_replace('/,00$/', '', number_format((float)$value, 2, ',', '.')))
<p class="small text-secondary">Ringkasan seluruh kas perumahan dari awal pencatatan. Filter di bawah berlaku untuk daftar transaksi.</p>
<div class="row g-3 mb-4">
    <div class="col-md-4"><x-stat-card label="Pemasukan Seluruh Kas" :value="$money($income)" icon="graph-up-arrow" /></div>
    <div class="col-md-4"><x-stat-card label="Pengeluaran Seluruh Kas" :value="$money($expense)" icon="graph-down-arrow" /></div>
    <div class="col-md-4"><x-stat-card label="Saldo Seluruh Kas" :value="$money($income-$expense)" icon="wallet2" /></div>
</div>
<form class="filter-bar row g-2 mb-3">
    <div class="col-md-4"><label class="form-label" for="cash-search">Nama transaksi</label><input id="cash-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari transaksi..."></div>
    <div class="col-md"><label class="form-label" for="cash-type">Jenis transaksi</label><select id="cash-type" class="form-select" name="type"><option value="">Semua transaksi</option><option value="income" @selected(request('type')==='income')>Pemasukan</option><option value="expense" @selected(request('type')==='expense')>Pengeluaran</option></select></div>
    <div class="col-md"><label class="form-label" for="cash-category">Kategori</label><select id="cash-category" class="form-select" name="category"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->value }}" @selected(request('category')===$category->value)>{{ $category->label() }}</option>@endforeach</select></div>
    <div class="col-md"><label class="form-label" for="cash-source">Sumber</label><select id="cash-source" class="form-select" name="source"><option value="">Semua sumber</option><option value="invoice" @selected(request('source')==='invoice')>Tagihan</option><option value="manual" @selected(request('source')==='manual')>Manual</option></select></div>
    <div class="col-auto align-self-end"><button class="btn btn-primary">Filter</button><a class="btn btn-light" href="{{ route('pengurus.cashflows.index') }}">Reset</a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-mobile-cards"><thead><tr><th>Tanggal</th><th>Transaksi</th><th>Kategori</th><th>Sumber</th><th>Nominal</th><th class="text-end">Aksi</th></tr></thead><tbody>
@forelse($cashflows as $cashflow)
<tr>
    <td>{{ $cashflow->accounting_date?->format('d M Y') }}<small class="d-block text-secondary">Dicatat {{ $cashflow->created_at->format('d/m/Y H:i') }}</small></td>
    <td class="fw-semibold">{{ $cashflow->name }}
        @if($cashflow->attachment_path)<small class="d-block"><a href="{{ route('pengurus.cashflows.attachment', $cashflow) }}">Unduh nota / bukti</a></small>@endif
        @if($cashflow->payment_batch_id)<small class="d-block"><a href="{{ route('payment-batches.show',$cashflow->payment_batch_id) }}">Rincian pembayaran gabungan</a></small>@endif
        @unless($cashflow->isManual())<small class="d-block text-secondary">{{ $cashflow->method_label }} · {{ $cashflow->invoice?->house_label }}</small>@endunless
    </td>
    <td>{{ $cashflow->category->label() }}</td>
    <td><span class="badge text-bg-{{ $cashflow->source==='invoice' ? 'info' : 'secondary' }}">{{ $cashflow->source==='invoice' ? 'Tagihan' : 'Manual' }}</span></td>
    <td class="fw-semibold text-nowrap text-{{ $cashflow->type==='income' ? 'success' : 'danger' }}">{{ $cashflow->type==='income' ? '+' : '-' }}{{ $money($cashflow->total_cashflow) }}</td>
    <td class="text-end">
        @if($cashflow->isManual())
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCashflow-{{ $cashflow->id }}">Edit</button>
        <form class="d-inline" method="POST" enctype="multipart/form-data" action="{{ route('pengurus.cashflows.destroy',$cashflow) }}" data-confirm="Hapus transaksi manual ini?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>
        @else<span class="text-secondary small">Terkunci</span>@endif
    </td>
</tr>
@empty<tr><td colspan="6"><x-empty-state icon="wallet2" title="Tidak ada transaksi yang sesuai filter" /></td></tr>@endforelse
</tbody></table></div>
@if($cashflows->hasPages())<div class="card-footer bg-white">{{ $cashflows->links() }}</div>@endif
</div>
@foreach($cashflows->filter(fn($cashflow) => $cashflow->isManual()) as $cashflow)
<div class="modal fade" id="editCashflow-{{ $cashflow->id }}" tabindex="-1" aria-labelledby="editCashflow-title-{{ $cashflow->id }}"><div class="modal-dialog"><div class="modal-content"><form method="POST" enctype="multipart/form-data" action="{{ route('pengurus.cashflows.update',$cashflow) }}">
    @csrf @method('PUT')
    <div class="modal-header"><h5 class="modal-title" id="editCashflow-title-{{ $cashflow->id }}">Edit Transaksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body vstack gap-3">@include('pengurus.cashflows.fields', ['cashflow' => $cashflow])</div>
    <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
</form></div></div></div>
@endforeach
<div class="modal fade" id="addCashflow" tabindex="-1" aria-labelledby="addCashflow-title"><div class="modal-dialog"><div class="modal-content"><form method="POST" enctype="multipart/form-data" action="{{ route('pengurus.cashflows.store') }}">
    @csrf
    <div class="modal-header"><h5 class="modal-title" id="addCashflow-title">Tambah Transaksi Manual</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body vstack gap-3">@include('pengurus.cashflows.fields', ['cashflow' => new \App\Models\Cashflow])</div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
</form></div></div></div>
@endsection
