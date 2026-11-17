<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanNotifikasi extends Model
{
    protected $table = 'pengaturan_notifikasi';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}