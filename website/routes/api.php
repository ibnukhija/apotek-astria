<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\OwnerController;

// Route Login yang sudah dibuat
Route::post('/login-mobile', [AuthController::class, 'loginMobile']);

// Endpoint Data untuk Android Mobile Owner
Route::get('/dashboard', [ApiController::class, 'dashboard']);
Route::get('/stok', [ApiController::class, 'stok']);
Route::put('/obat/{id}', [ApiController::class, 'updateObatCepat']);
Route::get('/riwayat', [ApiController::class, 'riwayat']);
Route::get('/profil', [ApiController::class, 'profil']);

// Tambahan mobile owner: poin D (karyawan) dan E (simpan profil)
Route::put('/profil', [OwnerController::class, 'updateProfil']);
Route::get('/karyawan', [OwnerController::class, 'karyawanIndex']);
Route::post('/karyawan', [OwnerController::class, 'karyawanStore']);
Route::put('/karyawan/{id}', [OwnerController::class, 'karyawanUpdate']);
Route::put('/karyawan/{id}/status', [OwnerController::class, 'karyawanToggleStatus']);