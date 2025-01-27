<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\PengurusController;
use App\Http\Controllers\WargaController;
use App\Http\Middleware\UserAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [SessionController::class, 'index'])->name('login');
    Route::post('/login', [SessionController::class, 'login']);
});

Route::get('/home', function () {
    if (Auth::check()) {
        // Get the authenticated user's role
        $userRole = Auth::user()->role;
        switch ($userRole) {
            case 'admin':
                return redirect('/admin/dashboard');
            case 'pengurus':
                return redirect('/pengurus/dashboard');
            case 'warga':
                return redirect('/warga/dashboard');
            default:
                return redirect('/warga/dashboard');
        }
    }
    return redirect('/');
});

// Group routes that require authentication
Route::middleware('auth')->group(function () {    
    // Admin routes
    Route::middleware(UserAccess::class . ':admin')->group(function () {
        Route::get('/admin/profile', [SessionController::class, 'profile']);
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        Route::post('/admin/register', [AdminController::class, 'register']);
    });

    // Pengurus routes
    Route::middleware(UserAccess::class . ':pengurus')->group(function () {
        Route::get('/pengurus/profile', [SessionController::class, 'profile']);
        Route::get('/pengurus/dashboard', [PengurusController::class, 'dashboard']);
        Route::get('/pengurus/register', [PengurusController::class, 'register']);
        Route::post('/pengurus/register', [PengurusController::class, 'addMember']);
        Route::get('/pengurus/members', [PengurusController::class, 'view_member']);
        Route::put('/pengurus/members/update/{id}', [PengurusController::class, 'update_member'])->name('pengurus.update_member');
        Route::delete('/pengurus/members/delete/{id}', [PengurusController::class, 'delete_member'])->name('pengurus.delete_member');
        Route::get('/pengurus/invoice', [PengurusController::class, 'create_invoice']);
        Route::post('/pengurus/broadcast', [PengurusController::class, 'send_invoice']);
        Route::get('/pengurus/invoice/verification', [PengurusController::class, 'verify_invoice']);
        Route::post('/pengurus/invoice/verify/{id}', [PengurusController::class, 'process_verification']);
        Route::post('/pengurus/invoice/unverify/{id}', [PengurusController::class, 'process_unverification']);
        Route::get('/pengurus/cashflow', [PengurusController::class, 'view_cashflow']);
        Route::post('/pengurus/cashflow/add', [PengurusController::class, 'add_cashflow']);
        Route::put('/pengurus/cashflow/update/{id}', [PengurusController::class, 'update_cashflow']);
        Route::delete('/pengurus/cashflow/delete/{id}', [PengurusController::class, 'delete_cashflow']);
    });

    // Warga routes
    Route::middleware(UserAccess::class . ':warga')->group(function () {
        Route::get('/warga/profile', [SessionController::class, 'profile']);
        Route::get('/warga/dashboard', [WargaController::class, 'dashboard']);
        Route::get('/warga/bills', [WargaController::class, 'viewBills']);
        Route::get('/warga/invoice/history', [WargaController::class, 'invoiceHistory']);
        Route::get('/warga/invoice/{id}', [WargaController::class, 'viewInvoice']);
        Route::post('/warga/invoice/{id}', [WargaController::class, 'payInvoice']);
    });

    // Logout route
    Route::get('/logout', [SessionController::class, 'logout']);
});
