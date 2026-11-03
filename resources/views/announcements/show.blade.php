@extends('layouts.app')
@section('title', 'Pengumuman - EnviroPay')
@section('page-title', $announcement->title)
@section('page-subtitle', 'Pengumuman perumahan Anda.')
@section('page-actions')<a class="btn btn-light" href="{{ route('announcements.index') }}">Kembali</a>@endsection
@section('content')
@if($announcement->status === 'draft')<div class="alert alert-warning">Draft ini hanya dapat dilihat pengurus. Warga belum menerima notifikasi.</div>@elseif($announcement->status === 'archived')<div class="alert alert-secondary">Pengumuman ini sudah diarsipkan dan tidak ditampilkan pada daftar pengumuman aktif.</div>@endif
<div class="card"><div class="card-body"><small class="text-secondary">{{ $announcement->author?->name }} · {{ ($announcement->published_at ?? $announcement->created_at)->format('d/m/Y H:i') }} WIB</small><div class="mt-4 text-break" style="white-space:pre-wrap">{{ $announcement->body }}</div></div></div>
@if(auth()->user()->isManager())<div class="d-flex gap-2 mt-4">@if($announcement->status === 'draft')<a class="btn btn-outline-primary" href="{{ route('pengurus.announcements.edit',$announcement) }}">Edit Draft</a><form method="POST" action="{{ route('pengurus.announcements.publish',$announcement) }}" data-confirm="Terbitkan dan kirim notifikasi kepada seluruh warga aktif? Isi pengumuman terbit tidak dapat diubah.">@csrf<button class="btn btn-primary">Terbitkan</button></form>@elseif($announcement->status === 'published')<form method="POST" action="{{ route('pengurus.announcements.archive',$announcement) }}" data-confirm="Arsipkan pengumuman ini?">@csrf<button class="btn btn-outline-secondary">Arsipkan</button></form>@endif</div>@endif
@endsection
