<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KategoriController extends Controller
{
    public function index()
    {
        $kategoris = Kategori::withCount('obat')->orderBy('nama_kategori')->get();

        return view('kategori.index', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $nama = $this->namaTervalidasi($request);

        Kategori::create(['nama_kategori' => $nama]);

        return redirect()->route('kategori.index')
            ->with('success', "Kategori \"{$nama}\" berhasil ditambahkan!");
    }

    public function update(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);
        $nama = $this->namaTervalidasi($request, $kategori->id);

        $kategori->update(['nama_kategori' => $nama]);

        return redirect()->route('kategori.index')
            ->with('success', "Kategori berhasil diperbarui menjadi \"{$nama}\".");
    }

    public function destroy($id)
    {
        $kategori = Kategori::withCount('obat')->findOrFail($id);

        if ($kategori->obat_count > 0) {
            return redirect()->route('kategori.index')->withErrors([
                'hapus' => "Kategori \"{$kategori->nama_kategori}\" tidak dapat dihapus karena masih dipakai {$kategori->obat_count} obat.",
            ]);
        }

        $kategori->delete();

        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil dihapus!');
    }

    /**
     * Tambah kategori dari form produk (live search). Selalu membalas JSON.
     */
    public function storeAjax(Request $request)
    {
        try {
            $nama = $this->namaTervalidasi($request);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first('nama_kategori'),
            ], 422);
        }

        $kategori = Kategori::create(['nama_kategori' => $nama]);

        return response()->json(['success' => true, 'data' => $kategori]);
    }

    /**
     * Validasi nama, rapikan penulisannya, dan tolak jika sudah ada
     * ("Anti-Alergi", "anti alergi", dan "Antialergi" dianggap sama).
     */
    private function namaTervalidasi(Request $request, ?int $kecualiId = null): string
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:100',
        ], [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.max'      => 'Nama kategori maksimal 100 karakter.',
        ]);

        $nama = Kategori::normalisasi($request->nama_kategori);

        if (Kategori::kunci($nama) === '') {
            throw ValidationException::withMessages(['nama_kategori' => 'Nama kategori harus berisi huruf atau angka.']);
        }

        if ($duplikat = Kategori::cariDuplikat($nama, $kecualiId)) {
            throw ValidationException::withMessages([
                'nama_kategori' => "Kategori \"{$duplikat->nama_kategori}\" sudah ada.",
            ]);
        }

        return $nama;
    }
}
