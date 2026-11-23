<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Web Karyawan - Apotek Astria 2 Farma')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'soft-teal': '#2A9D8F', 'off-white': '#F8F9FA', 'pure-white': '#FFFFFF',
                        'dark-slate': '#2B2D42', 'muted-coral': '#E76F51', 'border-color': '#E2E8F0', 'gray-text': '#64748B',
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        /* Pagination serasi tema terang & compact */
        .pagination-custom nav div.hidden.sm\:flex-1 > div:first-child { display: none !important; }
        .pagination-custom nav div.hidden.sm\:flex-1 > div:last-child { margin-left: auto; }
        .pagination-custom nav a,
        .pagination-custom nav span > span {
            padding: 0.375rem 0.625rem !important;
            font-size: 0.75rem !important;
            line-height: 1rem !important;
        }
        .pagination-custom nav span[aria-current="page"] > span {
            background-color: #2A9D8F !important;
            border-color: #2A9D8F !important;
            color: #FFFFFF !important;
            font-weight: 700 !important;
        }

        /* Sidebar desktop collapsible mode */
        @media (min-width: 1024px) {
            #appSidebar.collapsed {
                width: 4.75rem !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            #appSidebar.collapsed .sidebar-hide-on-collapse {
                display: none !important;
            }
            #appSidebar.collapsed .sidebar-header {
                flex-direction: column !important;
                align-items: center !important;
                gap: 0.5rem !important;
            }
            #appSidebar.collapsed .sidebar-nav-link {
                justify-content: center !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            #appSidebar.collapsed .sidebar-bottom-item {
                justify-content: center !important;
                padding-left: 0.25rem !important;
                padding-right: 0.25rem !important;
            }
        }
    </style>
</head>
<body class="bg-off-white text-dark-slate font-sans m-0 min-h-screen lg:h-screen lg:overflow-hidden">
    <div class="flex min-h-screen lg:h-screen flex-col lg:flex-row">

        <!-- Header Mobile (tampil di layar kecil/tablet < lg) -->
        <header class="lg:hidden sticky top-0 z-30 bg-pure-white border-b border-border-color px-4 py-3 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <button type="button" onclick="bukaSidebar()" aria-label="Buka menu navigasi"
                    class="p-2 -ml-1 rounded-lg border border-border-color text-dark-slate hover:bg-off-white transition">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <img src="{{ asset('img/logo-ular-nobg.png') }}" alt="Logo" class="w-8 h-8">
                <div class="leading-tight">
                    <div class="font-bold text-soft-teal text-sm">Astria Farma</div>
                    <div class="text-[11px] text-gray-text">Apotek Astria 2</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-[#E8F4F2] rounded-full flex items-center justify-center text-soft-teal font-bold text-xs uppercase">
                    {{ Auth::check() ? substr(Auth::user()->nama, 0, 1) : 'U' }}
                </div>
                <span class="text-xs font-semibold text-dark-slate max-w-[100px] truncate">
                    {{ Auth::check() ? Auth::user()->nama : 'User' }}
                </span>
            </div>
        </header>

        <!-- Backdrop Drawer Mobile -->
        <div id="sidebarBackdrop" onclick="tutupSidebar()"
            class="hidden fixed inset-0 bg-black/40 z-30 lg:hidden backdrop-blur-[1px]"></div>

        <!-- Memanggil Komponen Sidebar -->
        @include('layouts.sidebar')

        <!-- Area Konten Utama -->
        <main class="flex-1 min-w-0 p-3.5 sm:p-4 lg:p-5 flex flex-col lg:h-screen lg:overflow-hidden">
            @yield('content')
        </main>

    </div>

    <script>
        function bukaSidebar() {
            document.getElementById('appSidebar')?.classList.remove('-translate-x-full');
            document.getElementById('sidebarBackdrop')?.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function tutupSidebar() {
            document.getElementById('appSidebar')?.classList.add('-translate-x-full');
            document.getElementById('sidebarBackdrop')?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Toggle dan sinkronisasi status sidebar desktop dengan localStorage
        function toggleSidebarDesktop() {
            const sidebar = document.getElementById('appSidebar');
            if (!sidebar) return;
            const isCollapsed = sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
            const toggleIcon = document.getElementById('btnToggleDesktopIcon');
            if (toggleIcon) {
                toggleIcon.style.transform = isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        }

        function applySidebarState() {
            if (window.innerWidth < 1024) return;
            const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
            const sidebar = document.getElementById('appSidebar');
            const toggleIcon = document.getElementById('btnToggleDesktopIcon');
            if (sidebar) {
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    if (toggleIcon) toggleIcon.style.transform = 'rotate(180deg)';
                } else {
                    sidebar.classList.remove('collapsed');
                    if (toggleIcon) toggleIcon.style.transform = 'rotate(0deg)';
                }
            }
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                applySidebarState();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') tutupSidebar();
        });

        function tutupAlert(el) {
            if (!el || el.dataset.closing) return;
            el.dataset.closing = '1';
            el.classList.add('opacity-0');
            setTimeout(() => el.remove(), 300);
        }

        // Otomatis hilangkan notifikasi flash setelah 4 detik
        document.querySelectorAll('.flash-alert').forEach((alertEl) => {
            setTimeout(() => tutupAlert(alertEl), 4000);
        });
    </script>
</body>
</html>
