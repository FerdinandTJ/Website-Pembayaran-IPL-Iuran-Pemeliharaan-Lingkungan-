@extends('layouts.app')
@section('title', 'Detail Tagihan - EnviroPay')
@section('page-title', $invoice->title)
@section('page-subtitle', 'Tagihan #'.$invoice->id.' untuk periode '.$invoice->billing_period->translatedFormat('F Y'))
@section('page-actions')@if($invoice->status->value === 'verified')<a class="btn btn-primary" href="{{ route('receipts.show',$invoice) }}">Kuitansi / Cetak</a> @endif <a class="btn btn-light" href="{{ route('warga.invoices.index') }}"><i class="bi bi-arrow-left me-1"></i>Kembali</a>@endsection

@section('content')
@if($invoice->status->value === 'rejected')
    <div class="alert alert-danger"><div class="fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>Bukti pembayaran ditolak</div><div class="mt-1">{{ $invoice->rejection_reason }}</div><small>Silakan unggah bukti baru yang lebih jelas atau sesuai.</small></div>
@elseif($invoice->status->value === 'pending')
    <div class="alert alert-info"><i class="bi bi-hourglass-split me-1"></i>Bukti sudah diterima dan sedang diperiksa pengurus.</div>
@elseif($invoice->status->value === 'verified')
    <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>Pembayaran {{ $invoice->payment_method === 'cash' ? 'cash diterima' : 'transfer diverifikasi' }} pada {{ $invoice->verified_at?->format('d M Y H:i') }}.</div>
@endif

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between"><strong>Rincian Tagihan</strong><x-status-badge :status="$invoice->status" /></div>
            <div class="card-body">
                <p class="small text-secondary">Rumah: {{ $invoice->house_label ?: auth()->user()->house_address }}</p>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><small class="text-secondary d-block">Periode</small><strong>{{ $invoice->billing_period->translatedFormat('F Y') }}</strong></div>
                    <div class="col-md-6"><small class="text-secondary d-block">Jatuh tempo</small><strong>{{ $invoice->due_date->format('d M Y') }}</strong></div>
                </div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Komponen</th><th class="text-end">Nominal</th></tr></thead><tbody>
                    @foreach($invoice->components as $component)<tr><td>{{ $component->name }}</td><td class="text-end">Rp {{ number_format($component->amount, 0, ',', '.') }}</td></tr>@endforeach
                    <tr><td class="fw-bold">Total</td><td class="text-end fw-bold fs-5">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td></tr>
                </tbody></table></div>
            </div>
        </div>

        @if($invoice->payment_proof_path)
            <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><strong>Bukti Pembayaran</strong><a target="_blank" class="btn btn-sm btn-outline-primary" href="{{ route('invoices.proof', $invoice) }}">Buka ukuran penuh</a></div><div class="card-body text-center"><img class="proof-preview" src="{{ route('invoices.proof', $invoice) }}" alt="Bukti pembayaran"></div></div>
        @endif
    </div>

    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header"><strong>Daftar Rekening Perumahan</strong></div>
            <div class="card-body p-0">
                @forelse($invoice->housing->paymentAccounts as $account)
                    <div class="p-3 border-bottom"><div class="fw-semibold">{{ $account->bank_name }}</div><div class="fs-5 fw-bold text-success">{{ $account->account_number }}</div><small class="text-secondary">a.n. {{ $account->account_holder }}</small></div>
                @empty
                    <x-empty-state icon="bank" title="Belum ada rekening aktif" message="Hubungi pengurus sebelum melakukan transfer." />
                @endforelse
            </div>
        </div>

        @if($invoice->canReceiveProof())
            <div class="card"><div class="card-header"><strong>{{ $invoice->status->value === 'rejected' ? 'Upload Ulang Bukti' : 'Kirim Bukti Pembayaran' }}</strong></div><div class="card-body"><p class="small text-secondary">Transfer ke salah satu rekening aktif di atas, lalu unggah bukti yang dapat dibaca dengan jelas.</p><form method="POST" enctype="multipart/form-data" action="{{ route('warga.invoices.payment', $invoice) }}" class="vstack gap-3">@csrf<div><label class="form-label">Bukti transfer</label><input type="file" class="form-control" name="proof" accept="image/jpeg,image/png,image/webp" required><small class="text-secondary">JPG, PNG, atau WebP. Maksimal 5 MB.</small></div><button class="btn btn-primary">Kirim Bukti</button></form></div></div>
        @else
            <div class="card"><div class="card-body"><h6><i class="bi bi-shield-check me-1"></i>Status Pembayaran</h6><p class="small text-secondary mb-0">{{ $invoice->status->value === 'pending' ? 'Pengurus sedang memeriksa bukti pembayaran Anda.' : 'Tagihan ini sudah selesai dan tidak dapat diubah.' }}</p></div></div>
        @endif
    </div>
</div>
@endsection
