<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokOpname;

class StokOpnameApiController extends Controller
{
    public function index()
    {
        $data = StokOpname::with('user:id,nama')->withCount('details')
            ->where('status', StokOpname::STATUS_FINAL)
            ->latest('id')->limit(24)->get()
            ->map(fn ($o) => [
                'id'           => $o->id,
                'kode'         => $o->kode,
                'periode'      => $o->periode,
                'tanggal'      => $o->tanggal?->format('Y-m-d'),
                'petugas'      => $o->user->nama ?? 'Karyawan',
                'jumlah_obat'  => $o->details_count,
                'finalized_at' => $o->finalized_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $data]);
    }

    public function show(StokOpname $stokOpname)
    {
        $rows = $stokOpname->details()->with('obat:id,kode_obat,nama_obat,satuan')->get()
            ->filter(fn ($d) => (int) $d->stok_fisik !== (int) $d->stok_sistem)
            ->map(fn ($d) => [
                'kode_obat'   => $d->obat->kode_obat,
                'nama_obat'   => $d->obat->nama_obat,
                'satuan'      => $d->obat->satuan,
                'stok_sistem' => (int) $d->stok_sistem,
                'stok_fisik'  => (int) $d->stok_fisik,
                'selisih'     => (int) $d->selisih,
                'rusak'       => (int) $d->jml_rusak,
                'hilang'      => (int) $d->jml_hilang,
                'catatan'     => $d->catatan,
            ])->values();

        return response()->json([
            'data' => [
                'kode'      => $stokOpname->kode,
                'periode'   => $stokOpname->periode,
                'status'    => $stokOpname->status,
                'ringkasan' => [
                    'obat_selisih' => $rows->count(),
                    'unit_kurang'  => (int) $rows->sum(fn ($r) => max(0, -$r['selisih'])),
                    'unit_lebih'   => (int) $rows->sum(fn ($r) => max(0, $r['selisih'])),
                    'rusak'        => (int) $rows->sum('rusak'),
                    'hilang'       => (int) $rows->sum('hilang'),
                ],
                'selisih' => $rows,
            ],
        ]);
    }
}