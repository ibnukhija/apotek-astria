@extends('layouts.app')

@section('title', 'Profil Saya - Apotek Astria 2')

@section('content')
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1 mb-4 shrink-0">
    <div>
        <h2 class="m-0 text-xl lg:text-2xl font-bold">Profil Saya</h2>
        <span class="text-gray-text text-xs sm:text-sm">Kelola data akun karyawan dan lihat jadwal operasional apotek</span>
    </div>
</div>

@if (session('success'))
    <div class="flash-alert mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm font-medium flex items-center justify-between transition-opacity duration-300">
        <span>{{ session('success') }}</span>
        <button type="button" onclick="tutupAlert(this.parentElement)" class="text-green-700 font-bold text-lg leading-none">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
        <ul class="list-disc list-inside m-0 space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
    {{-- Form Profil Karyawan --}}
    <div class="lg:col-span-2 bg-pure-white border border-border-color rounded-xl p-5 shadow-sm">
        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-border-color">
            <div class="w-12 h-12 bg-[#E8F4F2] rounded-full flex items-center justify-center text-soft-teal font-bold text-lg uppercase shrink-0">
                {{ substr($user->nama, 0, 1) }}
            </div>
            <div>
                <h3 class="m-0 text-base font-bold text-dark-slate">{{ $user->nama }}</h3>
                <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-text">
                    <span>{{ '@' . $user->username }}</span>
                    <span>&bull;</span>
                    <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-semibold uppercase">{{ $user->status }}</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('profil.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="nama" class="block text-xs font-bold text-dark-slate mb-1.5">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $user->nama) }}" required
                    class="w-full py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
            </div>

            <div>
                <label for="username" class="block text-xs font-bold text-dark-slate mb-1.5">Username Login</label>
                <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required
                    class="w-full py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-border-color">
                <div>
                    <label for="password" class="block text-xs font-bold text-dark-slate mb-1.5">Password Baru (opsional)</label>
                    <input type="password" id="password" name="password" placeholder="Kosongkan jika tidak diubah"
                        class="w-full py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-dark-slate mb-1.5">Ulangi Password Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Konfirmasi password baru"
                        class="w-full py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="px-5 py-2.5 bg-soft-teal text-white rounded-lg text-sm font-bold hover:opacity-90 transition shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    {{-- Kartu Status Operasional Apotek --}}
    <div class="bg-pure-white border border-border-color rounded-xl p-5 shadow-sm space-y-3">
        <div class="flex items-center justify-between gap-2">
            <h3 class="m-0 text-base font-bold text-dark-slate">Status Operasional Apotek</h3>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $profilApotek['is_buka'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ strtoupper($profilApotek['status_toko']) }}
            </span>
        </div>

        <div class="text-sm font-semibold text-dark-slate">{{ $profilApotek['nama_apotek'] }}</div>
        <div class="text-xs text-gray-text leading-relaxed">{{ $profilApotek['alamat'] }}</div>

        <div class="bg-off-white border border-border-color rounded-lg p-3 space-y-1.5 text-xs">
            <div class="flex justify-between gap-2">
                <span class="text-gray-text">Jadwal</span>
                <span class="font-semibold text-dark-slate text-right">{{ $profilApotek['jadwal_ringkas'] }}</span>
            </div>
            <div class="flex justify-between gap-2">
                <span class="text-gray-text">Keterangan</span>
                <span class="font-semibold {{ $profilApotek['is_buka'] ? 'text-green-700' : 'text-red-600' }} text-right">{{ $profilApotek['keterangan_status'] }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
