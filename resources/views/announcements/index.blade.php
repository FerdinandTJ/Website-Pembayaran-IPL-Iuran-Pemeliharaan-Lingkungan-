@extends('layouts.app')
@section('title', 'Pengumuman - EnviroPay')
@section('page-title', 'Pengumuman Perumahan')
@section('page-subtitle', 'Informasi terbaru untuk warga di perumahan Anda.')
@section('page-actions')@if(auth()->user()->isManager())<a class="btn btn-primary" href="{{ route('pengurus.announcements.create') }}">Buat Pengumuman</a>@endif @endsection
@section('content')
<div class="row g-3">@forelse($announcements as $announcement)<div class="col-12"><div class="card"><div class="card-body"><div class="d-flex justify-content-between gap-3"><h5><a class="text-decoration-none" href="{{ route('announcements.show',$announcement) }}">{{ $announcement->title }}</a></h5><span class="badge align-self-start text-bg-{{ $announcement->status === 'published' ? 'success' : 'secondary' }}">{{ ['draft'=>'Draft','published'=>'Terbit','archived'=>'Arsip'][$announcement->status] }}</span></div><p class="text-secondary text-break">{{ Str::limit($announcement->body,200) }}</p><small class="text-secondary">{{ $announcement->author?->name }} · {{ ($announcement->published_at ?? $announcement->created_at)->format('d/m/Y H:i') }} WIB</small></div></div></div>@empty<div class="col-12"><div class="card"><x-empty-state icon="megaphone" title="Belum ada pengumuman" /></div></div>@endforelse</div><div class="mt-3">{{ $announcements->links() }}</div>
@endsection
