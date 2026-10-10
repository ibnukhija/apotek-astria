<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Obat extends Model
{
    /** Stok di bawah angka ini = "Hampir habis" (merah). */
    public const BATAS_HAMPIR_HABIS = 10;

    /** Stok mulai angka ini = "Cukup" (hijau). Di antaranya = "Menipis" (kuning). */
    public const BATAS_CUKUP = 25;

    protected $table = 'obat';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'margin' => 'float',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    /**
     * Harga jual otomatis = harga beli + margin (persen atau nominal).
     */
    public static function hitungHargaJual(int $hargaBeli, float $margin, string $tipe): int
    {
        if ($tipe === 'persen') {
            return (int) round($hargaBeli * (1 + $margin / 100));
        }

        return $hargaBeli + (int) round($margin);
    }

    /**
     * Label dan warna status stok.
     *   stok < 10        => Hampir habis (merah)
     *   10 <= stok < 25  => Menipis      (kuning)
     *   stok >= 25       => Cukup        (hijau)
     */
    public static function infoStok(int $stok): array
    {
        if ($stok < self::BATAS_HAMPIR_HABIS) {
            return ['label' => 'Hampir habis', 'kelas' => 'bg-red-100 text-red-700'];
        }

        if ($stok < self::BATAS_CUKUP) {
            return ['label' => 'Menipis', 'kelas' => 'bg-yellow-100 text-yellow-700'];
        }

        return ['label' => 'Cukup', 'kelas' => 'bg-green-100 text-green-700'];
    }

    public function getStokInfoAttribute(): array
    {
        return self::infoStok((int) $this->stok);
    }

    /** "50%" untuk margin persen, "Rp3.500" untuk margin nominal. */
    public function getMarginTeksAttribute(): string
    {
        if ($this->tipe_margin === 'persen') {
            return rtrim(rtrim(number_format($this->margin, 2, ',', '.'), '0'), ',') . '%';
        }

        return 'Rp' . number_format($this->margin, 0, ',', '.');
    }
}
