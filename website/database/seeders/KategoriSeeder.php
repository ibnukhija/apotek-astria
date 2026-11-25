<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kategori;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoris = [
            'Analgesik', 
            'Antibiotik', 
            'Pencernaan', 
            'Vitamin', 
            'Batuk & Flu', 
            'Perawatan', 
            'Antiseptik',
            'Antialergi'
        ];

        foreach ($kategoris as $kategori) {
            Kategori::firstOrCreate([
                'nama_kategori' => $kategori,
            ]);
        }
    }
}