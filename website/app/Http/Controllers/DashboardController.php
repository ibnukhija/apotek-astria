<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\KondisiStokObat;
use App\Models\Obat;
use App\Models\ProfilApotek;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $hariIni = Carbon::today();
        $batasHampirExpired = (clone $hariIni)->addDays(Obat::BATAS_HAMPIR_KADALUARSA);

        // 1. Ringkasan Kasir / Penjualan Hari Ini
        $penjualanHariIni = (int) Transaksi::whereDate('created_at', $hariIni)->sum('total_harga');
        $transaksiHariIni = Transaksi::whereDate('created_at', $hariIni)->count();
        $itemTerjualHariIni = (int) DB::table('detail_transaksi')
            ->join('transaksi', 'transaksi.id', '=', 'detail_transaksi.transaksi_id')
            ->whereDate('transaksi.created_at', $hariIni)
            ->sum('detail_transaksi.qty');

        // 2. Ringkasan Stok Produk (Aktif)
        $obatAktif = Obat::where('status', 'tersedia');
        $totalProduk = (clone $obatAktif)->count();
        $totalKategori = Kategori::count();
        $stokCukup = (clone $obatAktif)->where('stok', '>=', Obat::BATAS_CUKUP)->count();
        $stokMenipis = (clone $obatAktif)
            ->where('stok', '>=', Obat::BATAS_HAMPIR_HABIS)
            ->where('stok', '<', Obat::BATAS_CUKUP)
            ->count();
        $stokHampirHabis = (clone $obatAktif)->where('stok', '<', Obat::BATAS_HAMPIR_HABIS)->count();

        // 3. Peringatan Kadaluarsa & Rusak (obat tanpa stok tidak dihitung kadaluarsa)
        $hampirExpired = (clone $obatAktif)
            ->where('stok', '>', 0)
            ->whereNotNull('tgl_kadaluarsa')
            ->whereDate('tgl_kadaluarsa', '>=', $hariIni)
            ->whereDate('tgl_kadaluarsa', '<=', $batasHampirExpired)
            ->count();
        $sudahExpired = (clone $obatAktif)
            ->where('stok', '>', 0)
            ->whereNotNull('tgl_kadaluarsa')
            ->whereDate('tgl_kadaluarsa', '<', $hariIni)
            ->count();
        $unitRusak = (int) KondisiStokObat::where('jenis', KondisiStokObat::JENIS_RUSAK)->sum('jumlah');

        // 4. Data Widget: 5 Transaksi Terakhir
        $transaksiTerbaru = Transaksi::with('user')
            ->latest('id')
            ->limit(5)
            ->get();

        // 5. Data Widget: 5 Obat Kritis (Stok < 10 atau expired mendekati)
        $obatKritis = Obat::where('status', 'tersedia')
            ->where(function ($q) use ($hariIni, $batasHampirExpired) {
                $q->where('stok', '<', Obat::BATAS_HAMPIR_HABIS)
                  ->orWhere(function ($exp) use ($hariIni, $batasHampirExpired) {
                      $exp->where('stok', '>', 0)
                          ->whereNotNull('tgl_kadaluarsa')
                          ->whereDate('tgl_kadaluarsa', '<=', $batasHampirExpired);
                  });
            })
            ->orderBy('stok', 'asc')
            ->limit(5)
            ->get();

        $statusApotek = ProfilApotek::ambilAtauDefault()->statusSaatIni();

        return view('dashboard.index', compact(
            'penjualanHariIni',
            'transaksiHariIni',
            'itemTerjualHariIni',
            'totalProduk',
            'totalKategori',
            'stokCukup',
            'stokMenipis',
            'stokHampirHabis',
            'hampirExpired',
            'sudahExpired',
            'unitRusak',
            'transaksiTerbaru',
            'obatKritis',
            'statusApotek'
        ));
    }
}