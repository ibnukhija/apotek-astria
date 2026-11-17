<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KondisiStokObat extends Model
{
    public const JENIS_RUSAK = 'rusak';
    public const JENIS_RETUR = 'retur_supplier';
    public const JENIS_MUSNAH = 'musnah_expired';
    public const JENIS_OPNAME = 'stok_opname';

    public const DAFTAR_JENIS = [
        self::JENIS_RUSAK  => 'Obat Rusak',
        self::JENIS_RETUR  => 'Retur ke Supplier',
        self::JENIS_MUSNAH => 'Musnahkan (Expired)',
        self::JENIS_OPNAME => 'Cek Stok Fisik (Opname)',
    ];

    protected $table = 'kondisi_stok_obat';

    protected $guarded = ['id'];

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getJenisLabelAttribute(): string
    {
        return self::DAFTAR_JENIS[$this->jenis] ?? ucfirst((string) $this->jenis);
    }
}
