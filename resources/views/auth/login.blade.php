<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - EnviroPay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/js/app.js')
</head>
<body class="login-page">
<main class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="row g-0 shadow-lg rounded-4 overflow-hidden bg-white w-100" style="max-width:920px">
        <section class="col-lg-6 login-visual p-5 d-none d-lg-flex flex-column justify-content-between">
            <div class="d-flex align-items-center gap-3 fs-4 fw-bold"><span class="brand-mark"><i class="bi bi-leaf"></i></span>EnviroPay</div>
            <div><h1 class="display-6 fw-bold">Iuran lingkungan lebih tertib dan transparan.</h1><p class="text-white-50 mb-0">Kelola tagihan, verifikasi pembayaran manual, dan arus kas perumahan dalam satu tempat.</p></div>
            <small class="text-white-50">Sistem Pembayaran Iuran Pemeliharaan Lingkungan</small>
        </section>
        <section class="col-lg-6 p-4 p-md-5 d-flex align-items-center">
            <div class="w-100">
                <div class="d-lg-none d-flex align-items-center gap-2 fs-5 fw-bold text-success mb-4"><i class="bi bi-leaf"></i>EnviroPay</div>
                <h2 class="fw-bold mb-2">Selamat datang</h2><p class="text-secondary mb-4">Masuk dengan email atau username Anda.</p>
                @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
                <form action="{{ route('login.store') }}" method="POST" class="vstack gap-3">@csrf
                    <div><label class="form-label" for="identifier">Email atau username</label><input class="form-control form-control-lg @error('identifier') is-invalid @enderror" id="identifier" name="identifier" value="{{ old('identifier') }}" autocomplete="username" required autofocus></div>
                    <div><label class="form-label" for="password">Password</label><input type="password" class="form-control form-control-lg" id="password" name="password" autocomplete="current-password" required></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="remember" value="1" id="remember"><label class="form-check-label" for="remember">Ingat saya</label></div>
                    <button class="btn btn-primary btn-lg w-100" type="submit">Masuk <i class="bi bi-arrow-right ms-1"></i></button>
                </form>
            </div>
        </section>
    </div>
</main>
</body></html>
