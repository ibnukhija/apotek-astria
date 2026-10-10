<div class="w-[250px] bg-pure-white border-r border-border-color p-5 flex flex-col shrink-0">
    <div class="flex items-center gap-2.5 mb-10 font-bold text-soft-teal text-lg">
        <div class="bg-soft-teal p-1.5 rounded-md text-white">💊</div>
        <div>Astria Farma<br><span class="text-[11px] text-gray-text font-normal">Apotek Astria 2</span></div>
    </div>
    <div class="text-xs text-gray-text font-semibold mb-2.5">OPERASIONAL</div>
    
    <!-- Link Navigasi -->
    <!-- Logika sederhana: Beri warna Soft Teal jika route sedang aktif -->
    <a href="#" class="block p-3 mb-2 rounded-lg {{ request()->routeIs('kasir.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">Kasir (Sprint 2)</a>
    <a href="{{ route('obat.index') }}" class="block p-3 mb-2 rounded-lg {{ request()->routeIs('obat.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">Kelola Produk</a>
    <a href="{{ route('kategori.index') }}" class="block p-3 mb-2 rounded-lg {{ request()->routeIs('kategori.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">Kelola Kategori</a>
    
    <div class="mt-auto pt-5 border-t border-border-color flex flex-col gap-4">
        <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 bg-[#E8F4F2] rounded-full flex items-center justify-center text-soft-teal font-bold text-base uppercase">
                {{ Auth::check() ? substr(Auth::user()->nama, 0, 1) : 'U' }}
            </div>
            <div class="text-sm font-semibold leading-tight">{{ Auth::check() ? Auth::user()->nama : 'User' }}<br><span class="text-xs text-gray-text font-normal">Akun Karyawan</span></div>
        </div>
        
        <!-- Tombol Logout -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full bg-muted-coral text-white text-center p-3.5 rounded-lg font-bold text-sm hover:opacity-90 transition shadow-sm">
                Logout Sistem
            </button>
        </form>
    </div>
</div>
