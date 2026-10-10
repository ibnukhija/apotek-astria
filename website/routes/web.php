<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ObatController;
use App\Http\Controllers\KategoriController;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'cekrole:karyawan'])->group(function () {
    Route::resource('obat', ObatController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('kategori', KategoriController::class)->only(['index', 'store', 'update', 'destroy']);

    // Menambah kategori dari form produk (live search) via AJAX
    Route::post('/kategori/ajax', [KategoriController::class, 'storeAjax'])->name('kategori.storeAjax');
});
