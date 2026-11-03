@extends('layouts.app')
@section('title', 'Draft Pengumuman - EnviroPay')
@section('page-title', $announcement->exists ? 'Edit Draft Pengumuman' : 'Buat Pengumuman')
@section('page-subtitle', 'Simpan draft, periksa, lalu terbitkan untuk seluruh warga aktif.')
@section('content')
<div class="card"><div class="card-body"><form method="POST" action="{{ $announcement->exists ? route('pengurus.announcements.update',$announcement) : route('pengurus.announcements.store') }}" class="vstack gap-3">@csrf @if($announcement->exists) @method('PUT') @endif
<div><label for="announcement-title" class="form-label">Judul</label><input id="announcement-title" name="title" class="form-control" maxlength="255" value="{{ old('title',$announcement->title) }}" required></div>
<div><label for="announcement-body" class="form-label">Isi pengumuman</label><textarea id="announcement-body" name="body" class="form-control" rows="12" maxlength="10000" required>{{ old('body',$announcement->body) }}</textarea><small class="text-secondary">Teks biasa, maksimal 10.000 karakter. Tidak mendukung HTML atau lampiran.</small></div>
<div class="d-flex gap-2"><button class="btn btn-primary">Simpan Draft</button><a class="btn btn-light" href="{{ route('announcements.index') }}">Kembali</a></div></form></div></div>
@endsection
