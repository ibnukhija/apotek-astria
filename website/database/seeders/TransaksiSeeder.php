<?php

namespace Database\Seeders;

use App\Models\DetailTransaksi;
use App\Models\Obat;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan user dan obat sudah tersedia
        $user = User::where('role', 'karyawan')->first() ?? User::first();
        if (! $user) {
            $this->call(UserSeeder::class);
            $user = User::where('role', 'karyawan')->first() ?? User::first();
        }

        if (Obat::count() === 0) {
            $this->call(ObatSeeder::class);
        }

        // Cache obat berdasarkan nama untuk mempercepat query
        $obatMap = Obat::all()->keyBy('nama_obat');
        $obatList = Obat::all();

        // Data transaksi simulasi dari beberapa hari terakhir hingga hari ini
        $transaksiList = [
            // --- HARI INI ---
            [
                'waktu' => Carbon::today()->addHours(8)->addMinutes(15),
                'items' => [
                    ['nama' => 'Paracetamol 500mg', 'qty' => 2],
                    ['nama' => 'Vitamin C IPI',     'qty' => 1],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->addHours(10)->addMinutes(30),
                'items' => [
                    ['nama' => 'Bodrex',   'qty' => 1],
                    ['nama' => 'Decolgen', 'qty' => 2],
                ],
                'nominal_bayar' => 20000,
            ],
            [
                'waktu' => Carbon::today()->addHours(13)->addMinutes(45),
                'items' => [
                    ['nama' => 'Amoxicillin 500mg', 'qty' => 1],
                    ['nama' => 'Cetirizine 10mg',   'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],
            [
                'waktu' => Carbon::today()->addHours(16)->addMinutes(20),
                'items' => [
                    ['nama' => 'Promag',        'qty' => 2],
                    ['nama' => 'Antasida DOEN', 'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],
            [
                'waktu' => Carbon::today()->addHours(19)->addMinutes(10),
                'items' => [
                    ['nama' => 'Panadol Extra', 'qty' => 1],
                    ['nama' => 'Insto Regular', 'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],

            // --- KEMARIN ---
            [
                'waktu' => Carbon::yesterday()->addHours(9)->addMinutes(5),
                'items' => [
                    ['nama' => 'OBH Combi Dewasa',  'qty' => 1],
                    ['nama' => 'Paracetamol 500mg', 'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(14)->addMinutes(20),
                'items' => [
                    ['nama' => 'Betadine 15ml', 'qty' => 1],
                    ['nama' => 'Oskadon',       'qty' => 2],
                ],
                'nominal_bayar' => 30000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(18)->addMinutes(50),
                'items' => [
                    ['nama' => 'Ibuprofen 400mg', 'qty' => 1],
                    ['nama' => 'Promag',          'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],

            // --- 2 HARI LALU ---
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(11)->addMinutes(15),
                'items' => [
                    ['nama' => 'Cetirizine 10mg', 'qty' => 2],
                    ['nama' => 'Bodrex',          'qty' => 1],
                ],
                'nominal_bayar' => 35000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(16)->addMinutes(40),
                'items' => [
                    ['nama' => 'Panadol Extra', 'qty' => 1],
                    ['nama' => 'Vitamin C IPI', 'qty' => 2],
                ],
                'nominal_bayar' => 35000,
            ],

            // --- 3 HARI LALU ---
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(10)->addMinutes(0),
                'items' => [
                    ['nama' => 'Amoxicillin 500mg', 'qty' => 2],
                    ['nama' => 'Decolgen',          'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(15)->addMinutes(30),
                'items' => [
                    ['nama' => 'Antasida DOEN', 'qty' => 2],
                    ['nama' => 'Promag',        'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],

            // --- 5 HARI LALU ---
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(13)->addMinutes(20),
                'items' => [
                    ['nama' => 'Insto Regular', 'qty' => 1],
                    ['nama' => 'Betadine 15ml', 'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],

            // --- 7 HARI LALU ---
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(9)->addMinutes(45),
                'items' => [
                    ['nama' => 'OBH Combi Dewasa',  'qty' => 1],
                    ['nama' => 'Paracetamol 500mg', 'qty' => 2],
                    ['nama' => 'Vitamin C IPI',     'qty' => 1],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(17)->addMinutes(10),
                'items' => [
                    ['nama' => 'Ibuprofen 400mg', 'qty' => 1],
                    ['nama' => 'Panadol Extra',   'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],
        ];

        foreach ($transaksiList as $index => $trxData) {
            $waktu = $trxData['waktu'];
            // Generate kode unik konsisten berdasarkan urutan dan tanggal
            $suffix = strtoupper(substr(md5($index . $waktu->timestamp), 0, 5));
            $noTransaksi = 'TRX-' . $waktu->format('Ymd') . '-' . $suffix;

            // Jika transaksi sudah ada, lewati
            if (Transaksi::where('no_transaksi', $noTransaksi)->exists()) {
                continue;
            }

            $totalHarga = 0;
            $detailList = [];

            foreach ($trxData['items'] as $item) {
                $obat = $obatMap->get($item['nama']) ?? $obatList->random();
                $hargaSatuan = (int) $obat->harga_jual;
                $qty = (int) $item['qty'];
                $subtotal = $hargaSatuan * $qty;
                $totalHarga += $subtotal;

                $detailList[] = [
                    'obat_id'      => $obat->id,
                    'qty'          => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'subtotal'     => $subtotal,
                ];
            }

            // Hitung total bayar dan kembalian
            $totalBayar = $trxData['nominal_bayar'] ?? $totalHarga;
            if ($totalBayar < $totalHarga) {
                // Bulatkan ke kelipatan 5.000 atau 10.000 di atas total harga
                $totalBayar = (int) ceil($totalHarga / 5000) * 5000;
            }
            $kembalian = $totalBayar - $totalHarga;

            // Buat transaksi
            $transaksi = Transaksi::create([
                'user_id'      => $user->id,
                'no_transaksi' => $noTransaksi,
                'total_harga'  => $totalHarga,
                'total_bayar'  => $totalBayar,
                'kembalian'    => $kembalian,
                'created_at'   => $waktu,
                'updated_at'   => $waktu,
            ]);

            // Buat detail transaksi
            foreach ($detailList as $detail) {
                $transaksi->details()->create([
                    'obat_id'      => $detail['obat_id'],
                    'qty'          => $detail['qty'],
                    'harga_satuan' => $detail['harga_satuan'],
                    'subtotal'     => $detail['subtotal'],
                    'created_at'   => $waktu,
                    'updated_at'   => $waktu,
                ]);
            }
        }
    }
}
