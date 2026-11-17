<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    public const METODE_CASH = 'cash';
    public const METODE_QRIS = 'qris';

    protected $table = 'transaksi';
    protected $guarded = ['id'];

    public function getMetodeBayarLabelAttribute(): string
    {
        return $this->metode_bayar === self::METODE_QRIS ? 'QRIS' : 'Cash';
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details() {
        return $this->hasMany(DetailTransaksi::class, 'transaksi_id');
    }

    public function rincian() {
        return $this->hasMany(DetailTransaksi::class, 'transaksi_id');
    }
}
