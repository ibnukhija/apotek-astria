@extends('layouts.app')

@section('title', 'Kasir - Apotek Astria 2')

@section('content')

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1 mb-4 shrink-0">
    <div>
        <h2 class="m-0 text-xl lg:text-2xl font-bold">Kasir</h2>
        <span class="text-gray-text text-xs sm:text-sm">Transaksi baru</span>
    </div>
    <span class="text-gray-text text-xs sm:text-sm font-medium">
        {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
    </span>
</div>

<div class="lg:flex-1 lg:min-h-0 grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- ===== KIRI: PENCARIAN, KATEGORI, TABEL PRODUK ===== --}}
    <div class="lg:col-span-2 min-h-[420px] lg:min-h-0 bg-pure-white border border-border-color rounded-xl flex flex-col overflow-hidden shadow-sm">

        <form method="GET" action="{{ route('kasir.index') }}" id="filterForm"
              class="p-3 px-4 border-b border-border-color shrink-0 space-y-2.5">
            <input type="hidden" name="kategori" id="inputKategori" value="{{ request('kategori') }}">
            <input type="text" name="q" id="searchProduct" value="{{ request('q') }}"
                placeholder="Cari nama obat atau kode produk..." autocomplete="off"
                class="w-full py-2 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">

            <div class="flex gap-1.5 items-center overflow-x-auto pb-0.5">
                <button type="button" data-kategori=""
                    class="pill shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold border border-border-color {{ request('kategori') ? 'bg-pure-white' : 'bg-soft-teal text-white' }}">Semua</button>
                @foreach ($kategoris as $kategori)
                    <button type="button" data-kategori="{{ $kategori->id }}"
                        class="pill shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold border border-border-color {{ request('kategori') == $kategori->id ? 'bg-soft-teal text-white' : 'bg-pure-white' }}">
                        {{ $kategori->nama_kategori }}
                    </button>
                @endforeach
            </div>
        </form>

        <div class="flex-1 overflow-auto transition-opacity" id="areaProduk">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 bg-off-white z-10">
                    <tr>
                        @foreach (['PRODUK', 'KATEGORI', 'STOK', 'HARGA'] as $judul)
                            <th class="py-2.5 px-3.5 text-[11px] text-gray-text font-bold uppercase border-b border-border-color whitespace-nowrap">{{ $judul }}</th>
                        @endforeach
                        <th class="py-2.5 px-3.5 border-b border-border-color"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($obats as $obat)
                        @php($info = $obat->stok_info)
                        @php($kadaluarsa = $obat->kadaluarsa_info)
                        @php($stokLayak = $obat->batches_exists ? (int) ($obat->stok_layak_jual ?? 0) : (int) $obat->stok)
                        @php($stokExpired = max(0, (int) $obat->stok - $stokLayak))
                        <tr class="border-b border-border-color hover:bg-off-white transition">
                            <td class="py-2.5 px-3.5 text-xs sm:text-[13px] min-w-[190px]">
                                <strong>{{ $obat->nama_obat }}</strong>
                                @if ($kadaluarsa['label'] !== 'Aman')
                                    <span class="{{ $kadaluarsa['kelas'] }} ml-1 px-2 py-0.5 rounded-full text-[10px] font-semibold whitespace-nowrap">{{ $kadaluarsa['label'] }}</span>
                                @endif
                                <br>
                                <span class="text-[11px] text-gray-text whitespace-nowrap">
                                    {{ $obat->kode_obat }} / {{ $obat->satuan }}
                                    @if ($obat->tgl_kadaluarsa)
                                        &bull; <span class="{{ $kadaluarsa['label'] === 'Kadaluarsa' ? 'text-red-600 font-semibold' : ($kadaluarsa['label'] === 'Hampir kadaluarsa' ? 'text-yellow-700 font-semibold' : '') }}">ED: {{ $obat->tgl_kadaluarsa->format('d/m/Y') }}</span>
                                    @endif
                                </span>
                            </td>
                            <td class="py-2.5 px-3.5 text-xs sm:text-[13px] whitespace-nowrap">{{ $obat->kategori->nama_kategori ?? '-' }}</td>
                            <td class="py-2.5 px-3.5 text-xs font-semibold whitespace-nowrap">
                                <span class="{{ $info['kelas'] }} inline-block px-2 py-0.5 rounded-full text-[11px] whitespace-nowrap">{{ $obat->stok }} ({{ $info['label'] }})</span>
                                @if ($stokExpired > 0)
                                    <div class="text-[10px] text-red-600 font-medium mt-0.5">{{ $stokLayak }} layak jual &bull; {{ $stokExpired }} expired</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3.5 text-xs sm:text-[13px] font-bold whitespace-nowrap">Rp{{ number_format($obat->harga_jual, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-3.5 text-center whitespace-nowrap">
                                <button type="button"
                                    data-id="{{ $obat->id }}"
                                    data-nama="{{ $obat->nama_obat }}"
                                    data-harga="{{ (int) $obat->harga_jual }}"
                                    data-stok="{{ $stokLayak }}"
                                    data-kadaluarsa="{{ $kadaluarsa['label'] === 'Kadaluarsa' ? '1' : '0' }}"
                                    data-ed="{{ $obat->tgl_kadaluarsa ? $obat->tgl_kadaluarsa->format('d/m/Y') : '-' }}"
                                    @if ($stokLayak <= 0) disabled title="{{ $stokExpired > 0 ? 'Semua stok batch sudah kadaluarsa, tindak lanjuti di Kondisi Obat' : 'Stok habis' }}" @endif
                                    class="btn-tambah w-8 h-8 rounded-lg bg-[#E8F4F2] text-soft-teal font-bold hover:bg-soft-teal hover:text-white transition {{ $stokLayak <= 0 ? 'opacity-40 pointer-events-none' : '' }}">+</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center text-gray-text text-sm">
                                {{ request()->hasAny(['q', 'kategori']) ? 'Tidak ada produk yang cocok.' : 'Belum ada produk tersedia.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="areaFooter" class="py-2.5 px-4 border-t border-border-color shrink-0 flex flex-col sm:flex-row justify-between items-center gap-2">
            <div class="text-xs text-gray-text font-medium">
                Menampilkan {{ $obats->firstItem() ?? 0 }}-{{ $obats->lastItem() ?? 0 }} dari {{ $obats->total() }} produk
            </div>
            <div class="pagination-custom shrink-0">{{ $obats->onEachSide(1)->links() }}</div>
        </div>
    </div>

    {{-- ===== KANAN: RINGKASAN PESANAN ===== --}}
    <div class="lg:min-h-0 bg-pure-white border border-border-color rounded-xl p-4 shadow-sm flex flex-col">
        <h3 class="m-0 mb-3 text-base font-bold">Ringkasan Pesanan</h3>
        <div id="daftarKeranjang" class="flex-1 min-h-[140px] overflow-y-auto text-sm"></div>
        <div class="border-t border-border-color pt-3 mt-3 flex justify-between items-center">
            <span class="font-bold text-sm">Total Harga</span>
            <span id="totalHarga" class="text-lg lg:text-xl font-bold">Rp0</span>
        </div>

        <!-- Pilihan Metode Bayar -->
        <div class="mt-3">
            <label class="block text-xs font-semibold text-gray-text uppercase mb-1.5">Metode Pembayaran</label>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" id="btnMetodeCash" onclick="pilihMetode('cash')"
                    class="py-2 px-3 rounded-lg text-xs sm:text-sm font-bold border border-soft-teal bg-[#E8F4F2] text-soft-teal transition">
                    Cash
                </button>
                <button type="button" id="btnMetodeQris" onclick="pilihMetode('qris')"
                    class="py-2 px-3 rounded-lg text-xs sm:text-sm font-bold border border-border-color bg-pure-white text-gray-text hover:bg-off-white transition">
                    QRIS
                </button>
            </div>
        </div>

        <!-- Input Uang Diterima & Kembalian (Khusus Cash) -->
        <div id="boxCash" class="mt-3 mb-3">
            <div class="flex justify-between items-center mb-1">
                <label class="block text-xs font-semibold text-gray-text uppercase">Uang Diterima (Rp)</label>
                <button type="button" onclick="setUangPas()" class="text-xs text-soft-teal hover:underline font-semibold cursor-pointer">Uang Pas</button>
            </div>
            <input type="text" inputmode="numeric" id="uang_diterima" placeholder="0" autocomplete="off"
                class="w-full p-2.5 border border-border-color rounded-lg text-sm font-semibold outline-none focus:border-soft-teal"
                oninput="formatUangDiterima(this)">

            <div class="flex justify-between items-center mt-2 text-sm">
                <span class="text-gray-text">Kembalian:</span>
                <span id="kembalian_teks" class="font-bold text-gray-800">Rp0</span>
            </div>
        </div>

        <!-- Info QRIS (Khusus QRIS) -->
        <div id="boxQrisInfo" class="hidden mt-3 mb-3 p-3 rounded-lg bg-blue-50 border border-blue-200 text-xs text-blue-800 leading-relaxed">
            Pembayaran non-tunai via <strong>QRIS (DANA Bisnis)</strong>. Klik <strong>Bayar</strong> untuk menampilkan kode QR dan konfirmasi pembayaran.
        </div>

        <button type="button" id="btn_bayar"
            class="w-full bg-soft-teal text-white p-3 rounded-lg font-bold hover:opacity-90 transition opacity-50 cursor-not-allowed" disabled>
            Bayar
        </button>
    </div>
</div>

{{-- ================= MODAL KONFIRMASI PEMBAYARAN QRIS ================= --}}
<div id="modalQris" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-pure-white w-full max-w-md max-h-[92vh] overflow-y-auto rounded-xl p-5 shadow-2xl border border-border-color text-center">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold mb-1.5">
            QRIS &bull; DANA Bisnis
        </div>
        <h3 class="m-0 text-base sm:text-lg font-bold text-dark-slate">Scan QRIS untuk Membayar</h3>
        <p class="text-xs text-gray-text mt-1 mb-3">Tunjukkan QR kepada pembeli dan pastikan nominal sesuai</p>

        <div class="bg-off-white border border-border-color rounded-xl p-3 sm:p-4 mb-4">
            <div class="text-xs text-gray-text mb-0.5">Total Tagihan</div>
            <div id="mdlQrisTotal" class="text-2xl font-bold text-soft-teal mb-2.5">Rp0</div>

            @if (file_exists(public_path('img/qris.jpeg')))
                <img src="{{ asset('img/qris.jpeg') }}?v={{ filemtime(public_path('img/qris.jpeg')) }}" alt="QRIS Apotek Astria"
                    class="w-auto max-h-72 sm:max-h-80 max-w-full object-contain mx-auto rounded-lg border border-border-color bg-white">
            @else
                <div class="w-48 h-48 mx-auto rounded-lg border border-border-color bg-white p-3 flex flex-col items-center justify-center">
                    <svg viewBox="0 0 100 100" class="w-36 h-36 text-dark-slate" fill="currentColor" aria-hidden="true">
                        <rect x="6" y="6" width="26" height="26" rx="3" fill="none" stroke="currentColor" stroke-width="5"/>
                        <rect x="13" y="13" width="12" height="12" rx="1"/>
                        <rect x="68" y="6" width="26" height="26" rx="3" fill="none" stroke="currentColor" stroke-width="5"/>
                        <rect x="75" y="13" width="12" height="12" rx="1"/>
                        <rect x="6" y="68" width="26" height="26" rx="3" fill="none" stroke="currentColor" stroke-width="5"/>
                        <rect x="13" y="75" width="12" height="12" rx="1"/>
                        <rect x="42" y="10" width="6" height="6"/><rect x="52" y="10" width="6" height="6"/>
                        <rect x="42" y="22" width="10" height="6"/><rect x="40" y="38" width="8" height="8"/>
                        <rect x="52" y="38" width="8" height="8"/><rect x="64" y="42" width="6" height="6"/>
                        <rect x="10" y="42" width="6" height="6"/><rect x="22" y="46" width="10" height="6"/>
                        <rect x="42" y="54" width="6" height="6"/><rect x="54" y="52" width="10" height="6"/>
                        <rect x="72" y="48" width="8" height="8"/><rect x="84" y="42" width="8" height="6"/>
                        <rect x="44" y="68" width="8" height="8"/><rect x="58" y="68" width="6" height="6"/>
                        <rect x="70" y="66" width="10" height="6"/><rect x="84" y="68" width="8" height="10"/>
                        <rect x="44" y="84" width="12" height="8"/><rect x="64" y="80" width="8" height="12"/>
                        <rect x="78" y="84" width="12" height="8"/>
                    </svg>
                    <span class="text-[10px] text-gray-text mt-1">QRIS Apotek Astria 2</span>
                </div>
                <p class="text-[11px] text-gray-text mt-2 mb-0">
                    Tip: simpan gambar QR DANA Bisnis ke <code class="bg-gray-100 px-1 rounded">public/img/qris.jpeg</code> untuk menampilkan QR asli.
                </p>
            @endif
        </div>

        <div class="flex gap-2.5">
            <button type="button" id="btnBatalQris" onclick="tutupModalQris()"
                class="flex-1 px-4 py-2.5 border border-border-color bg-pure-white rounded-lg text-sm font-semibold hover:bg-off-white transition">
                Batal
            </button>
            <button type="button" id="btnKonfirmasiQris"
                class="flex-1 px-4 py-2.5 bg-soft-teal text-white rounded-lg text-sm font-bold hover:opacity-90 shadow-sm transition">
                Sudah Dibayar
            </button>
        </div>
    </div>
</div>

{{-- ================= MODAL KONFIRMASI OBAT KADALUARSA ================= --}}
<div id="modalKadaluarsa" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-pure-white w-full max-w-md rounded-xl p-5 sm:p-6 shadow-2xl border border-border-color">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 text-red-700 flex items-center justify-center shrink-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3l10 18H2L12 3z"/><path d="M12 10v4"/><circle cx="12" cy="17.5" r=".5" fill="currentColor"/>
                </svg>
            </div>
            <div>
                <h3 class="m-0 text-base sm:text-lg font-bold text-dark-slate">Peringatan Obat Kadaluarsa</h3>
                <p class="text-xs text-gray-text mt-0.5 mb-0">Pastikan kondisi fisik obat sebelum dijual</p>
            </div>
        </div>

        <div class="bg-red-50 border border-red-200 rounded-lg p-3.5 text-xs sm:text-sm text-dark-slate mb-5 leading-relaxed">
            Produk <strong id="mdlExpNama"></strong> di sistem tercatat sudah lewat tanggal kadaluarsa
            (<strong id="mdlExpTanggal" class="text-red-700"></strong>).
            Pastikan kemasan obat yang akan diserahkan adalah stok baru dan belum kadaluarsa.
        </div>

        <div class="flex justify-end gap-2.5">
            <button type="button" onclick="tutupModalKadaluarsa()"
                class="px-4 py-2 border border-border-color bg-pure-white rounded-lg text-sm font-semibold hover:bg-off-white transition">
                Batal
            </button>
            <button type="button" onclick="konfirmasiTambahKadaluarsa()"
                class="px-4 py-2 bg-muted-coral text-white rounded-lg text-sm font-bold hover:opacity-90 shadow-sm transition">
                Ya, Tetap Tambahkan
            </button>
        </div>
    </div>
</div>

<script>
const $ = (id) => document.getElementById(id);
const formFilter = $('filterForm');
let timerCari, kontrolCari;

function buatUrl() {
    const params = new URLSearchParams();
    new FormData(formFilter).forEach((v, k) => { if (v !== '') params.append(k, v); });
    const qs = params.toString();
    return formFilter.action + (qs ? '?' + qs : '');
}

async function muatProduk(url) {
    kontrolCari?.abort();
    const kontrol = kontrolCari = new AbortController();
    const area = $('areaProduk');
    area.classList.add('opacity-50');
    try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: kontrol.signal });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const produkBaru = doc.getElementById('areaProduk');
        const footerBaru = doc.getElementById('areaFooter');
        if (!produkBaru || !footerBaru) { window.location.href = url; return; }
        $('areaProduk').innerHTML = produkBaru.innerHTML;
        $('areaFooter').innerHTML = footerBaru.innerHTML;
        history.replaceState(null, '', url);
    } catch (err) {
        if (err.name !== 'AbortError') console.error('Gagal memuat produk:', err);
    } finally {
        if (kontrolCari === kontrol) area.classList.remove('opacity-50');
    }
}

$('searchProduct').addEventListener('input', () => {
    clearTimeout(timerCari);
    timerCari = setTimeout(() => muatProduk(buatUrl()), 300);
});

// Tombol pill kategori
document.querySelectorAll('.pill').forEach(btn => {
    btn.addEventListener('click', () => {
        $('inputKategori').value = btn.dataset.kategori;
        document.querySelectorAll('.pill').forEach(b => {
            const aktif = b === btn;
            b.classList.toggle('bg-soft-teal', aktif);
            b.classList.toggle('text-white', aktif);
            b.classList.toggle('bg-pure-white', !aktif);
        });
        muatProduk(buatUrl());
    });
});

formFilter.addEventListener('submit', (e) => { e.preventDefault(); clearTimeout(timerCari); muatProduk(buatUrl()); });

$('areaFooter').addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a) return;
    e.preventDefault();
    muatProduk(a.href);
});

/* ---------- KERANJANG & METODE BAYAR ---------- */
const keranjang = {};   // { id: { id, nama, harga, stok, qty } }
const rupiah = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID');
let totalBelanja = 0;
let metodeBayar = 'cash';

function pilihMetode(metode) {
    metodeBayar = metode === 'qris' ? 'qris' : 'cash';
    const isQris = metodeBayar === 'qris';

    $('btnMetodeCash').className = isQris
        ? 'py-2 px-3 rounded-lg text-xs sm:text-sm font-bold border border-border-color bg-pure-white text-gray-text hover:bg-off-white transition'
        : 'py-2 px-3 rounded-lg text-xs sm:text-sm font-bold border border-soft-teal bg-[#E8F4F2] text-soft-teal transition';
    $('btnMetodeQris').className = isQris
        ? 'py-2 px-3 rounded-lg text-xs sm:text-sm font-bold border border-soft-teal bg-blue-50 text-blue-700 transition'
        : 'py-2 px-3 rounded-lg text-xs sm:text-sm font-bold border border-border-color bg-pure-white text-gray-text hover:bg-off-white transition';

    $('boxCash').classList.toggle('hidden', isQris);
    $('boxQrisInfo').classList.toggle('hidden', !isQris);
    $('btn_bayar').innerText = isQris ? 'Bayar dengan QRIS' : 'Bayar';

    perbaruiStatusTombolBayar();
}

function getUangDiterima() {
    const el = document.getElementById('uang_diterima');
    if (!el) return 0;
    const bersih = el.value.replace(/\D/g, '');
    return parseInt(bersih, 10) || 0;
}

function formatUangDiterima(el) {
    const cursorPosition = el.selectionStart;
    const oldLength = el.value.length;
    
    // Ambil hanya angka
    const bersih = el.value.replace(/\D/g, '');
    if (bersih) {
        el.value = Number(bersih).toLocaleString('id-ID');
    } else {
        el.value = '';
    }
    
    // Sesuaikan posisi kursor
    const newLength = el.value.length;
    const newCursor = Math.max(0, cursorPosition + (newLength - oldLength));
    el.setSelectionRange(newCursor, newCursor);
    
    hitungKembalian();
}

function setUangPas() {
    if (totalBelanja <= 0) return;
    const el = document.getElementById('uang_diterima');
    el.value = Number(totalBelanja).toLocaleString('id-ID');
    hitungKembalian();
}

function perbaruiStatusTombolBayar() {
    const btnBayar = document.getElementById('btn_bayar');
    if (totalBelanja === 0) {
        btnBayar.disabled = true;
        btnBayar.classList.add('opacity-50', 'cursor-not-allowed');
        return;
    }

    if (metodeBayar === 'qris') {
        btnBayar.disabled = false;
        btnBayar.classList.remove('opacity-50', 'cursor-not-allowed');
        return;
    }

    hitungKembalian();
}

function hitungKembalian() {
    const inputUang = getUangDiterima();
    const kembalian = inputUang - totalBelanja;
    const kembalianEl = document.getElementById('kembalian_teks');
    const btnBayar = document.getElementById('btn_bayar');

    if (totalBelanja === 0) {
        kembalianEl.innerText = 'Rp0';
        kembalianEl.className = 'font-bold text-gray-800';
        btnBayar.disabled = true;
        btnBayar.classList.add('opacity-50', 'cursor-not-allowed');
        return;
    }

    if (metodeBayar === 'qris') {
        btnBayar.disabled = false;
        btnBayar.classList.remove('opacity-50', 'cursor-not-allowed');
        return;
    }

    if (kembalian >= 0 && inputUang >= totalBelanja) {
        kembalianEl.innerText = 'Rp' + Math.round(kembalian).toLocaleString('id-ID');
        kembalianEl.className = 'font-bold text-soft-teal';
        btnBayar.disabled = false;
        btnBayar.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        kembalianEl.innerText = 'Uang Kurang (Rp' + Math.abs(Math.round(kembalian)).toLocaleString('id-ID') + ')';
        kembalianEl.className = 'font-bold text-muted-coral';
        btnBayar.disabled = true;
        btnBayar.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

function renderKeranjang() {
    const items = Object.values(keranjang);
    const area = $('daftarKeranjang');

    if (!items.length) {
        area.innerHTML = '<p class="text-gray-text">Belum ada produk dipilih.</p>';
        $('totalHarga').textContent = 'Rp0';
        totalBelanja = 0;
        hitungKembalian();
        return;
    }

    area.innerHTML = items.map(i => `
        <div class="flex justify-between items-start gap-3 py-3 border-b border-border-color">
            <div class="flex-1">
                <div class="font-semibold">
                    ${i.nama}
                    ${i.kadaluarsa ? '<span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-700">Cek tanggal kemasan</span>' : ''}
                </div>
                <div class="text-xs text-gray-text">${i.qty} x ${rupiah(i.harga)}</div>
                <div class="flex items-center gap-2 mt-2">
                    <button type="button" data-aksi="kurang" data-id="${i.id}" class="w-6 h-6 rounded border border-border-color font-bold">−</button>
                    <span class="w-6 text-center">${i.qty}</span>
                    <button type="button" data-aksi="tambah" data-id="${i.id}" class="w-6 h-6 rounded border border-border-color font-bold">+</button>
                    <button type="button" data-aksi="hapus" data-id="${i.id}" class="ml-2 text-xs text-muted-coral font-semibold">Hapus</button>
                </div>
            </div>
            <div class="font-bold">${rupiah(i.harga * i.qty)}</div>
        </div>
    `).join('');

    totalBelanja = items.reduce((s, i) => s + i.harga * i.qty, 0);
    $('totalHarga').textContent = rupiah(totalBelanja);
    hitungKembalian();
}

let itemKadaluarsaTertunda = null;

function bukaModalKadaluarsa(d) {
    itemKadaluarsaTertunda = d;
    $('mdlExpNama').textContent = d.nama;
    $('mdlExpTanggal').textContent = d.ed || '-';
    $('modalKadaluarsa').classList.remove('hidden');
}

function tutupModalKadaluarsa() {
    itemKadaluarsaTertunda = null;
    $('modalKadaluarsa').classList.add('hidden');
}

function konfirmasiTambahKadaluarsa() {
    if (itemKadaluarsaTertunda) {
        tambahItem(itemKadaluarsaTertunda);
    }
    tutupModalKadaluarsa();
}

$('modalKadaluarsa')?.addEventListener('click', (e) => {
    if (e.target.id === 'modalKadaluarsa') tutupModalKadaluarsa();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (!$('modalKadaluarsa').classList.contains('hidden')) tutupModalKadaluarsa();
        if (!$('modalQris').classList.contains('hidden')) tutupModalQris();
    }
});

$('modalQris')?.addEventListener('click', (e) => {
    if (e.target.id === 'modalQris') tutupModalQris();
});

function bukaModalQris() {
    $('mdlQrisTotal').textContent = rupiah(totalBelanja);
    $('modalQris').classList.remove('hidden');
}

function tutupModalQris() {
    $('modalQris').classList.add('hidden');
}

function tambahItem(d) {
    const id = d.id;
    if (!keranjang[id]) {
        keranjang[id] = {
            id,
            nama: d.nama,
            harga: Number(d.harga),
            stok: Number(d.stok),
            kadaluarsa: d.kadaluarsa === '1' || d.kadaluarsa === true,
            qty: 0
        };
    }
    if (keranjang[id].qty >= keranjang[id].stok) {
        alert('Jumlah melebihi stok tersedia (' + keranjang[id].stok + ').');
        return;
    }
    keranjang[id].qty++;
    renderKeranjang();
}

// Tombol + di tabel produk (delegasi, tetap jalan setelah tabel diganti AJAX)
$('areaProduk').addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-tambah');
    if (!btn) return;

    if (btn.dataset.kadaluarsa === '1' && !keranjang[btn.dataset.id]) {
        bukaModalKadaluarsa({ ...btn.dataset });
        return;
    }

    tambahItem(btn.dataset);
});

// Tombol di keranjang
$('daftarKeranjang').addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-aksi]');
    if (!btn) return;
    const item = keranjang[btn.dataset.id];
    if (!item) return;

    if (btn.dataset.aksi === 'tambah') tambahItem(item);
    if (btn.dataset.aksi === 'kurang') { item.qty--; if (item.qty <= 0) delete keranjang[item.id]; }
    if (btn.dataset.aksi === 'hapus') delete keranjang[item.id];
    renderKeranjang();
});

/* ---------- BAYAR (stok berkurang otomatis & transaksi tersimpan) ---------- */
const urlBayar = "{{ route('kasir.store') }}";
const csrfToken = "{{ csrf_token() }}";

const btnBayar = document.getElementById('btn_bayar');

async function prosesPembayaran(metode, totalBayar, tombolSumber) {
    const items = Object.values(keranjang).map(i => ({ obat_id: i.id, jumlah: i.qty }));
    if (!items.length) { alert('Keranjang masih kosong.'); return; }

    const teksAsli = tombolSumber.innerText;
    tombolSumber.disabled = true;
    tombolSumber.innerText = 'Memproses...';

    try {
        const res = await fetch(urlBayar, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                items,
                metode_bayar: metode,
                total_bayar: totalBayar
            })
        });
        const j = await res.json();

        if (res.ok && j.success) {
            tutupModalQris();
            alert('Pembayaran berhasil. Transaksi telah dicatat.');
            if (j.transaksi_id) {
                window.location.href = '/kasir/struk/' + j.transaksi_id;
                return;
            }
            window.location.reload();
        } else {
            const pesan = j.errors?.stok?.[0] || j.errors?.total_bayar?.[0] || j.message || 'Gagal memproses pembayaran.';
            alert(pesan);
            tombolSumber.disabled = false;
            tombolSumber.innerText = teksAsli;
            perbaruiStatusTombolBayar();
        }
    } catch (err) {
        console.error('Gagal memproses pembayaran:', err);
        alert('Terjadi kesalahan koneksi atau server.');
        tombolSumber.disabled = false;
        tombolSumber.innerText = teksAsli;
        perbaruiStatusTombolBayar();
    }
}

btnBayar.addEventListener('click', () => {
    const items = Object.values(keranjang);
    if (!items.length) { alert('Keranjang masih kosong.'); return; }

    if (metodeBayar === 'qris') {
        bukaModalQris();
        return;
    }

    const inputUang = getUangDiterima();
    if (inputUang < totalBelanja) {
        alert('Nominal uang yang diterima kurang dari total belanja.');
        return;
    }
    prosesPembayaran('cash', inputUang, btnBayar);
});

$('btnKonfirmasiQris').addEventListener('click', () => {
    prosesPembayaran('qris', totalBelanja, $('btnKonfirmasiQris'));
});

renderKeranjang();
</script>
@endsection