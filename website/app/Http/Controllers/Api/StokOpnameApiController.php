<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;

class StokOpnameApiController extends Controller
{
    public function index()
    {
        $data = StokOpname::with('user:id,nama')
            ->withCount('details')
            ->where('status', StokOpname::STATUS_FINAL)
            ->latest('id')->limit(24)->get()
            ->map(function ($o) {
                // Hitung ringkasan dari detail (tanpa load semua detail untuk performa)
                $detailAgg = StokOpnameDetail::where('stok_opname_id', $o->id)
                    ->selectRaw('
                        SUM(CASE WHEN stok_fisik IS NOT NULL AND stok_fisik <> stok_sistem THEN 1 ELSE 0 END) as obat_selisih,
                        SUM(CASE WHEN stok_fisik IS NOT NULL AND stok_fisik < stok_sistem THEN stok_sistem - stok_fisik ELSE 0 END) as unit_kurang,
                        SUM(CASE WHEN stok_fisik IS NOT NULL AND stok_fisik > stok_sistem THEN stok_fisik - stok_sistem ELSE 0 END) as unit_lebih,
                        SUM(jml_rusak) as rusak,
                        SUM(jml_hilang) as hilang
                    ')
                    ->first();

                return [
                    'id'            => $o->id,
                    'kode'          => $o->kode,
                    'periode'       => $o->periode,
                    'tanggal'       => $o->tanggal?->format('Y-m-d'),
                    'petugas'       => $o->user->nama ?? 'Karyawan',
                    'jumlah_obat'   => $o->details_count,
                    'finalized_at'  => $o->finalized_at?->toIso8601String(),
                    'ringkasan'     => [
                        'obat_selisih' => (int) ($detailAgg->obat_selisih ?? 0),
                        'unit_kurang'  => (int) ($detailAgg->unit_kurang ?? 0),
                        'unit_lebih'   => (int) ($detailAgg->unit_lebih ?? 0),
                        'rusak'        => (int) ($detailAgg->rusak ?? 0),
                        'hilang'       => (int) ($detailAgg->hilang ?? 0),
                    ],
                ];
            });

        return response()->json(['data' => $data]);
    }

    public function show(StokOpname $stokOpname)
    {
        $rows = $stokOpname->details()->with('obat:id,kode_obat,nama_obat,satuan')->get()
            ->map(fn ($d) => [
                'kode_obat'   => $d->obat->kode_obat,
                'nama_obat'   => $d->obat->nama_obat,
                'satuan'      => $d->obat->satuan,
                'stok_sistem' => (int) $d->stok_sistem,
                'stok_fisik'  => $d->stok_fisik !== null ? (int) $d->stok_fisik : null,
                'selisih'     => $d->stok_fisik !== null ? (int) $d->selisih : null,
                'rusak'       => (int) $d->jml_rusak,
                'hilang'      => (int) $d->jml_hilang,
                'catatan'     => $d->catatan,
                'state'       => $this->hitungState($d),
            ])->values();

        $ringkasan = [
            'total_obat'    => $rows->count(),
            'sudah_dihitung' => $rows->filter(fn ($r) => $r['stok_fisik'] !== null)->count(),
            'obat_selisih'  => $rows->filter(fn ($r) => in_array($r['state'], ['kurang', 'lebih', 'rincian']))->count(),
            'unit_kurang'   => (int) $rows->sum(fn ($r) => max(0, -($r['selisih'] ?? 0))),
            'unit_lebih'    => (int) $rows->sum(fn ($r) => max(0, $r['selisih'] ?? 0)),
            'rusak'         => (int) $rows->sum('rusak'),
            'hilang'        => (int) $rows->sum('hilang'),
            'rincian_belum_cocok' => $rows->filter(fn ($r) => $r['state'] === 'rincian')->count(),
        ];

        return response()->json([
            'data' => [
                'kode'      => $stokOpname->kode,
                'periode'   => $stokOpname->periode,
                'status'    => $stokOpname->status,
                'ringkasan' => $ringkasan,
                'selisih'   => $rows,
            ],
        ]);
    }

    /**
     * State: kosong | sama | kurang | lebih | rincian
     * Logika sama dengan frontend show.blade.php
     */
    private function hitungState(StokOpnameDetail $d): string
    {
        if ($d->stok_fisik === null) {
            return 'kosong';
        }
        $sistem = (int) $d->stok_sistem;
        $fisik  = (int) $d->stok_fisik;
        $selisih = $fisik - $sistem;
        $rusak  = (int) $d->jml_rusak;
        $hilang = (int) $d->jml_hilang;
        $rinci  = $rusak + $hilang;

        if ($selisih === 0) {
            return 'sama';
        } elseif ($selisih > 0) {
            return 'lebih';
        } elseif ($rinci === -$selisih) {
            return 'kurang';
        } else {
            return 'rincian';
        }
    }
}