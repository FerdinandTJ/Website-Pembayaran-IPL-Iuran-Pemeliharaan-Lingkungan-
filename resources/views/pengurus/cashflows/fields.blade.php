@php($prefix = $cashflow->exists ? 'cashflow-'.$cashflow->id : 'cashflow-new')
<div>
    <label class="form-label" for="{{ $prefix }}-name">Nama transaksi</label>
    <input id="{{ $prefix }}-name" class="form-control" name="name" value="{{ $cashflow->exists ? $cashflow->name : old('name') }}" maxlength="255" required>
</div>
<div>
    <label class="form-label" for="{{ $prefix }}-type">Jenis transaksi</label>
    <select id="{{ $prefix }}-type" class="form-select" name="type">
        <option value="income" @selected(($cashflow->exists ? $cashflow->type : old('type', 'income')) === 'income')>Pemasukan</option>
        <option value="expense" @selected(($cashflow->exists ? $cashflow->type : old('type')) === 'expense')>Pengeluaran</option>
    </select>
</div>
<div>
    <label class="form-label" for="{{ $prefix }}-category">Kategori</label>
    <select id="{{ $prefix }}-category" class="form-select" name="category" required>
        @foreach($manualCategories as $category)
        <option value="{{ $category->value }}" @selected(($cashflow->exists ? $cashflow->category->value : old('category', 'uncategorized')) === $category->value)>{{ $category->label() }}</option>
        @endforeach
    </select>
    <small class="text-secondary">Pilih sesuai tujuan transaksi agar laporan per kategori mudah ditelusuri.</small>
</div>
<div>
    <label class="form-label" for="{{ $prefix }}-amount">Nominal</label>
    <input id="{{ $prefix }}-amount" type="text" inputmode="decimal" data-rupiah data-rupiah-decimals="2" class="form-control" name="total_cashflow" value="{{ $cashflow->exists ? $cashflow->total_cashflow : old('total_cashflow') }}" min="1" required>
</div>

<div><label class="form-label" for="{{ $prefix }}-date">Tanggal transaksi</label><input id="{{ $prefix }}-date" type="date" class="form-control" name="transaction_date" value="{{ $cashflow->exists ? $cashflow->accounting_date?->toDateString() : old('transaction_date', today('Asia/Jakarta')->toDateString()) }}" max="{{ today('Asia/Jakarta')->toDateString() }}" required><small class="text-secondary">Tanggal uang diterima/dikeluarkan. Waktu pencatatan tetap direkam otomatis oleh server.</small></div>
<div><label class="form-label" for="{{ $prefix }}-attachment">Nota / bukti transaksi (opsional)</label><input id="{{ $prefix }}-attachment" type="file" class="form-control" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf"><small class="text-secondary">JPG, PNG, WebP atau PDF maksimal 5 MB; hanya pengurus perumahan dapat mengunduh.</small></div>
@if($cashflow->exists && $cashflow->attachment_path)<label class="form-check"><input type="checkbox" class="form-check-input" name="remove_attachment" value="1"><span class="form-check-label">Hapus lampiran lama</span></label>@endif
