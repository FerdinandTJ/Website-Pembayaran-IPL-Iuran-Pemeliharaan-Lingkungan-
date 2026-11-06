@extends('layouts.app')
@section('title', 'Riwayat WhatsApp - EnviroPay')
@section('page-title', 'Riwayat WhatsApp')
@section('page-subtitle', 'Pantau pengingat tagihan dan kuitansi, serta periksa kendala pengiriman warga.')
@section('page-actions')<a class="btn btn-outline-primary" href="https://fonnte.com/" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i>Buka Fonnte</a>@endsection
@section('content')
@if(!$configured)<div class="alert alert-warning mb-4">Pengiriman WhatsApp belum aktif. Riwayat tetap dapat dibaca; hubungi administrator untuk memeriksa pengaturan pengiriman.</div>@endif
<form class="filter-bar row g-2 mb-4" method="GET" action="{{ route('pengurus.whatsapp-history.index') }}">
    <div class="col-md-3"><label class="form-label" for="wa-from">Dari tanggal antrean</label><input id="wa-from" class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] }}"></div>
    <div class="col-md-3"><label class="form-label" for="wa-to">Sampai tanggal</label><input id="wa-to" class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] }}"></div>
    <div class="col-md-3"><label class="form-label" for="wa-event">Jenis pesan</label><select id="wa-event" class="form-select" name="event"><option value="">Semua jenis pesan</option>@foreach(\App\Models\WhatsAppDelivery::EVENTS as $event=>$label)<option value="{{ $event }}" @selected(($filters['event'] ?? '')===$event)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label" for="wa-status">Status</label><select id="wa-status" class="form-select" name="status"><option value="">Semua status</option>@foreach(\App\Models\WhatsAppDelivery::STATUSES as $status=>$label)<option value="{{ $status }}" @selected(($filters['status'] ?? '')===$status)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label" for="wa-search">Nama warga atau rumah</label><input id="wa-search" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama warga atau rumah pada tagihan..."></div>
    <div class="col-auto align-self-end d-flex gap-2"><button class="btn btn-primary">Tampilkan</button><a class="btn btn-light" href="{{ route('pengurus.whatsapp-history.index') }}">Reset</a></div>
    <div class="col-12"><small class="text-secondary">Tanggal mengikuti waktu pesan masuk antrean (WIB). Kosongkan kedua tanggal untuk seluruh riwayat.</small></div>
</form>
<div class="row g-3 mb-4">@foreach(\App\Models\WhatsAppDelivery::STATUSES as $status=>$label)<div class="col-6 col-lg-4 col-xxl-2"><x-stat-card :label="$label" :value="$summary[$status]" :icon="['pending'=>'hourglass-split','processing'=>'arrow-repeat','accepted'=>'check2-circle','failed'=>'exclamation-circle','skipped'=>'skip-forward','uncertain'=>'question-circle'][$status]" /></div>@endforeach</div>
<div class="alert alert-light border mb-4">Ringkasan mengikuti seluruh filter dan mencakup semua halaman. <strong>Diterima Fonnte belum membuktikan pesan sampai atau dibaca warga.</strong> Untuk hasil tidak pasti, periksa riwayat pada dashboard Fonnte terlebih dahulu.</div>
<div class="card"><div class="card-header d-flex flex-wrap justify-content-between align-items-center"><strong>Daftar Pengiriman</strong><span class="small text-secondary">{{ $deliveries->total() }} pesan sesuai filter</span></div><div class="table-responsive"><table class="table"><thead><tr><th>Masuk Antrean</th><th>Warga / Rumah</th><th>Jenis Pesan</th><th>Status</th><th>Pemrosesan</th><th>Aksi</th></tr></thead><tbody>
@forelse($deliveries as $delivery)<tr>
    <td class="text-nowrap">{{ $delivery->created_at->format('d/m/Y') }}<small class="d-block text-secondary">{{ $delivery->created_at->format('H:i') }} WIB</small></td>
    <td><strong>{{ $delivery->recipient_name ?: ($delivery->user?->name ?: 'Warga tidak tersedia') }}</strong><small class="d-block text-secondary">{{ $delivery->recipient_house ?: ($delivery->invoice?->house_label ?: 'Rumah belum tercatat') }}</small></td>
    <td>{{ $delivery->event_label }}<small class="d-block text-secondary">{{ $delivery->invoice?->title ?: 'Tagihan tidak tersedia' }}</small></td>
    <td><span class="badge text-bg-{{ $delivery->status_color }}">{{ $delivery->status_label }}</span>@if($delivery->last_error)<small class="d-block text-secondary">{{ $delivery->issue_label }}</small>@endif</td>
    <td>{{ $delivery->attempts }} kali<small class="d-block text-secondary">{{ $delivery->last_attempt_at ? 'Percobaan '.$delivery->last_attempt_at->format('d/m H:i').' WIB' : 'Belum ada waktu permintaan tercatat' }}</small></td>
    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('pengurus.whatsapp-history.show', ['whatsAppDelivery'=>$delivery->id, ...$filters, 'page'=>$deliveries->currentPage()]) }}">Detail</a></td>
</tr>@empty<tr><td colspan="6"><x-empty-state icon="whatsapp" title="Belum ada riwayat yang sesuai filter" message="Coba ubah tanggal, jenis pesan, status, atau pencarian. Pesan baru muncul setelah masuk antrean pengiriman." /></td></tr>@endforelse
</tbody></table></div>@if($deliveries->hasPages())<div class="card-footer bg-white">{{ $deliveries->links() }}</div>@endif</div>
@endsection
