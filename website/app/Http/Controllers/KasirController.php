<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Obat;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\ProfilApotek;
use App\Models\User;
use App\Services\StokService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KasirController extends Controller
{
    public function index(Request $request)
    {
        $obats = Obat::with('kategori')
            ->withExists('batches')
            ->withSum(['batches as stok_layak_jual' => fn ($q) => $q->layakJual()], 'stok')
            ->where('status', 'tersedia')
            ->when($request->q, function ($query, $cari) {
                $query->where(function ($w) use ($cari) {
                    $w->where('nama_obat', 'like', "%{$cari}%")
                      ->orWhere('kode_obat', 'like', "%{$cari}%");
                });
            })
            ->when($request->kategori, fn ($query, $id) => $query->where('kategori_id', $id))
            ->orderBy('nama_obat')
            ->paginate(12)
            ->withQueryString();

        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('kasir.kasir', compact('obats', 'kategoris'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items'           => 'required|array|min:1',
            'items.*.obat_id' => 'required|integer|exists:obat,id',
            'items.*.jumlah'  => 'required|integer|min:1',
            'metode_bayar'    => 'nullable|in:cash,qris',
            'total_bayar'     => 'required_unless:metode_bayar,qris|nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            StokService::kurangi($data['items']);

            $totalHarga = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $obat = Obat::findOrFail($item['obat_id']);
                $subtotal = (int) $obat->harga_jual * (int) $item['jumlah'];
                $totalHarga += $subtotal;
                $itemsData[] = [
                    'obat_id'      => $obat->id,
                    'qty'          => (int) $item['jumlah'],
                    'harga_satuan' => (int) $obat->harga_jual,
                    'subtotal'     => $subtotal,
                ];
            }

            $metodeBayar = $data['metode_bayar'] ?? Transaksi::METODE_CASH;
            $totalBayar = $metodeBayar === Transaksi::METODE_QRIS
                ? $totalHarga
                : (int) ($data['total_bayar'] ?? 0);

            if ($totalBayar < $totalHarga) {
                throw ValidationException::withMessages([
                    'total_bayar' => ['Nominal uang yang diterima kurang dari total harga belanja.'],
                ]);
            }

            $kembalian = $totalBayar - $totalHarga;

            $transaksi = Transaksi::create([
                'no_transaksi' => 'TRX-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'metode_bayar' => $metodeBayar,
                'user_id'      => Auth::id() ?? (User::first()?->id ?? 1),
                'total_harga'  => $totalHarga,
                'total_bayar'  => $totalBayar,
                'kembalian'    => $kembalian,
            ]);

            foreach ($itemsData as $detail) {
                $transaksi->details()->create($detail);
            }

            return response()->json([
                'success'      => true,
                'message'      => 'Transaksi berhasil disimpan.',
                'transaksi_id' => $transaksi->id,
                'id'           => $transaksi->id,
            ]);
        });
    }

    public function bayar(Request $request)
    {
        return $this->store($request);
    }

    public function riwayat(Request $request)
    {
        $query = Transaksi::with(['user', 'details.obat'])->latest();

        $tanggal = null;
        if ($request->filled('tanggal')) {
            try {
                $tanggal = Carbon::parse($request->tanggal);
                $query->whereDate('created_at', $tanggal);
            } catch (\Throwable) {
                $tanggal = null;
            }
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('no_transaksi', 'like', "%{$q}%")
                  ->orWhereHas('user', function ($u) use ($q) {
                      $u->where('nama', 'like', "%{$q}%");
                  });
            });
        }

        $omsetQuery = clone $query;
        if (! $tanggal) {
            $omsetQuery->whereDate('created_at', Carbon::today());
        }

        $tglLabel = $tanggal?->format('d/m/Y');
        $adaPencarian = $request->filled('q');

        $ringkasan = [
            'total_transaksi' => (clone $query)->count(),
            'label_transaksi' => $tglLabel
                ? "Total Transaksi ({$tglLabel})"
                : ($adaPencarian ? 'Total Transaksi (Hasil Pencarian)' : 'Total Transaksi Keseluruhan'),
            'total_omset'     => $omsetQuery->sum('total_harga'),
            'label_omset'     => $tglLabel
                ? "Penjualan ({$tglLabel})"
                : 'Penjualan Hari Ini',
            'total_item'      => (int) DB::table('detail_transaksi')
                ->whereIn('transaksi_id', (clone $query)->reorder()->select('id'))
                ->sum('qty'),
            'label_item'      => $tglLabel
                ? "Produk Terjual ({$tglLabel})"
                : ($adaPencarian ? 'Produk Terjual (Hasil Pencarian)' : 'Produk Terjual Keseluruhan'),
        ];

        $transaksis = $query->paginate(20)->withQueryString();

        return view('kasir.riwayat', compact('transaksis', 'ringkasan'));
    }

    public function cetakStruk($id)
    {
        $transaksi = Transaksi::with(['user', 'details.obat'])->findOrFail($id);
        $profil = ProfilApotek::first() ?? (object)[
            'nama_apotek' => 'Apotek Astria 2 Farma',
            'alamat' => 'Jl. Mastrip No.79A, Sukorame, Kediri',
        ];

        return view('kasir.struk', compact('transaksi', 'profil'));
    }
}