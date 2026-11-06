@extends('layouts.app')
@section('title', 'Detail Riwayat WhatsApp - EnviroPay')
@section('page-title', 'Detail Pengiriman WhatsApp')
@section('page-subtitle', $delivery->event_label.' · Catatan #'.$delivery->id)
@section('page-actions')<a class="btn btn-light" href="{{ route('pengurus.whatsapp-history.index', [...$filters, 'page'=>$page]) }}">Kembali ke Riwayat</a>@endsection
@section('content')
<div class="alert alert-{{ $delivery->status==='uncertain' ? 'warning' : 'light border' }} mb-4"><span class="badge text-bg-{{ $delivery->status_color }} me-2">{{ $delivery->status_label }}</span><strong>{{ $delivery->issue_label }}</strong><p class="mb-0 mt-2">{{ $delivery->action_hint }}</p></div>
<div class="row g-4 mb-4"><div class="col-lg-6"><div class="card h-100"><div class="card-header"><strong>Penerima dan Nomor Tujuan</strong></div><div class="card-body">
<dl class="row g-3 mb-0">
    <dt class="col-sm-5">Nama penerima</dt><dd class="col-sm-7 mb-0">{{ $delivery->recipient_name ?: ($delivery->user?->name ?: 'Warga tidak tersedia') }}</dd>
    <dt class="col-sm-5">Rumah</dt><dd class="col-sm-7 mb-0 text-break">{{ $delivery->recipient_house ?: ($delivery->invoice?->house_label ?: 'Belum tercatat') }}</dd>
    <dt class="col-sm-5">Nomor tujuan percobaan terakhir</dt><dd class="col-sm-7 mb-0">{{ $delivery->recipient_phone ?: 'Belum tercatat' }}</dd>
    <dt class="col-sm-5">Nomor akun saat ini</dt><dd class="col-sm-7 mb-0 text-break">{{ $delivery->user?->phone_number ?: 'Tidak tersedia' }}</dd>
</dl>
<p class="small text-secondary mt-3 mb-0">Nama dan rumah memakai catatan percobaan bila tersedia, atau data akun/tagihan saat ini. Nomor tujuan dicatat saat percobaan permintaan baru ke Fonnte. Riwayat lama mungkin belum memiliki nomor tujuan atau waktu permintaan; nomor akun saat ini tidak digunakan sebagai pengganti catatan pengiriman lama.</p>
@if($delivery->user?->isResident())<a class="btn btn-outline-primary mt-3" href="{{ route('pengurus.residents.edit',$delivery->user) }}">Periksa Data Warga</a>@endif
</div></div></div><div class="col-lg-6"><div class="card h-100"><div class="card-header"><strong>Waktu dan Pemrosesan</strong></div><div class="card-body"><dl class="row g-3 mb-0">
    <dt class="col-sm-5">Masuk antrean</dt><dd class="col-sm-7 mb-0">{{ $delivery->created_at->format('d/m/Y H:i:s') }} WIB</dd>
    <dt class="col-sm-5">Jumlah pemrosesan</dt><dd class="col-sm-7 mb-0">{{ $delivery->attempts }} kali<small class="d-block text-secondary">Termasuk pemeriksaan kelayakan; tidak selalu berarti pesan dikirim.</small></dd>
    <dt class="col-sm-5">Percobaan permintaan terakhir</dt><dd class="col-sm-7 mb-0">{{ $delivery->last_attempt_at ? $delivery->last_attempt_at->format('d/m/Y H:i:s').' WIB' : 'Belum tercatat' }}</dd>
    @if($delivery->accepted_at)<dt class="col-sm-5">Diterima Fonnte</dt><dd class="col-sm-7 mb-0">{{ $delivery->accepted_at->format('d/m/Y H:i:s') }} WIB</dd>@endif
    @if($delivery->status==='pending')<dt class="col-sm-5">Jadwal proses berikutnya</dt><dd class="col-sm-7 mb-0">{{ $delivery->available_at?->format('d/m/Y H:i:s') }} WIB<small class="d-block text-secondary">Diproses saat pengiriman aktif dan scheduler berjalan.</small></dd>@endif
    <dt class="col-sm-5">Catatan terakhir diperbarui</dt><dd class="col-sm-7 mb-0">{{ $delivery->updated_at->format('d/m/Y H:i:s') }} WIB</dd>
</dl></div></div></div></div>
<div class="card mb-4"><div class="card-header"><strong>Tagihan dan Kuitansi Terkait</strong></div><div class="card-body">
@if($matchingInvoice)
<div class="row g-3"><div class="col-md-6"><small class="text-secondary d-block">Tagihan #{{ $delivery->invoice->id }}</small><strong>{{ $delivery->invoice->title }}</strong><p class="small text-secondary mb-0">Periode {{ $delivery->invoice->billing_period->translatedFormat('F Y') }} · Jatuh tempo {{ $delivery->invoice->due_date->format('d/m/Y') }}</p></div><div class="col-md-3"><small class="text-secondary d-block">Nominal tagihan saat ini</small><strong>Rp {{ number_format($delivery->invoice->total_amount,2,',','.') }}</strong></div><div class="col-md-3"><small class="text-secondary d-block mb-1">Status tagihan saat ini</small><x-status-badge :status="$delivery->invoice->status" /></div></div>
@if($matchingReceipt)<p class="mt-3 mb-1">Kuitansi <strong>{{ $delivery->receipt->number }}</strong>@if($delivery->receipt->payment_batch_id) · Pembayaran gabungan @endif</p>@endif
<p class="small text-secondary mt-3 mb-0">Rincian tagihan di atas mengikuti data aplikasi saat ini. Riwayat pengiriman ini tidak menyimpan salinan isi pesan.</p>
<div class="d-flex flex-wrap gap-2 mt-3">
@if($matchingReceipt && $delivery->invoice->status===\App\Enums\InvoiceStatus::Verified)<a class="btn btn-outline-primary" href="{{ route('receipts.show',$delivery->invoice) }}">Lihat Kuitansi</a>
@elseif($delivery->invoice->status===\App\Enums\InvoiceStatus::Pending)<a class="btn btn-outline-primary" href="{{ route('pengurus.verifications.index',['invoice_id'=>$delivery->invoice->id]) }}">Lihat Verifikasi</a>
@else<a class="btn btn-outline-primary" href="{{ route('pengurus.invoices.index',['search'=>$delivery->invoice->title,'period'=>$delivery->invoice->billing_period->format('Y-m')]) }}">Lihat Daftar Tagihan</a>@endif
</div>
@else<p class="text-secondary mb-0">Tagihan tidak tersedia atau tidak lagi sesuai dengan penerima pada riwayat ini.</p>@endif
</div></div>
<div class="card"><div class="card-body"><h2 class="h6">Periksa Hasil di Fonnte</h2><p class="small text-secondary">Diterima Fonnte belum menjadi bukti pesan sampai atau dibaca. Periksa waktu, nomor tujuan dan riwayat Fonnte jika hasil tidak pasti. Halaman ini hanya menampilkan catatan dan tidak memicu pengiriman.</p><a class="btn btn-outline-primary" href="https://fonnte.com/" target="_blank" rel="noopener noreferrer">Buka Fonnte</a></div></div>
@endsection
