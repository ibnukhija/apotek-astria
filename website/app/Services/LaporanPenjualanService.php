<?php

namespace App\Services;

use App\Models\Kategori;
use App\Models\Obat;
use App\Models\ProfilApotek;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPenjualanService
{
    public const BATAS_EKSPOR_BARIS = 5000;

    /**
     * Validasi dan normalisasi filter laporan penjualan dari request.
     */
    public function ambilFilter(Request $request): array
    {
        $validated = $request->validate([
            'periode'         => 'nullable|in:semua,hari_ini,minggu_ini,bulan_ini,tahun_ini,kustom',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date' . ($request->filled('tanggal_mulai') ? '|after_or_equal:tanggal_mulai' : ''),
            'kategori'        => 'nullable|string|max:100',
            'nama_obat'       => 'nullable|string|max:100',
            'metode_bayar'    => 'nullable|in:cash,qris',
        ]);

        $periode = $validated['periode'] ?? null;
        $tglMulai = $validated['tanggal_mulai'] ?? null;
        $tglSelesai = $validated['tanggal_selesai'] ?? null;

        if ($periode === null) {
            $periode = ($tglMulai || $tglSelesai) ? 'kustom' : 'semua';
        }

        $hariIni = Carbon::today();
        [$awal, $akhir, $labelPeriode] = $this->tentukanRentangTanggal($periode, $tglMulai, $tglSelesai, $hariIni);

        $kategori = trim((string) ($validated['kategori'] ?? ''));
        if (strcasecmp($kategori, 'Semua') === 0 || strcasecmp($kategori, 'Semua Kategori') === 0) {
            $kategori = '';
        }

        return [
            'periode'         => $periode,
            'periode_label'   => $labelPeriode,
            'tanggal_mulai'   => $awal?->format('Y-m-d'),
            'tanggal_selesai' => $akhir?->format('Y-m-d'),
            'awal'            => $awal,
            'akhir'           => $akhir,
            'kategori'        => $kategori,
            'nama_obat'       => trim((string) ($validated['nama_obat'] ?? '')),
            'metode_bayar'    => $validated['metode_bayar'] ?? '',
        ];
    }

    /**
     * Susun data laporan terpaginasi, ringkasan, dan opsi filter untuk API mobile.
     */
    public function ambilLaporanPaginasi(array $filter, int $perHalaman = 50): array
    {
        $baseQuery = $this->buatQueryDasar($filter);
        $ringkasan = $this->hitungRingkasan(clone $baseQuery);

        $paginated = (clone $baseQuery)
            ->select($this->kolomSelect())
            ->orderByDesc('transaksi.created_at')
            ->orderByDesc('detail_transaksi.id')
            ->paginate($perHalaman);

        $items = collect($paginated->items())
            ->map(fn ($row) => $this->formatBaris($row))
            ->values()
            ->all();

        return [
            'items'        => $items,
            'ringkasan'    => $ringkasan,
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'total'        => $paginated->total(),
            'kategoris'    => Kategori::orderBy('nama_kategori')->pluck('nama_kategori')->values()->all(),
            'daftar_obat'  => Obat::orderBy('nama_obat')->pluck('nama_obat')->values()->all(),
        ];
    }

    /**
     * Ambil seluruh baris laporan untuk keperluan ekspor Excel / PDF.
     */
    public function ambilDataEkspor(array $filter): array
    {
        $baseQuery = $this->buatQueryDasar($filter);
        $ringkasan = $this->hitungRingkasan(clone $baseQuery);

        $items = (clone $baseQuery)
            ->select($this->kolomSelect())
            ->orderByDesc('transaksi.created_at')
            ->orderByDesc('detail_transaksi.id')
            ->limit(self::BATAS_EKSPOR_BARIS)
            ->get()
            ->map(fn ($row) => $this->formatBaris($row))
            ->values()
            ->all();

        $profil = ProfilApotek::first();

        return [
            'nama_apotek' => $profil->nama_apotek ?? 'Apotek Astria 2 Farma',
            'alamat'      => $profil->alamat ?? 'Jl. Mastrip No.79A, Sukorame, Kec. Mojoroto, Kota Kediri',
            'dicetak_pada' => Carbon::now()->format('d/m/Y H:i'),
            'filter'      => $filter,
            'ringkasan'   => $ringkasan,
            'items'       => $items,
        ];
    }

    private function buatQueryDasar(array $filter): Builder
    {
        $query = DB::table('detail_transaksi')
            ->join('transaksi', 'transaksi.id', '=', 'detail_transaksi.transaksi_id')
            ->join('obat', 'obat.id', '=', 'detail_transaksi.obat_id')
            ->leftJoin('kategori', 'kategori.id', '=', 'obat.kategori_id')
            ->leftJoin('users', 'users.id', '=', 'transaksi.user_id');

        if ($filter['awal'] && $filter['akhir']) {
            $query->whereBetween('transaksi.created_at', [$filter['awal'], $filter['akhir']]);
        } elseif ($filter['awal']) {
            $query->where('transaksi.created_at', '>=', $filter['awal']);
        } elseif ($filter['akhir']) {
            $query->where('transaksi.created_at', '<=', $filter['akhir']);
        }

        if ($filter['kategori'] !== '') {
            $query->where('kategori.nama_kategori', $filter['kategori']);
        }

        if ($filter['nama_obat'] !== '') {
            $kata = $filter['nama_obat'];
            $query->where(function ($q) use ($kata) {
                $q->where('obat.nama_obat', 'like', "%{$kata}%")
                  ->orWhere('obat.kode_obat', 'like', "%{$kata}%");
            });
        }

        if ($filter['metode_bayar'] !== '') {
            $query->where('transaksi.metode_bayar', $filter['metode_bayar']);
        }

        return $query;
    }

    private function kolomSelect(): array
    {
        return [
            'detail_transaksi.id as detail_id',
            'transaksi.id as transaksi_id',
            'transaksi.no_transaksi',
            'transaksi.created_at',
            'transaksi.metode_bayar',
            'users.nama as nama_kasir',
            'obat.kode_obat',
            'obat.nama_obat',
            'obat.satuan',
            'kategori.nama_kategori',
            'detail_transaksi.qty',
            'detail_transaksi.harga_satuan',
            'detail_transaksi.subtotal',
        ];
    }

    private function formatBaris(object $row): array
    {
        $metode = ($row->metode_bayar ?? Transaksi::METODE_CASH) === Transaksi::METODE_QRIS
            ? Transaksi::METODE_QRIS
            : Transaksi::METODE_CASH;

        return [
            'detail_id'          => (int) $row->detail_id,
            'transaksi_id'       => (int) $row->transaksi_id,
            'no_transaksi'       => $row->no_transaksi,
            'tanggal'            => Carbon::parse($row->created_at)->format('d/m/Y H:i'),
            'created_at'         => (string) $row->created_at,
            'nama_kasir'         => $row->nama_kasir ?: 'Kasir',
            'metode_bayar'       => $metode,
            'metode_bayar_label' => $metode === Transaksi::METODE_QRIS ? 'QRIS' : 'Cash',
            'kode_obat'          => $row->kode_obat,
            'nama_obat'          => $row->nama_obat,
            'nama_kategori'      => $row->nama_kategori ?: '-',
            'satuan'             => $row->satuan ?: '-',
            'qty'                => (int) $row->qty,
            'harga_satuan'       => (int) $row->harga_satuan,
            'subtotal'           => (int) $row->subtotal,
        ];
    }

    private function hitungRingkasan(Builder $query): array
    {
        $agg = $query->selectRaw("
            COALESCE(SUM(detail_transaksi.subtotal), 0) as total_pendapatan,
            COALESCE(SUM(detail_transaksi.qty), 0) as total_qty,
            COUNT(DISTINCT transaksi.id) as jumlah_transaksi,
            COUNT(detail_transaksi.id) as jumlah_item,
            COALESCE(SUM(CASE WHEN COALESCE(transaksi.metode_bayar, 'cash') = 'cash' THEN detail_transaksi.subtotal ELSE 0 END), 0) as total_cash,
            COALESCE(SUM(CASE WHEN transaksi.metode_bayar = 'qris' THEN detail_transaksi.subtotal ELSE 0 END), 0) as total_qris
        ")->first();

        return [
            'total_pendapatan' => (int) ($agg->total_pendapatan ?? 0),
            'total_qty'        => (int) ($agg->total_qty ?? 0),
            'jumlah_transaksi' => (int) ($agg->jumlah_transaksi ?? 0),
            'jumlah_item'      => (int) ($agg->jumlah_item ?? 0),
            'total_cash'       => (int) ($agg->total_cash ?? 0),
            'total_qris'       => (int) ($agg->total_qris ?? 0),
        ];
    }

    private function tentukanRentangTanggal(string $periode, ?string $tglMulai, ?string $tglSelesai, Carbon $hariIni): array
    {
        if ($periode === 'hari_ini') {
            return [$hariIni->copy()->startOfDay(), $hariIni->copy()->endOfDay(), 'Hari Ini (' . $hariIni->format('d/m/Y') . ')'];
        }
        if ($periode === 'minggu_ini') {
            $awal = $hariIni->copy()->startOfWeek();
            $akhir = $hariIni->copy()->endOfDay();
            return [$awal, $akhir, 'Minggu Ini (' . $awal->format('d/m') . ' - ' . $akhir->format('d/m/Y') . ')'];
        }
        if ($periode === 'bulan_ini') {
            $awal = $hariIni->copy()->startOfMonth();
            $akhir = $hariIni->copy()->endOfDay();
            return [$awal, $akhir, 'Bulan Ini (' . $awal->format('d/m') . ' - ' . $akhir->format('d/m/Y') . ')'];
        }
        if ($periode === 'tahun_ini') {
            $awal = $hariIni->copy()->startOfYear();
            $akhir = $hariIni->copy()->endOfDay();
            return [$awal, $akhir, 'Tahun ' . $hariIni->year];
        }
        if ($periode === 'kustom' || $tglMulai || $tglSelesai) {
            $awal = $tglMulai ? Carbon::parse($tglMulai)->startOfDay() : null;
            $akhir = $tglSelesai ? Carbon::parse($tglSelesai)->endOfDay() : null;
            $label = match (true) {
                $awal && $akhir => $awal->format('d/m/Y') . ' - ' . $akhir->format('d/m/Y'),
                (bool) $awal    => 'Mulai ' . $awal->format('d/m/Y'),
                (bool) $akhir   => 'Sampai ' . $akhir->format('d/m/Y'),
                default         => 'Semua Periode',
            };
            return [$awal, $akhir, $label];
        }

        return [null, null, 'Semua Periode'];
    }
}
