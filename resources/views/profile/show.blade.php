@extends('layouts.app')
@section('title', 'Profil - EnviroPay')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Informasi akun yang sedang digunakan.')
@section('content')
<div class="row g-4"><div class="col-lg-8"><div class="card"><div class="card-body p-4"><div class="d-flex align-items-center gap-3 mb-4"><span class="avatar fs-4" style="width:64px;height:64px">{{ strtoupper(substr($user->name,0,1)) }}</span><div><h4 class="mb-1">{{ $user->name }}</h4><span class="badge text-bg-success">{{ ucfirst($user->role) }}</span></div></div><div class="row g-4">
<div class="col-md-6"><small class="text-secondary d-block">Username</small><strong>{{ $user->username }}</strong></div><div class="col-md-6"><small class="text-secondary d-block">Email</small><strong>{{ $user->email }}</strong></div><div class="col-md-6"><small class="text-secondary d-block">Nomor telepon</small><strong>{{ $user->phone_number ?: '-' }}</strong></div><div class="col-md-6"><small class="text-secondary d-block">Status akun</small><strong>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</strong></div>@if($user->housing)<div class="col-md-6"><small class="text-secondary d-block">Perumahan</small><strong>{{ $user->housing->name }}</strong></div><div class="col-md-6"><small class="text-secondary d-block">Alamat rumah</small><strong>{{ $user->house_address ?: '-' }}</strong></div>@endif
</div></div></div></div></div>
@endsection
