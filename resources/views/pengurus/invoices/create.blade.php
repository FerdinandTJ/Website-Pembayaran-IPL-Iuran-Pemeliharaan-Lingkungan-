@extends('layouts.app')
@section('title', 'Buat Tagihan - EnviroPay')
@section('page-title', 'Buat Tagihan')
@section('page-subtitle', 'Kirim kepada seluruh warga aktif atau pilih penerima tertentu.')

@section('content')
<form method="POST" action="{{ route('pengurus.invoices.store') }}" id="invoice-form">
    @csrf
    <div class="alert alert-light border"><input type="hidden" name="apply_house_tariff" value="0"><label class="form-check mb-1"><input class="form-check-input" type="checkbox" name="apply_house_tariff" value="1" @checked(old('apply_house_tariff',true))><span class="form-check-label">Terapkan aturan tarif rumah kosong</span></label><small>Nominal khusus menggantikan komponen dengan satu komponen tarif rumah kosong; bebas iuran dilewati. Matikan untuk tagihan khusus di luar IPL. Akun yang bukan penanggung jawab rumah tidak menerima tagihan baru.</small></div>
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><strong>Informasi Tagihan</strong></div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Judul tagihan</label>
                            <input class="form-control" name="title" value="{{ old('title') }}" placeholder="Contoh: IPL Bulanan atau Perbaikan Jalan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Periode</label>
                            <input type="month" class="form-control" name="billing_period" value="{{ old('billing_period', now()->format('Y-m')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jatuh tempo</label>
                            <input type="date" class="form-control" name="due_date" value="{{ old('due_date', now()->addMonth()->day(10)->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Komponen Tagihan</strong>
                    <button type="button" class="btn btn-sm btn-soft-primary" data-add-component><i class="bi bi-plus-lg"></i> Tambah komponen</button>
                </div>
                <div class="card-body p-4">
                    <div data-components data-initial-components="{{ json_encode(old('components', [])) }}" class="vstack gap-2"></div>
                    <template id="component-template">
                        <div class="row g-2 align-items-end component-row">
                            <div class="col-md"><label class="form-label">Nama komponen</label><input class="form-control" data-field="name" required placeholder="Contoh: Kebersihan"></div>
                            <div class="col-md-4"><label class="form-label">Nominal</label><input type="text" inputmode="numeric" data-rupiah min="1000" step="1" class="form-control" data-field="amount" required placeholder="Rp 50.000"></div>
                            <div class="col-auto"><button type="button" class="btn btn-outline-danger" data-remove-component aria-label="Hapus"><i class="bi bi-trash"></i></button></div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div><small class="text-secondary">Total per warga</small><div class="fs-4 fw-bold" data-invoice-total>Rp 0</div></div>
                <div class="d-flex gap-2"><a class="btn btn-light" href="{{ route('pengurus.invoices.index') }}">Batal</a><button class="btn btn-primary"><i class="bi bi-send me-1"></i>Kirim Tagihan</button></div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card position-sticky" style="top: 94px">
                <div class="card-header"><strong>Penerima Tagihan</strong></div>
                <div class="card-body">
                    <label class="border rounded-3 p-3 d-flex gap-3 mb-2 w-100">
                        <input class="form-check-input mt-1" type="radio" name="recipient_mode" value="all" data-recipient-mode @checked(old('recipient_mode', 'all') === 'all')>
                        <span><span class="fw-semibold d-block">Kirim ke semua</span><small class="text-secondary">Seluruh {{ $residents->count() }} warga aktif menerima tagihan.</small></span>
                    </label>
                    <label class="border rounded-3 p-3 d-flex gap-3 mb-3 w-100">
                        <input class="form-check-input mt-1" type="radio" name="recipient_mode" value="selected" data-recipient-mode @checked(old('recipient_mode') === 'selected')>
                        <span><span class="fw-semibold d-block">Pilih warga tertentu</span><small class="text-secondary">Tagihan hanya dikirim kepada warga yang dicentang.</small></span>
                    </label>

                    <div data-recipient-list class="border rounded-3 overflow-hidden">
                        <div class="p-2 border-bottom bg-light">
                            <label class="form-check mb-0"><input class="form-check-input" type="checkbox" data-select-all><span class="form-check-label small fw-semibold">Pilih semua warga</span></label>
                        </div>
                        <div style="max-height: 330px; overflow-y: auto">
                            @forelse($residents as $resident)
                                <label class="d-flex gap-2 p-2 border-bottom mb-0">
                                    <input class="form-check-input" type="checkbox" name="resident_ids[]" value="{{ $resident->id }}" data-resident-checkbox @checked(in_array($resident->id, old('resident_ids', [])))>
                                    <span><span class="small fw-semibold d-block">{{ $resident->name }}</span><small class="text-secondary">{{ $resident->house?->code }} · {{ Str::limit($resident->house_address, 35) }}{{ $resident->house && $resident->house->responsible_user_id !== $resident->id ? ' (bukan PJ IPL)' : '' }}</small></span>
                                </label>
                            @empty
                                <div class="p-3 text-secondary small">Belum ada warga aktif.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
