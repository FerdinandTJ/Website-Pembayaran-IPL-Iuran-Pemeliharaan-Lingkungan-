<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EnviroPay - Sistem Pembayaran Iuran Lingkungan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c5282;
            --secondary-color: #3182ce;
            --accent-color: #68d391;
            --dark-color: #1a202c;
            --light-color: #f7fafc;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .navbar-scrolled {
            background: rgba(44, 82, 130, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="white" opacity="0.1"><path d="M421.9,6.5c22.6-2.5,51.5,0.4,75.5,5.3c23.6,4.9,70.9,23.5,100.5,35.7c75.8,32.2,133.7,44.5,192.6,49.7c23.6,2.1,48.7,3.5,103.4-2.5c54.7-6,106.2-25.6,106.2-25.6V0H0v30.3c0,0,72,32.6,158.4,30.5c39.2-0.7,92.8-6.7,134-22.4c21.2-8.1,52.2-18.2,79.7-24.2C399.3,7.9,411.6,7.5,421.9,6.5z"/></svg>') repeat-x;
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent-color), #48bb78);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(104, 211, 145, 0.3);
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-10px);
        }
        
        .stats-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(44, 82, 130, 0.4);
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(44, 82, 130, 0.6);
            background: linear-gradient(135deg, #2a4d6b, #2c5282);
        }
        
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 3rem 0 1rem;
        }
        
        .section-padding {
            padding: 5rem 0;
        }
        
        .text-primary-custom {
            color: var(--primary-color) !important;
        }
        
        .bg-light-custom {
            background: var(--light-color) !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: transparent;">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="#">
                <i class="fas fa-leaf me-2 text-success"></i>
                EnviroPay
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Kontak</a>
                    </li>
                    <li class="nav-item ms-3">
                        <a class="btn btn-primary-custom" href="{{ url('/') }}">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            Masuk
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        Kelola Iuran Lingkungan
                        <span class="text-warning">Lebih Mudah</span>
                    </h1>
                    <p class="lead mb-4">
                        EnviroPay adalah solusi digital terdepan untuk pengelolaan iuran pemeliharaan lingkungan. 
                        Sistem pembayaran yang aman, transparan, dan mudah digunakan untuk warga dan pengurus.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ url('/') }}" class="btn btn-primary-custom btn-lg">
                            <i class="fas fa-rocket me-2"></i>
                            Mulai Sekarang
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-play-circle me-2"></i>
                            Pelajari Lebih Lanjut
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-mobile-alt" style="font-size: 20rem; opacity: 0.1;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="section-padding bg-light-custom">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">
                            <i class="fas fa-users text-primary-custom"></i>
                        </div>
                        <h5>Warga Aktif</h5>
                        <p class="text-muted">Ribuan warga telah mempercayai EnviroPay</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">
                            <i class="fas fa-shield-alt text-success"></i>
                        </div>
                        <h5>Keamanan Terjamin</h5>
                        <p class="text-muted">Sistem enkripsi tingkat bank untuk keamanan data</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">
                            <i class="fas fa-clock text-info"></i>
                        </div>
                        <h5>24/7 Layanan</h5>
                        <p class="text-muted">Akses kapan saja dan dimana saja</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">
                            <i class="fas fa-chart-line text-warning"></i>
                        </div>
                        <h5>Transparan</h5>
                        <p class="text-muted">Laporan keuangan yang jelas dan akurat</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary-custom">Fitur Unggulan</h2>
                <p class="lead text-muted">Semua yang Anda butuhkan untuk mengelola iuran lingkungan</p>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-credit-card fa-2x text-white"></i>
                        </div>
                        <h4>Pembayaran Digital</h4>
                        <p class="text-muted">Bayar iuran dengan mudah melalui transfer bank dan upload bukti pembayaran.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-chart-pie fa-2x text-white"></i>
                        </div>
                        <h4>Dashboard Interaktif</h4>
                        <p class="text-muted">Pantau status pembayaran dan riwayat transaksi dalam satu dashboard.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-bell fa-2x text-white"></i>
                        </div>
                        <h4>Notifikasi Real-time</h4>
                        <p class="text-muted">Dapatkan pemberitahuan otomatis untuk tagihan baru dan status pembayaran.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-users-cog fa-2x text-white"></i>
                        </div>
                        <h4>Manajemen Warga</h4>
                        <p class="text-muted">Kelola data warga dan hak akses dengan sistem yang terorganisir.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-file-invoice-dollar fa-2x text-white"></i>
                        </div>
                        <h4>Laporan Keuangan</h4>
                        <p class="text-muted">Generate laporan keuangan yang detail dan transparan untuk audit.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt fa-2x text-white"></i>
                        </div>
                        <h4>Mobile Friendly</h4>
                        <p class="text-muted">Akses dari smartphone, tablet, atau komputer dengan tampilan responsif.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section-padding bg-light-custom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold text-primary-custom mb-4">Tentang EnviroPay</h2>
                    <p class="lead mb-4">
                        EnviroPay hadir sebagai solusi digital untuk mempermudah pengelolaan iuran pemeliharaan lingkungan perumahan.
                    </p>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                                <span>Mudah Digunakan</span>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                                <span>Aman & Terpercaya</span>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                                <span>Transparan</span>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                                <span>Efisien</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-home" style="font-size: 15rem; color: var(--primary-color); opacity: 0.1;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary-custom">Hubungi Kami</h2>
                <p class="lead text-muted">Butuh bantuan? Tim support kami siap membantu Anda</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon bg-primary">
                            <i class="fas fa-envelope fa-2x text-white"></i>
                        </div>
                        <h5>Email</h5>
                        <p class="text-muted">support@enviropay.com</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon bg-success">
                            <i class="fas fa-phone fa-2x text-white"></i>
                        </div>
                        <h5>Telepon</h5>
                        <p class="text-muted">0800-1234-5678</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon bg-info">
                            <i class="fab fa-whatsapp fa-2x text-white"></i>
                        </div>
                        <h5>WhatsApp</h5>
                        <p class="text-muted">+62 812-3456-7890</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-leaf me-2 text-success"></i>
                        EnviroPay
                    </h5>
                    <p class="text-muted">
                        Solusi digital terdepan untuk pengelolaan iuran pemeliharaan lingkungan yang aman, 
                        transparan, dan mudah digunakan.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-muted"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Produk</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Dashboard</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Pembayaran</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Laporan</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Manajemen</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Dukungan</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Bantuan</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Tutorial</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">FAQ</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Kontak</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Perusahaan</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Tentang Kami</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Karir</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Blog</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Partnership</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Legal</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Privasi</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Syarat</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Keamanan</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Lisensi</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="text-muted mb-0">
                    &copy; {{ date('Y') }} EnviroPay. Semua hak dilindungi undang-undang.
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
