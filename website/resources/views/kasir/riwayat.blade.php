@extends('layouts.app')

@section('title', 'Riwayat Transaksi - Apotek Astria 2')

@section('content')

{{-- ================= JUDUL HALAMAN ================= --}}
<div class="flex justify-between items-center mb-5 shrink-0">
    <div>
        <h2 class="m-0 text-2xl font-bold">Riwayat Transaksi</h2>
        <span class="text-gray-text text-sm">Histori dan arsip penjualan obat di kasir</span>
    </div>

    <div class="flex items-center gap-4">
        <span class="text-gray-text text-sm font-medium">
            {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
        </span>
    </div>
</div>

{{-- ================= KOTAK RINGKASAN ================= --}}
<div class="grid grid-cols-3 gap-4 mb-5 shrink-0">
    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-[#E8F4F2] flex items-center justify-center shrink-0">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2A9D8F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
        </div>
        <div>
            <div class="text-xs text-gray-text font-medium">Total Transaksi</div>
            <div class="text-2xl font-bold text-dark-slate">
                {{ number_format($ringkasan['total_transaksi'] ?? $transaksis->total(), 0, ',', '.') }}
                <span class="text-xs font-normal text-gray-text">transaksi</span>
            </div>
        </div>
    </div>

    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div>
            <div class="text-xs text-gray-text font-medium">Total Pendapatan</div>
            <div class="text-2xl font-bold text-emerald-600">
                Rp{{ number_format($ringkasan['total_omset'] ?? 0, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            </svg>
        </div>
        <div>
            <div class="text-xs text-gray-text font-medium">Produk Terjual</div>
            <div class="text-2xl font-bold text-dark-slate">
                {{ number_format($ringkasan['total_item'] ?? 0, 0, ',', '.') }}
                <span class="text-xs font-normal text-gray-text">item</span>
            </div>
        </div>
    </div>
</div>

{{-- ================= TABEL & KONTROL RIWAYAT ================= --}}
<div class="flex-1 min-h-0 bg-pure-white border border-border-color rounded-xl flex flex-col overflow-hidden shadow-sm">

    {{-- Filter & Pencarian --}}
    <form method="GET" action="{{ route('kasir.riwayat') }}" class="p-3.5 px-5 border-b border-border-color bg-pure-white shrink-0 flex flex-wrap items-center justify-between gap-3">
        {{-- Kiri: Hanya Pencarian --}}
        <div class="relative w-80">
            <span class="absolute left-3 top-2.5 text-gray-text text-sm pointer-events-none">🔍</span>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari No. Trx / Kasir..."
                class="w-full py-2 px-3 pl-9 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal transition">
        </div>

        {{-- Kanan: Tanggal, Filter, dan Reset --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-gray-text shrink-0">Tanggal:</span>
                <input type="date" name="tanggal" value="{{ request('tanggal') }}" 
                    class="p-2 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal cursor-pointer">
            </div>

            <button type="submit" class="bg-soft-teal text-white px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90 shadow-sm transition flex items-center gap-1.5">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
                Filter
            </button>

            <a href="{{ route('kasir.riwayat') }}" 
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 border border-border-color px-3.5 py-2 rounded-lg text-sm font-semibold shadow-sm transition inline-flex items-center gap-1.5"
               title="Reset filter dan pencarian">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                    <path d="M3 3v5h5"/>
                </svg>
                Reset
            </a>
        </div>
    </form>

    {{-- Area Tabel yang Bisa Di-Scroll --}}
    <div class="flex-1 overflow-auto">
        <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 bg-off-white z-10 border-b border-border-color shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <tr>
                    <th class="p-3.5 px-5 text-xs text-gray-text font-bold uppercase tracking-wider whitespace-nowrap">NO. TRANSAKSI</th>
                    <th class="p-3.5 px-5 text-xs text-gray-text font-bold uppercase tracking-wider whitespace-nowrap">WAKTU</th>
                    <th class="p-3.5 px-5 text-xs text-gray-text font-bold uppercase tracking-wider whitespace-nowrap">KASIR</th>
                    <th class="p-3.5 px-5 text-xs text-gray-text font-bold uppercase tracking-wider">RINCIAN OBAT</th>
                    <th class="p-3.5 px-5 text-xs text-gray-text font-bold uppercase tracking-wider whitespace-nowrap">TOTAL</th>
                    <th class="p-3.5 px-5 text-xs text-gray-text font-bold uppercase tracking-wider whitespace-nowrap">PEMBAYARAN</th>
                    <th class="p-3.5 px-5 text-xs text-gray-text font-bold uppercase tracking-wider text-center whitespace-nowrap">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksis as $trx)
                <tr class="border-b border-border-color hover:bg-[#F8FBFA] transition">
                    <td class="p-3.5 px-5 text-sm whitespace-nowrap">
                        <span class="font-mono font-bold text-soft-teal bg-[#E8F4F2] px-2.5 py-1 rounded-md text-xs whitespace-nowrap inline-block">
                            {{ $trx->no_transaksi }}
                        </span>
                    </td>
                    <td class="p-3.5 px-5 text-sm whitespace-nowrap">
                        <div class="font-medium text-dark-slate">{{ $trx->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs text-gray-text mt-0.5">{{ $trx->created_at->format('H:i') }} WIB</div>
                    </td>
                    <td class="p-3.5 px-5 text-sm whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-[#E8F4F2] text-soft-teal flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                {{ substr($trx->user->nama ?? 'Kasir', 0, 1) }}
                            </div>
                            <span class="font-medium text-dark-slate">{{ $trx->user->nama ?? 'Kasir' }}</span>
                        </div>
                    </td>
                    <td class="p-3.5 px-5 text-sm">
                        <div class="font-semibold text-xs text-dark-slate mb-0.5">
                            <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[11px] font-bold">
                                {{ $trx->details->sum('qty') }} item
                            </span>
                        </div>
                        <div class="text-xs text-gray-text max-w-xs truncate" title="{{ $trx->details->map(fn($d) => ($d->obat->nama_obat ?? 'Obat') . ' (' . $d->qty . ')')->join(', ') }}">
                            {{ $trx->details->map(fn($d) => ($d->obat->nama_obat ?? 'Obat') . ' (' . $d->qty . ')')->join(', ') }}
                        </div>
                    </td>
                    <td class="p-3.5 px-5 text-sm font-bold text-dark-slate whitespace-nowrap">
                        Rp{{ number_format($trx->total_harga, 0, ',', '.') }}
                    </td>
                    <td class="p-3.5 px-5 text-sm whitespace-nowrap">
                        <div class="text-xs">
                            <span class="text-gray-text">Bayar:</span> 
                            <span class="font-semibold text-dark-slate">Rp{{ number_format($trx->total_bayar, 0, ',', '.') }}</span>
                        </div>
                        <div class="text-xs text-soft-teal font-medium mt-0.5">
                            Kembali: Rp{{ number_format($trx->kembalian, 0, ',', '.') }}
                        </div>
                    </td>
                    <td class="p-3.5 px-5 text-sm text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" 
                                data-trx="{{ json_encode([
                                    'no_transaksi' => $trx->no_transaksi,
                                    'waktu' => $trx->created_at->format('d/m/Y H:i') . ' WIB',
                                    'kasir' => $trx->user->nama ?? 'Kasir',
                                    'total_harga' => number_format($trx->total_harga, 0, ',', '.'),
                                    'total_bayar' => number_format($trx->total_bayar, 0, ',', '.'),
                                    'kembalian' => number_format($trx->kembalian, 0, ',', '.'),
                                    'struk_url' => route('kasir.struk', $trx->id),
                                    'items' => $trx->details->map(fn($d) => [
                                        'nama' => $d->obat->nama_obat ?? 'Produk',
                                        'kode' => $d->obat->kode_obat ?? '-',
                                        'satuan' => $d->obat->satuan ?? '',
                                        'qty' => $d->qty,
                                        'harga' => number_format($d->harga_satuan, 0, ',', '.'),
                                        'subtotal' => number_format($d->subtotal, 0, ',', '.')
                                    ])
                                ]) }}"
                                onclick="bukaDetailModal(JSON.parse(this.dataset.trx))"
                                class="px-2.5 py-1.5 border border-border-color bg-pure-white text-dark-slate hover:bg-off-white rounded-lg text-xs font-semibold transition"
                                title="Lihat Rincian Item">
                                Detail
                            </button>
                            <a href="{{ route('kasir.struk', $trx->id) }}" target="_blank" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-soft-teal text-white rounded-lg text-xs font-semibold hover:opacity-90 shadow-sm transition"
                               title="Cetak Struk Transaksi">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                    <rect x="6" y="14" width="12" height="8"></rect>
                                </svg>
                                Struk
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-12 text-center text-gray-text">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-14 h-14 rounded-full bg-off-white flex items-center justify-center mb-3">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </div>
                            <div class="font-semibold text-base text-dark-slate mb-1">
                                @if(request()->hasAny(['tanggal', 'q']))
                                    Tidak ada transaksi yang cocok dengan filter
                                @else
                                    Belum ada riwayat transaksi
                                @endif
                            </div>
                            <p class="text-xs text-gray-text max-w-sm m-0 mb-3">
                                @if(request()->hasAny(['tanggal', 'q']))
                                    Coba ganti filter tanggal atau kata kunci pencarian Anda.
                                @else
                                    Transaksi penjualan kasir yang telah selesai akan otomatis tercatat di sini.
                                @endif
                            </p>
                            @if(request()->hasAny(['tanggal', 'q']))
                                <a href="{{ route('kasir.riwayat') }}" class="px-4 py-2 bg-soft-teal text-white rounded-lg text-xs font-semibold hover:opacity-90 transition">
                                    Reset Filter
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Keterangan & Pagination (Tetap di Bawah) --}}
    <div class="p-4 px-5 border-t border-border-color bg-pure-white shrink-0 flex flex-wrap justify-between items-center gap-4">
        <div class="text-sm text-gray-text font-medium">
            Menampilkan {{ $transaksis->firstItem() ?? 0 }}-{{ $transaksis->lastItem() ?? 0 }} dari {{ $transaksis->total() }} transaksi
            <span class="block text-xs font-normal mt-0.5">
                Maksimal 20 transaksi ditampilkan per halaman
            </span>
        </div>
        <div class="pagination-custom">
            {{ $transaksis->links() }}
        </div>
    </div>
</div>

{{-- ================= MODAL DETAIL TRANSAKSI ================= --}}
<div id="detailModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-pure-white w-full max-w-xl rounded-xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
        {{-- Header Modal --}}
        <div class="p-5 border-b border-border-color flex justify-between items-center bg-off-white shrink-0">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="m-0 text-lg font-bold text-dark-slate">Rincian Transaksi</h3>
                    <span id="mdlNoTrx" class="font-mono text-xs font-bold text-soft-teal bg-[#E8F4F2] px-2 py-0.5 rounded"></span>
                </div>
                <div class="text-xs text-gray-text mt-1">
                    <span id="mdlWaktu"></span> &bull; Kasir: <span id="mdlKasir" class="font-medium text-dark-slate"></span>
                </div>
            </div>
            <button type="button" onclick="tutupDetailModal()" class="text-gray-text hover:text-dark-slate text-xl font-bold p-1 rounded-md leading-none">&times;</button>
        </div>

        {{-- Tabel Item Belanja yang Bisa Di-Scroll di dalam Modal --}}
        <div class="flex-1 overflow-y-auto p-5">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border-color text-xs text-gray-text font-bold uppercase">
                        <th class="pb-2">Obat / Produk</th>
                        <th class="pb-2 text-center">Qty</th>
                        <th class="pb-2 text-right">Harga</th>
                        <th class="pb-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="mdlTableBody" class="divide-y divide-border-color">
                    {{-- Diisi secara dinamis oleh JS --}}
                </tbody>
            </table>
        </div>

        {{-- Footer Modal: Total, Bayar, Kembalian & Tombol Aksi --}}
        <div class="p-5 border-t border-border-color bg-off-white shrink-0">
            <div class="space-y-1.5 text-sm mb-4">
                <div class="flex justify-between text-dark-slate">
                    <span class="font-semibold">Total Belanja:</span>
                    <span id="mdlTotalHarga" class="font-bold text-soft-teal text-base"></span>
                </div>
                <div class="flex justify-between text-gray-text text-xs">
                    <span>Uang Diterima:</span>
                    <span id="mdlTotalBayar" class="font-medium text-dark-slate"></span>
                </div>
                <div class="flex justify-between text-gray-text text-xs">
                    <span>Kembalian:</span>
                    <span id="mdlKembalian" class="font-medium text-emerald-600"></span>
                </div>
            </div>

            <div class="flex justify-end gap-2.5">
                <button type="button" onclick="tutupDetailModal()"
                    class="px-4 py-2 border border-border-color bg-pure-white rounded-lg text-sm font-semibold hover:bg-gray-50 transition">
                    Tutup
                </button>
                <a id="mdlBtnStruk" href="#" target="_blank"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-soft-teal text-white rounded-lg text-sm font-semibold hover:opacity-90 shadow-sm transition">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Cetak Struk
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ================= CSS ================= --}}
<style>
    /* Merapikan Pagination Tailwind Bawaan Laravel */
    .pagination-custom nav div.hidden.sm\:flex-1 > div:first-child { display: none !important; }
    .pagination-custom nav div.hidden.sm\:flex-1 > div:last-child { margin-left: auto; }
</style>

{{-- ================= JAVASCRIPT ================= --}}
<script>
    function bukaDetailModal(data) {
        document.getElementById('mdlNoTrx').textContent = data.no_transaksi;
        document.getElementById('mdlWaktu').textContent = data.waktu;
        document.getElementById('mdlKasir').textContent = data.kasir;
        document.getElementById('mdlTotalHarga').textContent = 'Rp' + data.total_harga;
        document.getElementById('mdlTotalBayar').textContent = 'Rp' + data.total_bayar;
        document.getElementById('mdlKembalian').textContent = 'Rp' + data.kembalian;
        document.getElementById('mdlBtnStruk').href = data.struk_url;

        const tbody = document.getElementById('mdlTableBody');
        tbody.innerHTML = '';

        data.items.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="py-2.5">
                    <div class="font-semibold text-dark-slate">${item.nama}</div>
                    <div class="text-[11px] text-gray-text">${item.kode && item.kode !== '-' ? item.kode + ' &bull; ' : ''}${item.satuan || ''}</div>
                </td>
                <td class="py-2.5 text-center font-medium">${item.qty}</td>
                <td class="py-2.5 text-right text-gray-text">Rp${item.harga}</td>
                <td class="py-2.5 text-right font-semibold text-dark-slate">Rp${item.subtotal}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('detailModal').classList.remove('hidden');
    }

    function tutupDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }

    // Menutup modal jika area di luar kotak modal diklik
    document.getElementById('detailModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            tutupDetailModal();
        }
    });

    // Menutup modal jika tombol Escape ditekan
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            tutupDetailModal();
        }
    });
</script>
@endsection
