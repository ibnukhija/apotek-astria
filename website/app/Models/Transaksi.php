<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $table = 'transaksi';
    protected $guarded = ['id'];

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
