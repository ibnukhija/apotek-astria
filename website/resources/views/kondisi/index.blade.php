@extends('layouts.app')

@section('title', 'Kondisi Obat & Stok Opname - Apotek Astria 2')

@section('content')
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-4 shrink-0">
    <div>
        <h2 class="m-0 text-xl lg:text-2xl font-bold">Kondisi Obat &amp; Stok Opname</h2>
        <span class="text-gray-text text-xs sm:text-sm">Pencatatan obat rusak, retur/pemusnahan kadaluarsa, dan cek stok fisik</span>
    </div>
    <button type="button" onclick="bukaModalKondisi()"
        class="bg-soft-teal text-white px-4 py-2.5 rounded-lg text-sm font-bold hover:opacity-90 transition shadow-sm shrink-0">
        + Catat Kondisi / Opname
    </button>
</div>

@if (session('success'))
    <div class="flash-alert mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm font-medium flex items-center justify-between transition-opacity duration-300 shrink-0">
        <span>{{ session('success') }}</span>
        <button type="button" onclick="tutupAlert(this.parentElement)" class="text-green-700 font-bold text-lg leading-none">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm shrink-0">
        <ul class="list-disc list-inside m-0 space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- 4 Kartu Ringkasan Kondisi Stok --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4 shrink-0">
    <div class="bg-pure-white border border-border-color rounded-xl p-3.5 shadow-sm">
        <div class="text-xs text-gray-text font-semibold">Stok Hampir Habis (&lt; 10)</div>
        <div class="text-2xl font-bold text-amber-600 mt-1">{{ $ringkasan['stok_menipis'] }}</div>
        <div class="text-[11px] text-gray-text">Produk segera restock</div>
    </div>
    <div class="bg-pure-white border border-border-color rounded-xl p-3.5 shadow-sm">
        <div class="text-xs text-gray-text font-semibold">Hampir Expired (&le; 7 hr)</div>
        <div class="text-2xl font-bold text-yellow-700 mt-1">{{ $ringkasan['hampir_expired'] }}</div>
        <div class="text-[11px] text-gray-text">Masih bisa retur ke supplier</div>
    </div>
    <div class="bg-pure-white border border-border-color rounded-xl p-3.5 shadow-sm">
        <div class="text-xs text-gray-text font-semibold">Sudah Expired</div>
        <div class="text-2xl font-bold text-red-600 mt-1">{{ $ringkasan['sudah_expired'] }}</div>
        <div class="text-[11px] text-gray-text">Harus segera dimusnahkan</div>
    </div>
    <div class="bg-pure-white border border-border-color rounded-xl p-3.5 shadow-sm">
        <div class="text-xs text-gray-text font-semibold">Total Unit Rusak</div>
        <div class="text-2xl font-bold text-muted-coral mt-1">{{ $ringkasan['unit_rusak'] }}</div>
        <div class="text-[11px] text-gray-text">Tercatat pada riwayat rusak</div>
    </div>
</div>

{{-- Panel Tabel Riwayat Kondisi & Opname --}}
<div class="flex-1 min-h-0 bg-pure-white border border-border-color rounded-xl flex flex-col overflow-hidden shadow-sm">
    <form method="GET" action="{{ route('kondisi.index') }}" class="p-3 px-4 border-b border-border-color flex flex-wrap gap-2 items-center shrink-0">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama obat, kode, atau penyebab..."
            class="flex-1 min-w-[180px] py-2 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
        <select name="jenis" class="py-2 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-white">
            <option value="">Semua Jenis</option>
            @foreach (\App\Models\KondisiStokObat::DAFTAR_JENIS as $kode => $label)
                <option value="{{ $kode }}" @selected(request('jenis') === $kode)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-soft-teal text-white rounded-lg text-sm font-bold hover:opacity-90 transition">Filter</button>
        @if (request()->hasAny(['q', 'jenis']) && (request('q') || request('jenis')))
            <a href="{{ route('kondisi.index') }}" class="px-3 py-2 border border-border-color rounded-lg text-sm text-gray-text hover:bg-off-white transition">Reset</a>
        @endif
    </form>

    <div class="flex-1 overflow-auto">
        <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 bg-off-white z-10">
                <tr>
                    @foreach (['WAKTU', 'OBAT', 'JENIS', 'PERUBAHAN STOK', 'JUMLAH / SELISIH', 'PENYEBAB & CATATAN', 'PETUGAS'] as $judul)
                        <th class="py-2.5 px-3 text-[11px] text-gray-text font-bold uppercase border-b border-border-color whitespace-nowrap">{{ $judul }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayat as $row)
                    @php
                        $badgeKelas = match ($row->jenis) {
                            'rusak'          => 'bg-red-100 text-red-700',
                            'retur_supplier' => 'bg-yellow-100 text-yellow-800',
                            'musnah_expired' => 'bg-rose-100 text-rose-800',
                            default          => 'bg-blue-100 text-blue-700',
                        };
                        $selisih = $row->stok_sesudah - $row->stok_sebelum;
                    @endphp
                    <tr class="border-b border-border-color hover:bg-off-white transition text-xs sm:text-[13px]">
                        <td class="py-2.5 px-3 text-gray-text whitespace-nowrap">{{ $row->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="py-2.5 px-3">
                            <div class="font-bold text-dark-slate">{{ $row->obat->nama_obat ?? '-' }}</div>
                            <div class="text-[11px] text-gray-text font-mono">{{ $row->obat->kode_obat ?? '-' }}</div>
                        </td>
                        <td class="py-2.5 px-3 whitespace-nowrap">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeKelas }}">{{ $row->jenis_label }}</span>
                        </td>
                        <td class="py-2.5 px-3 whitespace-nowrap font-medium">
                            {{ $row->stok_sebelum }} &rarr; <strong class="text-dark-slate">{{ $row->stok_sesudah }}</strong>
                        </td>
                        <td class="py-2.5 px-3 whitespace-nowrap font-bold {{ $selisih < 0 ? 'text-red-600' : ($selisih > 0 ? 'text-green-700' : 'text-gray-text') }}">
                            @if ($row->jenis === 'stok_opname')
                                {{ $selisih > 0 ? '+' . $selisih : $selisih }} unit
                            @else
                                -{{ $row->jumlah }} unit
                            @endif
                        </td>
                        <td class="py-2.5 px-3 min-w-[200px]">
                            <div class="font-semibold text-dark-slate">{{ $row->penyebab }}</div>
                            @if ($row->catatan)
                                <div class="text-[11px] text-gray-text mt-0.5">{{ $row->catatan }}</div>
                            @endif
                        </td>
                        <td class="py-2.5 px-3 text-gray-text whitespace-nowrap">{{ $row->user->nama ?? 'Karyawan' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-10 text-center text-gray-text text-sm">Belum ada catatan kondisi obat atau stok opname.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($riwayat->hasPages())
        <div class="p-3 border-t border-border-color bg-pure-white pagination-custom">
            {{ $riwayat->links() }}
        </div>
    @endif
</div>

{{-- ================= MODAL CATAT KONDISI / STOK OPNAME ================= --}}
<div id="modalKondisi" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-pure-white w-full max-w-lg max-h-[92vh] overflow-y-auto rounded-xl p-5 sm:p-6 shadow-2xl border border-border-color">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="m-0 text-base sm:text-lg font-bold text-dark-slate">Catat Kondisi Obat / Stok Opname</h3>
                <p class="text-xs text-gray-text m-0 mt-0.5">Stok aktif obat otomatis menyesuaikan catatan ini</p>
            </div>
            <button type="button" onclick="tutupModalKondisi()" class="text-gray-text hover:text-dark-slate text-xl leading-none px-2">&times;</button>
        </div>

        <form method="POST" action="{{ route('kondisi.store') }}" id="formKondisi" class="space-y-3.5" onsubmit="return validasiFormKondisi()">
            @csrf

            <div>
                <div class="flex items-center justify-between gap-2 mb-1">
                    <label for="f_obat_search" class="block text-xs font-bold text-dark-slate">Pilih Obat *</label>
                    <label id="lblFilterEd" class="inline-flex items-center gap-1.5 text-[11px] text-gray-text cursor-pointer select-none">
                        <input type="checkbox" id="chkFilterEd" onchange="renderDaftarObat(); $k('f_obat_dropdown').classList.remove('hidden');" class="rounded border-border-color accent-soft-teal">
                        <span>Hanya Kadaluarsa / H-7</span>
                    </label>
                </div>

                <input type="hidden" name="obat_id" id="f_obat_id" value="{{ old('obat_id', request('obat_id')) }}">
                <div id="obat-wrapper" class="relative">
                    <div class="relative">
                        <input type="text" id="f_obat_search" autocomplete="off"
                            placeholder="Ketik nama atau kode obat (mis. Paracetamol / OBT-001)..."
                            class="w-full py-2.5 pl-3 pr-9 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                        <button type="button" id="btnClearObat" onclick="hapusPilihanObat()" title="Ganti obat"
                            class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-text hover:text-dark-slate text-base leading-none px-1">&times;</button>
                    </div>
                    <div id="f_obat_dropdown"
                        class="hidden absolute left-0 right-0 mt-1 bg-pure-white border border-border-color rounded-lg shadow-xl max-h-60 overflow-y-auto z-30">
                        <ul id="f_obat_list" class="divide-y divide-border-color m-0 p-0 list-none"></ul>
                    </div>
                </div>
                <div id="infoObatTerpilih" class="hidden mt-1.5 text-xs bg-off-white border border-border-color rounded-lg px-3 py-2 text-dark-slate"></div>
            </div>

            <div>
                <label for="f_jenis" class="block text-xs font-bold text-dark-slate mb-1">Jenis Kondisi / Tindakan *</label>
                <select name="jenis" id="f_jenis" required onchange="sesuaikanFieldJenis()"
                    class="w-full py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal bg-white">
                    @foreach (\App\Models\KondisiStokObat::DAFTAR_JENIS as $kode => $label)
                        <option value="{{ $kode }}" @selected(old('jenis', request('jenis', 'rusak')) === $kode)>{{ $label }}</option>
                    @endforeach
                </select>
                <div id="infoPanduanJenis" class="mt-1.5 text-[11px] px-3 py-2 rounded-lg bg-[#E8F4F2] text-soft-teal font-medium"></div>
            </div>

            <div id="wrapJumlah">
                <label for="f_jumlah" class="block text-xs font-bold text-dark-slate mb-1">Jumlah Unit *</label>
                <input type="number" name="jumlah" id="f_jumlah" min="1" value="{{ old('jumlah', 1) }}"
                    class="w-full py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
            </div>

            <div id="wrapStokFisik" class="hidden">
                <label for="f_stok_fisik" class="block text-xs font-bold text-dark-slate mb-1">Stok Fisik Hasil Hitung *</label>
                <input type="number" name="stok_fisik" id="f_stok_fisik" min="0" value="{{ old('stok_fisik') }}" oninput="hitungSelisihOpname()"
                    class="w-full py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                <div id="infoSelisihOpname" class="text-xs text-gray-text mt-1"></div>
            </div>

            <div>
                <label for="f_penyebab" class="block text-xs font-bold text-dark-slate mb-1">Penyebab / Alasan *</label>
                <input type="text" name="penyebab" id="f_penyebab" required value="{{ old('penyebab') }}"
                    placeholder="Contoh: Kemasan sobek, Retur H-5 ke PBF, Hasil stok opname..."
                    class="w-full py-2.5 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                <div class="flex flex-wrap gap-1.5 mt-1.5">
                    @foreach (['Kemasan sobek / rusak', 'Lembap / berubah warna', 'Retur ke supplier (mendekati expired)', 'Dimusnahkan (kadaluarsa)', 'Penyesuaian stok fisik'] as $contoh)
                        <button type="button" onclick="document.getElementById('f_penyebab').value = '{{ $contoh }}'"
                            class="text-[11px] px-2 py-0.5 rounded bg-off-white border border-border-color text-gray-text hover:text-dark-slate">
                            {{ $contoh }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="f_catatan" class="block text-xs font-bold text-dark-slate mb-1">Catatan Tambahan (opsional)</label>
                <textarea name="catatan" id="f_catatan" rows="2" placeholder="Nomor batch, nama supplier retur, atau berita acara pemusnahan..."
                    class="w-full py-2 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">{{ old('catatan') }}</textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="tutupModalKondisi()"
                    class="px-4 py-2 border border-border-color rounded-lg text-sm font-semibold hover:bg-off-white transition">
                    Batal
                </button>
                <button type="submit"
                    class="px-5 py-2 bg-soft-teal text-white rounded-lg text-sm font-bold hover:opacity-90 transition shadow-sm">
                    Simpan Catatan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const $k = (id) => document.getElementById(id);
const daftarObat = @json($obats, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES);
let obatTerpilih = null;

function cariObatById(id) {
    if (!id) return null;
    return daftarObat.find(o => String(o.id) === String(id)) || null;
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
}

function renderDaftarObat() {
    const kata = ($k('f_obat_search').value || '').toLowerCase().trim();
    const hanyaEd = $k('chkFilterEd')?.checked ?? false;
    const ul = $k('f_obat_list');
    ul.innerHTML = '';

    let hasil = daftarObat.filter(o => {
        if (hanyaEd && o.status_kadaluarsa !== 'Kadaluarsa' && o.status_kadaluarsa !== 'Hampir kadaluarsa') {
            return false;
        }
        if (!kata) return true;
        return o.nama_obat.toLowerCase().includes(kata) || o.kode_obat.toLowerCase().includes(kata);
    });

    const maksTampil = 40;
    const dipotong = hasil.slice(0, maksTampil);

    if (dipotong.length === 0) {
        ul.innerHTML = '<li class="px-3 py-3 text-xs text-gray-text text-center">Obat tidak ditemukan.</li>';
        return;
    }

    dipotong.forEach(o => {
        const li = document.createElement('li');
        li.className = 'px-3 py-2.5 cursor-pointer hover:bg-[#E8F4F2] transition';
        li.innerHTML = `
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs sm:text-sm font-bold text-dark-slate truncate">${escapeHtml(o.kode_obat)} &bull; ${escapeHtml(o.nama_obat)}</span>
                <span class="${escapeHtml(o.kelas_kadaluarsa)} px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0">${escapeHtml(o.status_kadaluarsa)}</span>
            </div>
            <div class="text-[11px] text-gray-text mt-0.5">Stok: <strong>${o.stok} ${escapeHtml(o.satuan)}</strong> &bull; ED: <strong>${escapeHtml(o.tgl_kadaluarsa)}</strong></div>
        `;
        li.onmousedown = (e) => {
            e.preventDefault();
            pilihObat(o);
        };
        ul.appendChild(li);
    });

    if (hasil.length > maksTampil) {
        const info = document.createElement('li');
        info.className = 'px-3 py-1.5 text-[11px] text-gray-text bg-off-white text-center';
        info.textContent = `Menampilkan ${maksTampil} dari ${hasil.length} hasil — ketik lebih spesifik`;
        ul.appendChild(info);
    }
}

function pilihObat(o) {
    obatTerpilih = o;
    $k('f_obat_id').value = o.id;
    $k('f_obat_search').value = `${o.kode_obat} - ${o.nama_obat}`;
    $k('btnClearObat').classList.remove('hidden');
    $k('f_obat_dropdown').classList.add('hidden');
    perbaruiInfoObat();
}

function hapusPilihanObat() {
    obatTerpilih = null;
    $k('f_obat_id').value = '';
    $k('f_obat_search').value = '';
    $k('btnClearObat').classList.add('hidden');
    perbaruiInfoObat();
    renderDaftarObat();
    $k('f_obat_dropdown').classList.remove('hidden');
    $k('f_obat_search').focus();
}

$k('f_obat_search')?.addEventListener('focus', () => {
    renderDaftarObat();
    $k('f_obat_dropdown').classList.remove('hidden');
});

$k('f_obat_search')?.addEventListener('input', () => {
    obatTerpilih = null;
    $k('f_obat_id').value = '';
    $k('btnClearObat').classList.toggle('hidden', !$k('f_obat_search').value);
    perbaruiInfoObat();
    renderDaftarObat();
    $k('f_obat_dropdown').classList.remove('hidden');
});

document.addEventListener('click', (e) => {
    const wrap = $k('obat-wrapper');
    if (wrap && !wrap.contains(e.target) && !e.target.closest?.('#lblFilterEd')) {
        $k('f_obat_dropdown')?.classList.add('hidden');
        if (!obatTerpilih && $k('f_obat_id').value) {
            const ditemukan = cariObatById($k('f_obat_id').value);
            if (ditemukan) pilihObat(ditemukan);
        }
    }
});

function validasiFormKondisi() {
    if (!$k('f_obat_id').value) {
        alert('Silakan pilih obat dari daftar hasil pencarian terlebih dahulu.');
        $k('f_obat_search').focus();
        return false;
    }
    return true;
}

function bukaModalKondisi() {
    $k('modalKondisi').classList.remove('hidden');
    if (!obatTerpilih && $k('f_obat_id').value) {
        const awal = cariObatById($k('f_obat_id').value);
        if (awal) pilihObat(awal);
    }
    perbaruiInfoObat();
    sesuaikanFieldJenis();
}

function tutupModalKondisi() {
    $k('modalKondisi').classList.add('hidden');
}

function perbaruiInfoObat() {
    const box = $k('infoObatTerpilih');
    if (!obatTerpilih) {
        box.classList.add('hidden');
        return;
    }
    box.classList.remove('hidden');
    box.innerHTML = `Stok sistem: <strong>${obatTerpilih.stok} ${escapeHtml(obatTerpilih.satuan)}</strong> &bull; ED: <strong>${escapeHtml(obatTerpilih.tgl_kadaluarsa)}</strong> <span class="${escapeHtml(obatTerpilih.kelas_kadaluarsa)} ml-1 px-2 py-0.5 rounded-full text-[10px] font-semibold">${escapeHtml(obatTerpilih.status_kadaluarsa)}</span>`;
    hitungSelisihOpname();
}

function sesuaikanFieldJenis() {
    const jenis = $k('f_jenis').value;
    const isOpname = jenis === 'stok_opname';
    $k('wrapJumlah').classList.toggle('hidden', isOpname);
    $k('wrapStokFisik').classList.toggle('hidden', !isOpname);
    $k('f_jumlah').required = !isOpname;
    $k('f_stok_fisik').required = isOpname;

    const panduan = {
        rusak: 'Stok obat akan dikurangi sesuai jumlah unit rusak.',
        retur_supplier: 'Untuk obat mendekati expired (<= 7 hari) yang dikembalikan ke supplier; stok aktif akan dikurangi.',
        musnah_expired: 'Untuk obat yang sudah lewat tanggal expired dan dimusnahkan; stok aktif akan dikurangi.',
        stok_opname: 'Masukkan hasil hitung stok fisik di rak; sistem otomatis menghitung selisih dan menyamakan stok sistem.'
    };
    $k('infoPanduanJenis').textContent = panduan[jenis] || '';
    hitungSelisihOpname();
}

function hitungSelisihOpname() {
    if ($k('f_jenis').value !== 'stok_opname' || !obatTerpilih) return;
    const info = $k('infoSelisihOpname');
    const fisik = parseInt($k('f_stok_fisik').value, 10);
    if (Number.isNaN(fisik)) { info.textContent = ''; return; }
    const sistem = parseInt(obatTerpilih.stok || '0', 10);
    const selisih = fisik - sistem;
    info.textContent = selisih === 0
        ? 'Selisih: 0 (stok fisik cocok dengan sistem)'
        : `Selisih: ${selisih > 0 ? '+' + selisih : selisih} unit (stok sistem ${sistem} -> ${fisik})`;
}

$k('modalKondisi')?.addEventListener('click', (e) => {
    if (e.target.id === 'modalKondisi') tutupModalKondisi();
});

@if ($errors->any() || request()->filled('obat_id'))
    bukaModalKondisi();
@endif
</script>
@endsection
