<?php

namespace App\Http\Controllers;

use App\Models\KondisiStokObat;
use App\Models\Obat;
use App\Services\KondisiStokService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KondisiStokController extends Controller
{
    public function index(Request $request)
    {
        $hariIni = Carbon::today();
        $batasHampirExpired = $hariIni->copy()->addDays(Obat::BATAS_HAMPIR_KADALUARSA);

        $aktif = Obat::where('status', 'tersedia');
        $ringkasan = [
            'stok_menipis'   => (clone $aktif)->where('stok', '<', Obat::BATAS_HAMPIR_HABIS)->count(),
            'hampir_expired' => (clone $aktif)->whereNotNull('tgl_kadaluarsa')
                ->whereDate('tgl_kadaluarsa', '>=', $hariIni)
                ->whereDate('tgl_kadaluarsa', '<=', $batasHampirExpired)
                ->count(),
            'sudah_expired'  => (clone $aktif)->whereNotNull('tgl_kadaluarsa')
                ->whereDate('tgl_kadaluarsa', '<', $hariIni)
                ->count(),
            'unit_rusak'     => (int) KondisiStokObat::where('jenis', KondisiStokObat::JENIS_RUSAK)->sum('jumlah'),
        ];

        $riwayat = KondisiStokObat::with(['obat', 'user'])
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->jenis))
            ->when($request->filled('q'), function ($q) use ($request) {
                $kata = trim($request->q);
                $q->where(function ($sub) use ($kata) {
                    $sub->where('penyebab', 'like', "%{$kata}%")
                        ->orWhere('catatan', 'like', "%{$kata}%")
                        ->orWhereHas('obat', function ($obatQ) use ($kata) {
                            $obatQ->where('nama_obat', 'like', "%{$kata}%")
                                ->orWhere('kode_obat', 'like', "%{$kata}%");
                        });
                });
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $obats = Obat::orderBy('nama_obat')
            ->get(['id', 'kode_obat', 'nama_obat', 'stok', 'satuan', 'tgl_kadaluarsa', 'status'])
            ->map(function (Obat $o) {
                $info = $o->kadaluarsa_info;
                return [
                    'id'                => $o->id,
                    'kode_obat'         => $o->kode_obat,
                    'nama_obat'         => $o->nama_obat,
                    'stok'              => (int) $o->stok,
                    'satuan'            => $o->satuan,
                    'tgl_kadaluarsa'    => $o->tgl_kadaluarsa?->format('d/m/Y') ?? '-',
                    'status_kadaluarsa' => $info['label'],
                    'kelas_kadaluarsa'  => $info['kelas'],
                ];
            });

        return view('kondisi.index', compact('ringkasan', 'riwayat', 'obats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'obat_id'    => 'required|exists:obat,id',
            'jenis'      => 'required|in:rusak,retur_supplier,musnah_expired,stok_opname',
            'jumlah'     => 'required_unless:jenis,stok_opname|nullable|integer|min:1',
            'stok_fisik' => 'required_if:jenis,stok_opname|nullable|integer|min:0',
            'penyebab'   => 'required|string|max:255',
            'catatan'    => 'nullable|string|max:1000',
        ], [
            'obat_id.required'       => 'Obat wajib dipilih.',
            'jenis.required'         => 'Jenis tindakan/kondisi wajib dipilih.',
            'jumlah.required_unless' => 'Jumlah unit wajib diisi.',
            'jumlah.min'             => 'Jumlah unit minimal 1.',
            'stok_fisik.required_if' => 'Stok fisik hasil cek wajib diisi.',
            'stok_fisik.min'         => 'Stok fisik tidak boleh kurang dari 0.',
            'penyebab.required'      => 'Penyebab atau keterangan wajib diisi.',
        ]);

        KondisiStokService::catat($data, Auth::id());

        return redirect()->route('kondisi.index')
            ->with('success', 'Catatan kondisi stok berhasil disimpan dan stok obat telah diperbarui.');
    }
}
