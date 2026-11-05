@extends('layouts.app')
@section('title', 'Pembayaran Cash - EnviroPay')
@section('page-title', 'Terima Pembayaran Cash')
@section('page-subtitle', 'Pilih warga dan tagihan, lalu konfirmasi hanya setelah uang diterima.')
@section('content')
<div class="alert alert-light border">Pilih satu atau beberapa tagihan milik warga yang sama. Pembayaran harus lunas sesuai total pilihan. Waktu penerimaan dicatat otomatis saat konfirmasi. Tagihan menunggu verifikasi transfer harus diproses terlebih dahulu.</div>
<div class="row g-4"><div class="col-lg-5"><div class="card"><div class="card-header"><strong>1. Pilih Warga</strong></div><div class="card-body"><form class="d-flex gap-2 mb-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama atau alamat" aria-label="Cari warga"><button class="btn btn-primary">Cari</button></form><div class="list-group">@forelse($residents as $person)<a class="list-group-item list-group-item-action {{ $resident?->id === $person->id ? 'active' : '' }}" href="{{ route('pengurus.cash-payments.index',['resident_id'=>$person->id, 'search'=>request('search')]) }}"><strong>{{ $person->name }}</strong>@unless($person->is_active)<span class="badge text-bg-secondary ms-1">Nonaktif</span>@endunless<small class="d-block">{{ $person->house?->label ?: $person->house_address }}</small></a>@empty<p>Warga tidak ditemukan.</p>@endforelse</div><div class="mt-3">{{ $residents->links() }}</div></div></div></div>
<div class="col-lg-7"><div class="card"><div class="card-header"><strong>2. Pilih Tagihan {{ $resident ? '— '.$resident->name : '' }}</strong></div><div class="card-body">
@if($resident)
<form method="GET" action="{{ route('pengurus.cash-batches.create') }}" data-payment-selection>
@if($invoices->count())<label class="form-check mb-3"><input type="checkbox" class="form-check-input" data-payment-all><span class="form-check-label">Pilih semua tagihan di halaman ini</span></label>@endif
@forelse($invoices as $invoice)<label class="border rounded-3 p-3 mb-3 d-block"><div class="d-flex align-items-start gap-3"><input class="form-check-input flex-shrink-0" type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" data-payment-invoice data-amount="{{ $invoice->total_amount }}" aria-label="Pilih {{ $invoice->title }}"><div><strong>{{ $invoice->title }}</strong><small class="d-block text-secondary">{{ $invoice->billing_period->translatedFormat('F Y') }} · Jatuh tempo {{ $invoice->due_date->format('d/m/Y') }}</small><small class="d-block text-secondary">{{ $invoice->house_label ?: $resident->house_address }}</small><strong class="d-block mt-2">Rp {{ number_format($invoice->total_amount,0,',','.') }}</strong></div></div></label>@empty<x-empty-state icon="check2-circle" title="Tidak ada tagihan yang dapat dibayar cash" />@endforelse
<x-payment-selection-summary />
</form><div class="mt-3">{{ $invoices->links() }}</div>
@else<x-empty-state icon="person-check" title="Pilih warga terlebih dahulu" />@endif
</div></div></div></div>
@endsection
