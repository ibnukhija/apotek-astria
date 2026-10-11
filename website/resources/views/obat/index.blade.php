@extends('layouts.app')

@section('title', 'Kelola Produk - Apotek Astria 2')

@section('content')

{{-- ================= JUDUL HALAMAN ================= --}}
<div class="flex justify-between items-center mb-5 shrink-0">
    <div>
        <h2 class="m-0 text-2xl font-bold">Kelola Data Produk</h2>
        <span class="text-gray-text text-sm">Pantau stok, kategori, dan harga obat</span>
    </div>

    <div class="flex items-center gap-4">
        <span class="text-gray-text text-sm font-medium">
            {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
        </span>

        <button type="button" onclick="bukaModal('add')"
            class="bg-soft-teal text-white px-5 py-2.5 border-none rounded-lg font-bold shadow-sm cursor-pointer hover:opacity-90 transition">
            + Tambah Produk
        </button>
    </div>
</div>

{{-- ================= NOTIFIKASI ================= --}}
@if (session('success'))
    <div class="bg-[#E8F4F2] text-soft-teal p-4 rounded-lg mb-5 font-semibold border border-soft-teal shrink-0">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-5 border border-red-300 shrink-0">
        <ul class="list-disc pl-5 m-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ================= KOTAK RINGKASAN ================= --}}
<div class="grid grid-cols-4 gap-4 mb-5 shrink-0">
    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-[#E8F4F2] flex items-center justify-center shrink-0">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2A9D8F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
        </div>
        <div>
            <div class="text-sm text-gray-text">Total produk aktif</div>
            <div><span class="text-3xl font-bold">{{ $ringkasan['total'] ?? 0 }}</span> <span class="text-sm text-gray-text">{{ $ringkasan['kategori'] ?? 0 }} kategori</span></div>
        </div>
    </div>

    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#15803D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg>
        </div>
        <div>
            <div class="text-sm text-gray-text">Stok cukup</div>
            <div><span class="text-3xl font-bold">{{ $ringkasan['cukup'] ?? 0 }}</span> <span class="text-sm text-green-700">aman</span></div>
        </div>
    </div>

    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-yellow-100 flex items-center justify-center shrink-0">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l10 18H2L12 3z"/><path d="M12 10v5M12 18v.5"/></svg>
        </div>
        <div>
            <div class="text-sm text-gray-text">Stok menipis</div>
            <div><span class="text-3xl font-bold">{{ $ringkasan['menipis'] ?? 0 }}</span> <span class="text-sm text-yellow-700">perlu restok</span></div>
        </div>
    </div>

    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#B91C1C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/></svg>
        </div>
        <div>
            <div class="text-sm text-gray-text">Hampir habis</div>
            <div><span class="text-3xl font-bold">{{ $ringkasan['hampir_habis'] ?? 0 }}</span> <span class="text-sm text-red-700">segera restok</span></div>
        </div>
    </div>
</div>

{{-- ================= AREA KONTEN UTAMA (TABEL & SEARCH) ================= --}}
<div class="flex-1 min-h-0 bg-pure-white border border-border-color rounded-xl flex flex-col overflow-hidden shadow-sm">

    {{-- Pencarian & Filter --}}
    <form method="GET" action="{{ route('obat.index') }}" id="filterForm"
          class="flex gap-4 items-center p-4 border-b border-border-color bg-pure-white shrink-0">
        <div class="flex-1 relative">
            <span class="absolute left-3 top-2.5 text-gray-text">🔍</span>
            <input type="text" name="q" id="searchProduct" value="{{ request('q') }}"
                placeholder="Cari nama atau kode produk..." autocomplete="off"
                class="w-full py-2.5 px-3 pl-9 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
        </div>

        <select name="kategori" id="filterKategori"
            class="w-52 py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white cursor-pointer">
            <option value="">Semua kategori</option>
            @foreach ($kategoris as $kategori)
                <option value="{{ $kategori->id }}" @selected(request('kategori') == $kategori->id)>
                    {{ $kategori->nama_kategori }}
                </option>
            @endforeach
        </select>
    </form>

    {{-- Tabel Produk (area yang diganti lewat AJAX) --}}
    <div class="flex-1 overflow-y-auto transition-opacity" id="areaTabel">
        <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 bg-off-white z-10">
                <tr>
                    @foreach (['KODE', 'NAMA PRODUK', 'KATEGORI', 'STOK', 'HARGA BELI', 'MARGIN', 'HARGA JUAL'] as $judul)
                        <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">{{ $judul }}</th>
                    @endforeach
                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color text-center">AKSI</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($obats as $obat)
                    <tr class="border-b border-border-color hover:bg-off-white transition">
                        <td class="p-4 px-5 text-gray-text text-sm">{{ $obat->kode_obat }}</td>

                        <td class="p-4 px-5 text-sm">
                            <strong>{{ $obat->nama_obat }}</strong>
                            @if ($obat->status === 'tidak')
                                <span class="ml-1 text-[10px] bg-gray-100 text-gray-text px-2 py-0.5 rounded-full font-semibold">Tidak tersedia</span>
                            @endif
                            <br>
                            <span class="text-xs text-gray-text">{{ $obat->satuan }}</span>
                        </td>
                        <td class="p-4 px-5 text-sm">{{ $obat->kategori->nama_kategori ?? '-' }}</td>

                        <td class="p-4 px-5 text-sm font-semibold">
                            @php($info = $obat->stok_info)
                            <span class="{{ $info['kelas'] }} px-2.5 py-1 rounded-full text-xs">{{ $obat->stok }} ({{ $info['label'] }})</span>
                        </td>
                        <td class="p-4 px-5 text-sm">Rp{{ number_format($obat->harga_beli, 0, ',', '.') }}</td>
                        <td class="p-4 px-5 text-sm">{{ $obat->margin_teks }}</td>
                        <td class="p-4 px-5 text-sm font-bold text-soft-teal">Rp{{ number_format($obat->harga_jual, 0, ',', '.') }}</td>

                        <td class="p-4 px-5 text-sm text-center whitespace-nowrap">
                            <button type="button"
                                data-obat="{{ json_encode([
                                    'id'          => $obat->id,
                                    'kode_obat'   => $obat->kode_obat,
                                    'nama_obat'   => $obat->nama_obat,
                                    'kategori_id' => $obat->kategori_id,
                                    'kategori'    => $obat->kategori->nama_kategori ?? '',
                                    'satuan'      => $obat->satuan,
                                    'harga_beli'  => $obat->harga_beli,
                                    'margin'      => $obat->margin,
                                    'tipe_margin' => $obat->tipe_margin,
                                    'stok'        => $obat->stok,
                                    'status'      => $obat->status,
                                ]) }}"
                                onclick="bukaModal('edit', JSON.parse(this.dataset.obat))"
                                class="text-soft-teal font-semibold mr-3 hover:underline">
                                ✎ Edit
                            </button>

                            <form action="{{ route('obat.destroy', $obat->id) }}" method="POST" class="inline-block"
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk {{ addslashes($obat->nama_obat) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-muted-coral font-semibold hover:underline">🗑 Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-10 text-center text-gray-text">
                            @if (request()->hasAny(['q', 'kategori']))
                                Tidak ada produk yang cocok dengan pencarian.
                            @else
                                Belum ada data produk di apotek.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Keterangan & Pagination (area yang diganti lewat AJAX) --}}
    <div id="areaFooter" class="p-4 px-5 border-t border-border-color bg-pure-white shrink-0 flex flex-wrap justify-between items-center gap-4">
        <div class="text-sm text-gray-text font-medium">
            Menampilkan {{ $obats->firstItem() ?? 0 }}-{{ $obats->lastItem() ?? 0 }} dari {{ $obats->total() }} produk
            <span class="block text-xs font-normal mt-0.5">
                Keterangan stok: Cukup (25 ke atas), Menipis (10-24), Hampir habis (kurang dari 10)
            </span>
        </div>
        <div class="pagination-custom">{{ $obats->links() }}</div>
    </div>
</div>

{{-- ================= MODAL TAMBAH / EDIT PRODUK ================= --}}
<div id="productModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-pure-white w-full max-w-xl rounded-xl p-8 shadow-xl max-h-[90vh] overflow-y-auto">
        <h3 id="modalJudul" class="mt-0 text-xl font-bold mb-1">Tambah Data Produk</h3>
        <p class="text-sm text-gray-text mb-6">Harga jual dihitung otomatis dari harga beli dan margin keuntungan.</p>

        <form id="productForm" action="{{ route('obat.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST" disabled>
            <input type="hidden" name="_mode" id="formMode" value="add">
            <input type="hidden" name="_id" id="formId" value="">

            <div class="grid grid-cols-2 gap-5 mb-6">
                <div>
                    <label class="block mb-2 text-sm font-semibold">Kode Obat</label>
                    <input type="text" name="kode_obat" id="f_kode_obat" required placeholder="Otomatis"
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-semibold">Nama Obat</label>
                    <input type="text" name="nama_obat" id="f_nama_obat" required placeholder="Contoh: nama obat dan dosisnya"
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>

                {{-- KATEGORI: LIVE SEARCH COMBOBOX --}}
                <div class="relative col-span-1" id="kategori-wrapper">
                    <label class="block mb-2 text-sm font-semibold">Kategori</label>
                    <input type="hidden" name="kategori_id" id="kategori_id_input">
                    <input type="text" id="kategori_search" placeholder="Ketik untuk mencari kategori..." autocomplete="off" required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white">

                    <div id="kategori_dropdown"
                        class="hidden absolute z-50 w-full mt-1 bg-pure-white border border-border-color rounded-lg shadow-lg overflow-hidden">
                        <ul id="kategori_list" class="max-h-40 overflow-y-auto m-0 p-0 list-none"></ul>
                        <div id="kategori_tambah"></div>
                        <div class="px-3 py-2 border-t border-border-color bg-off-white text-[11px] text-gray-text">
                            Pencarian dari awalan huruf. Penulisan otomatis disesuaikan.
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold">Satuan</label>
                    <select name="satuan" id="f_satuan" required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white cursor-pointer">
                        <option value="Strip">Strip</option>
                        <option value="Botol">Botol</option>
                        <option value="Tablet">Tablet</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold">Harga Beli (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-text pointer-events-none">Rp</span>
                        <input type="text" inputmode="numeric" id="f_harga_beli_tampil" required autocomplete="off"
                            class="w-full p-3 pl-8 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                        {{-- Angka polos yang dikirim ke server (tanpa titik) --}}
                        <input type="hidden" name="harga_beli" id="f_harga_beli">
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold">Margin Keuntungan</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            {{-- Nominal: Rp di kiri. Persen: % di kanan --}}
                            <span id="margin_prefix" class="hidden absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-text pointer-events-none">Rp</span>
                            <input type="text" inputmode="decimal" id="f_margin_tampil" required autocomplete="off"
                                class="w-full p-3 pr-10 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                            {{-- Angka polos yang dikirim ke server --}}
                            <input type="hidden" name="margin" id="f_margin">
                            <span id="margin_suffix" class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-text pointer-events-none">%</span>
                        </div>
                        <select name="tipe_margin" id="f_tipe_margin"
                            class="p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white cursor-pointer">
                            <option value="persen">Persen</option>
                            <option value="nominal">Nominal</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold">Harga Jual (otomatis)</label>
                    <input type="text" id="f_harga_jual" readonly value="Rp0"
                        class="w-full p-3 border border-border-color rounded-lg text-sm bg-[#E8F4F2] font-bold text-soft-teal outline-none">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold">Stok</label>
                    <input type="number" name="stok" id="f_stok" min="0" required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold">Status</label>
                    <select name="status" id="f_status"
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white cursor-pointer">
                        <option value="tersedia">Tersedia</option>
                        <option value="tidak">Tidak tersedia</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-5">
                <button type="button" onclick="tutupModal()"
                    class="px-5 py-2.5 border border-border-color bg-pure-white rounded-lg font-bold hover:bg-off-white transition">
                    Batal
                </button>
                <button type="submit" id="btnSimpan"
                    class="px-5 py-2.5 border-none bg-soft-teal text-white rounded-lg font-bold hover:opacity-90 shadow-sm transition">
                    Simpan Produk
                </button>
            </div>
        </form>
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
const kategoriData = @json($kategoris->map(fn ($k) => ['id' => $k->id, 'nama_kategori' => $k->nama_kategori])->values());
const urlObat = "{{ url('/obat') }}";
const urlTambahKategori = "{{ route('kategori.storeAjax') }}";
const urlGenerateKode = "{{ url('/obat/generate-kode') }}";
const csrf = "{{ csrf_token() }}";

const $ = (id) => document.getElementById(id);

/* ---------- PENCARIAN & FILTER PRODUK (AJAX, TANPA RELOAD) ---------- */
const formFilter  = $('filterForm');
const searchInput = $('searchProduct');
let timerCari, kontrolCari;

function buatUrl() {
    const params = new URLSearchParams();
    new FormData(formFilter).forEach((v, k) => { if (v !== '') params.append(k, v); });
    const qs = params.toString();
    return formFilter.action + (qs ? '?' + qs : '');
}

async function muatTabel(url) {
    kontrolCari?.abort();                    // batalkan request lama yang belum selesai
    const kontrol = kontrolCari = new AbortController();
    const area = $('areaTabel');
    area.classList.add('opacity-50');

    try {
        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: kontrol.signal
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);

        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const tabelBaru  = doc.getElementById('areaTabel');
        const footerBaru = doc.getElementById('areaFooter');

        // Kalau bukan halaman yang diharapkan (misal sesi habis -> redirect login), muat biasa
        if (!tabelBaru || !footerBaru) { window.location.href = url; return; }

        $('areaTabel').innerHTML  = tabelBaru.innerHTML;
        $('areaFooter').innerHTML = footerBaru.innerHTML;
        history.replaceState(null, '', url);  // URL ikut berubah, bisa di-refresh/dibagikan
    } catch (err) {
        if (err.name !== 'AbortError') console.error('Gagal memuat tabel:', err);
    } finally {
        if (kontrolCari === kontrol) area.classList.remove('opacity-50');
    }
}

// Ketik -> tunggu 300 ms -> ambil data
searchInput.addEventListener('input', () => {
    clearTimeout(timerCari);
    timerCari = setTimeout(() => muatTabel(buatUrl()), 300);
});

// Ganti kategori -> langsung ambil data
$('filterKategori').addEventListener('change', () => muatTabel(buatUrl()));

// Tekan Enter -> jangan reload, pakai cara yang sama
formFilter.addEventListener('submit', (e) => {
    e.preventDefault();
    clearTimeout(timerCari);
    muatTabel(buatUrl());
});

// Klik nomor halaman pagination -> juga tanpa reload
$('areaFooter').addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a) return;
    e.preventDefault();
    muatTabel(a.href);
});

/* ---------- FORMAT RUPIAH & HARGA JUAL OTOMATIS ---------- */
const rupiah = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID');
const tampilBeli  = $('f_harga_beli_tampil');
const tampilMargin = $('f_margin_tampil');

function hitungHargaJual() {
    // Simbol margin: Nominal -> Rp di depan angka, Persen -> % di belakang angka
    const nominal = $('f_tipe_margin').value === 'nominal';
    $('margin_suffix').classList.toggle('hidden', nominal);
    $('margin_prefix').classList.toggle('hidden', !nominal);
    tampilMargin.classList.toggle('pl-8', nominal);
    tampilMargin.classList.toggle('pr-10', !nominal);

    const beli = parseFloat($('f_harga_beli').value) || 0;
    const margin = parseFloat($('f_margin').value) || 0;
    const jual = nominal
        ? beli + Math.round(margin)
        : Math.round(beli * (1 + margin / 100));
    $('f_harga_jual').value = rupiah(jual);
}

/* Harga beli: tampil dengan titik ribuan (8.000), yang dikirim ke server tetap angka polos (8000) */
function setHargaBeli(angka) {
    const bersih = String(angka ?? '').replace(/\D/g, '');
    $('f_harga_beli').value = bersih;
    tampilBeli.value = bersih ? Number(bersih).toLocaleString('id-ID') : '';
}

tampilBeli.addEventListener('input', () => {
    setHargaBeli(tampilBeli.value);
    hitungHargaJual();
});

/* Margin: Nominal tampil dengan titik ribuan (20.000), Persen boleh desimal (12,5).
   Yang dikirim ke server angka polos (20000 atau 12.5) */
function formatMargin() {
    if ($('f_tipe_margin').value === 'nominal') {
        const bersih = tampilMargin.value.replace(/\D/g, '');
        $('f_margin').value = bersih;
        tampilMargin.value = bersih ? Number(bersih).toLocaleString('id-ID') : '';
    } else {
        let s = tampilMargin.value.replace(/[^\d.,]/g, '').replace(/\./g, ',');
        const b = s.split(',');
        s = b[0] + (b.length > 1 ? ',' + b.slice(1).join('') : '');
        tampilMargin.value = s;
        $('f_margin').value = s.replace(',', '.');
    }
}

tampilMargin.addEventListener('input', () => { formatMargin(); hitungHargaJual(); });

// Ganti Persen <-> Nominal: ubah tampilan angka sesuai tipe baru
$('f_tipe_margin').addEventListener('change', () => {
    const v = parseFloat($('f_margin').value);
    tampilMargin.value = isNaN(v)
        ? ''
        : ($('f_tipe_margin').value === 'nominal' ? String(Math.round(v)) : String(v).replace('.', ','));
    formatMargin();
    hitungHargaJual();
});

/* ---------- KODE OBAT OTOMATIS (OBT-xxx, MELANJUTKAN KODE TERAKHIR) ---------- */
function isiKodeBaru() {
    const inputKode = $('f_kode_obat');
    inputKode.value = 'Memuat kode...';

    fetch(urlGenerateKode, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => { inputKode.value = data.kode || ''; })
        .catch(err => {
            console.error('Gagal memuat kode otomatis:', err);
            inputKode.value = '';
        });
}

/* ---------- MODAL TAMBAH / EDIT PRODUK ---------- */
function isiForm(d) {
    $('f_kode_obat').value   = d.kode_obat ?? '';
    $('f_nama_obat').value   = d.nama_obat ?? '';
    $('f_satuan').value      = d.satuan ?? 'Strip';
    setHargaBeli(d.harga_beli ? Math.round(Number(d.harga_beli)) : '');
    // Tipe margin harus diisi dulu sebelum margin, karena format angka bergantung pada tipe
    $('f_tipe_margin').value = d.tipe_margin ?? 'persen';
    const m = d.margin ?? '';
    tampilMargin.value = (m === '' || m === null) ? '' : ($('f_tipe_margin').value === 'nominal'
        ? String(Math.round(Number(m)))
        : String(Number(m)).replace('.', ','));
    formatMargin();
    $('f_stok').value        = d.stok ?? '';
    $('f_status').value      = d.status ?? 'tersedia';
    $('kategori_id_input').value = d.kategori_id ?? '';
    $('kategori_search').value   = d.kategori ?? '';
    hitungHargaJual();
}

function bukaModal(mode, data = {}) {
    const edit = mode === 'edit';
    $('modalJudul').textContent = edit ? 'Edit Data Produk' : 'Tambah Data Produk';
    $('btnSimpan').textContent  = edit ? 'Simpan Perubahan' : 'Simpan Produk';
    $('formMode').value = mode;
    $('formId').value   = edit ? data.id : '';
    $('productForm').action = edit ? urlObat + '/' + data.id : "{{ route('obat.store') }}";
    $('formMethod').disabled = !edit;
    $('formMethod').value = 'PUT';
    isiForm(edit ? data : {});
    // Mode tambah: ambil kode berikutnya (kecuali form dibuka ulang dengan kode lama setelah validasi gagal)
    if (!edit && !data.kode_obat) isiKodeBaru();
    $('productModal').classList.remove('hidden');
}

function tutupModal() {
    $('productModal').classList.add('hidden');
    $('kategori_dropdown').classList.add('hidden');
}

/* ---------- KATEGORI COMBOBOX (LIVE SEARCH & ADD) ---------- */
const kInput = $('kategori_search'), kHidden = $('kategori_id_input');
const kDropdown = $('kategori_dropdown'), kList = $('kategori_list'), kTambah = $('kategori_tambah');

function renderKategori() {
    const ketikan = kInput.value;
    const k = ketikan.toLowerCase().trim();
    kList.innerHTML = '';
    kTambah.innerHTML = '';

    const hasil = k === ''
        ? kategoriData
        : kategoriData.filter(x => x.nama_kategori.toLowerCase().startsWith(k));

    hasil.forEach(x => {
        const li = document.createElement('li');
        li.className = 'px-3 py-2.5 text-sm cursor-pointer hover:bg-[#E8F4F2] transition text-dark-slate';
        li.textContent = x.nama_kategori;
        li.onmousedown = (e) => { e.preventDefault(); selectKategori(x.id, x.nama_kategori); };
        kList.appendChild(li);
    });

    if (k !== '' && !kategoriData.some(x => x.nama_kategori.toLowerCase() === k)) {
        const div = document.createElement('div');
        div.className = 'px-3 py-2.5 text-sm cursor-pointer font-bold text-soft-teal border-t border-border-color hover:bg-[#E8F4F2]';
        div.textContent = '+ Tambah kategori baru "' + ketikan + '"';
        div.onmousedown = (e) => { e.preventDefault(); tambahKategoriBaru(ketikan); };
        kTambah.appendChild(div);
    }

    if (!kList.children.length && !kTambah.children.length) {
        kList.innerHTML = '<li class="px-3 py-2.5 text-sm text-gray-text italic">Belum ada kategori.</li>';
    }
}

function selectKategori(id, nama) {
    kHidden.value = id;
    kInput.value = nama;
    kDropdown.classList.add('hidden');
}

kInput.addEventListener('focus', () => { renderKategori(); kDropdown.classList.remove('hidden'); });
kInput.addEventListener('input', () => { kHidden.value = ''; renderKategori(); kDropdown.classList.remove('hidden'); });
document.addEventListener('click', (e) => {
    if (!$('kategori-wrapper').contains(e.target)) {
        kDropdown.classList.add('hidden');
        if (!kHidden.value) kInput.value = '';
    }
});

function tambahKategoriBaru(nama) {
    fetch(urlTambahKategori, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ nama_kategori: nama })
    })
    .then(r => r.json().then(j => ({ ok: r.ok, j })))
    .then(({ ok, j }) => {
        if (ok && j.success) {
            kategoriData.push(j.data);
            selectKategori(j.data.id, j.data.nama_kategori);
        } else {
            alert(j.message || 'Gagal menambahkan kategori.');
        }
    })
    .catch(() => alert('Terjadi kesalahan sistem saat menyimpan kategori.'));
}

/* ---------- KEMBALIKAN DATA FORM JIKA VALIDASI ERROR ---------- */
@if ($errors->any() && old('_mode'))
    bukaModal(@json(old('_mode')), {
        id: @json(old('_id')),
        kode_obat: @json(old('kode_obat')),
        nama_obat: @json(old('nama_obat')),
        kategori_id: @json(old('kategori_id')),
        kategori: (kategoriData.find(k => k.id == @json(old('kategori_id'))) || {}).nama_kategori || '',
        satuan: @json(old('satuan')),
        harga_beli: @json(old('harga_beli')),
        margin: @json(old('margin')),
        tipe_margin: @json(old('tipe_margin')),
        stok: @json(old('stok')),
        status: @json(old('status')),
    });
@endif
</script>
@endsection