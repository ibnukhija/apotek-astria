@extends('layouts.app')

@section('title', 'Kelola Kategori - Apotek Astria 2')

@section('content')

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 shrink-0">
    <div>
        <h2 class="m-0 text-xl lg:text-2xl font-bold">Kelola Kategori</h2>
        <span class="text-gray-text text-xs sm:text-sm">Pengelompokan obat tanpa data ganda</span>
    </div>

    <div class="flex items-center justify-between sm:justify-end gap-3">
        <span class="text-gray-text text-xs sm:text-sm font-medium">
            {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
        </span>

        <button type="button" onclick="document.getElementById('nama_kategori').focus()"
            class="bg-soft-teal text-white px-4 py-2 border-none rounded-lg text-sm font-bold shadow-sm cursor-pointer hover:opacity-90 transition whitespace-nowrap">
            + Tambah Kategori
        </button>
    </div>
</div>

@if(session('success'))
<div role="alert" class="flash-alert transition-opacity duration-300 bg-[#E8F4F2] text-soft-teal py-2.5 px-4 rounded-lg mb-4 text-sm font-semibold border border-soft-teal shrink-0 flex items-center justify-between gap-3">
    <span>{{ session('success') }}</span>
    <button type="button" onclick="tutupAlert(this.closest('.flash-alert'))" aria-label="Tutup notifikasi"
        class="text-soft-teal/70 hover:text-soft-teal text-lg font-bold leading-none px-1">&times;</button>
</div>
@endif

@if($errors->has('hapus') || ($errors->has('nama_kategori') && old('_mode') === 'edit'))
<div class="bg-red-50 text-red-600 p-3 px-4 rounded-lg mb-4 text-sm border border-red-300 shrink-0">
    {{ $errors->first('hapus') ?: $errors->first('nama_kategori') }}
</div>
@endif

<div class="lg:flex-1 lg:min-h-0 flex flex-col lg:flex-row gap-4 items-stretch lg:items-start">

    {{-- DAFTAR KATEGORI --}}
    <div class="flex-1 min-w-0 w-full lg:max-h-full bg-pure-white border border-border-color rounded-xl overflow-auto shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 bg-off-white z-10">
                <tr>
                    <th class="py-2.5 px-4 text-[11px] text-gray-text font-bold uppercase border-b border-border-color whitespace-nowrap">NAMA KATEGORI</th>
                    <th class="py-2.5 px-4 text-[11px] text-gray-text font-bold uppercase border-b border-border-color whitespace-nowrap">JUMLAH</th>
                    <th class="py-2.5 px-4 text-[11px] text-gray-text font-bold uppercase border-b border-border-color whitespace-nowrap">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kategoris as $kategori)
                <tr class="border-b border-border-color hover:bg-off-white transition">
                    <td class="py-2.5 px-4 text-xs sm:text-[13px] whitespace-nowrap"><strong>{{ $kategori->nama_kategori }}</strong></td>
                    <td class="py-2.5 px-4 text-xs sm:text-[13px] whitespace-nowrap">{{ $kategori->obat_count }} obat</td>
                    <td class="py-2.5 px-4 text-xs sm:text-[13px]">
                        <button type="button"
                            onclick="bukaEdit({{ $kategori->id }}, @js($kategori->nama_kategori))"
                            class="text-soft-teal font-semibold mr-3 hover:underline">Edit</button>

                        @if($kategori->obat_count > 0)
                        <span class="text-gray-400 font-semibold cursor-not-allowed">Hapus</span>
                        <span class="block text-[11px] text-gray-text">tidak dapat dihapus, masih dipakai obat</span>
                        @else
                        <form action="{{ route('kategori.destroy', $kategori->id) }}" method="POST" class="inline-block"
                            onsubmit="return confirm('Hapus kategori {{ addslashes($kategori->nama_kategori) }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-muted-coral font-semibold hover:underline">Hapus</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="p-10 text-center text-gray-text text-sm">Belum ada kategori.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- FORM TAMBAH KATEGORI --}}
    <div class="w-full lg:w-80 shrink-0 bg-pure-white border border-border-color rounded-xl p-4 lg:p-5 shadow-sm">
        <h3 class="m-0 mb-3 text-base font-bold">Tambah kategori</h3>

        <form action="{{ route('kategori.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_mode" value="add">
            <label class="block mb-1.5 text-sm text-gray-text" for="nama_kategori">Nama kategori</label>
            <input type="text" id="nama_kategori" name="nama_kategori"
                value="{{ old('_mode') === 'add' ? old('nama_kategori') : '' }}" autocomplete="off" required
                class="w-full p-2.5 border rounded-lg text-sm outline-none focus:border-soft-teal {{ $errors->has('nama_kategori') && old('_mode') === 'add' ? 'border-muted-coral' : 'border-border-color' }}">

            @if($errors->has('nama_kategori') && old('_mode') === 'add')
            <div class="bg-[#FDE4DB] text-[#C0392B] rounded-lg p-2.5 text-sm mt-3">
                {{ $errors->first('nama_kategori') }}
            </div>
            @endif

            <p class="text-xs text-gray-text mt-3 mb-4">
                Penulisan dirapikan otomatis: huruf besar/kecil, spasi, dan tanda hubung dianggap sama.
            </p>

            <button type="submit"
                class="w-full px-5 py-2.5 border-none bg-soft-teal text-white rounded-lg font-bold hover:opacity-90 shadow-sm transition">
                Simpan
            </button>
        </form>
    </div>
</div>

{{-- MODAL EDIT KATEGORI --}}
<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-pure-white w-full max-w-md rounded-xl p-5 sm:p-7 shadow-xl">
        <h3 class="mt-0 text-xl font-bold mb-5">Edit Kategori</h3>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="_mode" value="edit">
            <label class="block mb-2 text-sm font-semibold">Nama kategori</label>
            <input type="text" name="nama_kategori" id="edit_nama" required autocomplete="off"
                class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                    class="px-5 py-2.5 border border-border-color bg-pure-white rounded-lg font-bold hover:bg-off-white transition">Batal</button>
                <button type="submit"
                    class="px-5 py-2.5 border-none bg-soft-teal text-white rounded-lg font-bold hover:opacity-90 shadow-sm transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function bukaEdit(id, nama) {
        document.getElementById('edit_nama').value = nama;
        document.getElementById('editForm').action = "{{ url('/kategori') }}/" + id;
        document.getElementById('editModal').classList.remove('hidden');
    }
</script>
@endsection