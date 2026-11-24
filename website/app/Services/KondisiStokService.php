<?php

namespace App\Services;

use App\Models\KondisiStokObat;
use App\Models\Obat;
use App\Models\ObatBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KondisiStokService
{
    /**
     * Catat perubahan kondisi stok obat (rusak, retur supplier, musnah expired, atau stok opname fisik)
     * secara atomik. Stok batch dan stok aktif pada tabel obat ikut diperbarui.
     * Opsional: $data['batch_id'] untuk menindak batch tertentu.
     */
    public static function catat(array $data, ?int $userId = null): KondisiStokObat
    {
        return DB::transaction(function () use ($data, $userId) {
            $obat = Obat::lockForUpdate()->findOrFail($data['obat_id']);
            $stokSebelum = (int) $obat->stok;
            $jenis = $data['jenis'];
            $punyaBatch = ObatBatch::where('obat_id', $obat->id)->exists();
            $batchId = null;

            if ($jenis === KondisiStokObat::JENIS_OPNAME) {
                $stokSesudah = max(0, (int) $data['stok_fisik']);
                $jumlah = abs($stokSesudah - $stokSebelum);

                if ($punyaBatch && $stokSesudah < $stokSebelum) {
                    // Selisih kurang: ambil dari batch kadaluarsa terdekat
                    BatchStokService::kurangiFefo($obat->id, $stokSebelum - $stokSesudah, false);
                } elseif ($punyaBatch && $stokSesudah > $stokSebelum) {
                    // Selisih lebih: tambahkan ke batch dengan kadaluarsa terjauh
                    $batch = ObatBatch::where('obat_id', $obat->id)
                        ->orderByDesc('tgl_kadaluarsa')->orderByDesc('id')->lockForUpdate()->first();
                    $batch->update(['stok' => $batch->stok + ($stokSesudah - $stokSebelum)]);
                    $batchId = $batch->id;
                }
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

                if ($punyaBatch) {
                    if (filled($data['batch_id'] ?? null)) {
                        $batch = ObatBatch::where('obat_id', $obat->id)
                            ->lockForUpdate()->findOrFail($data['batch_id']);
                        BatchStokService::kurangiBatch($batch, $jumlah);
                        $batchId = $batch->id;
                    } else {
                        BatchStokService::kurangiFefo($obat->id, $jumlah, false);
                    }
                }
            }

            $obat->update(['stok' => $stokSesudah]);

            $catat = KondisiStokObat::create([
                'obat_id'      => $obat->id,
                'batch_id'     => $batchId,
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