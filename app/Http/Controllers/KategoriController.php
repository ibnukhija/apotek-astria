<?php
namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function storeAjax(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori'
        ]);

        // Simpan ke database tabel kategori
        $kategori = Kategori::create([
            'nama_kategori' => $request->nama_kategori
        ]);

        // Mengembalikan data kategori yang baru dibuat dalam format JSON
        return response()->json([
            'success' => true,
            'data' => $kategori
        ]);
    }
}