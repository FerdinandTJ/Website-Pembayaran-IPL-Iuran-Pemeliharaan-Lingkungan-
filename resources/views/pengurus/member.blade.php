@extends('layouts.app')

@section('title', 'Daftar Warga')
@section('page-title', 'Daftar Warga')
@section('page-subtitle', 'Kelola data warga dalam sistem pembayaran iuran')
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
            <li><a class="dropdown-item" href="{{ url('/pengurus/register') }}">
                <i class="fas fa-user-plus me-1"></i>
                Tambah Warga
            </a></li>
            <li><a class="dropdown-item active" href="{{ url('/pengurus/members') }}">
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
    <!-- Alert Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Aksi Cepat
                        </h6>
                        <div class="d-flex gap-2">
                            <a href="{{ url('/pengurus/register') }}" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Tambah Warga
                            </a>
                            <a href="{{ url('/pengurus/invoice') }}" class="btn btn-success">
                                <i class="fas fa-file-invoice me-2"></i>Buat Tagihan
                            </a>
                            <a href="{{ url('/pengurus/invoice/verification') }}" class="btn btn-warning">
                                <i class="fas fa-check-double me-2"></i>Verifikasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Members Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-users me-2"></i>Data Warga
            </h6>
            <span class="badge bg-primary">{{ count($members) }} Warga</span>
        </div>
        <div class="card-body">
            @if(count($members) > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle"
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 5%;">#</th>
                                <th><i class="fas fa-user me-2"></i>Nama</th>
                                <th><i class="fas fa-at me-2"></i>Username</th>
                                <th><i class="fas fa-envelope me-2"></i>Email</th>
                                <th><i class="fas fa-phone me-2"></i>No. Telepon</th>
                                <th><i class="fas fa-home me-2"></i>Alamat Rumah</th>
                                <th class="text-center" style="width: 15%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($members as $key => $member)
                                <tr>
                                    <td class="text-center fw-bold">{{ $key + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary text-white me-2">
                                                {{ strtoupper(substr($member->name, 0, 1)) }}
                                            </div>
                                            <span class="fw-bold">{{ $member->name }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $member->username }}</span></td>
                                    <td>{{ $member->email }}</td>
                                    <td>{{ $member->phone_number }}</td>
                                    <td>{{ $member->house_address }}</td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editMemberModal{{ $member->id }}"
                                                    title="Edit Warga">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="confirmDelete({{ $member->id }}, '{{ $member->name }}')"
                                                    title="Hapus Warga">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Hidden form for delete -->
                                        <form id="deleteForm{{ $member->id }}" 
                                              action="{{ url('/pengurus/members/delete/' . $member->id) }}" 
                                              method="POST" 
                                              style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editMemberModal{{ $member->id }}" 
                                     tabindex="-1" 
                                     aria-labelledby="editMemberModalLabel{{ $member->id }}" 
                                     aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title" id="editMemberModalLabel{{ $member->id }}">
                                                    <i class="fas fa-user-edit me-2"></i>Edit Warga: {{ $member->name }}
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" 
                                                        data-bs-dismiss="modal" 
                                                        aria-label="Close"></button>
                                            </div>
                                            <form action="{{ url('/pengurus/members/update', $member->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="name{{ $member->id }}" class="form-label">
                                                                    <i class="fas fa-user me-1"></i>Nama Lengkap
                                                                </label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       id="name{{ $member->id }}" 
                                                                       name="name" 
                                                                       value="{{ $member->name }}" 
                                                                       required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="username{{ $member->id }}" class="form-label">
                                                                    <i class="fas fa-at me-1"></i>Username
                                                                </label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       id="username{{ $member->id }}" 
                                                                       name="username" 
                                                                       value="{{ $member->username }}" 
                                                                       required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="email{{ $member->id }}" class="form-label">
                                                                    <i class="fas fa-envelope me-1"></i>Email
                                                                </label>
                                                                <input type="email" 
                                                                       class="form-control" 
                                                                       id="email{{ $member->id }}" 
                                                                       name="email" 
                                                                       value="{{ $member->email }}" 
                                                                       required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="phone_number{{ $member->id }}" class="form-label">
                                                                    <i class="fas fa-phone me-1"></i>No. Telepon
                                                                </label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       id="phone_number{{ $member->id }}" 
                                                                       name="phone_number" 
                                                                       value="{{ $member->phone_number }}" 
                                                                       required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="house_address{{ $member->id }}" class="form-label">
                                                            <i class="fas fa-home me-1"></i>Alamat Rumah
                                                        </label>
                                                        <textarea class="form-control" 
                                                                  id="house_address{{ $member->id }}" 
                                                                  name="house_address" 
                                                                  rows="2" 
                                                                  required>{{ $member->house_address }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-2"></i>Batal
                                                    </button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-2"></i>Update Warga
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Belum ada warga terdaftar</h5>
                                            <p class="text-muted">Klik tombol "Tambah Warga" untuk menambahkan warga baru</p>
                                            <a href="{{ url('/pengurus/register') }}" class="btn btn-primary">
                                                <i class="fas fa-user-plus me-2"></i>Tambah Warga Pertama
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada warga terdaftar</h5>
                        <p class="text-muted">Klik tombol "Tambah Warga" untuk menambahkan warga baru</p>
                        <a href="{{ url('/pengurus/register') }}" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Tambah Warga Pertama
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="row">
        <div class="col-md-6">
            <div class="card bg-gradient-primary text-white shadow">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-1">
                                Total Warga Terdaftar
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-white">{{ count($members) }} Warga</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-gradient-info text-white shadow">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-1">
                                Kompleks Perumahan
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-white">{{ Auth::user()->housing_name }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-home fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.empty-state {
    padding: 2rem;
}

.table-responsive {
    border-radius: 0.5rem;
    overflow: hidden;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

<script>
function confirmDelete(memberId, memberName) {
    if (confirm(`Apakah Anda yakin ingin menghapus warga "${memberName}"? Tindakan ini tidak dapat dibatalkan.`)) {
        document.getElementById('deleteForm' + memberId).submit();
    }
}
</script>
@endsection
