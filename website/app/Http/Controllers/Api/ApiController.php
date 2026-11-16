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
    public function dashboard(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|in:harian,mingguan,bulanan',
        ]);

        $periode = $request->input('periode', 'harian');
        $hariIni = Carbon::today();

        $transaksiQuery = Transaksi::query();
        if ($periode === 'mingguan') {
            $transaksiQuery->whereBetween('created_at', [
                $hariIni->copy()->startOfWeek(),
                $hariIni->copy()->endOfDay(),
            ]);
        } elseif ($periode === 'bulanan') {
            $transaksiQuery->whereBetween('created_at', [
                $hariIni->copy()->startOfMonth(),
                $hariIni->copy()->endOfDay(),
            ]);
        } else {
            $transaksiQuery->whereDate('created_at', $hariIni);
        }

        $totalPenjualan = $transaksiQuery->sum('total_harga');
        $jumlahTransaksi = $transaksiQuery->count();

        // Obat tersedia yang stoknya di bawah batas hampir habis (< 10)
        $obatMenipis = Obat::where('stok', '<', Obat::BATAS_HAMPIR_HABIS)
            ->where('status', 'tersedia')
            ->orderBy('stok')
            ->orderBy('nama_obat')
            ->get();

        // Obat tersedia yang sudah kadaluarsa atau hampir kadaluarsa (<= 30 hari)
        $batasKadaluarsa = $hariIni->copy()->addDays(Obat::BATAS_HAMPIR_KADALUARSA);
        $obatKadaluarsa = Obat::whereNotNull('tgl_kadaluarsa')
            ->whereDate('tgl_kadaluarsa', '<=', $batasKadaluarsa)
            ->where('status', 'tersedia')
            ->orderBy('tgl_kadaluarsa')
            ->orderBy('nama_obat')
            ->get()
            ->map(function ($obat) {
                $obat->status_kadaluarsa = Obat::infoKadaluarsa($obat->tgl_kadaluarsa)['label'];
                return $obat;
            });

        // 5 transaksi terbaru
        $transaksiTerbaru = Transaksi::with('user')->latest()->orderByDesc('id')->limit(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'periode'               => $periode,
                'total_penjualan'       => (int) $totalPenjualan,
                'jumlah_transaksi'      => $jumlahTransaksi,
                'peringatan_obat'       => $obatMenipis,
                'peringatan_kadaluarsa' => $obatKadaluarsa,
                'transaksi_terbaru'     => $transaksiTerbaru,
            ]
        ]);
    }

    // 2. Untuk StokFragment (Monitoring & Penyesuaian Cepat)
    public function stok(Request $request)
    {
        $query = Obat::with('kategori');

        if ($request->filled('search')) {
            $kata = trim($request->search);
            $query->where(function ($q) use ($kata) {
                $q->where('nama_obat', 'like', "%{$kata}%")
                  ->orWhere('kode_obat', 'like', "%{$kata}%");
            });
        }

        $obats = $query->orderBy('nama_obat')->get()->map(function ($obat) {
            $obat->status_stok = Obat::infoStok((int) $obat->stok)['label'];
            $obat->status_kadaluarsa = Obat::infoKadaluarsa($obat->tgl_kadaluarsa)['label'];
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
        $data = $request->validate([
            'harga_jual' => 'required|integer|min:0',
            'status'     => 'required|in:tersedia,tidak'
        ]);

        $obat = Obat::findOrFail($id);
        $hargaJual = (int) $data['harga_jual'];

        $payload = [
            'harga_jual' => $hargaJual,
            'status'     => $data['status'],
        ];

        // Sinkronkan margin ke nominal agar tetap konsisten dengan hitungan harga di web
        if ($hargaJual !== (int) $obat->harga_jual) {
            $payload['tipe_margin'] = 'nominal';
            $payload['margin']      = max(0, $hargaJual - (int) $obat->harga_beli);
        }

        $obat->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Data obat berhasil diperbarui',
            'data'    => $obat->fresh(),
        ]);
    }

    // 3. Untuk RiwayatFragment (Laporan Transaksi Kasir)
    public function riwayat(Request $request)
    {
        $request->validate([
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date' . ($request->filled('tanggal_mulai') ? '|after_or_equal:tanggal_mulai' : ''),
        ]);

        $query = Transaksi::with(['user', 'details.obat'])->latest()->orderByDesc('id');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->tanggal_mulai)->startOfDay(),
                Carbon::parse($request->tanggal_selesai)->endOfDay(),
            ]);
        } elseif ($request->filled('tanggal_mulai')) {
            $query->where('created_at', '>=', Carbon::parse($request->tanggal_mulai)->startOfDay());
        } elseif ($request->filled('tanggal_selesai')) {
            $query->where('created_at', '<=', Carbon::parse($request->tanggal_selesai)->endOfDay());
        }

        $riwayat = $query->paginate(100);

        return response()->json([
            'success'      => true,
            'data'         => $riwayat->items(),
            'current_page' => $riwayat->currentPage(),
            'last_page'    => $riwayat->lastPage(),
            'total'        => $riwayat->total(),
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
