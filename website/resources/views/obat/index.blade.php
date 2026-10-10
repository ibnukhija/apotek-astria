@extends('layouts.app')

@section('title', 'Kelola Produk - Apotek Astria 2')

@section('content')

{{-- JUDUL HALAMAN --}}
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

@if(session('success'))
    <div class="bg-[#E8F4F2] text-soft-teal p-4 rounded-lg mb-5 font-semibold border border-soft-teal shrink-0">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-5 border border-red-300 shrink-0">
        <ul class="list-disc pl-5 m-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- KOTAK RINGKASAN --}}
<div class="grid grid-cols-4 gap-4 mb-5 shrink-0">
    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-[#E8F4F2] flex items-center justify-center shrink-0">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2A9D8F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
        </div>
        <div>
            <div class="text-sm text-gray-text">Total produk aktif</div>
            <div><span class="text-3xl font-bold">{{ $ringkasan['total'] }}</span> <span class="text-sm text-gray-text">{{ $ringkasan['kategori'] }} kategori</span></div>
        </div>
    </div>

    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#15803D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg>
        </div>
        <div>
            <div class="text-sm text-gray-text">Stok cukup</div>
            <div><span class="text-3xl font-bold">{{ $ringkasan['cukup'] }}</span> <span class="text-sm text-green-700">aman</span></div>
        </div>
    </div>

    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-yellow-100 flex items-center justify-center shrink-0">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l10 18H2L12 3z"/><path d="M12 10v5M12 18v.5"/></svg>
        </div>
        <div>
            <div class="text-sm text-gray-text">Stok menipis</div>
            <div><span class="text-3xl font-bold">{{ $ringkasan['menipis'] }}</span> <span class="text-sm text-yellow-700">perlu restok</span></div>
        </div>
    </div>

    <div class="bg-pure-white border border-border-color rounded-xl p-4 flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#B91C1C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/></svg>
        </div>
        <div>
            <div class="text-sm text-gray-text">Hampir habis</div>
            <div><span class="text-3xl font-bold">{{ $ringkasan['hampir_habis'] }}</span> <span class="text-sm text-red-700">segera restok</span></div>
        </div>
    </div>
</div>

{{-- TABEL PRODUK --}}
<div class="flex-1 min-h-0 bg-pure-white border border-border-color rounded-xl flex flex-col overflow-hidden shadow-sm">

    {{-- Pencarian + filter kategori (dikirim lewat URL agar ikut ke semua halaman) --}}
    <form method="GET" action="{{ route('obat.index') }}" id="filterForm"
          class="flex gap-4 items-center p-4 border-b border-border-color bg-pure-white shrink-0">
        <div class="flex-1 relative">
            <span class="absolute left-3 top-2.5 text-gray-text">🔍</span>
            <input type="text" name="q" id="searchProduct" value="{{ request('q') }}"
                placeholder="Cari nama atau kode produk..." autocomplete="off"
                class="w-full py-2.5 px-3 pl-9 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
        </div>

        <select name="kategori" onchange="this.form.submit()"
            class="w-52 py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white cursor-pointer">
            <option value="">Semua kategori</option>
            @foreach($kategoris as $kategori)
                <option value="{{ $kategori->id }}" @selected(request('kategori') == $kategori->id)>
                    {{ $kategori->nama_kategori }}
                </option>
            @endforeach
        </select>
    </form>

    <div class="flex-1 overflow-y-auto">
        <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 bg-off-white z-10">
                <tr>
                    @foreach(['KODE', 'NAMA PRODUK', 'KATEGORI', 'STOK', 'HARGA BELI', 'MARGIN', 'HARGA JUAL'] as $judul)
                        <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">{{ $judul }}</th>
                    @endforeach
                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color text-center">AKSI</th>
                </tr>
            </thead>

            <tbody>
                @forelse($obats as $obat)
                    @php $stok = $obat->stok_info; @endphp
                    <tr class="border-b border-border-color hover:bg-off-white transition">
                        <td class="p-4 px-5 text-gray-text text-sm">{{ $obat->kode_obat }}</td>

                        <td class="p-4 px-5 text-sm">
                            <strong>{{ $obat->nama_obat }}</strong>
                            @if($obat->status === 'tidak')
                                <span class="ml-1 text-[10px] bg-gray-100 text-gray-text px-2 py-0.5 rounded-full font-semibold">Tidak tersedia</span>
                            @endif
                            <br>
                            <span class="text-xs text-gray-text">{{ $obat->satuan }}</span>
                        </td>

                        <td class="p-4 px-5 text-sm">{{ $obat->kategori->nama_kategori ?? '-' }}</td>

                        <td class="p-4 px-5 text-sm font-semibold">
                            <span class="px-2.5 py-1 rounded-full text-xs whitespace-nowrap {{ $stok['kelas'] }}">
                                {{ $obat->stok }} ({{ $stok['label'] }})
                            </span>
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
                            @if(request()->hasAny(['q', 'kategori']))
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

    {{-- KETERANGAN + PAGINATION --}}
    <div class="p-4 px-5 border-t border-border-color bg-pure-white shrink-0 flex flex-wrap justify-between items-center gap-4">
        <div class="text-sm text-gray-text font-medium">
            Menampilkan {{ $obats->firstItem() ?? 0 }}-{{ $obats->lastItem() ?? 0 }} dari {{ $obats->total() }} produk
            <span class="block text-xs font-normal mt-0.5">
                Keterangan stok: Cukup ({{ \App\Models\Obat::BATAS_CUKUP }} ke atas),
                Menipis ({{ \App\Models\Obat::BATAS_HAMPIR_HABIS }}-{{ \App\Models\Obat::BATAS_CUKUP - 1 }}),
                Hampir habis (kurang dari {{ \App\Models\Obat::BATAS_HAMPIR_HABIS }})
            </span>
        </div>
        <div class="pagination-custom">{{ $obats->links() }}</div>
    </div>
</div>

{{-- MODAL TAMBAH / EDIT PRODUK --}}
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
                    <input type="text" name="kode_obat" id="f_kode_obat" required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-semibold">Nama Obat</label>
                    <input type="text" name="nama_obat" id="f_nama_obat" required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>

                {{-- KATEGORI: LIVE SEARCH --}}
                <div class="relative col-span-1" id="kategori-wrapper">
                    <label class="block mb-2 text-sm font-semibold">Kategori</label>
                    <input type="hidden" name="kategori_id" id="kategori_id_input">
                    <input type="text" id="kategori_search" placeholder="Ketik untuk mencari kategori..." autocomplete="off"
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white">
                    <div id="kategori_dropdown"
                        class="hidden absolute z-50 w-full mt-1 bg-pure-white border border-border-color rounded-lg shadow-lg overflow-hidden">
                        <ul id="kategori_list" class="max-h-40 overflow-y-auto m-0 p-0 list-none"></ul>
                        <div id="kategori_tambah"></div>
                        <div class="px-3 py-2 border-t border-border-color bg-off-white text-[11px] text-gray-text">
                            Pencarian dari awalan huruf. Penulisan otomatis: huruf awal besar, sisanya kecil.
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
                    <input type="number" name="harga_beli" id="f_harga_beli" min="0" required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold">Margin Keuntungan</label>
                    <div class="flex gap-2">
                        <input type="number" name="margin" id="f_margin" min="0" step="any" required
                            class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
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

<style>
.pagination-custom nav div.hidden.sm\:flex-1 > div:first-child { display: none !important; }
.pagination-custom nav div.hidden.sm\:flex-1 > div:last-child { margin-left: auto; }
</style>

<script>
const kategoriData = @json($kategoris->map(fn ($k) => ['id' => $k->id, 'nama_kategori' => $k->nama_kategori])->values());
const urlObat = "{{ url('/obat') }}";
const urlTambahKategori = "{{ route('kategori.storeAjax') }}";
const csrf = "{{ csrf_token() }}";

const $ = (id) => document.getElementById(id);

/* ---------- PENCARIAN PRODUK (otomatis kirim setelah berhenti mengetik) ---------- */
let timerCari;
$('searchProduct').addEventListener('input', function () {
    clearTimeout(timerCari);
    timerCari = setTimeout(() => $('filterForm').submit(), 450);
});

/* ---------- FORMAT & HARGA JUAL OTOMATIS ---------- */
const rupiah = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID');

function hitungHargaJual() {
    const beli = parseFloat($('f_harga_beli').value) || 0;
    const margin = parseFloat($('f_margin').value) || 0;
    const jual = $('f_tipe_margin').value === 'persen'
        ? Math.round(beli * (1 + margin / 100))
        : beli + Math.round(margin);
    $('f_harga_jual').value = rupiah(jual);
}
['f_harga_beli', 'f_margin', 'f_tipe_margin'].forEach(id => {
    $(id).addEventListener('input', hitungHargaJual);
    $(id).addEventListener('change', hitungHargaJual);
});

/* ---------- MODAL TAMBAH / EDIT ---------- */
function isiForm(d) {
    $('f_kode_obat').value   = d.kode_obat ?? '';
    $('f_nama_obat').value   = d.nama_obat ?? '';
    $('f_satuan').value      = d.satuan ?? 'Strip';
    $('f_harga_beli').value  = d.harga_beli ?? '';
    $('f_margin').value      = d.margin ?? '';
    $('f_tipe_margin').value = d.tipe_margin ?? 'persen';
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
    $('productModal').classList.remove('hidden');
}

function tutupModal() {
    $('productModal').classList.add('hidden');
    $('kategori_dropdown').classList.add('hidden');
}

/* ---------- KATEGORI: LIVE SEARCH (awalan huruf, penulisan dirapikan) ---------- */
const kunci  = (s) => s.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '');
const rapikan = (s) => s.trim().replace(/\s+/g, ' ').toLowerCase()
    .replace(/(^|[\s-])(\p{L})/gu, (m, a, b) => a + b.toUpperCase());

// Pisahkan nama menjadi bagian yang cocok dengan ketikan (disorot) dan sisanya.
function pisahSorot(nama, panjangKunci) {
    let hitung = 0, i = 0;
    while (i < nama.length && hitung < panjangKunci) {
        if (/[\p{L}\p{N}]/u.test(nama[i])) hitung++;
        i++;
    }
    return [nama.slice(0, i), nama.slice(i)];
}

const kInput = $('kategori_search'), kHidden = $('kategori_id_input');
const kDropdown = $('kategori_dropdown'), kList = $('kategori_list'), kTambah = $('kategori_tambah');

function renderKategori() {
    const ketikan = kInput.value;
    const k = kunci(ketikan);
    kList.innerHTML = '';
    kTambah.innerHTML = '';

    const hasil = k === ''
        ? kategoriData
        : kategoriData.filter(x => kunci(x.nama_kategori).startsWith(k));

    hasil.forEach(x => {
        const li = document.createElement('li');
        li.className = 'px-3 py-2.5 text-sm cursor-pointer hover:bg-[#E8F4F2] transition';
        const [cocok, sisa] = pisahSorot(x.nama_kategori, k.length);
        const b = document.createElement('b');
        b.className = 'text-soft-teal';
        b.textContent = cocok;
        li.append(b, document.createTextNode(sisa));
        li.onmousedown = (e) => { e.preventDefault(); pilihKategori(x.id, x.nama_kategori); };
        kList.appendChild(li);
    });

    // Tawarkan kategori baru hanya jika belum ada yang sama persis (selalu tampil di bawah daftar).
    if (k !== '' && !kategoriData.some(x => kunci(x.nama_kategori) === k)) {
        const div = document.createElement('div');
        div.className = 'px-3 py-2.5 text-sm cursor-pointer font-bold text-soft-teal border-t border-border-color hover:bg-[#E8F4F2]';
        div.textContent = '+ Tambah kategori baru "' + rapikan(ketikan) + '"';
        div.onmousedown = (e) => { e.preventDefault(); tambahKategoriBaru(rapikan(ketikan)); };
        kTambah.appendChild(div);
    }

    if (!kList.children.length && !kTambah.children.length) {
        kList.innerHTML = '<li class="px-3 py-2.5 text-sm text-gray-text italic">Belum ada kategori.</li>';
    }
}

function pilihKategori(id, nama) {
    kHidden.value = id;
    kInput.value = nama;
    kDropdown.classList.add('hidden');
}

kInput.addEventListener('focus', () => { renderKategori(); kDropdown.classList.remove('hidden'); });
kInput.addEventListener('input', () => { kHidden.value = ''; renderKategori(); kDropdown.classList.remove('hidden'); });
document.addEventListener('click', (e) => {
    if (!$('kategori-wrapper').contains(e.target)) {
        kDropdown.classList.add('hidden');
        if (!kHidden.value) kInput.value = '';   // belum memilih kategori yang sah
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
            pilihKategori(j.data.id, j.data.nama_kategori);
        } else {
            alert(j.message || 'Gagal menambahkan kategori.');
        }
    })
    .catch(() => alert('Terjadi kesalahan sistem saat menyimpan kategori.'));
}

/* ---------- BUKA KEMBALI FORM JIKA VALIDASI GAGAL ---------- */
@if($errors->any() && old('_mode'))
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
