@extends('layouts.app')

@section('title', 'Kelola Kategori - Apotek Astria 2')

@section('content')

<div class="flex justify-between items-center mb-5 shrink-0">
    <div>
        <h2 class="m-0 text-2xl font-bold">Kelola Kategori</h2>
        <span class="text-gray-text text-sm">Pengelompokan obat tanpa data ganda</span>
    </div>

    <button type="button" onclick="document.getElementById('nama_kategori').focus()"
        class="bg-soft-teal text-white px-5 py-2.5 border-none rounded-lg font-bold shadow-sm cursor-pointer hover:opacity-90 transition">
        + Tambah Kategori
    </button>
</div>

@if(session('success'))
    <div class="bg-[#E8F4F2] text-soft-teal p-4 rounded-lg mb-5 font-semibold border border-soft-teal shrink-0">
        {{ session('success') }}
    </div>
@endif

@if($errors->has('hapus') || ($errors->has('nama_kategori') && old('_mode') === 'edit'))
    <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-5 border border-red-300 shrink-0">
        {{ $errors->first('hapus') ?: $errors->first('nama_kategori') }}
    </div>
@endif

<div class="flex-1 min-h-0 flex gap-5 items-start">

    {{-- DAFTAR KATEGORI --}}
    <div class="flex-1 max-h-full bg-pure-white border border-border-color rounded-xl overflow-y-auto shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 bg-off-white z-10">
                <tr>
                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">NAMA KATEGORI</th>
                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">JUMLAH</th>
                    <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kategoris as $kategori)
                    <tr class="border-b border-border-color hover:bg-off-white transition">
                        <td class="p-4 px-5 text-sm"><strong>{{ $kategori->nama_kategori }}</strong></td>
                        <td class="p-4 px-5 text-sm">{{ $kategori->obat_count }} obat</td>
                        <td class="p-4 px-5 text-sm">
                            <button type="button"
                                onclick="bukaEdit({{ $kategori->id }}, @js($kategori->nama_kategori))"
                                class="text-soft-teal font-semibold mr-3 hover:underline">Edit</button>

                            @if($kategori->obat_count > 0)
                                <span class="text-gray-400 font-semibold cursor-not-allowed">Hapus</span>
                                <span class="block text-xs text-gray-text">tidak dapat dihapus, masih dipakai obat</span>
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
                    <tr><td colspan="3" class="p-10 text-center text-gray-text">Belum ada kategori.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- FORM TAMBAH KATEGORI --}}
    <div class="w-80 shrink-0 bg-pure-white border border-border-color rounded-xl p-5 shadow-sm">
        <h3 class="m-0 mb-4 text-base font-bold">Tambah kategori</h3>

        <form action="{{ route('kategori.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_mode" value="add">
            <label class="block mb-1.5 text-sm text-gray-text" for="nama_kategori">Nama kategori</label>
            <input type="text" id="nama_kategori" name="nama_kategori"
                value="{{ old('_mode') === 'add' ? old('nama_kategori') : '' }}" autocomplete="off" required
                class="w-full p-3 border rounded-lg text-sm outline-none focus:border-soft-teal {{ $errors->has('nama_kategori') && old('_mode') === 'add' ? 'border-muted-coral' : 'border-border-color' }}">

            @if($errors->has('nama_kategori') && old('_mode') === 'add')
                <div class="bg-[#FDE4DB] text-[#C0392B] rounded-lg p-2.5 text-sm mt-3">
                    {{ $errors->first('nama_kategori') }}
                </div>
            @endif

            <p class="text-xs text-gray-text mt-3 mb-4">
                Penulisan dirapikan otomatis: huruf besar/kecil, spasi, dan tanda hubung dianggap sama.
            </p>

            <button type="submit"
                class="w-full px-5 py-3 border-none bg-soft-teal text-white rounded-lg font-bold hover:opacity-90 shadow-sm transition">
                Simpan
            </button>
        </form>
    </div>
</div>

{{-- MODAL EDIT KATEGORI --}}
<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-pure-white w-full max-w-md rounded-xl p-8 shadow-xl">
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
