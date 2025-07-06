@extends('layouts.app')

@section('title', 'Buat Tagihan')
@section('page-title', 'Buat Tagihan')
@section('page-subtitle', 'Kelola tagihan iuran warga')
@section('dashboard-url', url('/pengurus/dashboard'))

@section('nav-items')
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/pengurus/dashboard') }}">
            <i class="fas fa-tachometer-alt me-1"></i>
            Dashboard
        </a>
    </li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-users me-1"></i>
            Kelola Warga
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ url('/pengurus/register') }}">
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
        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-file-invoice me-1"></i>
            Tagihan
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item active" href="{{ url('/pengurus/invoice') }}">
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
                            <a href="{{ url('/pengurus/members') }}" class="btn btn-info">
                                <i class="fas fa-users me-2"></i>Daftar Warga
                            </a>
                            <a href="{{ url('/pengurus/invoice/verification') }}" class="btn btn-warning">
                                <i class="fas fa-check-double me-2"></i>Verifikasi
                            </a>
                            <a href="{{ url('/pengurus/cashflow') }}" class="btn btn-success">
                                <i class="fas fa-chart-line me-2"></i>Cash Flow
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form Card -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-plus-circle me-2"></i>Form Tagihan Baru
                    </h6>
                </div>
                <div class="card-body">
                    <form id="invoice-form">
                        @csrf
                        <!-- Account Number Input -->
                        <div class="mb-4">
                            <label for="account_number" class="form-label fw-bold">
                                <i class="fas fa-credit-card me-2 text-primary"></i>Nomor Akun Bank
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-university"></i>
                                </span>
                                <input type="text" 
                                       name="account_number" 
                                       id="account_number" 
                                       class="form-control form-control-lg" 
                                       placeholder="Masukkan nomor rekening untuk pembayaran"
                                       required>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Nomor rekening yang akan digunakan warga untuk melakukan pembayaran
                            </div>
                        </div>

                        <!-- Components Section -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 fw-bold text-dark">
                                    <i class="fas fa-list me-2 text-success"></i>Komponen Tagihan
                                </h5>
                                <button type="button" class="btn btn-success btn-sm" id="add-component-btn">
                                    <i class="fas fa-plus me-2"></i>Tambah Komponen
                                </button>
                            </div>
                            
                            <div id="components-container" class="border rounded p-3 bg-light">
                                <ul id="components-list" class="list-group list-group-flush mb-0">
                                    <li class="list-group-item border-0 bg-transparent text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p class="mb-0">Belum ada komponen tagihan ditambahkan</p>
                                        <small>Klik "Tambah Komponen" untuk memulai</small>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Total and Send Button -->
                        <div class="border-top pt-4">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted me-2">Total Tagihan:</span>
                                        <h4 class="mb-0 text-success fw-bold" id="total-amount">Rp 0</h4>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <button type="button" class="btn btn-success btn-lg" id="send-broadcast-btn" disabled>
                                        <i class="fas fa-paper-plane me-2"></i>Kirim Tagihan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-lg-4">
            <!-- Guidelines Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-lightbulb me-2"></i>Panduan Penggunaan
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex align-items-start mb-2">
                            <div class="badge bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 12px;">1</div>
                            <small>Masukkan nomor rekening bank untuk pembayaran</small>
                        </div>
                        <div class="d-flex align-items-start mb-2">
                            <div class="badge bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 12px;">2</div>
                            <small>Tambahkan komponen tagihan (contoh: Iuran Kebersihan, Iuran Keamanan)</small>
                        </div>
                        <div class="d-flex align-items-start mb-2">
                            <div class="badge bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 12px;">3</div>
                            <small>Periksa kembali total tagihan</small>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="badge bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 12px;">4</div>
                            <small>Klik "Kirim Tagihan" untuk mengirim ke semua warga</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-history me-2"></i>Tips Tagihan
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning border-0 mb-3">
                        <div class="d-flex">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <small class="fw-bold">Periksa dengan teliti!</small>
                                <br>
                                <small>Pastikan nominal dan komponen tagihan sudah benar sebelum mengirim.</small>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info border-0 mb-0">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                <small class="fw-bold">Informasi</small>
                                <br>
                                <small>Tagihan akan dikirim ke semua warga yang terdaftar di sistem.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Adding Components -->
<div class="modal fade" id="add-component-modal" tabindex="-1" aria-labelledby="addComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addComponentModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Komponen Tagihan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="component-name" class="form-label fw-bold">
                        <i class="fas fa-tag me-2 text-primary"></i>Nama Tagihan
                    </label>
                    <input type="text" 
                           id="component-name" 
                           class="form-control" 
                           placeholder="Contoh: Iuran Kebersihan, Iuran Keamanan" 
                           required>
                </div>
                <div class="mb-3">
                    <label for="component-amount" class="form-label fw-bold">
                        <i class="fas fa-money-bill-wave me-2 text-success"></i>Jumlah (Rp)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" 
                               id="component-amount" 
                               class="form-control" 
                               placeholder="0" 
                               min="1000"
                               step="1000"
                               required>
                    </div>
                    <div class="form-text">
                        <i class="fas fa-info-circle me-1"></i>
                        Minimal Rp 1.000, kelipatan Rp 1.000
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-success" id="add-component-submit-btn">
                    <i class="fas fa-plus me-2"></i>Tambah Komponen
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.list-group-item {
    border: 1px solid #e3e6f0 !important;
    margin-bottom: 8px;
    border-radius: 0.35rem !important;
}

.empty-components {
    background: #f8f9fc;
    border: 2px dashed #d1d3e2;
    border-radius: 0.35rem;
    padding: 2rem;
}

#total-amount {
    font-size: 1.5rem;
}

.badge.rounded-circle {
    font-size: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // References to DOM elements
    const addComponentBtn = document.getElementById('add-component-btn');
    const addComponentModal = new bootstrap.Modal(document.getElementById('add-component-modal'));
    const addComponentSubmitBtn = document.getElementById('add-component-submit-btn');
    const componentsList = document.getElementById('components-list');
    const sendBroadcastBtn = document.getElementById('send-broadcast-btn');
    const totalAmountDisplay = document.getElementById('total-amount');
    
    let totalAmount = 0;
    let componentsCount = 0;

    // Event listeners
    addComponentBtn.addEventListener('click', () => {
        document.getElementById('component-name').value = '';
        document.getElementById('component-amount').value = '';
        addComponentModal.show();
    });

    addComponentSubmitBtn.addEventListener('click', () => {
        const nameInput = document.getElementById('component-name');
        const amountInput = document.getElementById('component-amount');

        const billName = nameInput.value.trim();
        const amount = parseInt(amountInput.value.trim());

        if (billName && amount && amount >= 1000) {
            // Clear empty state if this is first component
            if (componentsCount === 0) {
                componentsList.innerHTML = '';
            }

            const listItem = document.createElement('li');
            listItem.className = 'list-group-item d-flex justify-content-between align-items-center bg-white shadow-sm';
            listItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-receipt text-success me-3"></i>
                    <div>
                        <div class="fw-bold text-dark">${billName}</div>
                        <small class="text-muted">Rp ${amount.toLocaleString('id-ID')}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm delete-component-btn" title="Hapus">
                    <i class="fas fa-trash"></i>
                </button>
            `;

            // Add delete functionality to the button
            listItem.querySelector('.delete-component-btn').addEventListener('click', () => {
                totalAmount -= amount;
                componentsCount--;
                updateTotalDisplay();
                listItem.remove();
                
                if (componentsCount === 0) {
                    showEmptyState();
                    sendBroadcastBtn.disabled = true;
                }
            });

            componentsList.appendChild(listItem);
            
            // Update totals
            totalAmount += amount;
            componentsCount++;
            updateTotalDisplay();

            // Enable send button if we have components
            sendBroadcastBtn.disabled = false;

            // Clear inputs and close modal
            nameInput.value = '';
            amountInput.value = '';
            addComponentModal.hide();
        } else {
            alert('Pastikan nama tagihan diisi dan jumlah minimal Rp 1.000');
        }
    });

    sendBroadcastBtn.addEventListener('click', () => {
        const accountNumber = document.getElementById('account_number').value.trim();
        const components = [];

        if (!accountNumber) {
            alert('Nomor rekening harus diisi');
            return;
        }

        if (componentsCount === 0) {
            alert('Minimal satu komponen tagihan harus ditambahkan');
            return;
        }

        // Collect components data
        componentsList.querySelectorAll('.list-group-item').forEach(item => {
            const nameElement = item.querySelector('.fw-bold');
            const amountElement = item.querySelector('small');
            
            if (nameElement && amountElement) {
                const name = nameElement.textContent.trim();
                const amountText = amountElement.textContent.replace('Rp ', '').replace(/\./g, '');
                const amount = parseInt(amountText);
                components.push({ name, amount });
            }
        });

        if (confirm(`Kirim tagihan dengan total Rp ${totalAmount.toLocaleString('id-ID')} ke semua warga?`)) {
            // Disable button to prevent double submission
            sendBroadcastBtn.disabled = true;
            sendBroadcastBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengirim...';

            // Send data to server
            fetch('/pengurus/broadcast', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    account_number: accountNumber,
                    components: components
                })
            })
            .then(response => response.json())
            .then(data => {
                alert('Tagihan berhasil dikirim ke semua warga!');
                // Reset form
                document.getElementById('invoice-form').reset();
                showEmptyState();
                totalAmount = 0;
                componentsCount = 0;
                updateTotalDisplay();
                sendBroadcastBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Kirim Tagihan';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal mengirim tagihan. Silakan coba lagi.');
                sendBroadcastBtn.disabled = false;
                sendBroadcastBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Kirim Tagihan';
            });
        } else {
            sendBroadcastBtn.disabled = false;
        }
    });

    function updateTotalDisplay() {
        totalAmountDisplay.textContent = `Rp ${totalAmount.toLocaleString('id-ID')}`;
    }

    function showEmptyState() {
        componentsList.innerHTML = `
            <li class="list-group-item border-0 bg-transparent text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p class="mb-0">Belum ada komponen tagihan ditambahkan</p>
                <small>Klik "Tambah Komponen" untuk memulai</small>
            </li>
        `;
    }
});
</script>
@endsection
