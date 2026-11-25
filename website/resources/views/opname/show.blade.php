@extends('layouts.app')

@section('title', 'Opname ' . $stokOpname->kode . ' - Apotek Astria 2')

@section('content')
@php $readonly = ! $stokOpname->isDraft(); @endphp

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-3 shrink-0">
    <div>
        <h2 class="m-0 text-xl lg:text-2xl font-bold">
            Opname {{ $stokOpname->kode }}
            <span class="ml-1 align-middle px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $readonly ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-800' }}">{{ $readonly ? 'Final' : 'Draft' }}</span>
        </h2>
        <span class="text-gray-text text-xs sm:text-sm">
            Periode {{ $stokOpname->periode }} &bull; {{ $stokOpname->tanggal?->format('d/m/Y') }} &bull; Petugas {{ $stokOpname->user->nama ?? 'Karyawan' }}
            @if ($readonly) &bull; Final {{ $stokOpname->finalized_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} @endif
        </span>
    </div>
    <div class="flex gap-2 shrink-0">
        <a href="{{ route('stok-opname.index') }}" class="px-4 py-2.5 border border-border-color rounded-lg text-sm font-semibold hover:bg-off-white transition">&larr; Daftar</a>
        @unless ($readonly)
            <button type="button" id="btnRefreshStok"
                class="px-4 py-2.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-sm font-semibold hover:bg-blue-100 transition flex items-center gap-1.5"
                onclick="segaranStokSistem()">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                Segarkan Stok Sistem
            </button>
            <form method="POST" action="{{ route('stok-opname.finalisasi', $stokOpname->id) }}" onsubmit="return konfirmasiFinal()">
                @csrf
                <button type="submit" class="px-5 py-2.5 bg-soft-teal text-white rounded-lg text-sm font-bold hover:opacity-90 transition shadow-sm">Finalisasi Opname</button>
            </form>
        @endunless
    </div>
</div>

@if (session('success'))
    <div class="mb-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm font-medium shrink-0">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="mb-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm shrink-0">
        <ul class="list-disc list-inside m-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

{{-- Ringkasan live --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3 shrink-0">
    <div class="bg-pure-white border border-border-color rounded-xl p-3 shadow-sm">
        <div class="text-xs text-gray-text font-semibold">Sudah Dihitung</div>
        <div class="text-xl font-bold mt-1"><span id="sumTerisi">0</span> / {{ $details->count() }}</div>
    </div>
    <div class="bg-pure-white border border-border-color rounded-xl p-3 shadow-sm">
        <div class="text-xs text-gray-text font-semibold">Obat Ada Selisih</div>
        <div id="sumSelisih" class="text-xl font-bold mt-1 text-amber-600">0</div>
    </div>
    <div class="bg-pure-white border border-border-color rounded-xl p-3 shadow-sm">
        <div class="text-xs text-gray-text font-semibold">Total Unit Kurang / Lebih</div>
        <div class="text-xl font-bold mt-1"><span id="sumKurang" class="text-red-600">0</span> / <span id="sumLebih" class="text-green-700">0</span></div>
    </div>
    <div class="bg-pure-white border border-border-color rounded-xl p-3 shadow-sm">
        <div class="text-xs text-gray-text font-semibold">Rincian Belum Cocok</div>
        <div id="sumRincian" class="text-xl font-bold mt-1 text-red-600">0</div>
    </div>
</div>

<div class="flex-1 min-h-0 bg-pure-white border border-border-color rounded-xl flex flex-col overflow-hidden shadow-sm">
    <div class="p-3 px-4 border-b border-border-color flex flex-wrap gap-2 items-center shrink-0">
        <input type="text" id="cariOpname" placeholder="Cari nama / kode obat..."
            class="flex-1 min-w-[180px] py-2 px-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
        <select id="filterKat" class="py-2 px-3 border border-border-color rounded-lg text-sm outline-none bg-white">
            <option value="">Semua kategori</option>
            @foreach ($kategoris as $k)
                <option value="{{ $k->id }}">{{ $k->nama_kategori }}</option>
            @endforeach
        </select>
        <label class="inline-flex items-center gap-1.5 text-xs text-gray-text cursor-pointer select-none">
            <input type="checkbox" id="chkSelisih" class="accent-soft-teal" @checked($readonly)>
            <span>Hanya yang selisih</span>
        </label>
        @unless ($readonly)
            <label class="inline-flex items-center gap-1.5 text-xs text-gray-text cursor-pointer select-none">
                <input type="checkbox" id="chkBelum" class="accent-soft-teal">
                <span>Hanya yang belum dihitung</span>
            </label>
        @endunless
    </div>

    <div class="flex-1 overflow-auto">
        <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 bg-off-white z-10">
                <tr>
                    @foreach (['KODE', 'OBAT', 'KATEGORI', 'STOK SISTEM', 'STOK FISIK', 'SELISIH', 'RUSAK', 'HILANG', 'CATATAN', ''] as $j)
                        <th class="py-2.5 px-3 text-[11px] text-gray-text font-bold uppercase border-b border-border-color whitespace-nowrap">{{ $j }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="bodyOpname">
                @foreach ($details as $d)
                    <tr class="baris border-b border-border-color text-xs sm:text-[13px]"
                        data-cari="{{ strtolower($d->obat->nama_obat . ' ' . $d->obat->kode_obat) }}"
                        data-kategori="{{ $d->obat->kategori_id }}"
                        data-sistem="{{ $d->stok_sistem }}"
                        data-url="{{ route('stok-opname.detail', [$stokOpname->id, $d->id]) }}">
                        <td class="py-2 px-3 font-mono text-gray-text whitespace-nowrap">{{ $d->obat->kode_obat }}</td>
                        <td class="py-2 px-3 min-w-[160px]">
                            <div class="font-bold text-dark-slate">{{ $d->obat->nama_obat }}</div>
                            <div class="text-[11px] text-gray-text">{{ $d->obat->satuan }}</div>
                        </td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $d->obat->kategori->nama_kategori ?? '-' }}</td>
                        <td class="py-2 px-3 font-bold">{{ $d->stok_sistem }}</td>
                        <td class="py-2 px-3">
                            <input type="number" min="0" value="{{ $d->stok_fisik }}" @disabled($readonly)
                                class="in-fisik w-20 py-1.5 px-2 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                        </td>
                        <td class="sel py-2 px-3 font-bold whitespace-nowrap">-</td>
                        <td class="py-2 px-3">
                            <input type="number" min="0" value="{{ $d->jml_rusak }}" @disabled($readonly)
                                class="in-rusak w-16 py-1.5 px-2 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal disabled:bg-gray-100">
                        </td>
                        <td class="py-2 px-3">
                            <input type="number" min="0" value="{{ $d->jml_hilang }}" @disabled($readonly)
                                class="in-hilang w-16 py-1.5 px-2 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal disabled:bg-gray-100">
                        </td>
                        <td class="py-2 px-3 min-w-[220px] max-w-[340px]">
                            @if ($readonly)
                                <div class="text-xs sm:text-[13px] text-dark-slate whitespace-normal break-words leading-snug">{{ filled($d->catatan) ? $d->catatan : '-' }}</div>
                            @else
                                <textarea maxlength="255" rows="2"
                                    placeholder="Wajib bila selisih lebih"
                                    class="in-catatan w-56 sm:w-64 py-1.5 px-2 border border-border-color rounded-lg text-xs sm:text-sm outline-none focus:border-soft-teal resize-y leading-snug">{{ $d->catatan }}</textarea>
                            @endif
                        </td>
                        <td class="status py-2 px-3 text-[11px] text-gray-text whitespace-nowrap"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
const READONLY = @json($readonly);
const csrfOpname = "{{ csrf_token() }}";
const rows = [...document.querySelectorAll('#bodyOpname .baris')];
const num = (v) => (v === '' || v === null || v === undefined) ? null : parseInt(v, 10);

function hitungBaris(tr) {
    const sistem = Number(tr.dataset.sistem);
    const fisik  = num(tr.querySelector('.in-fisik').value);
    const rusak  = tr.querySelector('.in-rusak');
    const hilang = tr.querySelector('.in-hilang');
    const sel    = tr.querySelector('.sel');

    if (fisik === null || Number.isNaN(fisik)) {
        tr.dataset.state = 'kosong';
        sel.textContent = '-';
        sel.className = 'sel py-2 px-3 font-bold whitespace-nowrap text-gray-text';
        rusak.disabled = hilang.disabled = true;
        return;
    }

    const s = fisik - sistem;
    const kurang = s < 0;
    rusak.disabled = hilang.disabled = READONLY || !kurang;
    if (!kurang && !READONLY) { rusak.value = 0; hilang.value = 0; }

    const rinci = (num(rusak.value) || 0) + (num(hilang.value) || 0);
    tr.dataset.selisih = s;

    if (s === 0) {
        tr.dataset.state = 'sama';
        sel.textContent = '0 (sesuai)';
        sel.className = 'sel py-2 px-3 font-bold whitespace-nowrap text-gray-text';
    } else if (s > 0) {
        tr.dataset.state = 'lebih';
        sel.textContent = '+' + s;
        sel.className = 'sel py-2 px-3 font-bold whitespace-nowrap text-green-700';
    } else if (rinci === -s) {
        tr.dataset.state = 'kurang';
        sel.textContent = s + ' (rincian OK)';
        sel.className = 'sel py-2 px-3 font-bold whitespace-nowrap text-red-600';
    } else {
        tr.dataset.state = 'rincian';
        sel.textContent = s + ' (rincian ' + rinci + '/' + (-s) + ')';
        sel.className = 'sel py-2 px-3 font-bold whitespace-nowrap text-red-600';
    }
}

function hitungRingkasan() {
    let terisi = 0, selisih = 0, kurang = 0, lebih = 0, rincian = 0;
    rows.forEach(tr => {
        const st = tr.dataset.state;
        if (st && st !== 'kosong') terisi++;
        const s = Number(tr.dataset.selisih || 0);
        if (st === 'kurang' || st === 'rincian') { selisih++; kurang += -s; }
        if (st === 'lebih') { selisih++; lebih += s; }
        if (st === 'rincian') rincian++;
    });
    document.getElementById('sumTerisi').textContent = terisi;
    document.getElementById('sumSelisih').textContent = selisih;
    document.getElementById('sumKurang').textContent = kurang;
    document.getElementById('sumLebih').textContent = lebih;
    document.getElementById('sumRincian').textContent = rincian;
}

function terapkanFilter() {
    const kata = document.getElementById('cariOpname').value.toLowerCase().trim();
    const kat  = document.getElementById('filterKat').value;
    const hanyaSelisih = document.getElementById('chkSelisih').checked;
    const hanyaBelum = document.getElementById('chkBelum')?.checked ?? false;

    rows.forEach(tr => {
        const st = tr.dataset.state;
        let tampil = true;
        if (kata && !tr.dataset.cari.includes(kata)) tampil = false;
        if (kat && tr.dataset.kategori !== kat) tampil = false;
        if (hanyaSelisih && !['kurang', 'lebih', 'rincian'].includes(st)) tampil = false;
        if (hanyaBelum && st !== 'kosong') tampil = false;
        tr.classList.toggle('hidden', !tampil);
    });
}

function simpanBaris(tr) {
    const st = tr.querySelector('.status');
    st.textContent = 'menyimpan...';
    clearTimeout(tr._timer);
    tr._timer = setTimeout(async () => {
        try {
            const res = await fetch(tr.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfOpname,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    stok_fisik: num(tr.querySelector('.in-fisik').value),
                    jml_rusak:  num(tr.querySelector('.in-rusak').value) || 0,
                    jml_hilang: num(tr.querySelector('.in-hilang').value) || 0,
                    catatan:    tr.querySelector('.in-catatan').value
                })
            });
            st.textContent = res.ok ? 'tersimpan ✓' : 'GAGAL disimpan';
            st.style.color = res.ok ? '#15803D' : '#B91C1C';
        } catch (e) {
            st.textContent = 'GAGAL disimpan';
            st.style.color = '#B91C1C';
        }
    }, 500);
}

function segaranStokSistem() {
    const btn = document.getElementById('btnRefreshStok');
    if (!btn) return;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyegarkan...';

    fetch('{{ route("stok-opname.refresh-stok", $stokOpname->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfOpname,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        if (data.ok) {
            if (data.berubah && data.berubah.length > 0) {
                alert('Stok sistem disegarkan untuk ' + data.berubah.length + ' obat:\n' + data.berubah.slice(0, 10).join('\n') + (data.berubah.length > 10 ? '\n...' : '') + '\n\nSilakan periksa kembali selisih setiap baris.');
            } else {
                alert(data.message);
            }
            location.reload();
        } else {
            alert('Gagal menyegarkan: ' + (data.message ?? 'Unknown error'));
        }
    })
    .catch(e => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('Gagal menyegarkan stok sistem: ' + e.message);
    });
}

function konfirmasiFinal() {
    if ([...document.querySelectorAll('.status')].some(s => s.textContent === 'menyimpan...')) {
        alert('Masih ada isian yang sedang disimpan. Tunggu sebentar lalu coba lagi.');
        return false;
    }
    return confirm('Finalisasi opname? Stok aktif semua obat akan disesuaikan dengan stok fisik dan tidak bisa dibatalkan.');
}

rows.forEach(hitungBaris);
hitungRingkasan();
terapkanFilter();

if (!READONLY) {
    document.getElementById('bodyOpname').addEventListener('input', (e) => {
        const tr = e.target.closest('.baris');
        if (!tr) return;
        hitungBaris(tr);
        hitungRingkasan();
        simpanBaris(tr);
    });
}

['cariOpname', 'filterKat', 'chkSelisih', 'chkBelum'].forEach(id => {
    const el = document.getElementById(id);
    el?.addEventListener(el.type === 'checkbox' || el.tagName === 'SELECT' ? 'change' : 'input', terapkanFilter);
});
</script>
@endsection