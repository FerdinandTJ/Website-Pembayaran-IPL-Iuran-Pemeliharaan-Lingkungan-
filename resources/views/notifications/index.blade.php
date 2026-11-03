@extends('layouts.app')
@section('title', 'Notifikasi - EnviroPay')
@section('page-title', 'Notifikasi')
@section('page-subtitle', 'Informasi tagihan dan perkembangan pembayaran Anda.')
@section('page-actions')
<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="btn btn-outline-primary" @disabled($unreadCount === 0)>Tandai semua dibaca</button></form>
@endsection
@section('content')
<nav class="nav nav-pills gap-2 mb-4" aria-label="Filter notifikasi">
<a class="nav-link {{ request('filter') !== 'unread' ? 'active' : '' }}" href="{{ route('notifications.index') }}">Semua</a>
<a class="nav-link {{ request('filter') === 'unread' ? 'active' : '' }}" href="{{ route('notifications.index', ['filter' => 'unread']) }}">Belum dibaca ({{ $unreadCount }})</a>
</nav>
<div class="card"><div class="list-group list-group-flush rounded-4">
@forelse($notifications as $notification)
<div class="list-group-item p-4 {{ $notification->read_at ? '' : 'notification-unread' }}">
    <div class="d-flex flex-wrap justify-content-between gap-2"><strong>{{ $notification->data['title'] }}</strong><small class="text-secondary">{{ $notification->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i') }} WIB</small></div>
    <p class="my-2 text-break">{{ $notification->data['message'] }}</p>
    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">{{ $notification->read_at ? 'Lihat detail' : 'Baca dan lihat detail' }}</button>@unless($notification->read_at)<span class="badge text-bg-success ms-2">Baru</span>@endunless</form>
</div>
@empty
<x-empty-state icon="bell" title="Belum ada notifikasi" message="Informasi tagihan dan pembayaran akan muncul di sini." />
@endforelse
</div><div class="card-footer bg-white">{{ $notifications->links() }}</div></div>
@endsection
