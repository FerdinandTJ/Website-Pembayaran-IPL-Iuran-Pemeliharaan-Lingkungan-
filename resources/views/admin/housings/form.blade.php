@extends('layouts.app')
@section('title', ($housing->exists ? 'Edit' : 'Tambah').' Perumahan - EnviroPay')
@section('page-title', $housing->exists ? 'Edit Perumahan' : 'Tambah Perumahan')
@section('page-subtitle', 'Data ini menjadi batas kepemilikan warga, tagihan, dan arus kas.')
@section('content')
<div class="card" style="max-width:760px"><div class="card-body p-4"><form method="POST" action="{{ $housing->exists ? route('admin.housings.update',$housing) : route('admin.housings.store') }}" class="row g-3">@csrf @if($housing->exists) @method('PUT') @endif
<div class="col-12"><label class="form-label">Nama perumahan</label><input class="form-control" name="name" value="{{ old('name',$housing->name) }}" required></div><div class="col-12"><label class="form-label">Alamat lengkap</label><textarea class="form-control" name="address" rows="4" required>{{ old('address',$housing->address) }}</textarea></div><div class="col-md-6"><label class="form-label">Nomor telepon</label><input class="form-control" name="phone_number" value="{{ old('phone_number',$housing->phone_number) }}"></div><div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="is_active"><option value="1" @selected(old('is_active',$housing->is_active ?? true))>Aktif</option><option value="0" @selected(!old('is_active',$housing->is_active ?? true))>Nonaktif</option></select></div><div class="col-12 d-flex gap-2"><button class="btn btn-primary">Simpan</button><a class="btn btn-light" href="{{ route('admin.housings.index') }}">Batal</a></div></form></div></div>
@endsection
