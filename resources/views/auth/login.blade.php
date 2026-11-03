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
    <div class="row g-0 login-panel rounded-4 overflow-hidden bg-white w-100" style="max-width:1000px">
        <section class="col-lg-6 login-visual p-5 d-none d-lg-flex flex-column justify-content-between">
            <div class="d-flex align-items-center gap-3 fs-4 fw-bold"><span class="brand-mark"><i class="bi bi-leaf"></i></span>EnviroPay</div>
            <div><h1>Lingkungan tertata.<br>Iuran terkelola.</h1><p>Tagihan, pembayaran, dan informasi lingkungan. Semua terhubung dalam satu tempat.</p><div class="mt-4"><div class="login-feature"><i class="bi bi-check2-circle"></i>Tagihan dan pembayaran yang tercatat</div><div class="login-feature"><i class="bi bi-check2-circle"></i>Transparansi kas lingkungan</div><div class="login-feature"><i class="bi bi-check2-circle"></i>Informasi untuk seluruh warga</div></div></div>
            <small class="text-white-50">Sistem Pembayaran Iuran Pemeliharaan Lingkungan</small>
        </section>
        <section class="col-lg-6 p-4 p-md-5 d-flex align-items-center">
            <div class="w-100">
                <div class="d-lg-none d-flex align-items-center gap-2 fs-5 fw-bold text-success mb-4"><i class="bi bi-leaf"></i>EnviroPay</div>
                <div class="page-eyebrow mb-3">SELAMAT DATANG DI ENVIROPAY</div><h2 class="mb-2" style="font-weight:600;letter-spacing:-.04em">Masuk ke akun Anda</h2><p class="text-secondary mb-4">Masuk dengan email atau username Anda.</p>
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
