<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObatBatch extends Model
{
    protected $table = 'obat_batch';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tgl_terima'     => 'date:Y-m-d',
            'tgl_kadaluarsa' => 'date:Y-m-d',
        ];
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    /** Masih ada stok (termasuk yang sudah kadaluarsa). */
    public function scopeAdaStok($q)
    {
        return $q->where('stok', '>', 0);
    }

    /** Boleh dijual: ada stok dan belum kadaluarsa. */
    public function scopeLayakJual($q)
    {
        return $q->adaStok()->whereDate('tgl_kadaluarsa', '>=', today());
    }

    /** Sudah kadaluarsa tapi stoknya belum dibuang/diretur. */
    public function scopeKadaluarsaBelumDitindak($q)
    {
        return $q->adaStok()->whereDate('tgl_kadaluarsa', '<', today());
    }

    public function getKadaluarsaInfoAttribute(): array
    {
        return Obat::infoKadaluarsa($this->tgl_kadaluarsa);
    }
}