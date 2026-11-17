<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfilApotek;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OwnerController extends Controller
{
    // ===== Poin E: simpan profil apotek =====
    public function updateProfil(Request $request)
    {
        $v = $request->validate([
            'nama_apotek'        => 'required|string|max:255',
            'alamat'             => 'required|string',
            'latitude'           => 'nullable|numeric|between:-90,90',
            'longitude'          => 'nullable|numeric|between:-180,180',
            'jam_buka'           => 'nullable|date_format:H:i',
            'jam_tutup'          => 'nullable|date_format:H:i',
            'buka_24_jam'        => 'nullable|boolean',
            'hari_operasional'   => 'nullable|array|min:1',
            'hari_operasional.*' => ['required', Rule::in(ProfilApotek::SEMUA_HARI)],
            'tutup_paksa'        => 'nullable|boolean',
            'alasan_tutup'       => 'nullable|string|max:255',
        ], [
            'hari_operasional.min' => 'Pilih minimal satu hari operasional.',
        ]);

        $profil = ProfilApotek::first();
        $hariDefault = $profil?->hariAktif() ?? ProfilApotek::SEMUA_HARI;
        $buka24Jam = array_key_exists('buka_24_jam', $v)
            ? (bool) $v['buka_24_jam']
            : (bool) ($profil?->buka_24_jam ?? false);
        $tutupPaksa = array_key_exists('tutup_paksa', $v)
            ? (bool) $v['tutup_paksa']
            : (bool) ($profil?->tutup_paksa ?? false);

        $hariOperasional = isset($v['hari_operasional'])
            ? array_values(array_intersect(ProfilApotek::SEMUA_HARI, $v['hari_operasional']))
            : $hariDefault;

        $payload = [
            'nama_apotek'      => $v['nama_apotek'],
            'alamat'           => $v['alamat'],
            'latitude'         => $v['latitude'] ?? null,
            'longitude'        => $v['longitude'] ?? null,
            'jam_buka'         => $v['jam_buka'] ?? ($profil?->jam_buka ?: ProfilApotek::DEFAULT_JAM_BUKA),
            'jam_tutup'        => $v['jam_tutup'] ?? ($profil?->jam_tutup ?: ProfilApotek::DEFAULT_JAM_TUTUP),
            'buka_24_jam'      => $buka24Jam,
            'hari_operasional' => $hariOperasional,
            'tutup_paksa'      => $tutupPaksa,
            'alasan_tutup'     => $tutupPaksa ? trim((string) ($v['alasan_tutup'] ?? '')) ?: null : null,
        ];

        if ($profil) {
            $profil->update($payload);
        } else {
            $profil = ProfilApotek::create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil apotek disimpan',
            'data'    => $profil->fresh()->terserialisasi(),
        ]);
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
        $user->status   = 'aktif';
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