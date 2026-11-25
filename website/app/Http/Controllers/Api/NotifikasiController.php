<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengaturanNotifikasi;
use App\Services\NotifikasiStokService;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function show(Request $request)
    {
        $userId = (int) ($request->user()?->id ?? $request->query('user_id'));
        $p = PengaturanNotifikasi::where('user_id', $userId)->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'aktif'            => $p?->aktif ?? true,
                'notif_kadaluarsa' => $p?->notif_kadaluarsa ?? true,
                'notif_rusak'      => $p?->notif_rusak ?? true,
                'notif_opname'     => $p?->notif_opname ?? true,
                'batas_stok'       => $p?->batas_stok ?? 10,
                'jam_ringkasan'    => $p?->jam_ringkasan ? substr($p->jam_ringkasan, 0, 5) : null,
            ],
        ]);
    }

    // Upsert parsial: hanya field yang dikirim yang diubah (mis. hanya fcm_token)
    public function update(Request $request)
    {
        $userId = $request->user()?->id;
        $v = $request->validate([
            'user_id'          => $userId ? 'nullable|integer' : 'required|integer|exists:users,id',
            'fcm_token'        => 'nullable|string',
            'aktif'            => 'sometimes|boolean',
            'notif_kadaluarsa' => 'sometimes|boolean',
            'notif_rusak'      => 'sometimes|boolean',
            'notif_opname'     => 'sometimes|boolean',
            'batas_stok'       => 'sometimes|integer|min:0|max:100000',
            'jam_ringkasan'    => 'nullable|date_format:H:i',
        ]);

        $targetUserId = (int) ($userId ?? $v['user_id']);
        $p = PengaturanNotifikasi::firstOrNew(['user_id' => $targetUserId]);

        if ($request->has('fcm_token'))        $p->fcm_token = $v['fcm_token'];
        if ($request->has('aktif'))            $p->aktif = $v['aktif'];
        if ($request->has('notif_kadaluarsa')) $p->notif_kadaluarsa = $v['notif_kadaluarsa'];
        if ($request->has('notif_rusak'))      $p->notif_rusak = $v['notif_rusak'];
        if ($request->has('notif_opname'))     $p->notif_opname = $v['notif_opname'];
        if ($request->has('batas_stok'))       $p->batas_stok = $v['batas_stok'];
        if ($request->has('jam_ringkasan'))    $p->jam_ringkasan = $v['jam_ringkasan'];
        $p->save();

        return response()->json(['success' => true, 'message' => 'Preferensi notifikasi disimpan']);
    }

    public function tes(Request $request)
    {
        $userId = $request->user()?->id;
        $v = $request->validate([
            'user_id' => $userId ? 'nullable|integer' : 'required|integer|exists:users,id',
        ]);

        $targetUserId = (int) ($userId ?? $v['user_id']);
        $p = PengaturanNotifikasi::where('user_id', $targetUserId)->first();
        if (!$p || !$p->fcm_token) {
            return response()->json([
                'success' => false,
                'message' => 'Perangkat belum terdaftar. Tutup dan buka ulang aplikasi, lalu coba lagi.',
            ], 422);
        }

        $ok = NotifikasiStokService::kirimRingkasan($p, true);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Notifikasi uji dikirim' : 'Gagal mengirim. Cek file kredensial Firebase di server.',
        ], $ok ? 200 : 500);
    }
}
