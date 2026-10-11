<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    protected $table = 'detail_transaksi';
    protected $guarded = ['id'];
    
    public function transaksi(){
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function obat() {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

}
