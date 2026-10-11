<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Transaksi - {{ $transaksi->no_transaksi }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 300px;
            margin: 0 auto;
            padding: 10px;
            font-size: 12px;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .border-top { border-top: 1px dashed #000; padding-top: 5px; }
        .border-bottom { border-bottom: 1px dashed #000; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th, td { padding: 3px 0; }
        .btn-print {
            display: block;
            width: 100%;
            padding: 8px;
            background: #208B7A;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        @media print {
            .no-print { display: none; }
            body { width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="javascript:window.print()" class="btn-print">Cetak Struk</a>
        <a href="{{ route('kasir.index') }}" style="display:block; text-align:center; font-size:11px; margin-bottom:10px; color:#555;">Kembali ke Kasir</a>
    </div>

    <div class="text-center border-bottom">
        <h3 style="margin: 0;">{{ $profil->nama_apotek }}</h3>
        <p style="margin: 2px 0;">{{ $profil->alamat }}</p>
    </div>

    <div style="margin: 8px 0;">
        <div>No. Nota : {{ $transaksi->no_transaksi }}</div>
        <div>Waktu    : {{ $transaksi->created_at->format('d/m/Y H:i') }}</div>
        <div>Kasir    : {{ $transaksi->user->nama ?? 'Kasir' }}</div>
    </div>

    <table class="border-top border-bottom">
        @foreach($transaksi->details as $item)
        <tr>
            <td colspan="2"><strong>{{ $item->obat->nama_obat ?? 'Produk' }}</strong></td>
        </tr>
        <tr>
            <td>{{ $item->qty }} x Rp{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
            <td class="text-right">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>

    <table>
        <tr>
            <td>Total</td>
            <td class="text-right">Rp{{ number_format($transaksi->total_harga, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Bayar</td>
            <td class="text-right">Rp{{ number_format($transaksi->total_bayar, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="text-right">Rp{{ number_format($transaksi->kembalian, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="text-center border-top" style="margin-top: 10px;">
        <p style="margin: 5px 0;">Terima Kasih</p>
        <p style="margin: 0; font-size: 10px;">Semoga Lekas Sembuh</p>
    </div>
</body>
</html>
