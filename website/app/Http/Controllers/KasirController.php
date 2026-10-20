<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Obat;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\ProfilApotek;
use App\Models\User;
use App\Services\StokService;
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
            'total_bayar'     => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $request) {
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

            $totalBayar = (int) $data['total_bayar'];
            if ($totalBayar < $totalHarga) {
                throw ValidationException::withMessages([
                    'total_bayar' => ['Nominal uang yang diterima kurang dari total harga belanja.'],
                ]);
            }

            $kembalian = $totalBayar - $totalHarga;

            $transaksi = Transaksi::create([
                'no_transaksi' => 'TRX-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
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

        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->tanggal);
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

        $ringkasan = [
            'total_transaksi' => (clone $query)->count(),
            'total_omset'     => (clone $query)->sum('total_harga'),
            'total_item'      => (int) DB::table('detail_transaksi')
                ->whereIn('transaksi_id', (clone $query)->reorder()->select('id'))
                ->sum('qty'),
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