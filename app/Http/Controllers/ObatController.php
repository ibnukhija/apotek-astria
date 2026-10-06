<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Obat;
use App\Models\Kategori;

class ObatController extends Controller
{
    public function index()
    {
        $obats = Obat::with('kategori')->paginate(10);
        
        $kategoris = Kategori::all();
        return view('obat.index', compact('obats', 'kategoris'));
    }

    public function store(Request $request) {
        $validateData = $request->validate([
            'kode_obat'     => 'required|unique:obat',
            'nama_obat'     => 'required|string|max:255',
            'kategori_id'   => 'required|exists:kategori,id',
            'satuan'        => 'required|in:Strip,Botol,Tablet',
            'harga_beli'    => 'required|integer|min:0',
            'harga_jual'    => 'required|integer|min:0',
            'stok'          => 'required|integer|min:0',
        ]);

        Obat::create($validateData);
        return redirect()->route('obat.index')->with('succes', 'Data obat berhasil ditambahkan!');
    }

    public function update(Request $request, $id) {
        $obat = Obat::findOrFail($id);
        
        $validateData = $request->validate([
            'kode_obat'     => 'required|unique:obat,kode_obat,' . $obat->id,
            'nama_obat'     => 'required|string|max:255',
            'kategori_id'   => 'required|exists:kategori,id',
            'satuan'        => 'required|in:Strip,Botol,Tablet',
            'harga_beli'    => 'required|integer|min:0',
            'harga_jual'    => 'required|integer|min:0',
            'stok'          => 'required|integer|min:0',
        ]);

        $obat->update($validateData);
        return redirect()->route('obat.index')->with('succes', 'Data obat berhasil diperbarui!');
    }

    public function destroy($id) {
        $obat = Obat::findOrFail($id);
        $obat->delete();

        return redirect()->route('obat.index')->with('success', 'Data obat berhasil dihapus!');
    }

}
