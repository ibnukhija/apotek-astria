<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfilApotek;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OwnerController extends Controller
{
    // ===== Poin E: simpan profil apotek =====
    public function updateProfil(Request $request)
    {
        $v = $request->validate([
            'nama_apotek' => 'required|string|max:255',
            'alamat'      => 'required|string',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
        ]);

        $profil = ProfilApotek::first();
        if ($profil) {
            $profil->update($v);
        } else {
            ProfilApotek::create($v);
        }

        return response()->json(['success' => true, 'message' => 'Profil apotek disimpan']);
    }

    // ===== Poin D: akun karyawan =====
    public function karyawanIndex()
    {
        $data = User::where('role', 'karyawan')
            ->orderBy('nama')
            ->get(['id', 'nama', 'username', 'status']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function karyawanStore(Request $request)
    {
        $v = $request->validate([
            'nama'     => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6',
        ]);

        $user = new User();
        $user->nama     = $v['nama'];
        $user->username = $v['username'];
        $user->password = Hash::make($v['password']);
        $user->role     = 'karyawan';
        $user->save();

        return response()->json(['success' => true, 'message' => 'Akun karyawan ditambahkan'], 201);
    }

    public function karyawanUpdate(Request $request, $id)
    {
        $user = User::where('role', 'karyawan')->find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Akun tidak ditemukan'], 404);
        }

        $v = $request->validate([
            'nama'     => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'password' => 'nullable|string|min:6',
        ]);

        $user->nama     = $v['nama'];
        $user->username = $v['username'];
        if (!empty($v['password'])) {
            $user->password = Hash::make($v['password']);
        }
        $user->save();

        return response()->json(['success' => true, 'message' => 'Akun karyawan diperbarui']);
    }

    public function karyawanToggleStatus($id)
    {
        $user = User::where('role', 'karyawan')->find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Akun tidak ditemukan'], 404);
        }

        $user->status = $user->status === 'nonaktif' ? 'aktif' : 'nonaktif';
        $user->save();

        return response()->json(['success' => true, 'message' => "Akun sekarang {$user->status}"]);
    }
}