<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use App\Services\StokOpnameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StokOpnameController extends Controller
{
    public function index()
    {
        $daftar = StokOpname::with('user')->withCount('details')->latest('id')->paginate(10);
        $draft  = StokOpname::where('status', StokOpname::STATUS_DRAFT)->first();

        return view('opname.index', compact('daftar', 'draft'));
    }

    public function store(Request $request)
    {
        $opname = StokOpnameService::mulai(Auth::id(), $request->input('catatan'));

        return redirect()->route('stok-opname.show', $opname->id);
    }

    public function show(StokOpname $stokOpname)
    {
        $details = $stokOpname->details()->with('obat.kategori')->get()
            ->sortBy(fn ($d) => $d->obat->nama_obat)->values();
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('opname.show', compact('stokOpname', 'details', 'kategoris'));
    }

    /** AJAX: simpan satu baris (otomatis saat petugas mengetik). */
    public function updateDetail(Request $request, StokOpname $stokOpname, StokOpnameDetail $detail)
    {
        abort_if($detail->stok_opname_id !== $stokOpname->id, 404);

        $data = $request->validate([
            'stok_fisik' => 'nullable|integer|min:0',
            'jml_rusak'  => 'nullable|integer|min:0',
            'jml_hilang' => 'nullable|integer|min:0',
            'catatan'    => 'nullable|string|max:255',
        ]);

        StokOpnameService::simpanDetail($stokOpname, $detail, $data);

        return response()->json(['ok' => true]);
    }

    public function finalisasi(StokOpname $stokOpname)
    {
        StokOpnameService::finalisasi($stokOpname, Auth::id());

        return redirect()->route('stok-opname.show', $stokOpname->id)
            ->with('success', 'Stok opname difinalisasi. Stok aktif sudah disesuaikan dengan stok fisik.');
    }

    /** AJAX: segarkan snapshot stok_sistem dengan stok terkini (sebelum finalisasi). */
    public function refreshStokSistem(StokOpname $stokOpname)
    {
        abort_unless($stokOpname->isDraft(), 403, 'Hanya opname draft yang bisa disegarkan.');

        $berubah = StokOpnameService::segarkanStokSistem($stokOpname);

        if ($berubah->isEmpty()) {
            return response()->json(['ok' => true, 'message' => 'Stok sistem sudah sama dengan snapshot, tidak ada perubahan.']);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Stok sistem disegarkan untuk ' . $berubah->count() . ' obat.',
            'berubah' => $berubah,
        ]);
    }
}