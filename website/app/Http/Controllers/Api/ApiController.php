<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Obat;
use App\Models\Transaksi;
use App\Models\ProfilApotek;
use App\Services\LaporanExcelService;
use App\Services\LaporanPdfService;
use App\Services\LaporanPenjualanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    // 1. Untuk BerandaFragment (Dashboard Owner)
    public function dashboard(Request $request)
    {
        $request->validate([
            'periode'        => 'nullable|in:harian,mingguan,bulanan,keseluruhan',
            'periode_grafik' => 'nullable|in:harian,mingguan,bulanan,tahunan',
        ]);

        $periode = $request->input('periode', 'harian');
        $defaultGrafik = in_array($periode, ['harian', 'mingguan', 'bulanan'], true) ? $periode : 'harian';
        $periodeGrafik = $request->input('periode_grafik', $defaultGrafik);
        $hariIni = Carbon::today();

        $transaksiQuery = Transaksi::query();
        $this->terapkanFilterPeriode($transaksiQuery, $periode, $hariIni);

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

        $grafikPenjualan = $this->hitungGrafikPenjualan($periodeGrafik, $hariIni);
        $obatTerlaris = $this->hitungObatTerlaris();

        // 5 transaksi terbaru (disertakan untuk kompatibilitas API)
        $transaksiTerbaru = Transaksi::with('user')->latest()->orderByDesc('id')->limit(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'periode'               => $periode,
                'periode_grafik'        => $periodeGrafik,
                'total_penjualan'       => (int) $totalPenjualan,
                'jumlah_transaksi'      => $jumlahTransaksi,
                'peringatan_obat'       => $obatMenipis,
                'peringatan_kadaluarsa' => $obatKadaluarsa,
                'grafik_penjualan'      => $grafikPenjualan,
                'obat_terlaris'         => $obatTerlaris,
                'transaksi_terbaru'     => $transaksiTerbaru,
            ]
        ]);
    }

    /**
     * Terapkan filter rentang waktu (harian/mingguan/bulanan/keseluruhan) pada query transaksi.
     */
    private function terapkanFilterPeriode($query, string $periode, Carbon $hariIni, string $kolom = 'created_at'): void
    {
        if ($periode === 'mingguan') {
            $query->whereBetween($kolom, [
                $hariIni->copy()->startOfWeek(),
                $hariIni->copy()->endOfDay(),
            ]);
        } elseif ($periode === 'bulanan') {
            $query->whereBetween($kolom, [
                $hariIni->copy()->startOfMonth(),
                $hariIni->copy()->endOfDay(),
            ]);
        } elseif ($periode === 'harian') {
            $query->whereDate($kolom, $hariIni);
        }
    }

    /**
     * Top 5 obat paling laku berdasarkan total unit terjual keseluruhan.
     */
    private function hitungObatTerlaris(): array
    {
        return DB::table('detail_transaksi')
            ->join('obat', 'obat.id', '=', 'detail_transaksi.obat_id')
            ->select(
                'obat.id as obat_id',
                'obat.kode_obat',
                'obat.nama_obat',
                'obat.satuan',
                DB::raw('SUM(detail_transaksi.qty) as total_terjual'),
                DB::raw('SUM(detail_transaksi.subtotal) as total_pendapatan')
            )
            ->groupBy('obat.id', 'obat.kode_obat', 'obat.nama_obat', 'obat.satuan')
            ->orderByDesc('total_terjual')
            ->orderByDesc('total_pendapatan')
            ->orderBy('obat.nama_obat')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'obat_id'          => (int) $row->obat_id,
                'kode_obat'        => $row->kode_obat,
                'nama_obat'        => $row->nama_obat,
                'satuan'           => $row->satuan,
                'total_terjual'    => (int) $row->total_terjual,
                'total_pendapatan' => (int) $row->total_pendapatan,
            ])
            ->values()
            ->all();
    }

    /**
     * Susun titik grafik penjualan untuk rentang harian (7 hari), mingguan (6 minggu),
     * bulanan (Jan-Des tahun berjalan), atau tahunan (4 tahun terakhir) secara konsisten di MySQL maupun SQLite.
     */
    private function hitungGrafikPenjualan(string $periodeGrafik, Carbon $hariIni): array
    {
        $namaBulan = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $buckets = [];
        if ($periodeGrafik === 'mingguan') {
            $awalRentang = $hariIni->copy()->startOfWeek()->subWeeks(5);
            for ($i = 0; $i < 6; $i++) {
                $awalMinggu = $awalRentang->copy()->addWeeks($i);
                $akhirMinggu = $awalMinggu->copy()->endOfWeek();
                $key = $awalMinggu->format('Y-m-d');
                $buckets[$key] = [
                    'label'            => $awalMinggu->format('d/m'),
                    'keterangan'       => $awalMinggu->format('d/m') . ' - ' . $akhirMinggu->format('d/m/Y'),
                    'total'            => 0,
                    'jumlah_transaksi' => 0,
                ];
            }
            $resolusiKey = fn (Carbon $tgl): string => $tgl->copy()->startOfWeek()->format('Y-m-d');
        } elseif ($periodeGrafik === 'bulanan') {
            $tahun = $hariIni->year;
            $awalRentang = Carbon::create($tahun, 1, 1)->startOfDay();
            for ($bulanKe = 1; $bulanKe <= 12; $bulanKe++) {
                $key = sprintf('%04d-%02d', $tahun, $bulanKe);
                $singkatan = $namaBulan[$bulanKe];
                $buckets[$key] = [
                    'label'            => $singkatan,
                    'keterangan'       => $singkatan . ' ' . $tahun,
                    'total'            => 0,
                    'jumlah_transaksi' => 0,
                ];
            }
            $resolusiKey = fn (Carbon $tgl): string => $tgl->format('Y-m');
        } elseif ($periodeGrafik === 'tahunan') {
            $tahunAkhir = $hariIni->year;
            $tahunMulai = $tahunAkhir - 3;
            $awalRentang = Carbon::create($tahunMulai, 1, 1)->startOfDay();
            for ($tahunKe = $tahunMulai; $tahunKe <= $tahunAkhir; $tahunKe++) {
                $key = (string) $tahunKe;
                $buckets[$key] = [
                    'label'            => $key,
                    'keterangan'       => 'Tahun ' . $key,
                    'total'            => 0,
                    'jumlah_transaksi' => 0,
                ];
            }
            $resolusiKey = fn (Carbon $tgl): string => $tgl->format('Y');
        } else {
            $awalRentang = $hariIni->copy()->subDays(6)->startOfDay();
            for ($i = 0; $i < 7; $i++) {
                $tgl = $awalRentang->copy()->addDays($i);
                $key = $tgl->format('Y-m-d');
                $buckets[$key] = [
                    'label'            => $tgl->format('d/m'),
                    'keterangan'       => $tgl->format('d') . ' ' . $namaBulan[(int) $tgl->month] . ' ' . $tgl->format('Y'),
                    'total'            => 0,
                    'jumlah_transaksi' => 0,
                ];
            }
            $resolusiKey = fn (Carbon $tgl): string => $tgl->format('Y-m-d');
        }

        $baris = DB::table('transaksi')
            ->whereBetween('created_at', [$awalRentang, $hariIni->copy()->endOfDay()])
            ->get(['created_at', 'total_harga']);

        foreach ($baris as $trx) {
            $key = $resolusiKey(Carbon::parse($trx->created_at));
            if (isset($buckets[$key])) {
                $buckets[$key]['total'] += (int) $trx->total_harga;
                $buckets[$key]['jumlah_transaksi']++;
            }
        }

        return array_values($buckets);
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

        if ($request->filled('kategori')) {
            $kategoriNama = trim($request->kategori);
            $query->whereHas('kategori', function ($q) use ($kategoriNama) {
                $q->where('nama_kategori', $kategoriNama);
            });
        }

        $ringkasanQuery = clone $query;
        $hampirHabisTotal = (clone $ringkasanQuery)
            ->where('stok', '<', Obat::BATAS_HAMPIR_HABIS)
            ->count();
        $batasKadaluarsa = Carbon::today()->addDays(Obat::BATAS_HAMPIR_KADALUARSA);
        $perhatianKadaluarsaTotal = (clone $ringkasanQuery)
            ->whereNotNull('tgl_kadaluarsa')
            ->whereDate('tgl_kadaluarsa', '<=', $batasKadaluarsa)
            ->count();

        $paginated = $query->orderBy('nama_obat')->orderBy('id')->paginate(50);

        $obats = collect($paginated->items())->map(function ($obat) {
            $obat->status_stok = Obat::infoStok((int) $obat->stok)['label'];
            $obat->status_kadaluarsa = Obat::infoKadaluarsa($obat->tgl_kadaluarsa)['label'];
            return $obat;
        })->values();

        return response()->json([
            'success'                    => true,
            'data'                       => $obats,
            'current_page'               => $paginated->currentPage(),
            'last_page'                  => $paginated->lastPage(),
            'total'                      => $paginated->total(),
            'hampir_habis_total'         => $hampirHabisTotal,
            'perhatian_kadaluarsa_total' => $perhatianKadaluarsaTotal,
            'kategoris'                  => Kategori::orderBy('nama_kategori')->pluck('nama_kategori'),
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

    // 3. Untuk RiwayatFragment (Daftar Transaksi Kasir)
    public function riwayat(Request $request)
    {
        $request->validate([
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date' . ($request->filled('tanggal_mulai') ? '|after_or_equal:tanggal_mulai' : ''),
            'metode_bayar'    => 'nullable|in:cash,qris',
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

        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar', $request->metode_bayar);
        }

        $riwayat = $query->paginate(50);

        $data = collect($riwayat->items())->map(function (Transaksi $trx) {
            $trx->metode_bayar = $trx->metode_bayar === Transaksi::METODE_QRIS
                ? Transaksi::METODE_QRIS
                : Transaksi::METODE_CASH;
            $trx->metode_bayar_label = $trx->metode_bayar_label;
            return $trx;
        })->values();

        return response()->json([
            'success'      => true,
            'data'         => $data,
            'current_page' => $riwayat->currentPage(),
            'last_page'    => $riwayat->lastPage(),
            'total'        => $riwayat->total(),
        ]);
    }

    // 3b. Untuk Tab Laporan Penjualan (Filter lengkap & pratinjau ekspor)
    public function laporan(Request $request, LaporanPenjualanService $service)
    {
        $filter = $service->ambilFilter($request);
        $hasil = $service->ambilLaporanPaginasi($filter);

        return response()->json([
            'success'      => true,
            'filter'       => [
                'periode'         => $filter['periode'],
                'periode_label'   => $filter['periode_label'],
                'tanggal_mulai'   => $filter['tanggal_mulai'],
                'tanggal_selesai' => $filter['tanggal_selesai'],
                'kategori'        => $filter['kategori'],
                'nama_obat'       => $filter['nama_obat'],
                'metode_bayar'    => $filter['metode_bayar'],
            ],
            'ringkasan'    => $hasil['ringkasan'],
            'data'         => $hasil['items'],
            'current_page' => $hasil['current_page'],
            'last_page'    => $hasil['last_page'],
            'total'        => $hasil['total'],
            'kategoris'    => $hasil['kategoris'],
            'daftar_obat'  => $hasil['daftar_obat'],
        ]);
    }

    // 3c. Ekspor Laporan Penjualan ke PDF atau Excel (XLSX)
    public function exportLaporan(
        Request $request,
        LaporanPenjualanService $service,
        LaporanExcelService $excel,
        LaporanPdfService $pdf
    ) {
        $request->validate([
            'format' => 'required|in:pdf,excel',
        ]);

        $filter = $service->ambilFilter($request);
        $dataEkspor = $service->ambilDataEkspor($filter);
        $capWaktu = Carbon::now()->format('Ymd_His');

        if ($request->input('format') === 'excel') {
            $konten = $excel->buatXlsx($dataEkspor);
            $namaFile = "Laporan_Penjualan_{$capWaktu}.xlsx";

            return response($konten, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$namaFile}\"",
            ]);
        }

        $konten = $pdf->buatPdf($dataEkspor);
        $namaFile = "Laporan_Penjualan_{$capWaktu}.pdf";

        return response($konten, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$namaFile}\"",
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
