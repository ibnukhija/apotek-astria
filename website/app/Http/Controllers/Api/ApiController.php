<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\Transaksi;
use App\Models\ProfilApotek;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    // 1. Untuk BerandaFragment (Dashboard Owner)
    public function dashboard()
    {
        $hariIni = Carbon::today();

        $totalPenjualanHariIni = Transaksi::whereDate('created_at', $hariIni)->sum('total_harga');
        $transaksiHariIni = Transaksi::whereDate('created_at', $hariIni)->count();
        
        // Obat yang stoknya kurang dari atau sama dengan 10 (menipis)
        $obatMenipis = Obat::where('stok', '<=', 10)->where('status', 'tersedia')->get();

        // 5 transaksi terbaru
        $transaksiTerbaru = Transaksi::with('user')->latest()->limit(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_penjualan' => (int) $totalPenjualanHariIni,
                'jumlah_transaksi' => $transaksiHariIni,
                'peringatan_obat' => $obatMenipis,
                'transaksi_terbaru' => $transaksiTerbaru
            ]
        ]);
    }

    // 2. Untuk StokFragment (Monitoring & Penyesuaian Cepat)
    public function stok(Request $request)
    {
        $query = Obat::with('kategori');

        if ($request->filled('search')) {
            $query->where('nama_obat', 'like', '%' . $request->search . '%')
                  ->orWhere('kode_obat', 'like', '%' . $request->search . '%');
        }

        $obats = $query->get()->map(function ($obat) {
            $obat->status_stok = $obat->stok <= 10 ? 'Hampir habis' : 'Cukup';
            return $obat;
        });

        return response()->json([
            'success' => true,
            'data' => $obats
        ]);
    }

    // Penyesuaian harga jual dan status ketersediaan obat dari Mobile
    public function updateObatCepat(Request $request, $id)
    {
        $request->validate([
            'harga_jual' => 'required|numeric',
            'status'     => 'required|in:tersedia,tidak'
        ]);

        $obat = Obat::findOrFail($id);
        $obat->update([
            'harga_jual' => $request->harga_jual,
            'status'     => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data obat berhasil diperbarui',
            'data'    => $obat
        ]);
    }

    // 3. Untuk RiwayatFragment (Laporan Transaksi Kasir)
    public function riwayat(Request $request)
    {
        $query = Transaksi::with(['user', 'details.obat'])->latest();

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->tanggal_mulai)->startOfDay(),
                Carbon::parse($request->tanggal_selesai)->endOfDay()
            ]);
        }

        $riwayat = $query->get();

        return response()->json([
            'success' => true,
            'data' => $riwayat
        ]);
    }

    // 4. Untuk ProfilFragment (Informasi Profil & Koordinat Apotek)
    public function profil()
    {
        $profil = ProfilApotek::first() ?? [
            'nama_apotek' => 'Apotek Astria 2 Farma',
            'alamat' => 'Jl. Mastrip No.79A, Sukorame, Kec. Mojoroto, Kota Kediri',
            'latitude' => -7.8166,
            'longitude' => 112.0116
        ];

        return response()->json([
            'success' => true,
            'data' => $profil
        ]);
    }
}
