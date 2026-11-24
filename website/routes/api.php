<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\StokOpnameApiController;

// Route Login yang sudah dibuat
Route::post('/login-mobile', [AuthController::class, 'loginMobile']);

// Endpoint Logout Mobile (cukup terautentikasi Sanctum)
Route::post('/logout-mobile', [AuthController::class, 'logoutMobile'])->middleware('auth:sanctum');

// Endpoint Data untuk Android Mobile Owner (wajib token Sanctum + role owner aktif)
Route::middleware(['auth:sanctum', 'cekrole:owner'])->group(function () {
    Route::get('/dashboard', [ApiController::class, 'dashboard']);
    Route::get('/stok', [ApiController::class, 'stok']);

    // Stok opname (riwayat + detail untuk mobile Owner)
    Route::get('/stok-opname', [StokOpnameApiController::class, 'index']);
    Route::get('/stok-opname/{stokOpname}', [StokOpnameApiController::class, 'show']);
    Route::put('/obat/{id}', [ApiController::class, 'updateObatCepat']);
    Route::get('/riwayat', [ApiController::class, 'riwayat']);
    Route::get('/laporan', [ApiController::class, 'laporan']);
    Route::get('/laporan/export', [ApiController::class, 'exportLaporan']);
    Route::get('/profil', [ApiController::class, 'profil']);

    // Tambahan mobile owner: poin D (karyawan) dan E (simpan profil)
    Route::put('/profil', [OwnerController::class, 'updateProfil']);
    Route::get('/karyawan', [OwnerController::class, 'karyawanIndex']);
    Route::post('/karyawan', [OwnerController::class, 'karyawanStore']);
    Route::put('/karyawan/{id}', [OwnerController::class, 'karyawanUpdate']);
    Route::put('/karyawan/{id}/status', [OwnerController::class, 'karyawanToggleStatus']);

    // Notifikasi stok (Firebase Cloud Messaging)
    Route::get('/notifikasi', [NotifikasiController::class, 'show']);
    Route::put('/notifikasi', [NotifikasiController::class, 'update']);
    Route::post('/notifikasi/tes', [NotifikasiController::class, 'tes']);
    Route::post('/chatbot', [\App\Http\Controllers\Api\ChatbotController::class, 'kirim']);
});
