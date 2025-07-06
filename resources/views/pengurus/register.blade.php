@extends('layouts.app')

@section('title', 'Tambah Warga Baru')
@section('page-title', 'Tambah Warga Baru')
@section('page-subtitle', 'Daftarkan warga baru untuk akses sistem pembayaran iuran')
@section('dashboard-url', url('/pengurus/dashboard'))

@section('nav-items')
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/pengurus/dashboard') }}">
            <i class="fas fa-tachometer-alt me-1"></i>
            Dashboard
        </a>
    </li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-users me-1"></i>
            Kelola Warga
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item active" href="{{ url('/pengurus/register') }}">
                <i class="fas fa-user-plus me-1"></i>
                Tambah Warga
            </a></li>
            <li><a class="dropdown-item" href="{{ url('/pengurus/members') }}">
                <i class="fas fa-list me-1"></i>
                Daftar Warga
            </a></li>
        </ul>
    </li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-file-invoice me-1"></i>
            Tagihan
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ url('/pengurus/invoice') }}">
                <i class="fas fa-plus me-1"></i>
                Buat Tagihan
            </a></li>
            <li><a class="dropdown-item" href="{{ url('/pengurus/invoice/verification') }}">
                <i class="fas fa-check-double me-1"></i>
                Verifikasi Pembayaran
            </a></li>
        </ul>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/pengurus/cashflow') }}">
            <i class="fas fa-chart-line me-1"></i>
            Cash Flow
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/pengurus/profile') }}">
            <i class="fas fa-user me-1"></i>
            Profil
        </a>
    </li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        Form Registrasi Warga
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Success Message -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Error Messages -->
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Validation Errors -->
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="fas fa-info-circle me-3 fs-4"></i>
                        <div>
                            <strong>Informasi:</strong> Warga yang terdaftar akan mendapatkan akses untuk melihat tagihan, 
                            melakukan pembayaran, dan memantau riwayat pembayaran iuran perumahan.
                        </div>
                    </div>

                    <form action="{{ url('/pengurus/register') }}" method="POST">
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
                                    <input type="text" name="name" id="name" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           placeholder="Masukkan nama lengkap warga" 
                                           value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-at me-1"></i>
                                        Username <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="username" id="username" 
                                           class="form-control @error('username') is-invalid @enderror" 
                                           placeholder="Username untuk login" 
                                           value="{{ old('username') }}" required>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Username harus unik dan mudah diingat</div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" name="email" id="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           placeholder="contoh@email.com" 
                                           value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Email akan digunakan untuk notifikasi tagihan</div>
                                </div>

                                <div class="mb-3">
                                    <label for="phone_number" class="form-label">
                                        <i class="fas fa-phone me-1"></i>
                                        Nomor Telepon <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" name="phone_number" id="phone_number" 
                                           class="form-control @error('phone_number') is-invalid @enderror" 
                                           placeholder="08xx-xxxx-xxxx" 
                                           value="{{ old('phone_number') }}" required>
                                    @error('phone_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-home me-1"></i>
                                    Informasi Tempat Tinggal
                                </h6>

                                <div class="mb-3">
                                    <label for="house_address" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Alamat Rumah <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="house_address" id="house_address" 
                                              class="form-control @error('house_address') is-invalid @enderror" rows="4"
                                              placeholder="Alamat lengkap rumah dalam perumahan" required>{{ old('house_address') }}</textarea>
                                    @error('house_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Contoh: Blok A No. 15, RT 02/RW 05
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>
                                        Password <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" name="password" id="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           placeholder="Password untuk akses akun" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Minimal 8 karakter, kombinasi huruf dan angka
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Info Cards -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Akses Warga:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Melihat tagihan bulanan</li>
                                        <li>Upload bukti pembayaran</li>
                                        <li>Cek riwayat pembayaran</li>
                                        <li>Update profil pribadi</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Perhatian:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Pastikan data yang dimasukkan benar</li>
                                        <li>Email akan digunakan untuk notifikasi</li>
                                        <li>Nomor telepon untuk komunikasi darurat</li>
                                        <li>Password tidak dapat diubah setelah dibuat</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ url('/pengurus/members') }}" class="btn btn-secondary">
                                <i class="fas fa-list me-1"></i>
                                Lihat Daftar Warga
                            </a>
                            <button type="submit" name="submit" class="btn btn-success btn-lg" id="submitBtn">
                                <i class="fas fa-user-plus me-2"></i>
                                Daftarkan Warga
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Registration Stats -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Statistik Pendaftaran
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <h5 class="text-primary mb-1">
                                    <i class="fas fa-users"></i>
                                </h5>
                                <small class="text-muted">Total Warga</small>
                                <p class="mb-0 fw-bold">-</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <h5 class="text-success mb-1">
                                    <i class="fas fa-user-check"></i>
                                </h5>
                                <small class="text-muted">Aktif Bulan Ini</small>
                                <p class="mb-0 fw-bold">-</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <h5 class="text-info mb-1">
                                <i class="fas fa-calendar-plus"></i>
                            </h5>
                            <small class="text-muted">Pendaftar Baru</small>
                            <p class="mb-0 fw-bold">-</p>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Statistik akan diperbarui setelah ada data warga
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('submitBtn');
    
    // Form submission handling
    form.addEventListener('submit', function(e) {
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mendaftarkan...';
        
        // Validate required fields
        const requiredFields = ['name', 'username', 'email', 'phone_number', 'house_address', 'password'];
        let isValid = true;
        
        requiredFields.forEach(field => {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        // Validate email format
        const emailInput = document.getElementById('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailInput.value && !emailRegex.test(emailInput.value)) {
            isValid = false;
            emailInput.classList.add('is-invalid');
        }
        
        // Validate password length
        const passwordInput = document.getElementById('password');
        if (passwordInput.value && passwordInput.value.length < 8) {
            isValid = false;
            passwordInput.classList.add('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Daftarkan Warga';
            
            // Show error message
            const existingAlert = document.querySelector('.alert-validation');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            const alertHtml = `
                <div class="alert alert-danger alert-dismissible fade show alert-validation" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Mohon periksa kembali:</strong> Pastikan semua field wajib sudah diisi dengan benar.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            form.insertAdjacentHTML('afterbegin', alertHtml);
            document.querySelector('.alert-validation').scrollIntoView({ behavior: 'smooth' });
        }
    });
    
    // Real-time validation
    const inputs = document.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
});
</script>
@endsection
