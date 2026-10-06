<!-- Gunakan Layout Utama -->
@extends('layouts.app')

@section('title', 'Kelola Produk - Apotek Astria 2 Farma')

<!-- Isi bagian konten utama -->
@section('content')
    <div class="flex justify-between items-center mb-5 shrink-0">
        <div>
            <h2 class="m-0 text-2xl font-bold">Kelola Data Produk</h2>
            <span class="text-gray-text text-sm">Pantau stok dan harga obat</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-gray-text text-sm font-medium">{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</span>
            <button class="bg-soft-teal text-white px-5 py-2.5 border-none rounded-lg font-bold shadow-sm cursor-pointer hover:opacity-90 transition" onclick="document.getElementById('productModal').classList.remove('hidden')">
                + Tambah Produk
            </button>
        </div>
    </div>
    
    @if(session('success'))
    <div class="bg-[#E8F4F2] text-soft-teal p-4 rounded-lg mb-5 font-semibold border border-soft-teal">
        {{ session('success') }}
    </div>
    @endif
    
    <!-- Kontainer Tabel -->
    <div class="flex-1 bg-pure-white border border-border-color rounded-xl flex flex-col overflow-hidden shadow-sm">
        <!-- Taruh Header Pencarian & Tabel Looping Obat di sini -->
        <div class="p-4 flex-1 overflow-y-auto flex items-center justify-center text-gray-text">
            [Tabel Data Obat Anda Berada Di Sini Sesuai Kode Sebelumnya]
        </div>
    </div>

    <!-- Modal Tambah Produk -->
    <!-- (Kode Modal Tambah Produk diletakkan di bagian paling bawah file ini) -->
@endsection
