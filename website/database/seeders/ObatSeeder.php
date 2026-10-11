<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Obat;
use Illuminate\Database\Seeder;

class ObatSeeder extends Seeder
{
    public function run(): void
    {
        $produk = [
            // nama, kategori, satuan, harga beli, stok, tipe margin, margin
            ['Paracetamol 500mg', 'Analgesik',   'Strip',  8000, 50, 'persen',  50],
            ['Amoxicillin 500mg', 'Antibiotik',  'Strip', 11000, 24, 'nominal', 5000],
            ['Cetirizine 10mg',   'Antialergi',  'Strip',  9000, 43, 'nominal', 3500],
            ['Vitamin C IPI',     'Vitamin',     'Botol',  7000, 25, 'persen',  30],
            ['Antasida DOEN',     'Pencernaan',  'Strip',  5000, 10, 'persen',  50],
            ['OBH Combi Dewasa',  'Batuk & Flu', 'Botol', 17000,  7, 'nominal', 7500],
            ['Ibuprofen 400mg',   'Analgesik',   'Strip', 12000, 30, 'persen',  40],
            ['Oskadon',           'Analgesik',   'Strip',  3500, 50, 'nominal', 1500],
            ['Promag',            'Pencernaan',  'Strip',  8000, 40, 'persen',  30],
            ['Insto Regular',     'Mata',        'Botol', 15000, 20, 'nominal', 5000],
            ['Bodrex',            'Analgesik',   'Strip',  4000, 45, 'persen',  40],
            ['Betadine 15ml',     'Antiseptik',  'Botol', 12000, 20, 'persen',  35],
            ['Panadol Extra',     'Analgesik',   'Strip',  9500, 30, 'nominal', 3000],
            ['Decolgen',          'Batuk & Flu', 'Strip',  3500, 60, 'persen',  50],
        ];

        foreach ($produk as [$nama, $kategori, $satuan, $beli, $stok, $tipe, $margin]) {
            $kategoriModel = Kategori::firstOrCreate(['nama_kategori' => $kategori]);

            $obat = Obat::firstOrNew(['nama_obat' => $nama]);

            // Kode hanya dibuat untuk produk baru, produk lama tidak berubah kodenya
            if (! $obat->exists) {
                $obat->kode_obat = Obat::buatKode();
            }

            $obat->fill([
                'kategori_id' => $kategoriModel->id,
                'satuan'      => $satuan,
                'harga_beli'  => $beli,
                'margin'      => $margin,
                'tipe_margin' => $tipe,
                'harga_jual'  => Obat::hitungHargaJual($beli, $margin, $tipe),
                'stok'        => $stok,
                'status'      => 'tersedia',
            ])->save();
        }
    }
}