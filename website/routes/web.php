<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ObatController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\KondisiStokController;
use App\Http\Controllers\ProfilKaryawanController;
use App\Http\Controllers\StokOpnameController;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'cekrole:karyawan'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/obat/{id}/terima-stok', [ObatController::class, 'terimaStok'])->name('obat.terimaStok');

    Route::resource('obat', ObatController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/stok-opname', [\App\Http\Controllers\Api\StokOpnameApiController::class, 'index']);
    Route::get('/stok-opname/{stokOpname}', [\App\Http\Controllers\Api\StokOpnameApiController::class, 'show']);

    Route::get('/kondisi-stok', [KondisiStokController::class, 'index'])->name('kondisi.index');
    Route::post('/kondisi-stok', [KondisiStokController::class, 'store'])->name('kondisi.store');

    Route::get('/stok-opname', [StokOpnameController::class, 'index'])->name('stok-opname.index');
    Route::post('/stok-opname', [StokOpnameController::class, 'store'])->name('stok-opname.store');
    Route::get('/stok-opname/{stokOpname}', [StokOpnameController::class, 'show'])->name('stok-opname.show');
    Route::post('/stok-opname/{stokOpname}/detail/{detail}', [StokOpnameController::class, 'updateDetail'])->name('stok-opname.detail');
    Route::post('/stok-opname/{stokOpname}/finalisasi', [StokOpnameController::class, 'finalisasi'])->name('stok-opname.finalisasi');
    Route::post('/stok-opname/{stokOpname}/refresh-stok', [StokOpnameController::class, 'refreshStokSistem'])->name('stok-opname.refresh-stok');

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

    // Profil akun karyawan
    Route::get('/profil', [ProfilKaryawanController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilKaryawanController::class, 'update'])->name('profil.update');
});