<?php

namespace App\Services;

use App\Models\Obat;
use App\Models\ObatBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BatchStokService
{
    /** Terima stok baru sebagai batch (kode batch, supplier, tanggal kadaluarsa sendiri). */
    public static function terima(int $obatId, array $data): ObatBatch
    {
        return DB::transaction(function () use ($obatId, $data) {
            $obat = Obat::lockForUpdate()->findOrFail($obatId);

            $batch = ObatBatch::create([
                'obat_id'        => $obat->id,
                'kode_batch'     => $data['kode_batch'],
                'supplier'       => $data['supplier'] ?? null,
                'tgl_terima'     => $data['tgl_terima'] ?? today()->toDateString(),
                'tgl_kadaluarsa' => $data['tgl_kadaluarsa'],
                'stok_awal'      => (int) $data['jumlah'],
                'stok'           => (int) $data['jumlah'],
                'harga_beli'     => $data['harga_beli'] ?? null,
            ]);

            $obat->update(['stok' => (int) $obat->stok + $batch->stok_awal]);
            self::sinkronTanggal($obat);

            return $batch;
        });
    }

    /**
     * Kurangi stok batch dengan prinsip FEFO (kadaluarsa terdekat dulu).
     * Harus dipanggil di dalam DB::transaction. Hanya mengurangi batch;
     * pemanggil yang memperbarui obat.stok.
     *
     * @return array<int, array{batch_id:int, kode_batch:string, qty:int}>
     */
    public static function kurangiFefo(int $obatId, int $jumlah, bool $hanyaLayakJual = true): array
    {
        $query = ObatBatch::where('obat_id', $obatId)->adaStok();
        if ($hanyaLayakJual) {
            $query->whereDate('tgl_kadaluarsa', '>=', today());
        }

        $batches = $query->orderBy('tgl_kadaluarsa')->orderBy('id')->lockForUpdate()->get();

        $tersedia = (int) $batches->sum('stok');
        if ($tersedia < $jumlah) {
            throw ValidationException::withMessages([
                'jumlah' => $hanyaLayakJual
                    ? "Stok layak jual tidak cukup (tersisa {$tersedia}, batch kadaluarsa tidak bisa dijual)."
                    : "Stok batch tidak cukup (tersisa {$tersedia}).",
            ]);
        }

        $sisa = $jumlah;
        $dipakai = [];
        foreach ($batches as $b) {
            if ($sisa <= 0) {
                break;
            }
            $ambil = min((int) $b->stok, $sisa);
            $b->update(['stok' => $b->stok - $ambil]);
            $dipakai[] = ['batch_id' => $b->id, 'kode_batch' => $b->kode_batch, 'qty' => $ambil];
            $sisa -= $ambil;
        }

        self::sinkronTanggal(Obat::find($obatId));

        return $dipakai;
    }

    /** Kurangi satu batch tertentu (dipakai untuk retur/musnah per batch). */
    public static function kurangiBatch(ObatBatch $batch, int $jumlah): void
    {
        if ($jumlah > (int) $batch->stok) {
            throw ValidationException::withMessages([
                'jumlah' => "Jumlah melebihi stok batch {$batch->kode_batch} ({$batch->stok}).",
            ]);
        }
        $batch->update(['stok' => $batch->stok - $jumlah]);
        self::sinkronTanggal($batch->obat);
    }

    /** Kolom lama obat.tgl_kadaluarsa = batch terdekat yang masih ada stok. */
    public static function sinkronTanggal(?Obat $obat): void
    {
        if (! $obat) {
            return;
        }
        $terdekat = ObatBatch::where('obat_id', $obat->id)->adaStok()->min('tgl_kadaluarsa');
        if ($terdekat) {
            $obat->update(['tgl_kadaluarsa' => $terdekat]);
        }
    }
}