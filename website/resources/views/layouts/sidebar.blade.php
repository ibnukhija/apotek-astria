<aside id="appSidebar"
    class="fixed inset-y-0 left-0 z-40 w-60 lg:w-56 xl:w-60 bg-pure-white border-r border-border-color p-4 flex flex-col shrink-0 transform -translate-x-full lg:translate-x-0 lg:static transition-all duration-200 ease-out">
    <div class="sidebar-header flex items-center justify-between gap-2 mb-6">
        <div class="flex items-center gap-2 font-bold text-soft-teal text-base leading-tight min-w-0">
            <img src="{{ asset('img/logo-ular-nobg.png') }}" alt="Logo" class="w-10 h-10 shrink-0">
            <div class="sidebar-hide-on-collapse min-w-0 truncate">
                Astria Farma<br>
                <span class="text-[11px] text-gray-text font-normal">Apotek Astria 2</span>
            </div>
        </div>
        <!-- Tombol Tutup di Mobile -->
        <button type="button" onclick="tutupSidebar()" aria-label="Tutup menu"
            class="lg:hidden p-1.5 rounded-lg text-gray-text hover:text-dark-slate hover:bg-off-white text-xl leading-none">&times;</button>
        <!-- Tombol Toggle Minimize/Expand di Desktop -->
        <button type="button" onclick="toggleSidebarDesktop()" id="btnToggleDesktop" aria-label="Toggle sidebar desktop"
            title="Perkecil / Perbesar Menu Sidebar"
            class="hidden lg:flex p-1.5 rounded-lg text-gray-text hover:text-dark-slate hover:bg-off-white transition items-center justify-center shrink-0">
            <svg id="btnToggleDesktopIcon" class="w-5 h-5 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="11 17 6 12 11 7"></polyline>
                <polyline points="18 17 13 12 18 7"></polyline>
            </svg>
        </button>
    </div>

    <div class="sidebar-hide-on-collapse text-[11px] text-gray-text font-semibold tracking-wider mb-2 px-1">OPERASIONAL</div>

    <!-- Link Navigasi -->
    <nav class="flex-1 overflow-y-auto space-y-1">
        <a href="{{ route('dashboard') }}"
            title="Dashboard"
            class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('dashboard') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span class="sidebar-hide-on-collapse truncate">Dashboard</span>
        </a>
        <a href="{{ route('kasir.index') }}"
            title="Kasir"
            class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('kasir.index') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <line x1="2" y1="10" x2="22" y2="10"/>
                <circle cx="7" cy="15" r="1"/>
                <circle cx="12" cy="15" r="1"/>
            </svg>
            <span class="sidebar-hide-on-collapse truncate">Kasir</span>
        </a>
        <a href="{{ route('kasir.riwayat') }}"
            title="Riwayat Transaksi"
            class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('kasir.riwayat') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            <span class="sidebar-hide-on-collapse truncate">Riwayat Transaksi</span>
        </a>
        <a href="{{ route('obat.index') }}"
            title="Kelola Produk"
            class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('obat.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/>
                <path d="M3 8l9 5 9-5M12 13v8"/>
            </svg>
            <span class="sidebar-hide-on-collapse truncate">Kelola Produk</span>
        </a>
        <a href="{{ route('kondisi.index') }}"
            title="Kondisi Stok"
            class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('kondisi.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <span class="sidebar-hide-on-collapse truncate">Kondisi Stok</span>
        </a>
        @php($jumlahDraftOpname = \App\Models\StokOpname::where('status', \App\Models\StokOpname::STATUS_DRAFT)->count())
        <a href="{{ route('stok-opname.index') }}"
            title="Stok Opname Bulanan"
            class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('stok-opname.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                <line x1="8" y1="11" x2="16" y2="11"/>
                <line x1="8" y1="15" x2="16" y2="15"/>
                <line x1="8" y1="19" x2="12" y2="19"/>
            </svg>
            <span class="sidebar-hide-on-collapse truncate flex-1">Stok Opname Bulanan</span>
            @if ($jumlahDraftOpname > 0)
                <span class="sidebar-hide-on-collapse shrink-0 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold"
                    title="{{ $jumlahDraftOpname }} opname draft belum difinalisasi">{{ $jumlahDraftOpname }} Draft</span>
            @endif
        </a>
        <a href="{{ route('kategori.index') }}"
            title="Kelola Kategori"
            class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('kategori.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                <line x1="7" y1="7" x2="7.01" y2="7"/>
            </svg>
            <span class="sidebar-hide-on-collapse truncate">Kelola Kategori</span>
        </a>
        <a href="{{ route('profil.edit') }}"
            title="Profil Saya"
            class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('profil.*') ? 'font-bold bg-[#E8F4F2] text-soft-teal' : 'font-medium text-gray-text hover:bg-off-white' }} transition">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span class="sidebar-hide-on-collapse truncate">Profil Saya</span>
        </a>
    </nav>

    @php($statusApotek = \App\Models\ProfilApotek::ambilAtauDefault()->statusSaatIni())
    <div class="mt-auto pt-4 border-t border-border-color flex flex-col gap-3">
        <div class="sidebar-bottom-item px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center justify-between {{ $statusApotek['is_buka'] ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}"
            title="Status Toko: {{ strtoupper($statusApotek['status_toko']) }}">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="w-2 h-2 rounded-full shrink-0 {{ $statusApotek['is_buka'] ? 'bg-green-600' : 'bg-red-600' }}"></span>
                <span class="sidebar-hide-on-collapse truncate">Status Toko</span>
            </div>
            <span class="sidebar-hide-on-collapse font-bold">{{ strtoupper($statusApotek['status_toko']) }}</span>
        </div>

        <a href="{{ route('profil.edit') }}"
            title="Profil: {{ Auth::check() ? Auth::user()->nama : 'User' }}"
            class="sidebar-bottom-item flex items-center gap-2.5 rounded-lg p-1 -m-1 hover:bg-off-white transition">
            <div class="w-9 h-9 bg-[#E8F4F2] rounded-full flex items-center justify-center text-soft-teal font-bold text-sm uppercase shrink-0">
                {{ Auth::check() ? substr(Auth::user()->nama, 0, 1) : 'U' }}
            </div>
            <div class="sidebar-hide-on-collapse text-sm font-semibold leading-tight min-w-0 truncate">
                {{ Auth::check() ? Auth::user()->nama : 'User' }}<br>
                <span class="text-xs text-gray-text font-normal">Profil Karyawan</span>
            </div>
        </a>

        <!-- Tombol Logout -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                title="Logout Sistem"
                class="sidebar-bottom-item w-full bg-muted-coral text-white flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg font-bold text-sm hover:opacity-90 transition shadow-sm">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span class="sidebar-hide-on-collapse truncate">Logout Sistem</span>
            </button>
        </form>
    </div>
</aside>

<script>
    if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth >= 1024) {
        document.getElementById('appSidebar')?.classList.add('collapsed');
        var toggleIconEarly = document.getElementById('btnToggleDesktopIcon');
        if (toggleIconEarly) toggleIconEarly.style.transform = 'rotate(180deg)';
    }
</script>
