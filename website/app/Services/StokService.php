<?php

namespace App\Services;

use App\Models\Obat;
use App\Models\ObatBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\NotifikasiStokService;

class StokService
{
    /**
     * Cek stok semua item, lalu kurangi stoknya dalam satu transaksi database.
     * $items = [['obat_id' => 1, 'jumlah' => 2], ...]
     * Jika ada satu item yang tidak cukup, semuanya dibatalkan.
     * Obat yang punya batch dikurangi dengan prinsip FEFO (kadaluarsa terdekat dulu),
     * dan batch yang sudah kadaluarsa tidak bisa dijual.
     */
    public static function kurangi(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                // lockForUpdate: mencegah dua kasir mengurangi stok yang sama bersamaan
                $obat = Obat::lockForUpdate()->findOrFail($item['obat_id']);
                $jumlah = (int) $item['jumlah'];

                if ($obat->status !== 'tersedia') {
                    throw ValidationException::withMessages([
                        'stok' => "{$obat->nama_obat} sedang tidak tersedia.",
                    ]);
                }

                if ($jumlah > $obat->stok) {
                    throw ValidationException::withMessages([
                        'stok' => "Stok {$obat->nama_obat} tidak cukup (tersisa {$obat->stok}).",
                    ]);
                }

                if (ObatBatch::where('obat_id', $obat->id)->exists()) {
                    try {
                        BatchStokService::kurangiFefo($obat->id, $jumlah);
                    } catch (ValidationException $e) {
                        // Pesan dipakai kasir, jadi sebut nama obatnya
                        throw ValidationException::withMessages([
                            'stok' => "{$obat->nama_obat}: stok yang layak jual tidak cukup (sisa stok mungkin sudah kadaluarsa, tindak lanjuti di halaman Kondisi Obat).",
                        ]);
                    }
                }

                $obat->decrement('stok', $jumlah);
            }

            DB::afterCommit(fn () => NotifikasiStokService::cekSetelahTransaksi($items));
        });
    }
}