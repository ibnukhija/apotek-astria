<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obat extends Model
{
    /** Stok di bawah angka ini = "Hampir habis" (merah). */
    public const BATAS_HAMPIR_HABIS = 10;

    /** Stok mulai angka ini = "Cukup" (hijau). Di antaranya = "Menipis" (kuning). */
    public const BATAS_CUKUP = 25;

    /** Sisa hari maksimal (inklusif) untuk status "Hampir kadaluarsa" (kuning, ~1 minggu sebelum expired). */
    public const BATAS_HAMPIR_KADALUARSA = 7;

    // Awalan Kode Obat
    public const PREFIX_KODE = 'OBT';

    protected $table = 'obat';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'margin'         => 'float',
            'tgl_kadaluarsa' => 'date:Y-m-d',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function kondisiStok(): HasMany
    {
        return $this->hasMany(KondisiStokObat::class, 'obat_id');
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

    // Generate Kode Obat Otomatis
    public static function buatKode(): string
    {
        $awal = self::PREFIX_KODE . '-';

        $urutTerbesar = static::where('kode_obat', 'like', $awal . '%')
            ->pluck('kode_obat')
            ->map(fn ($kode) => (int) substr($kode, strlen($awal)))
            ->max() ?? 0;

        return sprintf('%s%03d', $awal, $urutTerbesar + 1);
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

        if ($stok >= self::BATAS_CUKUP) {
            return ['label' => 'Cukup', 'kelas' => 'bg-green-100 text-green-700'];
        }
    }

    public function getStokInfoAttribute(): array
    {
        return self::infoStok((int) $this->stok);
    }

    /**
     * Label dan warna status tanggal kadaluarsa.
     *   lewat hari ini => Kadaluarsa         (merah)
     *   0 <= sisa <= 7 hari => Hampir kadaluarsa (kuning)
     *   > 7 hari       => Aman               (hijau)
     */
    public static function infoKadaluarsa(CarbonInterface|string|null $tanggal): array
    {
        if (! $tanggal) {
            return ['label' => 'Belum diatur', 'kelas' => 'bg-gray-100 text-gray-text'];
        }

        $tgl = Carbon::parse($tanggal)->startOfDay();
        $sisaHari = Carbon::today()->diffInDays($tgl, false);

        if ($sisaHari < 0) {
            return ['label' => 'Kadaluarsa', 'kelas' => 'bg-red-100 text-red-700'];
        }

        if ($sisaHari <= self::BATAS_HAMPIR_KADALUARSA) {
            return ['label' => 'Hampir kadaluarsa', 'kelas' => 'bg-yellow-100 text-yellow-700'];
        }

        return ['label' => 'Aman', 'kelas' => 'bg-green-100 text-green-700'];
    }

    public function getKadaluarsaInfoAttribute(): array
    {
        return self::infoKadaluarsa($this->tgl_kadaluarsa);
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
