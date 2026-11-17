<?php

namespace App\Http\Controllers;

use App\Models\ProfilApotek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfilKaryawanController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $profilApotek = ProfilApotek::ambilAtauDefault()->terserialisasi();

        return view('profil.index', compact('user', 'profilApotek'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'nama'     => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
        ], [
            'nama.required'     => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique'   => 'Username sudah digunakan akun lain.',
            'password.min'      => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->nama = trim($data['nama']);
        $user->username = trim($data['username']);
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return redirect()->route('profil.edit')->with('success', 'Profil akun berhasil diperbarui.');
    }
}
