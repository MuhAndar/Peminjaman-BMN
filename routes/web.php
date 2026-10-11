<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LoanRequestController;
use Illuminate\Support\Facades\Route;

// Guest Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/login/quick/{role}', [AuthController::class, 'quickLogin'])->name('login.quick');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware('auth')->group(function () {
    // Dashboard (Role Adaptive)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Katalog BMN
    Route::get('/barang', [AssetController::class, 'index'])->name('assets.index');
    Route::get('/barang/{asset}', [AssetController::class, 'show'])->name('assets.show');

    // Alur Peminjam
    Route::get('/pengajuan', [LoanRequestController::class, 'index'])->name('loans.index');
    Route::get('/pengajuan/baru', [LoanRequestController::class, 'create'])->name('loans.create');
    Route::post('/pengajuan', [LoanRequestController::class, 'store'])->name('loans.store');
    Route::get('/pengajuan/{id}', [LoanRequestController::class, 'show'])->name('loans.show');
    Route::post('/pengajuan/{id}/kembalikan', [LoanRequestController::class, 'requestReturn'])->name('loans.request-return');

    // Alur Pengelola BMN
    Route::prefix('kelola')->name('kelola.')->group(function () {
        Route::get('/antrean', [LoanRequestController::class, 'manageQueue'])->name('queue');
        Route::post('/pengajuan/{id}/telaah', [LoanRequestController::class, 'review'])->name('review');
        Route::post('/pengajuan/{id}/buat-bast', [LoanRequestController::class, 'issueBast'])->name('issue-bast');
        Route::post('/pengajuan/{id}/konfirmasi-aktif', [LoanRequestController::class, 'confirmActive'])->name('confirm-active');
        Route::post('/pengajuan/{id}/cek-kembali', [LoanRequestController::class, 'processReturnCheck'])->name('process-return-check');
        Route::post('/pengajuan/{id}/selesai-kembali', [LoanRequestController::class, 'finalizeReturn'])->name('finalize-return');
    });

    // Alur Ketua Tim Keuangan & Aset
    Route::prefix('persetujuan')->name('persetujuan.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/{id}', [ApprovalController::class, 'show'])->name('show');
        Route::post('/{id}/setujui', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('/{id}/revisi', [ApprovalController::class, 'requestRevision'])->name('revision');
        Route::post('/{id}/tolak', [ApprovalController::class, 'reject'])->name('reject');
    });

    // Administrasi Sistem (Khusus Admin)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/audit-logs', [\App\Http\Controllers\AdminController::class, 'auditLogs'])->name('audit-logs');
        Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('users');
        Route::get('/signatories', [\App\Http\Controllers\AdminController::class, 'signatories'])->name('signatories');
        Route::get('/settings', [\App\Http\Controllers\AdminController::class, 'settings'])->name('settings');
    });

    // Download & Preview PDF Resmi
    Route::get('/dokumen/{loanId}/pdf/{docType}', [DocumentController::class, 'showPdf'])->name('document.pdf');
});
