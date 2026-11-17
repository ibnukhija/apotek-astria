<aside id="appSidebar"
    class="fixed inset-y-0 left-0 z-40 w-60 lg:w-56 xl:w-60 bg-pure-white border-r border-border-color p-4 flex flex-col shrink-0 transform -translate-x-full lg:translate-x-0 lg:static transition-transform duration-200 ease-out">
    <div class="flex items-center justify-between gap-2 mb-6">
        <div class="flex items-center gap-2 font-bold text-soft-teal text-base leading-tight">
            <img src="{{ asset('img/logo-ular-nobg.png') }}" alt="Logo" class="w-10 h-10 shrink-0">
            <div>
                Astria Farma<br>
                <span class="text-[11px] text-gray-text font-normal">Apotek Astria 2</span>
            </div>
        </div>
        <button type="button" onclick="tutupSidebar()" aria-label="Tutup menu"
            class="lg:hidden p-1.5 rounded-lg text-gray-text hover:text-dark-slate hover:bg-off-white text-xl leading-none">&times;</button>
    </div>

    <div class="text-[11px] text-gray-text font-semibold tracking-wider mb-2 px-1">OPERASIONAL</div>

    <!-- Link Navigasi -->
    <nav class="flex-1 overflow-y-auto space-y-1">
        <a href="{{ route('kasir.index') }}" class="block px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('kasir.index') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">Kasir</a>
        <a href="{{ route('kasir.riwayat') }}" class="block px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('kasir.riwayat') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">Riwayat Transaksi</a>
        <a href="{{ route('obat.index') }}" class="block px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('obat.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">Kelola Produk</a>
        <a href="{{ route('kategori.index') }}" class="block px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('kategori.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">Kelola Kategori</a>
        <a href="{{ route('profil.edit') }}" class="block px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('profil.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">Profil Saya</a>
    </nav>

    @php($statusApotek = \App\Models\ProfilApotek::ambilAtauDefault()->statusSaatIni())
    <div class="mt-auto pt-4 border-t border-border-color flex flex-col gap-3">
        <div class="px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center justify-between {{ $statusApotek['is_buka'] ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            <span>Status Toko</span>
            <span>{{ strtoupper($statusApotek['status_toko']) }}</span>
        </div>

        <a href="{{ route('profil.edit') }}" class="flex items-center gap-2.5 rounded-lg p-1 -m-1 hover:bg-off-white transition" title="Lihat & ubah profil saya">
            <div class="w-9 h-9 bg-[#E8F4F2] rounded-full flex items-center justify-center text-soft-teal font-bold text-sm uppercase shrink-0">
                {{ Auth::check() ? substr(Auth::user()->nama, 0, 1) : 'U' }}
            </div>
            <div class="text-sm font-semibold leading-tight min-w-0 truncate">
                {{ Auth::check() ? Auth::user()->nama : 'User' }}<br>
                <span class="text-xs text-gray-text font-normal">Profil Karyawan</span>
            </div>
        </a>

        <!-- Tombol Logout -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full bg-muted-coral text-white text-center py-2.5 px-3 rounded-lg font-bold text-sm hover:opacity-90 transition shadow-sm">
                Logout Sistem
            </button>
        </form>
    </div>
</aside>
