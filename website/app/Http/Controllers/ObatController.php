<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Obat;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ObatController extends Controller
{
    public function index(Request $request)
    {
        $obats = Obat::with('kategori')
            ->when($request->filled('q'), function ($query) use ($request) {
                $kata = trim($request->q);
                $query->where(function ($w) use ($kata) {
                    $w->where('nama_obat', 'like', "%{$kata}%")
                      ->orWhere('kode_obat', 'like', "%{$kata}%");
                });
            })
            ->when($request->filled('kategori'), fn ($query) => $query->where('kategori_id', $request->kategori))
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

        Obat::create($data);

        return redirect()->route('obat.index')->with('success', 'Data obat berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $obat = Obat::findOrFail($id);
        $data = $this->validasi($request, $obat->id);

        $obat->update($data);

        return redirect()->route('obat.index')->with('success', 'Data obat berhasil diperbarui!');
    }

    public function destroy($id)
    {
        Obat::findOrFail($id)->delete();

        return redirect()->route('obat.index')->with('success', 'Data obat berhasil dihapus!');
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
            'stok'        => 'required|integer|min:0',
            'status'      => 'required|in:tersedia,tidak',
        ], [
            'kode_obat.required'   => 'Kode obat wajib diisi.',
            'kode_obat.unique'     => 'Kode obat sudah dipakai produk lain.',
            'nama_obat.required'   => 'Nama obat wajib diisi.',
            'kategori_id.required' => 'Kategori wajib dipilih dari daftar atau ditambahkan terlebih dahulu.',
            'kategori_id.exists'   => 'Kategori yang dipilih tidak ditemukan.',
            'harga_beli.required'  => 'Harga beli wajib diisi.',
            'margin.required'      => 'Margin keuntungan wajib diisi.',
            'stok.required'        => 'Stok wajib diisi.',
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
