<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $table = 'kategori';

    public $timestamps = false;

    protected $fillable = [
        'nama_kategori',
    ];

    public function obat(): HasMany
    {
        return $this->hasMany(Obat::class, 'kategori_id');
    }

    /**
     * Merapikan penulisan: spasi berlebih dibuang, huruf awal tiap kata besar,
     * sisanya kecil. Contoh: "  anti ALERGI " => "Anti Alergi".
     */
    public static function normalisasi(string $nama): string
    {
        $nama = trim(preg_replace('/\s+/u', ' ', $nama));
        $nama = mb_strtolower($nama, 'UTF-8');

        return preg_replace_callback(
            '/(^|[\s\-])(\p{L})/u',
            fn ($m) => $m[1] . mb_strtoupper($m[2], 'UTF-8'),
            $nama
        );
    }

    /**
     * Kunci pembanding: huruf kecil tanpa spasi/tanda hubung/simbol.
     * "Anti-Alergi", "anti alergi", dan "Antialergi" menghasilkan kunci yang sama.
     */
    public static function kunci(string $nama): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($nama, 'UTF-8'));
    }

    /**
     * Mencari kategori lain yang dianggap sama dengan $nama (null jika tidak ada).
     */
    public static function cariDuplikat(string $nama, ?int $kecualiId = null): ?self
    {
        $kunci = self::kunci($nama);

        return self::query()
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->get()
            ->first(fn (self $k) => self::kunci($k->nama_kategori) === $kunci);
    }
}
