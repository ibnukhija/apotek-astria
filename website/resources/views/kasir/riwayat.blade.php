@extends('layouts.app')

@section('title', 'Riwayat Transaksi - Apotek Astria 2')

@section('content')
<div class="flex justify-between items-center mb-5 shrink-0">
    <div>
        <h2 class="m-0 text-2xl font-bold">Riwayat Transaksi</h2>
        <span class="text-gray-text text-sm">Histori penjualan obat di kasir</span>
    </div>

    <form method="GET" action="{{ route('kasir.riwayat') }}" class="flex items-center gap-3">
        <input type="date" name="tanggal" value="{{ request('tanggal') }}" 
            class="p-2 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
        <button type="submit" class="bg-soft-teal text-white px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90">Filter</button>
        @if(request('tanggal'))
            <a href="{{ route('kasir.riwayat') }}" class="text-xs text-gray-text hover:underline">Reset</a>
        @endif
    </form>
</div>

<div class="bg-pure-white border border-border-color rounded-xl overflow-hidden shadow-sm">
    <table class="w-full text-left border-collapse">
        <thead class="bg-off-white">
            <tr>
                <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">NO. TRANSAKSI</th>
                <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">WAKTU</th>
                <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">KASIR</th>
                <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">TOTAL</th>
                <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color">PEMBAYARAN</th>
                <th class="p-3 px-5 text-xs text-gray-text font-bold uppercase border-b border-border-color text-center">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transaksis as $trx)
            <tr class="border-b border-border-color hover:bg-off-white transition">
                <td class="p-4 px-5 text-sm font-bold text-soft-teal">{{ $trx->no_transaksi }}</td>
                <td class="p-4 px-5 text-sm">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                <td class="p-4 px-5 text-sm">{{ $trx->user->nama ?? 'Kasir' }}</td>
                <td class="p-4 px-5 text-sm font-semibold">Rp{{ number_format($trx->total_harga, 0, ',', '.') }}</td>
                <td class="p-4 px-5 text-sm">
                    Bayar: Rp{{ number_format($trx->total_bayar, 0, ',', '.') }}<br>
                    <span class="text-xs text-gray-text">Kembali: Rp{{ number_format($trx->kembalian, 0, ',', '.') }}</span>
                </td>
                <td class="p-4 px-5 text-sm text-center">
                    <a href="{{ route('kasir.struk', $trx->id) }}" target="_blank" 
                       class="inline-block px-3 py-1.5 bg-soft-teal text-white rounded text-xs font-semibold hover:opacity-90">
                        Cetak Struk
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="p-8 text-center text-gray-text">Belum ada riwayat transaksi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="p-4">
        {{ $transaksis->links() }}
    </div>
</div>
@endsection
