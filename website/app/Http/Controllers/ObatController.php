<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Obat;
use App\Models\ObatBatch;
use App\Services\BatchStokService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ObatController extends Controller
{
    public function index(Request $request)
    {
        $obats = Obat::with(['kategori', 'batches' => fn ($q) => $q->adaStok()->orderBy('tgl_kadaluarsa')->orderBy('id')])
            ->withCount('batches')
            ->when($request->filled('q'), function ($query) use ($request) {
                $kata = trim($request->q);
                $query->where(function ($w) use ($kata) {
                    $w->where('nama_obat', 'like', "%{$kata}%")
                        ->orWhere('kode_obat', 'like', "%{$kata}%");
                });
            })
            ->when($request->filled('kategori'), fn($query) => $query->where('kategori_id', $request->kategori))
            ->when($request->filled('status_stok'), function ($query) use ($request) {
                $statusStok = strtolower(trim((string) $request->status_stok));
                if ($statusStok === 'tersedia') {
                    $query->where('status', 'tersedia');
                } elseif ($statusStok === 'cukup') {
                    $query->where('status', 'tersedia')->where('stok', '>=', Obat::BATAS_CUKUP);
                } elseif ($statusStok === 'menipis') {
                    $query->where('status', 'tersedia')
                          ->where('stok', '>=', Obat::BATAS_HAMPIR_HABIS)
                          ->where('stok', '<', Obat::BATAS_CUKUP);
                } elseif ($statusStok === 'hampir_habis') {
                    $query->where('status', 'tersedia')->where('stok', '<', Obat::BATAS_HAMPIR_HABIS);
                } elseif ($statusStok === 'perlu_restok') {
                    $query->where('status', 'tersedia')->where('stok', '<', Obat::BATAS_CUKUP);
                }
            })
            ->when($request->filled('status_kadaluarsa'), function ($query) use ($request) {
                if (strtolower(trim((string) $request->status_kadaluarsa)) === 'perhatian') {
                    $batas = Carbon::today()->addDays(Obat::BATAS_HAMPIR_KADALUARSA);
                    $query->where('status', 'tersedia')
                          ->whereNotNull('tgl_kadaluarsa')
                          ->whereDate('tgl_kadaluarsa', '<=', $batas);
                }
            })
            ->orderBy('kode_obat')
            ->paginate(10)
            ->withQueryString();

        $kategoris = Kategori::orderBy('nama_kategori')->get();

        // Kotak ringkasan (hanya produk berstatus tersedia / aktif).
        $aktif = Obat::where('status', 'tersedia');
        $ringkasan = [
            'total'        => (clone $aktif)->count(),
            'kategori'     => $kategoris->count(),
            'cukup'        => (clone $aktif)->where('stok', '>=', Obat::BATAS_CUKUP)->count(),
            'menipis'      => (clone $aktif)->where('stok', '>=', Obat::BATAS_HAMPIR_HABIS)
                ->where('stok', '<', Obat::BATAS_CUKUP)->count(),
            'hampir_habis' => (clone $aktif)->where('stok', '<', Obat::BATAS_HAMPIR_HABIS)->count(),
        ];

        return view('obat.index', compact('obats', 'kategoris', 'ringkasan'));
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        DB::transaction(function () use ($data) {
            $obat = Obat::create($data);
            $this->pastikanBatchAwal($obat);
        });

        return redirect()->route('obat.index')->with('success', 'Data obat berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $obat = Obat::findOrFail($id);
        $data = $this->validasi($request, $obat->id);

        DB::transaction(function () use ($obat, $data) {
            // Stok & tanggal kadaluarsa obat yang sudah punya batch diturunkan dari batch,
            // jadi tidak boleh ditimpa dari form edit.
            if ($obat->batches()->exists()) {
                unset($data['stok'], $data['tgl_kadaluarsa']);
            }

            $obat->update($data);
            $this->pastikanBatchAwal($obat->refresh());
        });

        return redirect()->route('obat.index')->with('success', 'Data obat berhasil diperbarui!');
    }

    /** Terima stok baru sebagai batch (kode batch, supplier, kadaluarsa sendiri). */
    public function terimaStok(Request $request, $id)
    {
        $obat = Obat::findOrFail($id);

        $data = $request->validate([
            'kode_batch'     => ['required', 'string', 'max:50',
                Rule::unique('obat_batch', 'kode_batch')->where('obat_id', $obat->id)],
            'supplier'       => 'nullable|string|max:100',
            'tgl_terima'     => 'nullable|date|before_or_equal:today',
            'tgl_kadaluarsa' => 'required|date|after:today',
            'jumlah'         => 'required|integer|min:1',
            'harga_beli'     => 'nullable|integer|min:0',
        ], [
            'kode_batch.required'      => 'Kode batch wajib diisi.',
            'kode_batch.unique'        => 'Kode batch ini sudah dipakai untuk obat yang sama.',
            'tgl_kadaluarsa.required'  => 'Tanggal kadaluarsa batch wajib diisi.',
            'tgl_kadaluarsa.after'     => 'Tanggal kadaluarsa batch harus setelah hari ini.',
            'jumlah.required'          => 'Jumlah stok masuk wajib diisi.',
            'jumlah.min'               => 'Jumlah stok masuk minimal 1.',
        ]);

        DB::transaction(function () use ($obat, $data) {
            $this->pastikanBatchAwal($obat);
            BatchStokService::terima($obat->id, $data);
        });

        return redirect()->route('obat.index')
            ->with('success', "Batch {$data['kode_batch']} ({$obat->nama_obat}) berhasil diterima: {$data['jumlah']} {$obat->satuan}.");
    }

    public function destroy($id)
    {
        Obat::findOrFail($id)->delete();

        return redirect()->route('obat.index')->with('success', 'Data obat berhasil dihapus!');
    }

    // Generate Kode Obat Otomatis via AJAX (Berdasarkan Nama Obat)
    public function generateKode()
    {
        return response()->json(['kode' => Obat::buatKode()]);
    }

    /** Obat yang punya stok tapi belum punya batch dibuatkan satu batch awal. */
    private function pastikanBatchAwal(Obat $obat): void
    {
        if ((int) $obat->stok > 0 && ! $obat->batches()->exists()) {
            ObatBatch::create([
                'obat_id'        => $obat->id,
                'kode_batch'     => 'BATCH-AWAL',
                'tgl_terima'     => today()->toDateString(),
                'tgl_kadaluarsa' => $obat->tgl_kadaluarsa,
                'stok_awal'      => (int) $obat->stok,
                'stok'           => (int) $obat->stok,
                'harga_beli'     => $obat->harga_beli,
            ]);
        }
    }

    /**
     * Validasi input dan hitung harga jual otomatis di server
     * (nilai harga_jual dari browser tidak dipercaya).
     */
    private function validasi(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'kode_obat'   => 'required|string|max:50|unique:obat,kode_obat' . ($id ? ',' . $id : ''),
            'nama_obat'   => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'satuan'      => 'required|in:Strip,Botol,Tablet',
            'harga_beli'  => 'required|integer|min:0',
            'margin'      => 'required|numeric|min:0',
            'tipe_margin' => 'required|in:persen,nominal',
            'stok'           => 'required|integer|min:0',
            'tgl_kadaluarsa' => 'required|date',
            'status'         => 'required|in:tersedia,tidak',
        ], [
            'kode_obat.required'      => 'Kode obat wajib diisi.',
            'kode_obat.unique'        => 'Kode obat sudah dipakai produk lain.',
            'nama_obat.required'      => 'Nama obat wajib diisi.',
            'kategori_id.required'    => 'Kategori wajib dipilih dari daftar atau ditambahkan terlebih dahulu.',
            'kategori_id.exists'      => 'Kategori yang dipilih tidak ditemukan.',
            'harga_beli.required'     => 'Harga beli wajib diisi.',
            'margin.required'         => 'Margin keuntungan wajib diisi.',
            'stok.required'           => 'Stok wajib diisi.',
            'tgl_kadaluarsa.required' => 'Tanggal kadaluarsa wajib diisi.',
            'tgl_kadaluarsa.date'     => 'Format tanggal kadaluarsa tidak valid.',
        ]);

        if ($data['tipe_margin'] === 'persen' && $data['margin'] > 1000) {
            throw ValidationException::withMessages(['margin' => 'Margin persen maksimal 1000%.']);
        }

        $data['harga_jual'] = Obat::hitungHargaJual(
            (int) $data['harga_beli'],
            (float) $data['margin'],
            $data['tipe_margin']
        );

        return $data;
    }
}