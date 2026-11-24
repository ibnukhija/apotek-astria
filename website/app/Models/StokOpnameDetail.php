<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokOpnameDetail extends Model
{
    protected $table = 'stok_opname_detail';

    protected $guarded = ['id'];

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StokOpname::class, 'stok_opname_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    /** fisik - sistem. Negatif = kurang, positif = lebih, null = belum dihitung. */
    public function getSelisihAttribute(): ?int
    {
        return $this->stok_fisik === null ? null : (int) $this->stok_fisik - (int) $this->stok_sistem;
    }
}