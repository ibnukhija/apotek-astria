@extends('layouts.app')

@section('title', 'Kelola Produk - Apotek Astria 2')

@section('content')

<div class="flex justify-between items-center mb-5 shrink-0">
    <div>
        <h2 class="m-0 text-2xl font-bold">Kelola Data Produk</h2>
        <span class="text-gray-text text-sm">Pantau stok dan harga obat</span>
    </div>

    <div class="flex items-center gap-4">
        <span class="text-gray-text text-sm font-medium">
            {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
        </span>

        <button
            class="bg-soft-teal text-white px-5 py-2.5 border-none rounded-lg font-bold shadow-sm cursor-pointer hover:opacity-90 transition"
            onclick="document.getElementById('productModal').classList.remove('hidden')"
        >
            + Tambah Produk
        </button>
    </div>
</div>

@if(session('success'))
    <div class="bg-[#E8F4F2] text-soft-teal p-4 rounded-lg mb-5 font-semibold border border-soft-teal">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-5 border border-red-300">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


<div class="flex-1 bg-pure-white border border-border-color rounded-xl flex flex-col overflow-hidden shadow-sm">

    {{-- HEADER TABEL --}}
    <div class="flex gap-4 items-center p-4 border-b border-border-color bg-pure-white shrink-0">
        <div class="flex-1 relative">
            <span class="absolute left-3 top-2.5 text-gray-text">🔍</span>

            <input
                type="text"
                id="searchProduct"
                placeholder="Cari nama atau kode produk..."
                class="w-full py-2.5 px-3 pl-9 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal"
            >
        </div>
    </div>


    {{-- TABEL PRODUK --}}
    <div class="flex-1 overflow-y-auto">
        <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 bg-off-white z-10">
                <tr>
                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">
                        KODE
                    </th>

                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">
                        NAMA PRODUK
                    </th>

                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">
                        KATEGORI
                    </th>

                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">
                        STOK
                    </th>

                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">
                        HARGA BELI
                    </th>

                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">
                        HARGA JUAL
                    </th>

                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color text-center">
                        AKSI
                    </th>
                </tr>
            </thead>

            <tbody id="productTableBody">

                @forelse($obats as $obat)

                    <tr
                        class="product-row border-b border-border-color hover:bg-off-white transition"
                        data-search="{{ strtolower($obat->kode_obat . ' ' . $obat->nama_obat) }}"
                    >

                        <td class="p-4 px-5 text-gray-text text-sm">
                            {{ $obat->kode_obat }}
                        </td>

                        <td class="p-4 px-5 text-sm">
                            <strong>{{ $obat->nama_obat }}</strong>
                            <br>
                            <span class="text-xs text-gray-text">
                                {{ $obat->satuan }}
                            </span>
                        </td>

                        <td class="p-4 px-5 text-sm">
                            {{ $obat->kategori->nama_kategori ?? '-' }}
                        </td>

                        <td class="p-4 px-5 text-sm font-semibold">
                            @if($obat->stok <= 10)
                                <span class="text-[#EAB308] bg-[#FEF9C3] px-2.5 py-1 rounded-full text-xs">
                                    {{ $obat->stok }} (Menipis)
                                </span>
                            @else
                                {{ $obat->stok }}
                            @endif
                        </td>

                        <td class="p-4 px-5 text-sm">
                            Rp{{ number_format($obat->harga_beli, 0, ',', '.') }}
                        </td>

                        <td class="p-4 px-5 text-sm font-bold text-soft-teal">
                            Rp{{ number_format($obat->harga_jual, 0, ',', '.') }}
                        </td>

                        <td class="p-4 px-5 text-sm text-center">

                            {{-- TOMBOL EDIT --}}
                            <button
                                type="button"
                                onclick="openEditModal(
                                    @js($obat->id),
                                    @js($obat->kode_obat),
                                    @js($obat->nama_obat),
                                    @js($obat->kategori_id),
                                    @js($obat->satuan),
                                    @js($obat->stok),
                                    @js($obat->harga_beli),
                                    @js($obat->harga_jual)
                                )"
                                class="text-soft-teal font-semibold mr-3 hover:underline">
                                ✎ Edit
                            </button>

                            {{-- TOMBOL HAPUS --}}
                            <form
                                action="{{ route('obat.destroy', $obat->id) }}"
                                method="POST"
                                class="inline-block"
                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="text-muted-coral font-semibold hover:underline">
                                    🗑 Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-10 text-center text-gray-text">
                            Belum ada data produk di apotek.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div class="p-4 px-5 border-t border-border-color bg-pure-white shrink-0 flex flex-wrap justify-between items-center gap-4">
        <div class="text-sm text-gray-text font-medium">
            Menampilkan
            {{ $obats->firstItem() ?? 0 }}-{{ $obats->lastItem() ?? 0 }}
            dari {{ $obats->total() }} produk
        </div>

        <div class="pagination-custom">
            {{ $obats->links() }}
        </div>
    </div>
</div>

{{-- MODAL TAMBAH PRODUK --}}
<div
    id="productModal"
    class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-pure-white w-full max-w-xl rounded-xl p-8 shadow-xl max-h-[90vh] overflow-y-auto">
        <h3 class="mt-0 text-xl font-bold mb-1">
            Tambah Data Produk
        </h3>
        <p class="text-sm text-gray-text mb-6">
            Isi informasi produk baru sesuai master data apotek.
        </p>
        <form action="{{ route('obat.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-2 gap-5 mb-6">
                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Kode Obat
                    </label>
                    <input
                        type="text"
                        name="kode_obat"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Nama Obat
                    </label>
                    <input
                        type="text"
                        name="nama_obat"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>

                {{-- KATEGORI --}}
                <div class="relative w-full col-span-2" id="kategori-wrapper">
                    <label class="block mb-2 text-sm font-semibold">
                        Kategori
                    </label>
                    <input
                        type="hidden"
                        name="kategori_id"
                        id="kategori_id_input"
                        required>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-text text-sm">
                            🔍
                        </span>
                        <input
                            type="text"
                            id="kategori_search"
                            placeholder="Cari kategori..."
                            autocomplete="off"
                            class="w-full p-3 pl-9 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white cursor-pointer">
                    </div>
                    <div
                        id="kategori_dropdown"
                        class="hidden absolute z-50 w-full mt-1 bg-pure-white border border-border-color rounded-lg shadow-lg overflow-hidden">
                        <ul
                            id="kategori_list"
                            class="max-h-48 overflow-y-auto m-0 p-0 list-none"
                        ></ul>
                        <div class="p-2 border-t border-border-color bg-off-white flex gap-2">
                            <input
                                type="text"
                                id="kategori_baru_input"
                                placeholder="Lain-lain: tulis kategori baru..."
                                class="flex-1 p-2.5 border border-border-color rounded-md text-sm outline-none focus:border-soft-teal">
                            <button
                                type="button"
                                onclick="simpanKategoriBaru()"
                                class="bg-[#2A9D8F] text-white px-4 py-2 rounded-md font-bold hover:opacity-90 transition">
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Satuan
                    </label>
                    <select
                        name="satuan"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white cursor-pointer">
                        <option value="Strip">Strip</option>
                        <option value="Botol">Botol</option>
                        <option value="Tablet">Tablet</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Stok Awal
                    </label>
                    <input
                        type="number"
                        name="stok"
                        min="0"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Harga Beli
                    </label>
                    <input
                        type="number"
                        name="harga_beli"
                        min="0"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Harga Jual
                    </label>
                    <input
                        type="number"
                        name="harga_jual"
                        min="0"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-5">
                <button
                    type="button"
                    class="px-5 py-2.5 border border-border-color bg-pure-white rounded-lg font-bold hover:bg-off-white transition"
                    onclick="document.getElementById('productModal').classList.add('hidden')">
                    Batal
                </button>

                <button
                    type="submit"
                    class="px-5 py-2.5 border-none bg-soft-teal text-white rounded-lg font-bold hover:opacity-90 shadow-sm transition">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT PRODUK --}}
<div
    id="editProductModal"
    class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm"
>
    <div class="bg-pure-white w-full max-w-xl rounded-xl p-8 shadow-xl max-h-[90vh] overflow-y-auto">
        <h3 class="mt-0 text-xl font-bold mb-1">
            Edit Data Produk
        </h3>
        <p class="text-sm text-gray-text mb-6">
            Ubah informasi produk sesuai kebutuhan.
        </p>
        <form id="editProductForm" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-5 mb-6">
                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Kode Obat
                    </label>
                    <input
                        type="text"
                        name="kode_obat"
                        id="edit_kode_obat"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal"
                    >
                </div>
                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Nama Obat
                    </label>
                    <input
                        type="text"
                        name="nama_obat"
                        id="edit_nama_obat"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal"
                    >
                </div>
                <div class="col-span-2">
                    <label class="block mb-2 text-sm font-semibold">
                        Kategori
                    </label>
                    <select
                        name="kategori_id"
                        id="edit_kategori_id"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white cursor-pointer">
                        @foreach($kategoris as $kategori)
                            <option value="{{ $kategori->id }}">
                                {{ $kategori->nama_kategori }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Satuan
                    </label>
                    <select
                        name="satuan"
                        id="edit_satuan"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-pure-white cursor-pointer">
                        <option value="Strip">Strip</option>
                        <option value="Botol">Botol</option>
                        <option value="Tablet">Tablet</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Stok
                    </label>
                    <input
                        type="number"
                        name="stok"
                        id="edit_stok"
                        min="0"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Harga Beli
                    </label>
                    <input
                        type="number"
                        name="harga_beli"
                        id="edit_harga_beli"
                        min="0"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-semibold">
                        Harga Jual
                    </label>
                    <input
                        type="number"
                        name="harga_jual"
                        id="edit_harga_jual"
                        min="0"
                        required
                        class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5">
                <button
                    type="button"
                    class="px-5 py-2.5 border border-border-color bg-pure-white rounded-lg font-bold hover:bg-off-white transition"
                    onclick="closeEditModal()">
                    Batal
                </button>
                <button
                    type="submit"
                    class="px-5 py-2.5 border-none bg-soft-teal text-white rounded-lg font-bold hover:opacity-90 shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.pagination-custom nav div.hidden.sm\:flex-1 > div:first-child {
    display: none !important;
}
.pagination-custom nav div.hidden.sm\:flex-1 > div:last-child {
    margin-left: auto;
}
</style>

<script>

const kategoriData = @json($kategoris);

const searchInput = document.getElementById('kategori_search');
const hiddenInput = document.getElementById('kategori_id_input');
const dropdown = document.getElementById('kategori_dropdown');
const list = document.getElementById('kategori_list');
const inputBaru = document.getElementById('kategori_baru_input');
const wrapper = document.getElementById('kategori-wrapper');



/* EDIT PRODUK */
function openEditModal(
    id,
    kode,
    nama,
    kategori,
    satuan,
    stok,
    hargaBeli,
    hargaJual
) {
    document.getElementById('edit_kode_obat').value = kode;
    document.getElementById('edit_nama_obat').value = nama;
    document.getElementById('edit_kategori_id').value = kategori;
    document.getElementById('edit_satuan').value = satuan;
    document.getElementById('edit_stok').value = stok;
    document.getElementById('edit_harga_beli').value = hargaBeli;
    document.getElementById('edit_harga_jual').value = hargaJual;

    document.getElementById('editProductForm').action =
        "{{ url('/obat') }}/" + id;
    document.getElementById('editProductModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editProductModal').classList.add('hidden');

}

/* PENCARIAN PRODUK */
const searchProduct = document.getElementById('searchProduct');
if (searchProduct) {
    searchProduct.addEventListener('input', function () {
        const keyword = this.value.toLowerCase().trim();
        document.querySelectorAll('.product-row').forEach(function (row) {
            const data = row.getAttribute('data-search');
            if (data.includes(keyword)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

/* KATEGORI LIVE SEARCH */
function renderList(filterText = '') {
    list.innerHTML = '';
    const filtered = kategoriData.filter(function (k) {
        return k.nama_kategori
            .toLowerCase()
            .includes(filterText.toLowerCase());
    });
    if (filtered.length === 0) {

        list.innerHTML =
            '<li class="p-3 text-sm text-gray-text italic">Kategori tidak ditemukan.</li>';
    } else {
        filtered.forEach(function (k) {
            const li = document.createElement('li');
            li.className =
                'p-3 text-sm cursor-pointer hover:bg-off-white hover:text-[#2A9D8F] transition border-b border-border-color last:border-0 font-medium';
            li.textContent = k.nama_kategori;
            li.onclick = function () {
                selectKategori(k.id, k.nama_kategori);
            };
            list.appendChild(li);
        });
    }
}

function selectKategori(id, nama) {
    hiddenInput.value = id;
    searchInput.value = nama;
    dropdown.classList.add('hidden');
}

searchInput.addEventListener('focus', function () {
    renderList(searchInput.value);
    dropdown.classList.remove('hidden');
});

searchInput.addEventListener('input', function (e) {
    renderList(e.target.value);
    dropdown.classList.remove('hidden');
    hiddenInput.value = '';
});

document.addEventListener('click', function (e) {
    if (!wrapper.contains(e.target)) {
        dropdown.classList.add('hidden');
        if (!hiddenInput.value) {
            searchInput.value = '';
        }
    }
});

/* TAMBAH KATEGORI BARU VIA AJAX */
function simpanKategoriBaru() {
    const namaBaru = inputBaru.value.trim();
    if (namaBaru === '') {
        alert('Nama kategori belum diisi.');
        return;
    }
    fetch("{{ route('kategori.storeAjax') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            nama_kategori: namaBaru
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            kategoriData.push(data.data);
            selectKategori(
                data.data.id,
                data.data.nama_kategori
            );
            inputBaru.value = '';
            alert(
                "Kategori '" +
                data.data.nama_kategori +
                "' berhasil ditambahkan!"
            );
        } else {
            alert(
                'Gagal menambahkan kategori. Pastikan nama tidak duplikat.'
            );
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert(
            'Terjadi kesalahan sistem saat menyimpan kategori.'
        );
    });
}
</script>
@endsection