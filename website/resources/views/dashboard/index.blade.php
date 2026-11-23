@extends('layouts.app')

@section('title', 'Dashboard Kasir & Operasional - Apotek Astria 2 Farma')

@section('content')
<div class="flex-1 overflow-y-auto pr-1 pb-4 flex flex-col gap-4">

    {{-- ================= HEADER DASHBOARD ================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-pure-white border border-border-color rounded-xl p-4 shadow-sm shrink-0">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl lg:text-2xl font-bold text-dark-slate m-0">Dashboard Operasional</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusApotek['is_buka'] ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300' }}">
                    {{ strtoupper($statusApotek['status_toko']) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-gray-text mt-1 mb-0">
                Ringkasan kasir, kondisi stok, dan tindakan cepat hari ini — {{ \Carbon\Carbon::today()->translatedFormat('l, d F Y') }}
            </p>
        </div>

        {{-- Pintasan Cepat Utama --}}
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('kasir.index') }}"
                class="bg-soft-teal text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <line x1="2" y1="10" x2="22" y2="10"/>
                    <circle cx="7" cy="15" r="1"/>
                    <circle cx="12" cy="15" r="1"/>
                </svg>
                <span>Buka Kasir</span>
            </a>
            <a href="{{ route('kondisi.index') }}"
                class="bg-pure-white border border-border-color text-dark-slate px-3.5 py-2 rounded-lg text-sm font-semibold hover:bg-off-white transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4 text-soft-teal shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <span>Stok Opname</span>
            </a>
            <a href="{{ route('obat.index') }}"
                class="bg-pure-white border border-border-color text-dark-slate px-3.5 py-2 rounded-lg text-sm font-semibold hover:bg-off-white transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4 text-soft-teal shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/>
                    <path d="M3 8l9 5 9-5M12 13v8"/>
                </svg>
                <span>Kelola Produk</span>
            </a>
        </div>
    </div>

    {{-- ================= KELOMPOK 1: KINERJA KASIR HARI INI ================= --}}
    <div>
        <div class="text-xs font-bold text-gray-text uppercase tracking-wider mb-2 px-1">Penjualan Kasir Hari Ini</div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {{-- Omset Hari Ini --}}
            <div class="bg-pure-white border border-border-color rounded-xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-text font-medium">Total Penjualan</div>
                    <div class="text-xl sm:text-2xl font-bold text-soft-teal mt-0.5">
                        Rp {{ number_format($penjualanHariIni, 0, ',', '.') }}
                    </div>
                    <div class="text-[11px] text-gray-text mt-0.5">Transaksi lunas hari ini</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-[#E8F4F2] flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-soft-teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
            </div>

            {{-- Jumlah Transaksi --}}
            <div class="bg-pure-white border border-border-color rounded-xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-text font-medium">Jumlah Transaksi</div>
                    <div class="text-xl sm:text-2xl font-bold text-dark-slate mt-0.5">
                        {{ $transaksiHariIni }} <span class="text-sm font-normal text-gray-text">transaksi</span>
                    </div>
                    <div class="text-[11px] text-gray-text mt-0.5">Struk tercetak hari ini</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                </div>
            </div>

            {{-- Produk Terjual --}}
            <div class="bg-pure-white border border-border-color rounded-xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-text font-medium">Produk Terjual</div>
                    <div class="text-xl sm:text-2xl font-bold text-dark-slate mt-0.5">
                        {{ $itemTerjualHariIni }} <span class="text-sm font-normal text-gray-text">unit</span>
                    </div>
                    <div class="text-[11px] text-gray-text mt-0.5">Total kuantitas item kasir</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= KELOMPOK 2: RINGKASAN & PERINGATAN STOK ================= --}}
    <div>
        <div class="text-xs font-bold text-gray-text uppercase tracking-wider mb-2 px-1">Ringkasan &amp; Kondisi Stok Obat</div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- Total Obat --}}
            <div class="bg-pure-white border border-border-color rounded-xl p-3.5 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#E8F4F2] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-soft-teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/>
                        <path d="M3 8l9 5 9-5M12 13v8"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-text truncate">Total Obat Aktif</div>
                    <div class="text-lg lg:text-xl font-bold text-dark-slate leading-tight">
                        {{ $totalProduk }} <span class="text-xs font-normal text-gray-text">({{ $totalKategori }} kat)</span>
                    </div>
                </div>
            </div>

            {{-- Stok Cukup --}}
            <div class="bg-pure-white border border-border-color rounded-xl p-3.5 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-green-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M8 12l3 3 5-6"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-text truncate">Stok Cukup (≥ 25)</div>
                    <div class="text-lg lg:text-xl font-bold text-green-700 leading-tight">
                        {{ $stokCukup }} <span class="text-xs font-semibold text-green-600">Aman</span>
                    </div>
                </div>
            </div>

            {{-- Stok Menipis --}}
            <div class="bg-pure-white border border-border-color rounded-xl p-3.5 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3l10 18H2L12 3z"/>
                        <path d="M12 10v5M12 18v.5"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-text truncate">Stok Menipis (&lt; 25)</div>
                    <div class="text-lg lg:text-xl font-bold text-amber-700 leading-tight">
                        {{ $stokMenipis + $stokHampirHabis }} <span class="text-xs font-semibold text-red-600">({{ $stokHampirHabis }} kritis)</span>
                    </div>
                </div>
            </div>

            {{-- Expired & Rusak --}}
            <div class="bg-pure-white border border-border-color rounded-xl p-3.5 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v6M12 16.5v.5"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs text-gray-text truncate">Kadaluarsa &amp; Rusak</div>
                    <div class="text-lg lg:text-xl font-bold text-red-700 leading-tight">
                        {{ $hampirExpired + $sudahExpired }} <span class="text-xs font-normal text-gray-text">exp • {{ $unitRusak }} rsk</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= KELOMPOK 3: DUA KOLOM WIDGET ================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Widget 1: Transaksi Terakhir --}}
        <div class="bg-pure-white border border-border-color rounded-xl p-4 shadow-sm flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-border-color mb-3">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-soft-teal shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                    <span class="text-sm font-bold text-dark-slate">Transaksi Kasir Terakhir</span>
                </div>
                <a href="{{ route('kasir.riwayat') }}" class="text-xs text-soft-teal hover:underline font-semibold">
                    Semua Riwayat &rarr;
                </a>
            </div>

            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead>
                        <tr class="text-gray-text border-b border-border-color">
                            <th class="py-2 pr-2 font-semibold">No. Transaksi</th>
                            <th class="py-2 px-2 font-semibold">Waktu</th>
                            <th class="py-2 px-2 font-semibold text-right">Total</th>
                            <th class="py-2 pl-2 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-color">
                        @forelse ($transaksiTerbaru as $trx)
                            <tr class="hover:bg-off-white transition">
                                <td class="py-2.5 pr-2 font-bold text-dark-slate">
                                    {{ $trx->no_transaksi }}
                                    <div class="text-[10px] font-normal text-gray-text">
                                        {{ $trx->user->nama ?? 'Kasir' }} • {{ $trx->metode_bayar_label }}
                                    </div>
                                </td>
                                <td class="py-2.5 px-2 text-gray-text whitespace-nowrap">
                                    {{ $trx->created_at ? $trx->created_at->format('H:i') : '-' }}
                                    <div class="text-[10px] text-gray-text">{{ $trx->created_at ? $trx->created_at->format('d/m') : '' }}</div>
                                </td>
                                <td class="py-2.5 px-2 text-right font-bold text-dark-slate whitespace-nowrap">
                                    Rp {{ number_format($trx->total_harga, 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 pl-2 text-center whitespace-nowrap">
                                    <a href="{{ route('kasir.struk', $trx->id) }}" target="_blank"
                                        class="px-2 py-1 bg-off-white hover:bg-border-color rounded text-[11px] font-semibold text-dark-slate transition">
                                        Struk
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-text">
                                    Belum ada transaksi kasir yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Widget 2: Obat Perlu Perhatian / Restok --}}
        <div class="bg-pure-white border border-border-color rounded-xl p-4 shadow-sm flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-border-color mb-3">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="text-sm font-bold text-dark-slate">Obat Perlu Perhatian Segera</span>
                </div>
                <a href="{{ route('kondisi.index') }}" class="text-xs text-soft-teal hover:underline font-semibold">
                    Cek Opname &rarr;
                </a>
            </div>

            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead>
                        <tr class="text-gray-text border-b border-border-color">
                            <th class="py-2 pr-2 font-semibold">Produk</th>
                            <th class="py-2 px-2 font-semibold text-center">Stok</th>
                            <th class="py-2 px-2 font-semibold">Kadaluarsa</th>
                            <th class="py-2 pl-2 font-semibold text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-color">
                        @forelse ($obatKritis as $item)
                            @php($stokInfo = $item->stok_info)
                            @php($kadInfo = $item->kadaluarsa_info)
                            <tr class="hover:bg-off-white transition">
                                <td class="py-2.5 pr-2 font-bold text-dark-slate">
                                    {{ $item->nama_obat }}
                                    <div class="text-[10px] font-normal text-gray-text">{{ $item->kode_obat }}</div>
                                </td>
                                <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $stokInfo['kelas'] }}">
                                        {{ $item->stok }} {{ $item->satuan }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-2 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $kadInfo['kelas'] }}">
                                        {{ $item->tgl_kadaluarsa ? $item->tgl_kadaluarsa->format('d/m/Y') : 'Belum diatur' }}
                                    </span>
                                </td>
                                <td class="py-2.5 pl-2 text-right whitespace-nowrap">
                                    <a href="{{ route('obat.index') }}?q={{ urlencode($item->nama_obat) }}"
                                        class="px-2 py-1 bg-[#E8F4F2] hover:bg-soft-teal hover:text-white rounded text-[11px] font-bold text-soft-teal transition">
                                        Restok
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-green-700 font-medium">
                                    ✓ Semua stok obat aman dan belum ada yang kadaluarsa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
