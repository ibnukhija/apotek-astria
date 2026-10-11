<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ObatController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KasirController;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'cekrole:karyawan'])->group(function () {
    Route::resource('obat', ObatController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('kategori', KategoriController::class)->only(['index', 'store', 'update', 'destroy']);

    // Menambah kategori dari form produk (live search) via AJAX
    Route::post('/kategori/ajax', [KategoriController::class, 'storeAjax'])->name('kategori.storeAjax');

    // Generate Kode Obat via AJAX
    Route::get('/obat/generate-kode', [ObatController::class, 'generateKode'])->name('obat.generateKode');

    // Kasir: cari produk dan filter kategori
    Route::get('/kasir', [KasirController::class, 'index'])->name('kasir.index');
    Route::post('/kasir/bayar', [KasirController::class, 'bayar'])->name('kasir.bayar');
    Route::post('/kasir/transaksi', [KasirController::class, 'store'])->name('kasir.store');

    // Tambahan untuk riwayat & struk Sprint 2
    Route::get('/kasir/riwayat', [KasirController::class, 'riwayat'])->name('kasir.riwayat');
    Route::get('/kasir/struk/{id}', [KasirController::class, 'cetakStruk'])->name('kasir.struk');
});