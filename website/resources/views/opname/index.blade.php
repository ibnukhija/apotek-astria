@extends('layouts.app')

@section('title', 'Stok Opname - Apotek Astria 2')

@section('content')
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-4 shrink-0">
    <div>
        <h2 class="m-0 text-xl lg:text-2xl font-bold">Stok Opname Bulanan</h2>
        <span class="text-gray-text text-xs sm:text-sm">Cek fisik seluruh obat: bandingkan stok sistem dengan jumlah riil di rak</span>
    </div>

    @if ($draft)
        <a href="{{ route('stok-opname.show', $draft->id) }}"
           class="bg-amber-500 text-white px-4 py-2.5 rounded-lg text-sm font-bold hover:opacity-90 transition shadow-sm shrink-0">
            Lanjutkan Opname ({{ $draft->kode }})
        </a>
    @else
        <form method="POST" action="{{ route('stok-opname.store') }}"
              onsubmit="return confirm('Mulai opname baru? Stok sistem semua obat akan dicatat sebagai acuan.');">
            @csrf
            <button type="submit" class="bg-soft-teal text-white px-4 py-2.5 rounded-lg text-sm font-bold hover:opacity-90 transition shadow-sm">
                + Mulai Opname Bulanan
            </button>
        </form>
    @endif
</div>

@if (session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm font-medium">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
        <ul class="list-disc list-inside m-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="flex-1 min-h-0 bg-pure-white border border-border-color rounded-xl flex flex-col overflow-hidden shadow-sm">
    <div class="flex-1 overflow-auto">
        <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 bg-off-white z-10">
                <tr>
                    @foreach (['KODE', 'PERIODE', 'TANGGAL', 'PETUGAS', 'JUMLAH OBAT', 'STATUS', 'AKSI'] as $j)
                        <th class="py-2.5 px-3 text-[11px] text-gray-text font-bold uppercase border-b border-border-color whitespace-nowrap">{{ $j }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $o)
                    <tr class="border-b border-border-color hover:bg-off-white transition text-xs sm:text-[13px]">
                        <td class="py-2.5 px-3 font-mono font-bold">{{ $o->kode }}</td>
                        <td class="py-2.5 px-3">{{ $o->periode }}</td>
                        <td class="py-2.5 px-3 whitespace-nowrap">{{ $o->tanggal?->format('d/m/Y') }}</td>
                        <td class="py-2.5 px-3">{{ $o->user->nama ?? 'Karyawan' }}</td>
                        <td class="py-2.5 px-3">{{ $o->details_count }}</td>
                        <td class="py-2.5 px-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $o->isDraft() ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-700' }}">
                                {{ $o->isDraft() ? 'Draft' : 'Final' }}
                            </span>
                        </td>
                        <td class="py-2.5 px-3">
                            <a href="{{ route('stok-opname.show', $o->id) }}" class="text-soft-teal font-semibold hover:underline">
                                {{ $o->isDraft() ? 'Lanjutkan' : 'Lihat Laporan' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-10 text-center text-gray-text text-sm">Belum ada opname.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($daftar->hasPages())
        <div class="p-3 border-t border-border-color pagination-custom">{{ $daftar->links() }}</div>
    @endif
</div>
@endsection