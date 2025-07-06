# 🏠 EnviroPay - Sistem Pembayaran Iuran Lingkungan

<div align="center">

![EnviroPay Logo](https://via.placeholder.com/200x80/4F46E5/ffffff?text=EnviroPay)

**Sistem Pembayaran Iuran Pengelolaan Lingkungan (IPL) Modern & Responsif**

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange.svg)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

</div>

---

## 📋 Daftar Isi

- [Tentang EnviroPay](#tentang-enviropay)
- [Fitur Utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Penggunaan](#penggunaan)
- [Struktur Proyek](#struktur-proyek)
- [API Endpoints](#api-endpoints)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Kontribusi](#kontribusi)
- [Roadmap](#roadmap)
- [License](#license)
- [Kontak](#kontak)

---

## 🌟 Tentang EnviroPay

**EnviroPay** adalah aplikasi web modern berbasis Laravel untuk mengelola pembayaran iuran pengelolaan lingkungan (IPL) di perumahan atau kompleks residential. Dirancang dengan antarmuka yang user-friendly dan responsif untuk mempermudah warga dalam melakukan pembayaran serta membantu pengurus dalam mengelola keuangan lingkungan.

### ✨ Mengapa EnviroPay?

- **🎨 UI/UX Modern**: Desain responsif dengan Bootstrap 5 dan tampilan yang clean
- **🔐 Keamanan Tinggi**: Sistem autentikasi Laravel dengan role-based access
- **📱 Mobile Friendly**: Dapat diakses dari berbagai perangkat
- **📊 Dashboard Informatif**: Statistik real-time dan laporan keuangan
- **🔍 Upload Bukti Bayar**: Fitur upload dan verifikasi bukti pembayaran
- **📈 Manajemen Cashflow**: Tracking pendapatan dan pengeluaran yang detail

---

## 🚀 Fitur Utama

### 👥 Manajemen User (3 Role)

#### 🏠 **Warga**
- ✅ Dashboard dengan statistik tagihan personal
- ✅ Lihat tagihan yang belum dibayar
- ✅ Upload bukti pembayaran (JPG, PNG, PDF)
- ✅ Riwayat pembayaran lengkap
- ✅ Notifikasi status verifikasi

#### 👔 **Pengurus**
- ✅ Dashboard dengan overview keuangan
- ✅ Manajemen data warga (CRUD)
- ✅ Buat dan kelola invoice/tagihan
- ✅ Verifikasi bukti pembayaran warga
- ✅ Laporan cashflow (pemasukan & pengeluaran)
- ✅ Quick action buttons untuk efisiensi

#### 🔧 **Admin**
- ✅ Manajemen akun pengurus
- ✅ Konfigurasi sistem global
- ✅ Monitoring seluruh aktivitas
- ✅ Backup dan restore data

### 💰 Manajemen Keuangan
- **Invoice Management**: Buat tagihan bulanan/tahunan dengan mudah
- **Payment Tracking**: Monitor status pembayaran real-time
- **Cashflow Reports**: Laporan arus kas yang detail dan visual
- **Financial Analytics**: Grafik pendapatan dan tren pembayaran

### 📁 Manajemen File
- **Bukti Pembayaran**: Upload multiple format (JPG, PNG, PDF)
- **Storage Optimization**: File terorganisir dengan validation
- **Preview System**: Preview bukti bayar langsung di browser

---

## 🛠️ Teknologi

### Backend
- **[Laravel 11.x](https://laravel.com)** - PHP Framework
- **[PHP 8.2+](https://php.net)** - Server-side scripting
- **[MySQL 8.0+](https://mysql.com)** - Database relational

### Frontend
- **[Bootstrap 5.3](https://getbootstrap.com)** - CSS Framework
- **[Blade Templates](https://laravel.com/docs/blade)** - Templating engine
- **[JavaScript ES6+](https://developer.mozilla.org/en-US/docs/Web/JavaScript)** - Client-side scripting
- **[Font Awesome](https://fontawesome.com)** - Icons

### Tools & Utilities
- **[Composer](https://getcomposer.org)** - PHP dependency manager
- **[NPM](https://npmjs.com)** - Node.js package manager
- **[Vite](https://vitejs.dev)** - Frontend build tool
- **[PHPUnit](https://phpunit.de)** - Testing framework

---

## 💻 Persyaratan Sistem

### Minimum Requirements
- **PHP**: 8.2 atau lebih tinggi
- **Database**: MySQL 8.0+ / MariaDB 10.3+
- **Web Server**: Apache 2.4+ / Nginx 1.18+
- **Memory**: 512MB RAM
- **Storage**: 1GB disk space

### Recommended Requirements
- **PHP**: 8.3+
- **Database**: MySQL 8.0+
- **Web Server**: Nginx dengan PHP-FPM
- **Memory**: 2GB+ RAM
- **Storage**: 5GB+ SSD

### PHP Extensions Required
```bash
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- GD atau Imagick (untuk image processing)
```

---

## 🔧 Instalasi

### 1. Clone Repository
```bash
git clone https://github.com/username/enviropay.git
cd enviropay
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Configuration
Edit file `.env` dan sesuaikan konfigurasi database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=enviropay_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Database Migration & Seeding
```bash
# Buat database
php artisan migrate

# Seed data dummy (opsional)
php artisan db:seed
```

### 6. Storage Setup
```bash
# Buat symbolic link untuk storage
php artisan storage:link

# Set permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
```

### 7. Build Assets
```bash
# Development
npm run dev

# Production
npm run build
```

### 8. Start Development Server
```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000`

---

## ⚙️ Konfigurasi

### Mail Configuration
Untuk fitur notifikasi email, edit `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### File Upload Configuration
Edit `config/filesystems.php` untuk konfigurasi upload:
```php
'uploads' => [
    'max_size' => 10240, // 10MB
    'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf'],
]
```

### Cache Configuration
Untuk performa optimal:
```bash
# Cache konfigurasi
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

---

## 🎯 Penggunaan

### Akses Default
Setelah seeding database, gunakan akun berikut:

#### Admin
- **Email**: admin@enviropay.com
- **Password**: admin123
- **Role**: Administrator

#### Pengurus
- **Email**: pengurus@enviropay.com
- **Password**: pengurus123
- **Role**: Pengurus

#### Warga
- **Email**: warga@enviropay.com
- **Password**: warga123
- **Role**: Warga

### Workflow Pembayaran

1. **Pengurus** membuat invoice/tagihan bulanan
2. **Warga** melihat tagihan di dashboard
3. **Warga** melakukan pembayaran dan upload bukti
4. **Pengurus** verifikasi bukti pembayaran
5. **Sistem** update status pembayaran otomatis

### Quick Start Guide

#### Untuk Pengurus:
1. Login sebagai pengurus
2. Buat member/warga baru di menu "Members"
3. Buat invoice di menu "Invoice"
4. Monitor pembayaran di "Verification"
5. Lihat laporan di "Cashflow"

#### Untuk Warga:
1. Login dengan akun yang diberikan pengurus
2. Lihat tagihan di dashboard
3. Upload bukti bayar untuk tagihan yang ada
4. Tunggu verifikasi dari pengurus

---

## 📁 Struktur Proyek

```
TUBES_WEBIPL/
├── 📁 app/
│   ├── 📁 Http/
│   │   ├── 📁 Controllers/        # Business logic controllers
│   │   └── 📁 Middleware/         # Custom middleware
│   ├── 📁 Models/                 # Eloquent models
│   └── 📁 Providers/              # Service providers
├── 📁 database/
│   ├── 📁 migrations/             # Database schemas
│   └── 📁 seeders/                # Test data seeders
├── 📁 public/
│   ├── 📁 storage/                # Symlink ke storage
│   └── index.php                  # Entry point
├── 📁 resources/
│   ├── 📁 css/                    # Stylesheets
│   ├── 📁 js/                     # JavaScript files
│   └── 📁 views/                  # Blade templates
│       ├── 📁 admin/              # Admin pages
│       ├── 📁 pengurus/           # Pengurus pages
│       └── 📁 warga/              # Warga pages
├── 📁 routes/
│   └── web.php                    # Web routes definition
├── 📁 storage/
│   ├── 📁 app/public/proofs/      # Bukti bayar uploads
│   └── 📁 logs/                   # Application logs
├── 📁 tests/                      # Automated tests
├── .env                           # Environment variables
├── composer.json                  # PHP dependencies
├── package.json                   # Node.js dependencies
└── README.md                      # This file
```

---

## 🔌 API Endpoints

### Authentication
```
POST /login              # User login
POST /logout             # User logout
```

### Warga Routes
```
GET  /warga/dashboard    # Dashboard warga
GET  /warga/invoices     # Daftar tagihan
POST /warga/upload-proof # Upload bukti bayar
```

### Pengurus Routes
```
GET  /pengurus/dashboard     # Dashboard pengurus
GET  /pengurus/members       # Manajemen warga
POST /pengurus/add-member    # Tambah warga baru
GET  /pengurus/invoices      # Manajemen invoice
GET  /pengurus/verification  # Verifikasi pembayaran
GET  /pengurus/cashflow      # Laporan keuangan
```

### Admin Routes
```
GET  /admin/dashboard    # Dashboard admin
GET  /admin/users        # Manajemen user
GET  /admin/settings     # Pengaturan sistem
```

---

## 🧪 Testing

### Unit Tests
```bash
# Jalankan semua tests
php artisan test

# Test specific class
php artisan test --filter=UserTest

# Test dengan coverage
php artisan test --coverage
```

### Feature Tests
```bash
# Test authentication
php artisan test tests/Feature/AuthTest.php

# Test payment flow
php artisan test tests/Feature/PaymentTest.php
```

### Manual Testing Scripts
Gunakan batch files yang tersedia:
```bash
# Test aplikasi secara keseluruhan
./test_app.bat

# Test navigasi
./test_navigation.bat

# Test verifikasi pembayaran
./test_verification.bat
```

### Browser Testing
```bash
# Jalankan Dusk tests (E2E)
php artisan dusk

# Specific browser test
php artisan dusk --filter=payment
```

---

## 🐛 Troubleshooting

### Common Issues

#### 1. Storage Permission Error
```bash
# Linux/Mac
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Windows
icacls storage /grant Users:F /T
```

#### 2. Database Connection Error
- Pastikan MySQL service berjalan
- Check kredensial di file `.env`
- Pastikan database sudah dibuat

#### 3. File Upload Issues
```bash
# Check symlink storage
php artisan storage:link

# Check folder permissions
ls -la storage/app/public/
```

#### 4. 500 Internal Server Error
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Check error logs
tail -f storage/logs/laravel.log
```

#### 5. CSS/JS Not Loading
```bash
# Rebuild assets
npm run build

# Clear browser cache
# Check public/build directory exists
```

### Debug Mode
Untuk development, aktifkan debug mode di `.env`:
```env
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

### Performance Issues
```bash
# Cache semua untuk production
php artisan optimize

# Clear semua cache
php artisan optimize:clear
```

---

## 🤝 Kontribusi

### Development Workflow

1. **Fork** repository ini
2. **Create** feature branch (`git checkout -b feature/AmazingFeature`)
3. **Commit** perubahan (`git commit -m 'Add some AmazingFeature'`)
4. **Push** ke branch (`git push origin feature/AmazingFeature`)
5. **Create** Pull Request

### Coding Standards

- Ikuti [PSR-12](https://www.php-fig.org/psr/psr-12/) untuk PHP
- Gunakan [Laravel conventions](https://laravel.com/docs/contributions#coding-style)
- Write unit tests untuk fitur baru
- Update dokumentasi jika diperlukan

### Commit Message Format
```
type(scope): description

feat(auth): add two-factor authentication
fix(payment): resolve upload validation issue
docs(readme): update installation guide
```

---

## 🗺️ Roadmap

### Version 2.0 (Q2 2024)
- [ ] 📧 Email notifications sistem
- [ ] 🔔 Real-time notifications (WebSocket)
- [ ] 📊 Advanced reporting & analytics
- [ ] 🌐 Multi-language support (ID/EN)
- [ ] 🔄 Automatic backup system

### Version 2.5 (Q3 2024)
- [ ] 📱 Progressive Web App (PWA)
- [ ] 💳 Payment gateway integration
- [ ] 🤖 WhatsApp bot notifications
- [ ] 📈 Financial forecasting
- [ ] 🏗️ Microservices architecture

### Version 3.0 (Q4 2024)
- [ ] 📱 Native mobile app (Flutter)
- [ ] ☁️ Cloud deployment (AWS/GCP)
- [ ] 🔐 OAuth2 integration
- [ ] 🧠 AI-powered insights
- [ ] 🔄 API versioning

---

## 📄 License

Distributed under the MIT License. See `LICENSE` file for more information.

```
MIT License

Copyright (c) 2024 EnviroPay

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 📞 Kontak

### Developer

**Ferdinand TJ**
- 📧 Email: ferdinandtj4@gmail.com
- 📱 Instagram: [@ferdinandtj__](https://www.instagram.com/ferdinandtj__)
- 💼 LinkedIn: [Ferdinand TJ](https://www.linkedin.com/in/ferdinandtj)
- 🐙 GitHub: [@ferdinandtj](https://github.com/ferdinandtj)

### Support

Jika Anda mengalami masalah atau memiliki pertanyaan:

1. **📋 Issues**: [GitHub Issues](https://github.com/username/enviropay/issues)
2. **💬 Discussions**: [GitHub Discussions](https://github.com/username/enviropay/discussions)
3. **📧 Email**: support@enviropay.com
4. **📞 WhatsApp**: +62 81684662

---

<div align="center">

**⭐ Jika project ini membantu Anda, berikan star ya! ⭐**

**Made with ❤️ for Indonesian Communities**

---

© 2024 EnviroPay. All rights reserved.

</div>
