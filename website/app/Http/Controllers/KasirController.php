<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Obat;
use App\Services\StokService;
use Illuminate\Http\Request;

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

    public function bayar(Request $request)
    {
        $data = $request->validate([
            'items'           => 'required|array|min:1',
            'items.*.obat_id' => 'required|integer|exists:obat,id',
            'items.*.jumlah'  => 'required|integer|min:1',
        ]);

        StokService::kurangi($data['items']);

        return response()->json([
            'success' => true,
            'message' => 'Stok berhasil diperbarui.',
        ]);
    }
}