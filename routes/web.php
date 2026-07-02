<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HousingController;
use App\Http\Controllers\Admin\ManagerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\Pengurus\CashflowController;
use App\Http\Controllers\Pengurus\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Pengurus\InvoiceController as ManagerInvoiceController;
use App\Http\Controllers\Pengurus\ResidentController;
use App\Http\Controllers\Pengurus\VerificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Warga\DashboardController as ResidentDashboardController;
use App\Http\Controllers\Warga\InvoiceController as ResidentInvoiceController;
use App\Http\Controllers\Warga\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', [AuthController::class, 'home'])->name('home');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/invoices/{invoice}/proof', PaymentProofController::class)->name('invoices.proof');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::resource('housings', HousingController::class)->except('show');
        Route::resource('managers', ManagerController::class)->except('show');
        Route::patch('/managers/{manager}/password', [ManagerController::class, 'resetPassword'])->name('managers.password');
    });

    Route::prefix('pengurus')->name('pengurus.')->middleware('role:pengurus')->group(function () {
        Route::get('/dashboard', ManagerDashboardController::class)->name('dashboard');
        Route::resource('residents', ResidentController::class)->except('show');
        Route::patch('/residents/{resident}/password', [ResidentController::class, 'resetPassword'])->name('residents.password');
        Route::resource('invoices', ManagerInvoiceController::class)->only('index', 'create', 'store');
        Route::get('/verifications', [VerificationController::class, 'index'])->name('verifications.index');
        Route::post('/invoices/{invoice}/verify', [VerificationController::class, 'verify'])->name('invoices.verify');
        Route::post('/invoices/{invoice}/reject', [VerificationController::class, 'reject'])->name('invoices.reject');
        Route::resource('cashflows', CashflowController::class)->only('index', 'store', 'update', 'destroy');
    });

    Route::prefix('warga')->name('warga.')->middleware('role:warga')->group(function () {
        Route::get('/dashboard', ResidentDashboardController::class)->name('dashboard');
        Route::resource('invoices', ResidentInvoiceController::class)->only('index', 'show');
        Route::post('/invoices/{invoice}/payment', [PaymentController::class, 'store'])->name('invoices.payment');
    });
});
