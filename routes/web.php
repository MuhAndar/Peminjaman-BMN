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
    Route::get('/barang/export/xlsx', [\App\Http\Controllers\DataImportController::class, 'exportAssets'])->name('assets.export');
    Route::get('/barang/{asset}', [AssetController::class, 'show'])->name('assets.show');

    // Profil & Pengaturan Data Diri Peminjaman & Tanda Tangan Digital Pengguna
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/biodata', [\App\Http\Controllers\ProfileController::class, 'updateBiodata'])->name('profile.biodata.update');
    Route::post('/profile/signature', [\App\Http\Controllers\ProfileController::class, 'updateSignature'])->name('profile.signature.update');
    Route::delete('/profile/signature', [\App\Http\Controllers\ProfileController::class, 'deleteSignature'])->name('profile.signature.delete');

    // Alur Peminjam
    Route::get('/pengajuan', [LoanRequestController::class, 'index'])->name('loans.index');
    Route::get('/pengajuan/baru', [LoanRequestController::class, 'create'])->name('loans.create');
    Route::post('/pengajuan', [LoanRequestController::class, 'store'])->name('loans.store');
    Route::get('/pengajuan/{id}', [LoanRequestController::class, 'show'])->name('loans.show');
    Route::post('/pengajuan/{id}/kembalikan', [LoanRequestController::class, 'requestReturn'])->name('loans.request-return');
    Route::post('/pengajuan/{id}/tanda-tangan', [LoanRequestController::class, 'signDocument'])->name('loans.sign');
    Route::post('/pengajuan/{id}/foto', [LoanRequestController::class, 'uploadPhotos'])->name('loans.photos.upload');
    Route::delete('/pengajuan/foto/{photoId}', [LoanRequestController::class, 'deletePhoto'])->name('loans.photos.delete');

    // Alur Pengelola BMN
    Route::prefix('kelola')->name('kelola.')->group(function () {
        Route::get('/antrean', [LoanRequestController::class, 'manageQueue'])->name('queue');
        Route::post('/pengajuan/{id}/telaah', [LoanRequestController::class, 'review'])->name('review');
        Route::post('/pengajuan/{id}/buat-bast', [LoanRequestController::class, 'issueBast'])->name('issue-bast');
        Route::post('/pengajuan/{id}/konfirmasi-aktif', [LoanRequestController::class, 'confirmActive'])->name('confirm-active');
        Route::post('/pengajuan/{id}/cek-kembali', [LoanRequestController::class, 'processReturnCheck'])->name('process-return-check');
        Route::post('/pengajuan/{id}/selesai-kembali', [LoanRequestController::class, 'finalizeReturn'])->name('finalize-return');

        // Fitur Unggah & Impor Data .xlsx
        Route::get('/import-data', [\App\Http\Controllers\DataImportController::class, 'index'])->name('import.index');
        Route::post('/import-data', [\App\Http\Controllers\DataImportController::class, 'upload'])->name('import.upload');
        Route::post('/import-data/commit', [\App\Http\Controllers\DataImportController::class, 'commitImport'])->name('import.commit');
        Route::get('/import-data/template', [\App\Http\Controllers\DataImportController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/import-data/export', [\App\Http\Controllers\DataImportController::class, 'exportAssets'])->name('import.export');
    });

    // Alur Ketua Tim Keuangan & Aset
    Route::prefix('persetujuan')->name('persetujuan.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/{id}', [ApprovalController::class, 'show'])->name('show');
        Route::post('/{id}/setujui', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('/{id}/revisi', [ApprovalController::class, 'requestRevision'])->name('revision');
        Route::post('/{id}/tolak', [ApprovalController::class, 'reject'])->name('reject');
    });

    // Alias routes untuk kompatibilitas nama approval.*
    Route::get('/persetujuan-alias', [ApprovalController::class, 'index'])->name('approval.index');
    Route::get('/persetujuan-alias/{id}', [ApprovalController::class, 'show'])->name('approval.show');

    // Audit Aktivitas Peminjaman BMN (Khusus Transaksi Peminjaman BMN, Bebas Data Sensitif)
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/peminjaman', [\App\Http\Controllers\LoanAuditController::class, 'index'])->name('loans');
        Route::get('/peminjaman/export', [\App\Http\Controllers\LoanAuditController::class, 'export'])->name('loans.export');
    });

    // Administrasi Sistem & Log Aktivitas Forensik (Khusus Admin)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/log-aktivitas', [\App\Http\Controllers\AdminController::class, 'auditLogs'])->name('activity-logs');
        Route::get('/audit-logs', [\App\Http\Controllers\AdminController::class, 'auditLogs'])->name('audit-logs');
        Route::get('/log-aktivitas/export', [\App\Http\Controllers\AdminController::class, 'exportAuditLogs'])->name('activity-logs.export');
        Route::get('/audit-logs/export', [\App\Http\Controllers\AdminController::class, 'exportAuditLogs'])->name('audit-logs.export');
        Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('users');
        Route::get('/signatories', [\App\Http\Controllers\AdminController::class, 'signatories'])->name('signatories');
        Route::get('/settings', [\App\Http\Controllers\AdminController::class, 'settings'])->name('settings');
    });

    // Download & Preview PDF Resmi
    Route::get('/dokumen/{loanId}/pdf/{docType}', [DocumentController::class, 'showPdf'])->name('document.pdf');
});
