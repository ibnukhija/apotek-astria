@extends('layouts.app')

@section('title', 'Kasir - Apotek Astria 2')

@section('content')

<div class="flex justify-between items-center mb-5 shrink-0">
    <div>
        <h2 class="m-0 text-2xl font-bold">Kasir</h2>
        <span class="text-gray-text text-sm">Transaksi baru</span>
    </div>
    <span class="text-gray-text text-sm font-medium">
        {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
    </span>
</div>

<div class="flex-1 min-h-0 grid grid-cols-3 gap-5">

    {{-- ===== KIRI: PENCARIAN, KATEGORI, TABEL PRODUK ===== --}}
    <div class="col-span-2 min-h-0 bg-pure-white border border-border-color rounded-xl flex flex-col overflow-hidden shadow-sm">

        <form method="GET" action="{{ route('kasir.index') }}" id="filterForm"
              class="p-4 border-b border-border-color shrink-0">
            <input type="hidden" name="kategori" id="inputKategori" value="{{ request('kategori') }}">
            <div class="flex flex-wrap gap-2 items-center">
                <input type="text" name="q" id="searchProduct" value="{{ request('q') }}"
                    placeholder="Cari nama obat atau kode produk..." autocomplete="off"
                    class="flex-1 min-w-[200px] py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">

                <button type="button" data-kategori=""
                    class="pill px-4 py-2 rounded-lg text-sm font-bold border border-border-color {{ request('kategori') ? 'bg-pure-white' : 'bg-soft-teal text-white' }}">Semua</button>
                @foreach ($kategoris as $kategori)
                    <button type="button" data-kategori="{{ $kategori->id }}"
                        class="pill px-4 py-2 rounded-lg text-sm font-bold border border-border-color {{ request('kategori') == $kategori->id ? 'bg-soft-teal text-white' : 'bg-pure-white' }}">
                        {{ $kategori->nama_kategori }}
                    </button>
                @endforeach
            </div>
        </form>

        <div class="flex-1 overflow-y-auto transition-opacity" id="areaProduk">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 bg-off-white z-10">
                    <tr>
                        @foreach (['PRODUK', 'KATEGORI', 'STOK', 'HARGA'] as $judul)
                            <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">{{ $judul }}</th>
                        @endforeach
                        <th class="p-3 px-5 border-b border-border-color"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($obats as $obat)
                        @php($info = $obat->stok_info)
                        <tr class="border-b border-border-color hover:bg-off-white transition">
                            <td class="p-4 px-5 text-sm">
                                <strong>{{ $obat->nama_obat }}</strong><br>
                                <span class="text-xs text-gray-text">{{ $obat->kode_obat }} / {{ $obat->satuan }}</span>
                            </td>
                            <td class="p-4 px-5 text-sm">{{ $obat->kategori->nama_kategori ?? '-' }}</td>
                            <td class="p-4 px-5 text-sm font-semibold">
                                <span class="{{ $info['kelas'] }} px-2.5 py-1 rounded-full text-xs">{{ $obat->stok }} ({{ $info['label'] }})</span>
                            </td>
                            <td class="p-4 px-5 text-sm font-bold">Rp{{ number_format($obat->harga_jual, 0, ',', '.') }}</td>
                            <td class="p-4 px-5 text-center">
                                <button type="button"
                                    data-id="{{ $obat->id }}"
                                    data-nama="{{ $obat->nama_obat }}"
                                    data-harga="{{ (int) $obat->harga_jual }}"
                                    data-stok="{{ $obat->stok }}"
                                    class="btn-tambah w-8 h-8 rounded-lg bg-[#E8F4F2] text-soft-teal font-bold hover:bg-soft-teal hover:text-white transition {{ $obat->stok <= 0 ? 'opacity-40 pointer-events-none' : '' }}">+</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center text-gray-text">
                                {{ request()->hasAny(['q', 'kategori']) ? 'Tidak ada produk yang cocok.' : 'Belum ada produk tersedia.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="areaFooter" class="p-4 px-5 border-t border-border-color shrink-0 flex flex-wrap justify-between items-center gap-4">
            <div class="text-sm text-gray-text font-medium">
                Menampilkan {{ $obats->firstItem() ?? 0 }}-{{ $obats->lastItem() ?? 0 }} dari {{ $obats->total() }} produk
            </div>
            <div class="pagination-custom">{{ $obats->links() }}</div>
        </div>
    </div>

    {{-- ===== KANAN: RINGKASAN PESANAN ===== --}}
    <div class="min-h-0 bg-pure-white border border-border-color rounded-xl p-5 shadow-sm flex flex-col">
        <h3 class="m-0 mb-4 text-base font-bold">Ringkasan Pesanan</h3>
        <div id="daftarKeranjang" class="flex-1 overflow-y-auto text-sm"></div>
        <div class="border-t border-border-color pt-4 mt-4 flex justify-between items-center">
            <span class="font-bold">Total Harga</span>
            <span id="totalHarga" class="text-xl font-bold">Rp0</span>
        </div>

        <!-- Input Uang Diterima & Kembalian -->
        <div class="mt-4 mb-4">
            <label class="block text-xs font-semibold text-gray-text mb-1 uppercase">Uang Diterima (Rp)</label>
            <input type="number" id="uang_diterima" min="0" placeholder="0"
                class="w-full p-2.5 border border-border-color rounded-lg text-sm font-semibold outline-none focus:border-soft-teal"
                oninput="hitungKembalian()">

            <div class="flex justify-between items-center mt-2 text-sm">
                <span class="text-gray-text">Kembalian:</span>
                <span id="kembalian_teks" class="font-bold text-gray-800">Rp0</span>
            </div>
        </div>

        <button type="button" id="btn_bayar"
            class="w-full bg-soft-teal text-white p-3.5 rounded-lg font-bold hover:opacity-90 transition opacity-50 cursor-not-allowed" disabled>
            Bayar
        </button>
    </div>
</div>

<style>
    .pagination-custom nav div.hidden.sm\:flex-1 > div:first-child { display: none !important; }
    .pagination-custom nav div.hidden.sm\:flex-1 > div:last-child { margin-left: auto; }
</style>

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

/* ---------- KERANJANG ---------- */
const keranjang = {};   // { id: { id, nama, harga, stok, qty } }
const rupiah = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID');
let totalBelanja = 0; // Sesuaikan dengan variabel total belanja keranjang Anda

function hitungKembalian() {
    const inputUang = parseFloat(document.getElementById('uang_diterima').value) || 0;
    const kembalian = inputUang - totalBelanja;
    const kembalianEl = document.getElementById('kembalian_teks');
    const btnBayar = document.getElementById('btn_bayar'); // id tombol bayar

    if (totalBelanja === 0) {
        kembalianEl.innerText = 'Rp0';
        kembalianEl.className = 'font-bold text-gray-800';
        btnBayar.disabled = true;
        btnBayar.classList.add('opacity-50', 'cursor-not-allowed');
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
                <div class="font-semibold">${i.nama}</div>
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

function tambahItem(d) {
    const id = d.id;
    if (!keranjang[id]) {
        keranjang[id] = { id, nama: d.nama, harga: Number(d.harga), stok: Number(d.stok), qty: 0 };
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
    if (btn) tambahItem(btn.dataset);
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
btnBayar.addEventListener('click', async () => {
    const items = Object.values(keranjang).map(i => ({ obat_id: i.id, jumlah: i.qty }));
    if (!items.length) { alert('Keranjang masih kosong.'); return; }

    const inputUang = parseFloat(document.getElementById('uang_diterima').value) || 0;
    if (inputUang < totalBelanja) {
        alert('Nominal uang yang diterima kurang dari total belanja.');
        return;
    }

    btnBayar.disabled = true;
    btnBayar.innerText = 'Memproses...';

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
                total_bayar: inputUang
            })
        });
        const j = await res.json();

        if (res.ok && j.success) {
            alert('Pembayaran berhasil. Transaksi telah dicatat.');
            if (j.transaksi_id) {
                window.open('/kasir/struk/' + j.transaksi_id, '_blank');
            }
            window.location.reload();
        } else {
            const pesan = j.errors?.stok?.[0] || j.errors?.total_bayar?.[0] || j.message || 'Gagal memproses pembayaran.';
            alert(pesan);
            btnBayar.disabled = false;
            btnBayar.innerText = 'Bayar';
            hitungKembalian();
        }
    } catch (err) {
        console.error('Gagal memproses pembayaran:', err);
        alert('Terjadi kesalahan koneksi atau server.');
        btnBayar.disabled = false;
        btnBayar.innerText = 'Bayar';
        hitungKembalian();
    }
});

renderKeranjang();
</script>
@endsection