<?php

namespace App\Services;

use App\Models\Obat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StokService
{
    /**
     * Cek stok semua item, lalu kurangi stoknya dalam satu transaksi database.
     * $items = [['obat_id' => 1, 'jumlah' => 2], ...]
     * Jika ada satu item yang tidak cukup, semuanya dibatalkan.
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

                $obat->decrement('stok', $jumlah);
            }
        });
    }
}