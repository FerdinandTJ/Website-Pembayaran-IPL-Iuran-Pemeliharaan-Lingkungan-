@extends('layouts.app')

@section('title', 'Tambah Pengurus')
@section('page-title', 'Tambah Pengurus Baru')
@section('page-subtitle', 'Daftarkan pengurus baru untuk mengelola perumahan')
@section('dashboard-url', url('/admin/dashboard'))

@section('nav-items')
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/admin/dashboard') }}">
            <i class="fas fa-tachometer-alt me-1"></i>
            Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ url('/admin/register') }}">
            <i class="fas fa-user-plus me-1"></i>
            Tambah Pengurus
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/admin/profile') }}">
            <i class="fas fa-user me-1"></i>
            Profil
        </a>
    </li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        Form Registrasi Pengurus
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="fas fa-info-circle me-3 fs-4"></i>
                        <div>
                            <strong>Informasi:</strong> Pengurus yang terdaftar akan memiliki akses untuk mengelola warga, 
                            membuat tagihan, dan memverifikasi pembayaran di perumahan yang ditentukan.
                        </div>
                    </div>

                    <form action="/admin/register" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-user me-1"></i>
                                    Informasi Personal
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-id-card me-1"></i>
                                        Nama Lengkap <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name" id="name" class="form-control" 
                                           placeholder="Masukkan nama lengkap" required>
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-at me-1"></i>
                                        Username <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="username" id="username" class="form-control" 
                                           placeholder="Masukkan username unik" required>
                                    <div class="form-text">Username akan digunakan untuk login</div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" name="email" id="email" class="form-control" 
                                           placeholder="contoh@email.com" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>
                                        Password <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" name="password" id="password" class="form-control" 
                                           placeholder="Masukkan password yang kuat" required>
                                    <div class="form-text">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Gunakan kombinasi huruf, angka, dan simbol
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-home me-1"></i>
                                    Informasi Perumahan
                                </h6>

                                <div class="mb-3">
                                    <label for="housing_name" class="form-label">
                                        <i class="fas fa-building me-1"></i>
                                        Nama Perumahan <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="housing_name" id="housing_name" class="form-control" 
                                           placeholder="Contoh: Perumahan Griya Asri" required>
                                </div>

                                <div class="mb-3">
                                    <label for="housing_address" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Alamat Perumahan
                                    </label>
                                    <textarea name="housing_address" id="housing_address" class="form-control" rows="3"
                                              placeholder="Alamat lengkap perumahan"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="phone_number" class="form-label">
                                        <i class="fas fa-phone me-1"></i>
                                        Nomor Telepon <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" name="phone_number" id="phone_number" class="form-control" 
                                           placeholder="08xx-xxxx-xxxx" required>
                                    <div class="form-text">Nomor telepon yang dapat dihubungi</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Perhatian:</strong> 
                                    <ul class="mb-0 mt-2">
                                        <li>Pastikan data yang dimasukkan benar</li>
                                        <li>Email akan digunakan untuk notifikasi sistem</li>
                                        <li>Password tidak dapat diubah setelah registrasi</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Hak Akses Pengurus:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Mengelola data warga</li>
                                        <li>Membuat dan mengirim tagihan</li>
                                        <li>Verifikasi pembayaran</li>
                                        <li>Mengelola cash flow perumahan</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ url('/admin/dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                Kembali
                            </a>
                            <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>
                                Daftarkan Pengurus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
