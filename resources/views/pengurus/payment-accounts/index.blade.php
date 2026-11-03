@extends('layouts.app')
@section('title', 'Rekening Pembayaran - EnviroPay')
@section('page-title', 'Rekening Pembayaran')
@section('page-subtitle', 'Simpan beberapa rekening dan pilih rekening tujuan saat membuat tagihan.')
@section('page-actions')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccount"><i class="bi bi-plus-lg me-1"></i>Tambah Rekening</button>@endsection

@section('content')
<div class="card">
    <div class="table-responsive"><table class="table table-mobile-cards"><thead><tr><th>Bank</th><th>Nomor Rekening</th><th>Nama Pemilik</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
    @forelse($accounts as $account)
        <tr>
            <td class="fw-semibold">{{ $account->bank_name }}</td>
            <td>{{ $account->account_number }}</td>
            <td>{{ $account->account_holder }}</td>
            <td><span class="badge text-bg-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAccount-{{ $account->id }}">Edit</button>
                @if($account->is_active)<form class="d-inline" method="POST" action="{{ route('pengurus.payment-accounts.destroy', $account) }}" data-confirm="Nonaktifkan rekening ini? Invoice lama tidak akan berubah.">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Nonaktifkan</button></form>@endif
                <div class="modal fade text-start" id="editAccount-{{ $account->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('pengurus.payment-accounts.update', $account) }}">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit Rekening</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body vstack gap-3"><div><label class="form-label">Nama bank</label><input class="form-control" name="bank_name" value="{{ $account->bank_name }}" required></div><div><label class="form-label">Nomor rekening</label><input class="form-control" name="account_number" value="{{ $account->account_number }}" required></div><div><label class="form-label">Nama pemilik rekening</label><input class="form-control" name="account_holder" value="{{ $account->account_holder }}" required></div><div><label class="form-label">Status</label><select class="form-select" name="is_active"><option value="1" @selected($account->is_active)>Aktif</option><option value="0" @selected(!$account->is_active)>Nonaktif</option></select></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div></div>
            </td>
        </tr>
    @empty
        <tr><td colspan="5"><x-empty-state icon="bank" title="Belum ada rekening" message="Tambahkan minimal satu rekening sebelum membuat tagihan." /></td></tr>
    @endforelse
    </tbody></table></div>
    @if($accounts->hasPages())<div class="card-footer bg-white">{{ $accounts->links() }}</div>@endif
</div>

<div class="modal fade" id="addAccount" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('pengurus.payment-accounts.store') }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Rekening</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body vstack gap-3"><div><label class="form-label">Nama bank</label><input class="form-control" name="bank_name" placeholder="BCA, BRI, Mandiri, dll." required></div><div><label class="form-label">Nomor rekening</label><input class="form-control" name="account_number" required></div><div><label class="form-label">Nama pemilik rekening</label><input class="form-control" name="account_holder" required></div><input type="hidden" name="is_active" value="1"></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form></div></div></div>
@endsection
