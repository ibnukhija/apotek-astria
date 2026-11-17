<?php

namespace App\Services;

use App\Models\KondisiStokObat;
use App\Models\Obat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KondisiStokService
{
    /**
     * Catat perubahan kondisi stok obat (rusak, retur supplier, musnah expired, atau stok opname fisik)
     * secara atomik dan perbarui stok aktif pada tabel obat.
     */
    public static function catat(array $data, ?int $userId = null): KondisiStokObat
    {
        return DB::transaction(function () use ($data, $userId) {
            $obat = Obat::lockForUpdate()->findOrFail($data['obat_id']);
            $stokSebelum = (int) $obat->stok;
            $jenis = $data['jenis'];

            if ($jenis === KondisiStokObat::JENIS_OPNAME) {
                $stokSesudah = max(0, (int) $data['stok_fisik']);
                $jumlah = abs($stokSesudah - $stokSebelum);
            } else {
                $jumlah = (int) $data['jumlah'];
                if ($jumlah <= 0) {
                    throw ValidationException::withMessages([
                        'jumlah' => 'Jumlah unit harus minimal 1.',
                    ]);
                }
                if ($jumlah > $stokSebelum) {
                    throw ValidationException::withMessages([
                        'jumlah' => "Jumlah melebihi stok sistem saat ini ({$stokSebelum}).",
                    ]);
                }
                $stokSesudah = $stokSebelum - $jumlah;
            }

            $obat->update(['stok' => $stokSesudah]);

            $catat = KondisiStokObat::create([
                'obat_id'      => $obat->id,
                'user_id'      => $userId,
                'jenis'        => $jenis,
                'jumlah'       => $jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'penyebab'     => trim((string) $data['penyebab']),
                'catatan'      => filled($data['catatan'] ?? null) ? trim((string) $data['catatan']) : null,
            ]);

            DB::afterCommit(fn () => NotifikasiStokService::cekSetelahKondisiStok($catat, $stokSebelum));

            return $catat;
        });
    }
}
