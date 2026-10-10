<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Obat;
use Illuminate\Database\Seeder;

class ObatSeeder extends Seeder
{
    /**
     * Contoh produk (sama dengan contoh pada desain antarmuka di laporan)
     * sehingga ketiga warna status stok langsung terlihat.
     */
    public function run(): void
    {
        $produk = [
            ['OBT-001', 'Paracetamol 500mg',  'Analgesik',   'Strip',  8000,   50, 'persen',  50],
            ['OBT-002', 'Amoxicillin 500mg',  'Antibiotik',  'Strip', 11000,   24, 'nominal', 5000],
            ['OBT-003', 'Cetirizine 10mg',    'Antialergi',  'Strip',  9000,   43, 'nominal', 3500],
            ['OBT-004', 'Vitamin C IPI',      'Vitamin',     'Botol',  7000,   25, 'persen',  30],
            ['OBT-005', 'Antasida DOEN',      'Pencernaan',  'Strip',  5000,   10, 'persen',  50],
            ['OBT-006', 'OBH Combi Dewasa',   'Batuk & Flu', 'Botol', 17000,    7, 'nominal', 7500],
        ];

        foreach ($produk as [$kode, $nama, $kategori, $satuan, $beli, $stok, $tipe, $margin]) {
            $kategoriModel = Kategori::firstOrCreate(['nama_kategori' => $kategori]);

            Obat::updateOrCreate(['kode_obat' => $kode], [
                'nama_obat'   => $nama,
                'kategori_id' => $kategoriModel->id,
                'satuan'      => $satuan,
                'harga_beli'  => $beli,
                'margin'      => $margin,
                'tipe_margin' => $tipe,
                'harga_jual'  => Obat::hitungHargaJual($beli, $margin, $tipe),
                'stok'        => $stok,
                'status'      => 'tersedia',
            ]);
        }
    }
}
