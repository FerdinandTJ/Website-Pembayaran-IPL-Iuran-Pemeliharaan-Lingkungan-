<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HousingController;
use App\Http\Controllers\Admin\ManagerController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentBatchController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\Pengurus\CashflowController;
use App\Http\Controllers\Pengurus\CashPaymentController;
use App\Http\Controllers\Pengurus\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Pengurus\HouseController;
use App\Http\Controllers\Pengurus\HouseImportController;
use App\Http\Controllers\Pengurus\InvoiceController as ManagerInvoiceController;
use App\Http\Controllers\Pengurus\PaymentAccountController;
use App\Http\Controllers\Pengurus\ReportController;
use App\Http\Controllers\Pengurus\ResidentController;
use App\Http\Controllers\Pengurus\ResidentImportController;
use App\Http\Controllers\Pengurus\VerificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\Warga\DashboardController as ResidentDashboardController;
use App\Http\Controllers\Warga\InvoiceController as ResidentInvoiceController;
use App\Http\Controllers\Warga\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/home', [AuthController::class, 'home'])->name('home');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile/password', [ProfileController::class, 'password'])->middleware('role:admin,pengurus,warga')->middleware('throttle:sensitive')->name('profile.password');
    Route::get('/invoices/{invoice}/proof', PaymentProofController::class)->name('invoices.proof');

    Route::middleware('role:pengurus,warga')->group(function () {
        Route::get('/payment-batches/{paymentBatch}', [PaymentBatchController::class, 'show'])->name('payment-batches.show');
        Route::get('/payment-batches/{paymentBatch}/proof', [PaymentBatchController::class, 'proof'])->name('payment-batches.proof');
        Route::get('/invoices/{invoice}/receipt', [ReceiptController::class, 'show'])->name('receipts.show');
        Route::get('/invoices/{invoice}/receipt.pdf', [ReceiptController::class, 'pdf'])->middleware('throttle:documents')->name('receipts.pdf');
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])->whereNumber('announcement')->name('announcements.show');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'count'])->name('notifications.count');
        Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::resource('housings', HousingController::class)->except('show');
        Route::resource('managers', ManagerController::class)->except('show');
        Route::patch('/managers/{manager}/password', [ManagerController::class, 'resetPassword'])->middleware('throttle:sensitive')->name('managers.password');
    });

    Route::prefix('pengurus')->name('pengurus.')->middleware('role:pengurus')->group(function () {
        Route::get('/dashboard', ManagerDashboardController::class)->name('dashboard');
        Route::get('/reports/{report}', [ReportController::class, 'index'])
            ->whereIn('report', ['arrears', 'billing', 'cashflow'])->name('reports.index');
        Route::get('/reports/{report}/export', [ReportController::class, 'export'])
            ->whereIn('report', ['arrears', 'billing', 'cashflow'])->name('reports.export');
        Route::resource('houses', HouseController::class)->except(['show', 'destroy']);
        Route::get('/house-imports', [HouseImportController::class, 'index'])->name('house-imports.index');
        Route::get('/house-imports/template', [HouseImportController::class, 'template'])->name('house-imports.template');
        Route::post('/house-imports/preview', [HouseImportController::class, 'preview'])->middleware('throttle:imports')->name('house-imports.preview');
        Route::post('/house-imports/{batch}/confirm', [HouseImportController::class, 'confirm'])->name('house-imports.confirm');
        Route::get('/resident-imports', [ResidentImportController::class, 'index'])->name('imports.index');
        Route::get('/resident-imports/template', [ResidentImportController::class, 'template'])->name('imports.template');
        Route::post('/resident-imports/preview', [ResidentImportController::class, 'preview'])->middleware('throttle:imports')->name('imports.preview');
        Route::post('/resident-imports/{batch}/confirm', [ResidentImportController::class, 'confirm'])->name('imports.confirm');
        Route::get('/cash-payments/confirm', [PaymentBatchController::class, 'cashPreview'])->name('cash-batches.create');
        Route::post('/cash-payments/batch', [PaymentBatchController::class, 'cashStore'])->name('cash-batches.store');
        Route::post('/payment-batches/{paymentBatch}/verify', [PaymentBatchController::class, 'verify'])->name('payment-batches.verify');
        Route::post('/payment-batches/{paymentBatch}/reject', [PaymentBatchController::class, 'reject'])->name('payment-batches.reject');
        Route::get('/cash-payments', [CashPaymentController::class, 'index'])->name('cash-payments.index');
        Route::post('/invoices/{invoice}/cash-payment', [CashPaymentController::class, 'store'])->name('cash-payments.store');
        Route::resource('announcements', AnnouncementController::class)->only(['create', 'store', 'edit', 'update']);
        Route::post('/announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
        Route::post('/announcements/{announcement}/archive', [AnnouncementController::class, 'archive'])->name('announcements.archive');
        Route::resource('residents', ResidentController::class)->except('show');
        Route::resource('payment-accounts', PaymentAccountController::class)->only('index', 'store', 'update', 'destroy');
        Route::patch('/residents/{resident}/password', [ResidentController::class, 'resetPassword'])->middleware('throttle:sensitive')->name('residents.password');
        Route::resource('invoices', ManagerInvoiceController::class)->only('index', 'create', 'store');
        Route::get('/verifications', [VerificationController::class, 'index'])->name('verifications.index');
        Route::post('/invoices/{invoice}/verify', [VerificationController::class, 'verify'])->name('invoices.verify');
        Route::post('/invoices/{invoice}/reject', [VerificationController::class, 'reject'])->name('invoices.reject');
        Route::resource('cashflows', CashflowController::class)->only('index', 'store', 'update', 'destroy');
    });

    Route::prefix('warga')->name('warga.')->middleware('role:warga')->group(function () {
        Route::get('/dashboard', ResidentDashboardController::class)->name('dashboard');
        Route::get('/payment-batches/select', [PaymentBatchController::class, 'select'])->name('payment-batches.select');
        Route::get('/payment-batches/create', [PaymentBatchController::class, 'transferPreview'])->name('payment-batches.create');
        Route::post('/payment-batches', [PaymentBatchController::class, 'transferStore'])->middleware('throttle:documents')->name('payment-batches.store');
        Route::resource('invoices', ResidentInvoiceController::class)->only('index', 'show');
        Route::post('/invoices/{invoice}/payment', [PaymentController::class, 'store'])->name('invoices.payment');
    });
});
