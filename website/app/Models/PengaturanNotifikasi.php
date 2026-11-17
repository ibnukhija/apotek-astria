<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanNotifikasi extends Model
{
    protected $table = 'pengaturan_notifikasi';
    protected $guarded = ['id'];

    protected $attributes = [
        'aktif'            => true,
        'notif_kadaluarsa' => true,
        'notif_rusak'      => true,
        'batas_stok'       => 10,
    ];

    protected function casts(): array
    {
        return [
            'aktif'            => 'boolean',
            'notif_kadaluarsa' => 'boolean',
            'notif_rusak'      => 'boolean',
        ];
    }
}