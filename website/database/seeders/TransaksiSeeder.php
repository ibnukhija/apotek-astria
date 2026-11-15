<?php

namespace Database\Seeders;

use App\Models\DetailTransaksi;
use App\Models\Obat;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        $obatList = Obat::all()->values();

        // Data transaksi simulasi dari beberapa hari terakhir hingga hari ini
        $transaksiList = [
            // =========================================================
            // --- HARI INI (25 Transaksi) ---
            // =========================================================
            [
                'waktu' => Carbon::today()->addHours(7)->addMinutes(15),
                'items' => [
                    ['nama' => 'Tolak Angin Cair Sachet',    'qty' => 4],
                    ['nama' => 'FreshCare Roll On Strong',   'qty' => 1],
                    ['nama' => 'Antimo 50mg Strip',          'qty' => 2],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->addHours(7)->addMinutes(45),
                'items' => [
                    ['nama' => 'Sanmol 500mg',            'qty' => 2],
                    ['nama' => 'Enervon-C Strip',         'qty' => 2],
                    ['nama' => 'Tolak Angin Cair Sachet', 'qty' => 3],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->addHours(8)->addMinutes(15),
                'items' => [
                    ['nama' => 'Paracetamol 500mg', 'qty' => 2],
                    ['nama' => 'Vitamin C IPI',     'qty' => 1],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->addHours(8)->addMinutes(40),
                'items' => [
                    ['nama' => 'Amlodipine 5mg',        'qty' => 3],
                    ['nama' => 'Candesartan 8mg',       'qty' => 2],
                    ['nama' => 'Atorvastatin 20mg',     'qty' => 2],
                    ['nama' => 'Aspilets Chewable 80mg','qty' => 1],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->addHours(9)->addMinutes(0),
                'items' => [
                    ['nama' => 'Oskadon', 'qty' => 3],
                    ['nama' => 'Promag',  'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],
            [
                'waktu' => Carbon::today()->addHours(9)->addMinutes(35),
                'items' => [
                    ['nama' => 'Mylanta Cair 50ml', 'qty' => 1],
                    ['nama' => 'Omeprazole 20mg',   'qty' => 2],
                    ['nama' => 'Domperidone 10mg',  'qty' => 1],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->addHours(10)->addMinutes(5),
                'items' => [
                    ['nama' => 'Tempra Drops 15ml',          'qty' => 1],
                    ['nama' => 'ByeBye Fever Bayi',          'qty' => 2],
                    ['nama' => 'Minyak Telon My Baby Plus 60ml', 'qty' => 1],
                ],
                'nominal_bayar' => 100000,
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
                'waktu' => Carbon::today()->addHours(10)->addMinutes(55),
                'items' => [
                    ['nama' => 'Prove D3-1000 IU', 'qty' => 2],
                    ['nama' => 'Zegavit',          'qty' => 2],
                    ['nama' => 'Vitalong C Strip', 'qty' => 2],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->addHours(11)->addMinutes(20),
                'items' => [
                    ['nama' => 'Ibuprofen 400mg', 'qty' => 2],
                    ['nama' => 'Betadine 15ml',   'qty' => 1],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->addHours(11)->addMinutes(50),
                'items' => [
                    ['nama' => 'Lacto-B Sachet',     'qty' => 4],
                    ['nama' => 'Zinc Sulfate 20mg',  'qty' => 1],
                    ['nama' => 'Oralit Sachet',      'qty' => 5],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->addHours(12)->addMinutes(10),
                'items' => [
                    ['nama' => 'Cefadroxil 500mg',     'qty' => 2],
                    ['nama' => 'Asam Mefenamat 500mg', 'qty' => 2],
                    ['nama' => 'Becom-C',              'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->addHours(12)->addMinutes(45),
                'items' => [
                    ['nama' => 'Ventolin Inhaler 100mcg', 'qty' => 1],
                    ['nama' => 'Salbutamol 4mg',          'qty' => 2],
                ],
                'nominal_bayar' => 200000,
            ],
            [
                'waktu' => Carbon::today()->addHours(13)->addMinutes(15),
                'items' => [
                    ['nama' => 'Daktarin Cream 5g',        'qty' => 1],
                    ['nama' => 'Caladine Powder Original 60g', 'qty' => 1],
                    ['nama' => 'Cetirizine 10mg',          'qty' => 1],
                ],
                'nominal_bayar' => 70000,
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
                'waktu' => Carbon::today()->addHours(14)->addMinutes(10),
                'items' => [
                    ['nama' => 'OBH Combi Dewasa',  'qty' => 1],
                    ['nama' => 'Panadol Extra',     'qty' => 2],
                    ['nama' => 'Vitamin C IPI',     'qty' => 1],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->addHours(14)->addMinutes(50),
                'items' => [
                    ['nama' => 'Cataflam 50mg',            'qty' => 1],
                    ['nama' => 'Clindamycin 300mg',        'qty' => 2],
                    ['nama' => 'Betadine Obat Kumur 190ml','qty' => 1],
                ],
                'nominal_bayar' => 160000,
            ],
            [
                'waktu' => Carbon::today()->addHours(15)->addMinutes(25),
                'items' => [
                    ['nama' => 'Rohto Cool',               'qty' => 1],
                    ['nama' => 'FreshCare Roll On Citrus', 'qty' => 2],
                    ['nama' => 'Salonpas Koyo 10s',        'qty' => 1],
                ],
                'nominal_bayar' => 70000,
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
                'waktu' => Carbon::today()->addHours(17)->addMinutes(0),
                'items' => [
                    ['nama' => 'Folavit 400mcg',                'qty' => 3],
                    ['nama' => 'Sangobion Kapsul',              'qty' => 2],
                    ['nama' => 'Blackmores Pregnancy & BF 60s', 'qty' => 1],
                ],
                'nominal_bayar' => 350000,
            ],
            [
                'waktu' => Carbon::today()->addHours(17)->addMinutes(30),
                'items' => [
                    ['nama' => 'Amoxicillin 500mg', 'qty' => 2],
                    ['nama' => 'Paracetamol 500mg', 'qty' => 1],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->addHours(18)->addMinutes(40),
                'items' => [
                    ['nama' => 'Sanmol Sirup 60ml',         'qty' => 1],
                    ['nama' => 'OBH Combi Anak Strawberry', 'qty' => 1],
                    ['nama' => 'ByeBye Fever Anak',         'qty' => 2],
                ],
                'nominal_bayar' => 75000,
            ],
            [
                'waktu' => Carbon::today()->addHours(19)->addMinutes(10),
                'items' => [
                    ['nama' => 'Panadol Extra', 'qty' => 1],
                    ['nama' => 'Insto Regular', 'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->addHours(19)->addMinutes(45),
                'items' => [
                    ['nama' => 'Tremenza',                  'qty' => 1],
                    ['nama' => 'Strepsils Cool Menthol 8s', 'qty' => 2],
                    ['nama' => 'Vicks Vaporub 25g',         'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->addHours(20)->addMinutes(15),
                'items' => [
                    ['nama' => 'Decolgen',      'qty' => 3],
                    ['nama' => 'Bodrex',        'qty' => 2],
                    ['nama' => 'Insto Regular', 'qty' => 1],
                ],
                'nominal_bayar' => 50000,
            ],

            // =========================================================
            // --- KEMARIN (20 Transaksi) ---
            // =========================================================
            [
                'waktu' => Carbon::yesterday()->addHours(7)->addMinutes(50),
                'items' => [
                    ['nama' => 'Glimepiride 2mg',  'qty' => 3],
                    ['nama' => 'Metformin 500mg',  'qty' => 4],
                    ['nama' => 'Mecobalamin 500mcg','qty' => 2],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(8)->addMinutes(30),
                'items' => [
                    ['nama' => 'Antasida DOEN',   'qty' => 2],
                    ['nama' => 'Cetirizine 10mg', 'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(9)->addMinutes(5),
                'items' => [
                    ['nama' => 'OBH Combi Dewasa',  'qty' => 1],
                    ['nama' => 'Paracetamol 500mg', 'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(9)->addMinutes(40),
                'items' => [
                    ['nama' => 'Inpepsa Suspensi 100ml', 'qty' => 1],
                    ['nama' => 'Pantoprazole 40mg',      'qty' => 2],
                    ['nama' => 'Ondansetron 4mg',        'qty' => 1],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(10)->addMinutes(15),
                'items' => [
                    ['nama' => 'Amlodipine 5mg',   'qty' => 3],
                    ['nama' => 'Simvastatin 10mg', 'qty' => 2],
                    ['nama' => 'Neurobion Putih',  'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(11)->addMinutes(0),
                'items' => [
                    ['nama' => 'Paracetamol 500mg', 'qty' => 3],
                    ['nama' => 'Vitamin C IPI',     'qty' => 2],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(11)->addMinutes(35),
                'items' => [
                    ['nama' => 'Silex Sirup 100ml',       'qty' => 1],
                    ['nama' => 'Sanmol Forte 650mg',      'qty' => 2],
                    ['nama' => 'Enervon-C Active Strip',  'qty' => 2],
                ],
                'nominal_bayar' => 120000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(12)->addMinutes(20),
                'items' => [
                    ['nama' => 'Diapet Kapsul', 'qty' => 2],
                    ['nama' => 'Oralit Sachet', 'qty' => 5],
                    ['nama' => 'Entrostop',     'qty' => 1],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(13)->addMinutes(15),
                'items' => [
                    ['nama' => 'Bodrex',        'qty' => 2],
                    ['nama' => 'Insto Regular', 'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(13)->addMinutes(50),
                'items' => [
                    ['nama' => 'Cendo Xitrol Minidose',  'qty' => 1],
                    ['nama' => 'CendoLyteers Minidose',  'qty' => 1],
                ],
                'nominal_bayar' => 70000,
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
                'waktu' => Carbon::yesterday()->addHours(15)->addMinutes(0),
                'items' => [
                    ['nama' => 'Mertigo 6mg',            'qty' => 1],
                    ['nama' => 'Flunarizine 5mg',        'qty' => 2],
                    ['nama' => 'Neurobion Forte Pink',   'qty' => 1],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(15)->addMinutes(40),
                'items' => [
                    ['nama' => 'Imboost Force',        'qty' => 1],
                    ['nama' => 'CDR Effervescent 10s', 'qty' => 1],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(16)->addMinutes(45),
                'items' => [
                    ['nama' => 'Amoxicillin 500mg', 'qty' => 1],
                    ['nama' => 'Ibuprofen 400mg',   'qty' => 1],
                    ['nama' => 'Decolgen',          'qty' => 2],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(17)->addMinutes(25),
                'items' => [
                    ['nama' => 'Allopurinol 300mg', 'qty' => 2],
                    ['nama' => 'Meloxicam 15mg',    'qty' => 2],
                    ['nama' => 'Hotin DCL Emulgel 30g', 'qty' => 1],
                ],
                'nominal_bayar' => 80000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(18)->addMinutes(10),
                'items' => [
                    ['nama' => 'Imboost Kids Sirup 60ml',     'qty' => 1],
                    ['nama' => 'Sakatonik ABC Strawberry 30s','qty' => 1],
                    ['nama' => 'Komix Herbal Original Tube',  'qty' => 2],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(18)->addMinutes(50),
                'items' => [
                    ['nama' => 'Ibuprofen 400mg', 'qty' => 1],
                    ['nama' => 'Promag',          'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(19)->addMinutes(30),
                'items' => [
                    ['nama' => 'Woods Peppermint Antituss', 'qty' => 1],
                    ['nama' => 'Degirol Hisap',             'qty' => 2],
                    ['nama' => 'FG Troches Meiji',          'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(20)->addMinutes(0),
                'items' => [
                    ['nama' => 'OBH Combi Dewasa', 'qty' => 2],
                    ['nama' => 'Panadol Extra',    'qty' => 1],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::yesterday()->addHours(20)->addMinutes(35),
                'items' => [
                    ['nama' => 'Plossa Eucalyptus Roll On',  'qty' => 2],
                    ['nama' => 'Adem Sari Ching Ku Sachet',  'qty' => 4],
                    ['nama' => 'Strepsils Original 8s',      'qty' => 1],
                ],
                'nominal_bayar' => 75000,
            ],

            // =========================================================
            // --- 2 HARI LALU (15 Transaksi) ---
            // =========================================================
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(8)->addMinutes(20),
                'items' => [
                    ['nama' => 'Metformin 500mg',   'qty' => 3],
                    ['nama' => 'Glimepiride 2mg',   'qty' => 2],
                    ['nama' => 'Allopurinol 100mg', 'qty' => 2],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(9)->addMinutes(15),
                'items' => [
                    ['nama' => 'Promag',        'qty' => 2],
                    ['nama' => 'Antasida DOEN', 'qty' => 2],
                ],
                'nominal_bayar' => 35000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(9)->addMinutes(55),
                'items' => [
                    ['nama' => 'Azithromycin 500mg',      'qty' => 1],
                    ['nama' => 'Acetylcysteine 200mg',    'qty' => 2],
                    ['nama' => 'Prove D3-5000 IU',        'qty' => 1],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(10)->addMinutes(40),
                'items' => [
                    ['nama' => 'Minyak Kayu Putih Cap Lang 60ml', 'qty' => 1],
                    ['nama' => 'Tolak Angin Cair Sachet',         'qty' => 4],
                    ['nama' => 'Antangin JRG Cair',               'qty' => 2],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(11)->addMinutes(15),
                'items' => [
                    ['nama' => 'Cetirizine 10mg', 'qty' => 2],
                    ['nama' => 'Bodrex',          'qty' => 1],
                ],
                'nominal_bayar' => 35000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(12)->addMinutes(0),
                'items' => [
                    ['nama' => 'Canesten Cream 5g',           'qty' => 1],
                    ['nama' => 'Ketoconazole Tablet 200mg',   'qty' => 2],
                    ['nama' => 'Incidal-OD 10mg',             'qty' => 1],
                ],
                'nominal_bayar' => 80000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(12)->addMinutes(45),
                'items' => [
                    ['nama' => 'Betadine 15ml', 'qty' => 1],
                    ['nama' => 'Oskadon',       'qty' => 2],
                ],
                'nominal_bayar' => 30000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(13)->addMinutes(30),
                'items' => [
                    ['nama' => 'Rhinos SR',               'qty' => 1],
                    ['nama' => 'Sanmol Forte 650mg',      'qty' => 2],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(14)->addMinutes(10),
                'items' => [
                    ['nama' => 'Alkohol 70% OneMed 100ml', 'qty' => 1],
                    ['nama' => 'Rivanol 100ml',            'qty' => 1],
                    ['nama' => 'Hansaplast Kain Elastis',  'qty' => 3],
                    ['nama' => 'Bioplacenton Gel 15g',     'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(15)->addMinutes(30),
                'items' => [
                    ['nama' => 'Cetirizine 10mg',   'qty' => 2],
                    ['nama' => 'Amoxicillin 500mg', 'qty' => 1],
                ],
                'nominal_bayar' => 45000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(16)->addMinutes(40),
                'items' => [
                    ['nama' => 'Panadol Extra', 'qty' => 1],
                    ['nama' => 'Vitamin C IPI', 'qty' => 2],
                ],
                'nominal_bayar' => 35000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(17)->addMinutes(30),
                'items' => [
                    ['nama' => 'Stimuno Forte Kapsul',      'qty' => 1],
                    ['nama' => 'Stimuno Sirup Anggur 60ml', 'qty' => 1],
                    ['nama' => 'Apialys Sirup 100ml',       'qty' => 1],
                ],
                'nominal_bayar' => 120000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(18)->addMinutes(20),
                'items' => [
                    ['nama' => 'Ibuprofen 400mg', 'qty' => 1],
                    ['nama' => 'Decolgen',        'qty' => 2],
                ],
                'nominal_bayar' => 35000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(19)->addMinutes(10),
                'items' => [
                    ['nama' => 'Bisoprolol 5mg',      'qty' => 2],
                    ['nama' => 'Clopidogrel 75mg',    'qty' => 2],
                    ['nama' => 'Furosemide 40mg',     'qty' => 2],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(2)->addHours(19)->addMinutes(50),
                'items' => [
                    ['nama' => 'Counterpain Cream 15g', 'qty' => 1],
                    ['nama' => 'Neuralgin RX',          'qty' => 2],
                ],
                'nominal_bayar' => 60000,
            ],

            // =========================================================
            // --- 3 HARI LALU (15 Transaksi) ---
            // =========================================================
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(8)->addMinutes(10),
                'items' => [
                    ['nama' => 'Captopril 25mg',          'qty' => 3],
                    ['nama' => 'HCT / Hydrochlorothiazide 25mg', 'qty' => 2],
                    ['nama' => 'Simvastatin 10mg',        'qty' => 2],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(8)->addMinutes(45),
                'items' => [
                    ['nama' => 'Vitamin C IPI',     'qty' => 2],
                    ['nama' => 'Paracetamol 500mg', 'qty' => 2],
                ],
                'nominal_bayar' => 45000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(10)->addMinutes(0),
                'items' => [
                    ['nama' => 'Amoxicillin 500mg', 'qty' => 2],
                    ['nama' => 'Decolgen',          'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(10)->addMinutes(50),
                'items' => [
                    ['nama' => 'Baquinor Forte 500mg', 'qty' => 1],
                    ['nama' => 'Buscopan 10mg',        'qty' => 1],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(11)->addMinutes(30),
                'items' => [
                    ['nama' => 'OBH Combi Dewasa', 'qty' => 1],
                    ['nama' => 'Bodrex',           'qty' => 2],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(12)->addMinutes(50),
                'items' => [
                    ['nama' => 'Polysilane Suspensi 100ml', 'qty' => 1],
                    ['nama' => 'Lansoprazole 30mg',         'qty' => 2],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(14)->addMinutes(0),
                'items' => [
                    ['nama' => 'Insto Regular', 'qty' => 2],
                    ['nama' => 'Panadol Extra', 'qty' => 1],
                ],
                'nominal_bayar' => 55000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(14)->addMinutes(45),
                'items' => [
                    ['nama' => 'Thrombophob Gel 20g',      'qty' => 1],
                    ['nama' => 'Hansaplast Aqua Protect',  'qty' => 2],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(15)->addMinutes(30),
                'items' => [
                    ['nama' => 'Antasida DOEN', 'qty' => 2],
                    ['nama' => 'Promag',        'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(16)->addMinutes(20),
                'items' => [
                    ['nama' => 'Sangobion Kapsul',          'qty' => 2],
                    ['nama' => 'Holisticare Ester C Strip', 'qty' => 2],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(17)->addMinutes(15),
                'items' => [
                    ['nama' => 'Cetirizine 10mg', 'qty' => 1],
                    ['nama' => 'Promag',          'qty' => 2],
                ],
                'nominal_bayar' => 35000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(18)->addMinutes(0),
                'items' => [
                    ['nama' => 'Vicks Formula 44 Dewasa 54ml', 'qty' => 1],
                    ['nama' => 'Vicks Formula 44 Anak 54ml',   'qty' => 1],
                    ['nama' => 'Panadol Anak Chewable',        'qty' => 1],
                ],
                'nominal_bayar' => 75000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(18)->addMinutes(40),
                'items' => [
                    ['nama' => 'Ciprofloxacin 500mg', 'qty' => 2],
                    ['nama' => 'Mefinal 500mg',       'qty' => 1],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(19)->addMinutes(25),
                'items' => [
                    ['nama' => 'Natur-E 100 IU 16s',  'qty' => 2],
                    ['nama' => 'Ever E 250 IU Strip', 'qty' => 1],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->subDays(3)->addHours(20)->addMinutes(5),
                'items' => [
                    ['nama' => 'Neozep Forte',  'qty' => 2],
                    ['nama' => 'Ambroxol 30mg', 'qty' => 2],
                    ['nama' => 'Becom-Zet',     'qty' => 1],
                ],
                'nominal_bayar' => 60000,
            ],

            // =========================================================
            // --- 4 HARI LALU (12 Transaksi) ---
            // =========================================================
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(8)->addMinutes(0),
                'items' => [
                    ['nama' => 'Puyer Bintang Toedjoe No.16', 'qty' => 2],
                    ['nama' => 'Balsem Otot Geliga 20g',      'qty' => 1],
                    ['nama' => 'Koyo Cabe Strip',             'qty' => 2],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(8)->addMinutes(30),
                'items' => [
                    ['nama' => 'Mixagrip Flu & Batuk', 'qty' => 3],
                    ['nama' => 'Enervon-C Strip',      'qty' => 2],
                ],
                'nominal_bayar' => 35000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(9)->addMinutes(30),
                'items' => [
                    ['nama' => 'Paracetamol 500mg', 'qty' => 2],
                    ['nama' => 'Decolgen',          'qty' => 2],
                ],
                'nominal_bayar' => 35000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(10)->addMinutes(20),
                'items' => [
                    ['nama' => 'Amoxan Dry Syrup 60ml',     'qty' => 1],
                    ['nama' => 'Proris Forte Sirup 50ml',   'qty' => 1],
                    ['nama' => 'Cetirizine Sirup 60ml',     'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(11)->addMinutes(10),
                'items' => [
                    ['nama' => 'Tempra Sirup Anak 60ml',  'qty' => 1],
                    ['nama' => 'Curcuma Plus Sirup 60ml', 'qty' => 1],
                ],
                'nominal_bayar' => 75000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(12)->addMinutes(0),
                'items' => [
                    ['nama' => 'Amoxicillin 500mg', 'qty' => 2],
                    ['nama' => 'Vitamin C IPI',     'qty' => 1],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(13)->addMinutes(15),
                'items' => [
                    ['nama' => 'Sucralfate Suspensi 100ml', 'qty' => 1],
                    ['nama' => 'Promag Double Action',      'qty' => 2],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(14)->addMinutes(15),
                'items' => [
                    ['nama' => 'Cendo Lyteers 15ml', 'qty' => 1],
                    ['nama' => 'Insto Dry Eyes',     'qty' => 1],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(15)->addMinutes(10),
                'items' => [
                    ['nama' => 'Betadine 15ml',   'qty' => 1],
                    ['nama' => 'Ibuprofen 400mg', 'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(16)->addMinutes(30),
                'items' => [
                    ['nama' => 'Pharmaton Formula Strip', 'qty' => 2],
                    ['nama' => 'Hemaviton Stamina Plus',  'qty' => 3],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(17)->addMinutes(20),
                'items' => [
                    ['nama' => 'Ketoconazole Cream 2%',     'qty' => 1],
                    ['nama' => 'Hydrocortisone Cream 2.5%', 'qty' => 1],
                    ['nama' => 'Cetirizine 10mg',           'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(4)->addHours(18)->addMinutes(40),
                'items' => [
                    ['nama' => 'Antasida DOEN', 'qty' => 3],
                    ['nama' => 'Bodrex',        'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],

            // =========================================================
            // --- 5 HARI LALU (12 Transaksi) ---
            // =========================================================
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(8)->addMinutes(10),
                'items' => [
                    ['nama' => 'Valsartan 80mg',   'qty' => 2],
                    ['nama' => 'Fenofibrate 100mg','qty' => 2],
                    ['nama' => 'Acarbose 50mg',    'qty' => 2],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(8)->addMinutes(50),
                'items' => [
                    ['nama' => 'Panadol Biru 500mg', 'qty' => 2],
                    ['nama' => 'Demacolin',          'qty' => 2],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(10)->addMinutes(15),
                'items' => [
                    ['nama' => 'Panadol Extra', 'qty' => 2],
                    ['nama' => 'Oskadon',       'qty' => 2],
                ],
                'nominal_bayar' => 35000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(11)->addMinutes(0),
                'items' => [
                    ['nama' => 'Aciclovir 400mg Tablet', 'qty' => 2],
                    ['nama' => 'Aciclovir Cream 5%',     'qty' => 2],
                    ['nama' => 'Imboost Regular',        'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(11)->addMinutes(40),
                'items' => [
                    ['nama' => 'Actifed Plus Cough Suppr', 'qty' => 1],
                    ['nama' => 'Imboost Regular',          'qty' => 1],
                ],
                'nominal_bayar' => 120000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(13)->addMinutes(20),
                'items' => [
                    ['nama' => 'Insto Regular', 'qty' => 1],
                    ['nama' => 'Betadine 15ml', 'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(14)->addMinutes(45),
                'items' => [
                    ['nama' => 'OBH Combi Dewasa',  'qty' => 1],
                    ['nama' => 'Amoxicillin 500mg', 'qty' => 1],
                ],
                'nominal_bayar' => 45000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(15)->addMinutes(30),
                'items' => [
                    ['nama' => 'Voltaren 50mg',           'qty' => 1],
                    ['nama' => 'Counterpain Cream 30g',   'qty' => 1],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(16)->addMinutes(10),
                'items' => [
                    ['nama' => 'Dulcolax 5mg', 'qty' => 2],
                    ['nama' => 'Microlax Gel', 'qty' => 1],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(17)->addMinutes(15),
                'items' => [
                    ['nama' => 'Lasal Sirup 100ml', 'qty' => 1],
                    ['nama' => 'Lasal Kapsul 2mg',  'qty' => 1],
                ],
                'nominal_bayar' => 80000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(17)->addMinutes(55),
                'items' => [
                    ['nama' => 'Incidal-OD 10mg',        'qty' => 1],
                    ['nama' => 'Methylprednisolone 4mg', 'qty' => 2],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(5)->addHours(19)->addMinutes(0),
                'items' => [
                    ['nama' => 'Cetirizine 10mg', 'qty' => 2],
                    ['nama' => 'Vitamin C IPI',   'qty' => 1],
                ],
                'nominal_bayar' => 40000,
            ],

            // =========================================================
            // --- 6 HARI LALU (12 Transaksi) ---
            // =========================================================
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(8)->addMinutes(20),
                'items' => [
                    ['nama' => 'Enervon-C Botol 30s',  'qty' => 1],
                    ['nama' => 'Neurobion Forte Pink', 'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(9)->addMinutes(0),
                'items' => [
                    ['nama' => 'Paracetamol 500mg', 'qty' => 2],
                    ['nama' => 'Promag',            'qty' => 2],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(10)->addMinutes(10),
                'items' => [
                    ['nama' => 'Dettol Antiseptik 245ml',     'qty' => 1],
                    ['nama' => 'Alkohol 70% OneMed 300ml',    'qty' => 1],
                    ['nama' => 'Alkohol Swab OneMed Strip',   'qty' => 5],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(11)->addMinutes(15),
                'items' => [
                    ['nama' => 'Cefixime 100mg',         'qty' => 2],
                    ['nama' => 'Siladex Mucolytic 60ml', 'qty' => 1],
                ],
                'nominal_bayar' => 75000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(12)->addMinutes(30),
                'items' => [
                    ['nama' => 'Vometa Sirup 60ml',          'qty' => 1],
                    ['nama' => 'Sanmag Suspensi 120ml',      'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(13)->addMinutes(30),
                'items' => [
                    ['nama' => 'Insto Regular', 'qty' => 1],
                    ['nama' => 'Decolgen',      'qty' => 2],
                ],
                'nominal_bayar' => 35000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(15)->addMinutes(0),
                'items' => [
                    ['nama' => 'Feminax',       'qty' => 2],
                    ['nama' => 'Ponstan 500mg', 'qty' => 1],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(16)->addMinutes(0),
                'items' => [
                    ['nama' => 'Hi-Bone Active',   'qty' => 2],
                    ['nama' => 'Prove D3-1000 IU', 'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(16)->addMinutes(50),
                'items' => [
                    ['nama' => 'Ibuprofen 400mg', 'qty' => 2],
                    ['nama' => 'Betadine 15ml',   'qty' => 1],
                ],
                'nominal_bayar' => 55000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(17)->addMinutes(40),
                'items' => [
                    ['nama' => 'Scabimite Cream 10g',  'qty' => 1],
                    ['nama' => 'Caladine Lotion 60ml', 'qty' => 1],
                    ['nama' => 'Alloris 10mg',         'qty' => 1],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(18)->addMinutes(25),
                'items' => [
                    ['nama' => 'Betadine Obat Kumur 190ml', 'qty' => 1],
                    ['nama' => 'Degirol Hisap',             'qty' => 2],
                ],
                'nominal_bayar' => 75000,
            ],
            [
                'waktu' => Carbon::today()->subDays(6)->addHours(19)->addMinutes(40),
                'items' => [
                    ['nama' => 'Safe Care Aromatherapy', 'qty' => 1],
                    ['nama' => 'Hotin Cream 60g',        'qty' => 1],
                    ['nama' => 'Paramex',                'qty' => 2],
                ],
                'nominal_bayar' => 60000,
            ],

            // =========================================================
            // --- 7 HARI LALU (12 Transaksi) ---
            // =========================================================
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(8)->addMinutes(30),
                'items' => [
                    ['nama' => 'Redoxon Triple Action 10s', 'qty' => 1],
                    ['nama' => 'Caviplex',                  'qty' => 2],
                ],
                'nominal_bayar' => 80000,
            ],
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
                'waktu' => Carbon::today()->subDays(7)->addHours(10)->addMinutes(30),
                'items' => [
                    ['nama' => 'Sanprima Forte',      'qty' => 1],
                    ['nama' => 'Paracetamol 500mg',   'qty' => 2],
                    ['nama' => 'Vitamin B Complex IPI','qty' => 1],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(11)->addMinutes(10),
                'items' => [
                    ['nama' => 'Antasida DOEN',   'qty' => 2],
                    ['nama' => 'Cetirizine 10mg', 'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(12)->addMinutes(15),
                'items' => [
                    ['nama' => 'Flagyl Forte 500mg',       'qty' => 1],
                    ['nama' => 'Norit Activated Carbon 40s','qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(13)->addMinutes(5),
                'items' => [
                    ['nama' => 'New Diatabs',    'qty' => 3],
                    ['nama' => 'Loperamide 2mg', 'qty' => 1],
                    ['nama' => 'Oralit Sachet',  'qty' => 4],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(14)->addMinutes(20),
                'items' => [
                    ['nama' => 'OBH Nellco Special 100ml', 'qty' => 1],
                    ['nama' => 'Ultraflu Extra',           'qty' => 2],
                    ['nama' => 'Strepsils Original 8s',    'qty' => 1],
                ],
                'nominal_bayar' => 75000,
            ],
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(15)->addMinutes(20),
                'items' => [
                    ['nama' => 'Bodrex',        'qty' => 3],
                    ['nama' => 'Vitamin C IPI', 'qty' => 2],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(16)->addMinutes(15),
                'items' => [
                    ['nama' => 'Cendo Catarlent 15ml', 'qty' => 1],
                    ['nama' => 'Rohto Dry Fresh',      'qty' => 1],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(17)->addMinutes(10),
                'items' => [
                    ['nama' => 'Ibuprofen 400mg', 'qty' => 1],
                    ['nama' => 'Panadol Extra',   'qty' => 1],
                ],
                'nominal_bayar' => 30000,
            ],
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(18)->addMinutes(35),
                'items' => [
                    ['nama' => 'Scott Emulsion Vita 200ml', 'qty' => 1],
                    ['nama' => 'Sanmol Sirup 60ml',         'qty' => 1],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->subDays(7)->addHours(20)->addMinutes(0),
                'items' => [
                    ['nama' => 'Amlodipine 10mg',  'qty' => 2],
                    ['nama' => 'Simvastatin 20mg', 'qty' => 2],
                ],
                'nominal_bayar' => 50000,
            ],

            // =========================================================
            // --- 8 HARI LALU (10 Transaksi) ---
            // =========================================================
            [
                'waktu' => Carbon::today()->subDays(8)->addHours(8)->addMinutes(15),
                'items' => [
                    ['nama' => 'Sanmol 500mg',             'qty' => 2],
                    ['nama' => 'Enervon-C Strip',          'qty' => 2],
                    ['nama' => 'Tolak Angin Cair Sachet',  'qty' => 3],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(8)->addHours(9)->addMinutes(30),
                'items' => [
                    ['nama' => 'Amoxan 500mg',         'qty' => 1],
                    ['nama' => 'Asam Mefenamat 500mg', 'qty' => 2],
                    ['nama' => 'Lansoprazole 30mg',    'qty' => 1],
                ],
                'nominal_bayar' => 80000,
            ],
            [
                'waktu' => Carbon::today()->subDays(8)->addHours(10)->addMinutes(45),
                'items' => [
                    ['nama' => 'OBH Combi Plus Menthol 100ml', 'qty' => 1],
                    ['nama' => 'Decolsin Kapsul',              'qty' => 2],
                    ['nama' => 'Strepsils Cool Menthol 8s',    'qty' => 1],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(8)->addHours(12)->addMinutes(10),
                'items' => [
                    ['nama' => 'Promag Double Action', 'qty' => 2],
                    ['nama' => 'Mylanta Cair 150ml',   'qty' => 1],
                ],
                'nominal_bayar' => 80000,
            ],
            [
                'waktu' => Carbon::today()->subDays(8)->addHours(13)->addMinutes(35),
                'items' => [
                    ['nama' => 'Prove D3-1000 IU',  'qty' => 2],
                    ['nama' => 'Zegavit',           'qty' => 1],
                    ['nama' => 'Vitalong C Strip',  'qty' => 2],
                ],
                'nominal_bayar' => 120000,
            ],
            [
                'waktu' => Carbon::today()->subDays(8)->addHours(15)->addMinutes(0),
                'items' => [
                    ['nama' => 'Rohto V-Extra',          'qty' => 1],
                    ['nama' => 'Insto Cool',             'qty' => 1],
                    ['nama' => 'Panadol Biru 500mg',     'qty' => 1],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(8)->addHours(16)->addMinutes(20),
                'items' => [
                    ['nama' => 'Candesartan 16mg',  'qty' => 2],
                    ['nama' => 'Bisoprolol 5mg',    'qty' => 2],
                    ['nama' => 'Atorvastatin 20mg', 'qty' => 2],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->subDays(8)->addHours(17)->addMinutes(40),
                'items' => [
                    ['nama' => 'Benoson-N Cream 5g',    'qty' => 1],
                    ['nama' => 'Cetirizine 10mg',       'qty' => 2],
                    ['nama' => 'Herocyn Bedak Kulit 85g', 'qty' => 1],
                ],
                'nominal_bayar' => 75000,
            ],
            [
                'waktu' => Carbon::today()->subDays(8)->addHours(19)->addMinutes(5),
                'items' => [
                    ['nama' => 'Tempra Forte 60ml',          'qty' => 1],
                    ['nama' => 'Bisolvon Kids 60ml',         'qty' => 1],
                    ['nama' => 'ByeBye Fever Anak',          'qty' => 2],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->subDays(8)->addHours(20)->addMinutes(15),
                'items' => [
                    ['nama' => 'FreshCare Roll On Lavender', 'qty' => 2],
                    ['nama' => 'Salonpas Hot Koyo',          'qty' => 2],
                    ['nama' => 'Paramex',                    'qty' => 2],
                ],
                'nominal_bayar' => 60000,
            ],

            // =========================================================
            // --- 9 HARI LALU (10 Transaksi) ---
            // =========================================================
            [
                'waktu' => Carbon::today()->subDays(9)->addHours(8)->addMinutes(10),
                'items' => [
                    ['nama' => 'Metformin 500mg',    'qty' => 4],
                    ['nama' => 'Glimepiride 3mg',    'qty' => 2],
                    ['nama' => 'Neurobion Putih',    'qty' => 2],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(9)->addHours(9)->addMinutes(25),
                'items' => [
                    ['nama' => 'Cefixime 200mg',       'qty' => 1],
                    ['nama' => 'Spedifen 400mg',       'qty' => 1],
                    ['nama' => 'Omeprazole 20mg',      'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(9)->addHours(10)->addMinutes(50),
                'items' => [
                    ['nama' => 'Actifed Kuning 60ml',  'qty' => 1],
                    ['nama' => 'FG Troches Meiji',     'qty' => 2],
                ],
                'nominal_bayar' => 120000,
            ],
            [
                'waktu' => Carbon::today()->subDays(9)->addHours(12)->addMinutes(15),
                'items' => [
                    ['nama' => 'Diapet Kapsul',  'qty' => 2],
                    ['nama' => 'Loperamide 2mg', 'qty' => 2],
                    ['nama' => 'Oralit Sachet',  'qty' => 6],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(9)->addHours(13)->addMinutes(40),
                'items' => [
                    ['nama' => 'Holisticare Ester C Botol 30s', 'qty' => 1],
                    ['nama' => 'Becom-Zet',                     'qty' => 2],
                ],
                'nominal_bayar' => 120000,
            ],
            [
                'waktu' => Carbon::today()->subDays(9)->addHours(15)->addMinutes(5),
                'items' => [
                    ['nama' => 'Betadine 60ml',             'qty' => 1],
                    ['nama' => 'Hansaplast Rol Kain 1.25cm','qty' => 2],
                    ['nama' => 'Rivanol 300ml',             'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(9)->addHours(16)->addMinutes(30),
                'items' => [
                    ['nama' => 'Minyak GPU Cap Lang 60ml', 'qty' => 1],
                    ['nama' => 'Hotin Cream 60g',          'qty' => 1],
                    ['nama' => 'Meloxicam 15mg',           'qty' => 2],
                ],
                'nominal_bayar' => 75000,
            ],
            [
                'waktu' => Carbon::today()->subDays(9)->addHours(17)->addMinutes(55),
                'items' => [
                    ['nama' => 'Rhinofed',              'qty' => 1],
                    ['nama' => 'Acetylcysteine 200mg',  'qty' => 2],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->subDays(9)->addHours(19)->addMinutes(10),
                'items' => [
                    ['nama' => 'Biolysin Sirup 60ml',         'qty' => 2],
                    ['nama' => 'Sanmol Drops 15ml',           'qty' => 1],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->subDays(9)->addHours(20)->addMinutes(20),
                'items' => [
                    ['nama' => 'Procold Flu & Batuk', 'qty' => 3],
                    ['nama' => 'Saridon',             'qty' => 2],
                    ['nama' => 'Adem Sari Ching Ku Sachet', 'qty' => 4],
                ],
                'nominal_bayar' => 50000,
            ],

            // =========================================================
            // --- 10 s/d 14 HARI LALU (25 Transaksi Eksplisit) ---
            // =========================================================
            [
                'waktu' => Carbon::today()->subDays(10)->addHours(8)->addMinutes(30),
                'items' => [
                    ['nama' => 'Paracetamol 500mg', 'qty' => 3],
                    ['nama' => 'Ambroxol 30mg',     'qty' => 2],
                    ['nama' => 'Enervon-C Strip',   'qty' => 2],
                ],
                'nominal_bayar' => 65000,
            ],
            [
                'waktu' => Carbon::today()->subDays(10)->addHours(11)->addMinutes(15),
                'items' => [
                    ['nama' => 'Amlodipine 10mg',  'qty' => 3],
                    ['nama' => 'Simvastatin 20mg', 'qty' => 2],
                    ['nama' => 'Allopurinol 100mg','qty' => 2],
                ],
                'nominal_bayar' => 75000,
            ],
            [
                'waktu' => Carbon::today()->subDays(10)->addHours(14)->addMinutes(20),
                'items' => [
                    ['nama' => 'Polysilane Suspensi 100ml', 'qty' => 1],
                    ['nama' => 'Omeprazole 20mg',           'qty' => 2],
                    ['nama' => 'Domperidone 10mg',          'qty' => 2],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->subDays(10)->addHours(17)->addMinutes(10),
                'items' => [
                    ['nama' => 'Doxycycline 100mg',         'qty' => 2],
                    ['nama' => 'Gentamicin Salep Kulit 0.1%', 'qty' => 2],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(10)->addHours(19)->addMinutes(40),
                'items' => [
                    ['nama' => 'OBH Combi Dewasa',  'qty' => 1],
                    ['nama' => 'Panadol Extra',     'qty' => 2],
                    ['nama' => 'Tolak Angin Cair Sachet', 'qty' => 4],
                ],
                'nominal_bayar' => 80000,
            ],
            [
                'waktu' => Carbon::today()->subDays(11)->addHours(9)->addMinutes(0),
                'items' => [
                    ['nama' => 'Cefadroxil Sirup Kering 60ml', 'qty' => 1],
                    ['nama' => 'Sanmol Sirup 60ml',            'qty' => 1],
                    ['nama' => 'Curcuma Plus Sirup 60ml',      'qty' => 1],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->subDays(11)->addHours(11)->addMinutes(45),
                'items' => [
                    ['nama' => 'Celebrex 200mg',        'qty' => 1],
                    ['nama' => 'Neurobion Forte Pink',  'qty' => 1],
                ],
                'nominal_bayar' => 200000,
            ],
            [
                'waktu' => Carbon::today()->subDays(11)->addHours(14)->addMinutes(30),
                'items' => [
                    ['nama' => 'Claritin 10mg',        'qty' => 1],
                    ['nama' => 'Caladine Lotion 60ml', 'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(11)->addHours(17)->addMinutes(15),
                'items' => [
                    ['nama' => 'Antasida DOEN Suspensi 60ml', 'qty' => 2],
                    ['nama' => 'Ranitidine 150mg',            'qty' => 2],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(11)->addHours(19)->addMinutes(50),
                'items' => [
                    ['nama' => 'Woods Peppermint Expectorant', 'qty' => 1],
                    ['nama' => 'Ultraflu',                     'qty' => 2],
                    ['nama' => 'Becom-C',                      'qty' => 1],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(12)->addHours(8)->addMinutes(40),
                'items' => [
                    ['nama' => 'Captopril 12.5mg',       'qty' => 3],
                    ['nama' => 'Gemfibrozil 300mg',      'qty' => 2],
                    ['nama' => 'Glibenclamide 5mg',      'qty' => 2],
                ],
                'nominal_bayar' => 60000,
            ],
            [
                'waktu' => Carbon::today()->subDays(12)->addHours(11)->addMinutes(20),
                'items' => [
                    ['nama' => 'Erythromycin 500mg', 'qty' => 2],
                    ['nama' => 'Demacolin',          'qty' => 2],
                    ['nama' => 'Degirol Hisap',      'qty' => 1],
                ],
                'nominal_bayar' => 80000,
            ],
            [
                'waktu' => Carbon::today()->subDays(12)->addHours(14)->addMinutes(10),
                'items' => [
                    ['nama' => 'Braito Original 5ml', 'qty' => 2],
                    ['nama' => 'Visine Original',     'qty' => 1],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(12)->addHours(16)->addMinutes(55),
                'items' => [
                    ['nama' => 'Povidone Iodine OneMed 30ml', 'qty' => 2],
                    ['nama' => 'Hansaplast Kain Elastis',     'qty' => 4],
                    ['nama' => 'Asam Mefenamat 500mg',        'qty' => 2],
                ],
                'nominal_bayar' => 70000,
            ],
            [
                'waktu' => Carbon::today()->subDays(12)->addHours(19)->addMinutes(30),
                'items' => [
                    ['nama' => 'Bisolvon Extra 60ml', 'qty' => 1],
                    ['nama' => 'Intunal Forte',       'qty' => 2],
                ],
                'nominal_bayar' => 80000,
            ],
            [
                'waktu' => Carbon::today()->subDays(13)->addHours(8)->addMinutes(25),
                'items' => [
                    ['nama' => 'Thiamphenicol 500mg', 'qty' => 2],
                    ['nama' => 'Sanmol Forte 650mg',  'qty' => 2],
                    ['nama' => 'Imboost Regular',     'qty' => 1],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(13)->addHours(11)->addMinutes(5),
                'items' => [
                    ['nama' => 'Cohistan Expectorant 60ml', 'qty' => 1],
                    ['nama' => 'Sanaflu Plus Batuk',        'qty' => 2],
                ],
                'nominal_bayar' => 50000,
            ],
            [
                'waktu' => Carbon::today()->subDays(13)->addHours(13)->addMinutes(50),
                'items' => [
                    ['nama' => 'Polyca / Plantacid Forte', 'qty' => 2],
                    ['nama' => 'Metoclopramide 10mg',      'qty' => 2],
                ],
                'nominal_bayar' => 40000,
            ],
            [
                'waktu' => Carbon::today()->subDays(13)->addHours(16)->addMinutes(40),
                'items' => [
                    ['nama' => 'Cinogenta Cream 10g',     'qty' => 1],
                    ['nama' => 'Methylprednisolone 8mg',  'qty' => 2],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(13)->addHours(19)->addMinutes(15),
                'items' => [
                    ['nama' => 'Listerine Cool Mint 100ml',     'qty' => 2],
                    ['nama' => 'Albothyl / Policresulen 5ml',   'qty' => 1],
                    ['nama' => 'Gom / Borax Glycerine 8ml',     'qty' => 2],
                ],
                'nominal_bayar' => 100000,
            ],
            [
                'waktu' => Carbon::today()->subDays(14)->addHours(8)->addMinutes(15),
                'items' => [
                    ['nama' => 'Theragran-M',      'qty' => 2],
                    ['nama' => 'Fatigon Putih Strip', 'qty' => 2],
                    ['nama' => 'Vitamin B12 IPI',  'qty' => 1],
                ],
                'nominal_bayar' => 80000,
            ],
            [
                'waktu' => Carbon::today()->subDays(14)->addHours(11)->addMinutes(30),
                'items' => [
                    ['nama' => 'Cravit 500mg',         'qty' => 1],
                    ['nama' => 'Cataflam 25mg',        'qty' => 1],
                ],
                'nominal_bayar' => 160000,
            ],
            [
                'waktu' => Carbon::today()->subDays(14)->addHours(14)->addMinutes(45),
                'items' => [
                    ['nama' => 'Imodium 2mg',    'qty' => 1],
                    ['nama' => 'Lacto-B Sachet', 'qty' => 4],
                    ['nama' => 'Oralit Sachet',  'qty' => 5],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->subDays(14)->addHours(17)->addMinutes(20),
                'items' => [
                    ['nama' => 'Lameson 4mg',            'qty' => 1],
                    ['nama' => 'Desloratadine / Aerius 5mg', 'qty' => 1],
                ],
                'nominal_bayar' => 150000,
            ],
            [
                'waktu' => Carbon::today()->subDays(14)->addHours(20)->addMinutes(0),
                'items' => [
                    ['nama' => 'MinyakTelon Tresno Joyo 100ml', 'qty' => 1],
                    ['nama' => 'Tolak Angin Anak Sachet',       'qty' => 5],
                    ['nama' => 'Antimo Anak Sachet Strawberry', 'qty' => 4],
                ],
                'nominal_bayar' => 70000,
            ],
        ];

        // =========================================================
        // --- GENERATE TRANSAKSI HINGGA TOTAL 3.000 TRANSAKSI ---
        // Tersebar merata selama 5 bulan penuh (150 hari ke belakang)
        // (~20 sampai 22 transaksi per hari => total tepat 3.000 transaksi)
        // =========================================================
        $paketBelanja = [
            [
                ['nama' => 'Paracetamol 500mg', 'qty' => 2],
                ['nama' => 'Decolgen',          'qty' => 2],
                ['nama' => 'Vitamin C IPI',     'qty' => 1],
            ],
            [
                ['nama' => 'Promag',            'qty' => 2],
                ['nama' => 'Mylanta Cair 50ml', 'qty' => 1],
            ],
            [
                ['nama' => 'Amoxicillin 500mg',    'qty' => 2],
                ['nama' => 'Asam Mefenamat 500mg', 'qty' => 2],
                ['nama' => 'Dexamethasone 0.5mg',  'qty' => 1],
            ],
            [
                ['nama' => 'OBH Combi Dewasa', 'qty' => 1],
                ['nama' => 'Ambroxol 30mg',    'qty' => 2],
                ['nama' => 'Enervon-C Strip',  'qty' => 2],
            ],
            [
                ['nama' => 'Sanmol 500mg',             'qty' => 2],
                ['nama' => 'Tolak Angin Cair Sachet',  'qty' => 4],
                ['nama' => 'FreshCare Roll On Citrus', 'qty' => 1],
            ],
            [
                ['nama' => 'Cetirizine 10mg',  'qty' => 2],
                ['nama' => 'Incidal-OD 10mg',  'qty' => 1],
                ['nama' => 'Kalpanax Krim 5g', 'qty' => 1],
            ],
            [
                ['nama' => 'Insto Regular',            'qty' => 1],
                ['nama' => 'Rohto Tetes Mata Regular', 'qty' => 1],
                ['nama' => 'Panadol Biru 500mg',       'qty' => 1],
            ],
            [
                ['nama' => 'Betadine 30ml',            'qty' => 1],
                ['nama' => 'Hansaplast Kain Elastis',  'qty' => 2],
                ['nama' => 'Alkohol 70% OneMed 100ml', 'qty' => 1],
            ],
            [
                ['nama' => 'Amlodipine 5mg',   'qty' => 3],
                ['nama' => 'Simvastatin 10mg', 'qty' => 3],
                ['nama' => 'Neurobion Putih',  'qty' => 1],
            ],
            [
                ['nama' => 'Metformin 500mg',       'qty' => 3],
                ['nama' => 'Glimepiride 2mg',       'qty' => 2],
                ['nama' => 'Vitamin B Complex IPI', 'qty' => 1],
            ],
            [
                ['nama' => 'Diapet Kapsul', 'qty' => 2],
                ['nama' => 'Entrostop',     'qty' => 2],
                ['nama' => 'Oralit Sachet', 'qty' => 6],
            ],
            [
                ['nama' => 'Cefadroxil 500mg', 'qty' => 2],
                ['nama' => 'Sanmol 500mg',     'qty' => 2],
                ['nama' => 'Becom-C',          'qty' => 1],
            ],
            [
                ['nama' => 'Imboost Regular',           'qty' => 1],
                ['nama' => 'Holisticare Ester C Strip', 'qty' => 2],
                ['nama' => 'Sangobion Kapsul',          'qty' => 1],
            ],
            [
                ['nama' => 'Mixagrip Flu',              'qty' => 3],
                ['nama' => 'Woods Peppermint Antituss', 'qty' => 1],
                ['nama' => 'Degirol Hisap',             'qty' => 1],
            ],
            [
                ['nama' => 'Omeprazole 20mg',   'qty' => 2],
                ['nama' => 'Polysilane Tablet', 'qty' => 2],
                ['nama' => 'Ranitidine 150mg',  'qty' => 2],
            ],
            [
                ['nama' => 'Natrium Diklofenak 50mg', 'qty' => 2],
                ['nama' => 'Counterpain Cream 15g',   'qty' => 1],
                ['nama' => 'Salonpas Koyo 10s',       'qty' => 2],
            ],
            [
                ['nama' => 'Candesartan 8mg',       'qty' => 3],
                ['nama' => 'Atorvastatin 10mg',     'qty' => 2],
                ['nama' => 'Aspilets Chewable 80mg','qty' => 2],
            ],
            [
                ['nama' => 'Tempra Sirup Anak 60ml',      'qty' => 1],
                ['nama' => 'OBH Combi Anak Jeruk',        'qty' => 1],
                ['nama' => 'Minyak Telon My Baby Plus 60ml', 'qty' => 1],
            ],
            [
                ['nama' => 'Ciprofloxacin 500mg', 'qty' => 2],
                ['nama' => 'Ponstan 500mg',       'qty' => 1],
                ['nama' => 'Lansoprazole 30mg',   'qty' => 1],
            ],
            [
                ['nama' => 'Zegavit',          'qty' => 2],
                ['nama' => 'Prove D3-1000 IU', 'qty' => 1],
                ['nama' => 'Vitalong C Strip', 'qty' => 2],
            ],
            [
                ['nama' => 'Salbutamol 2mg',    'qty' => 2],
                ['nama' => 'Teosal Tablet',     'qty' => 2],
                ['nama' => 'Neo Napacin Strip', 'qty' => 2],
            ],
            [
                ['nama' => 'Betahistine Mesylate 6mg', 'qty' => 2],
                ['nama' => 'Flunarizine 5mg',          'qty' => 1],
                ['nama' => 'Mecobalamin 500mcg',       'qty' => 2],
            ],
            [
                ['nama' => 'Daktarin Cream 5g',        'qty' => 1],
                ['nama' => 'Betamethasone Cream 0.1%', 'qty' => 1],
                ['nama' => 'Loratadine 10mg',          'qty' => 2],
            ],
            [
                ['nama' => 'Sucralfate Suspensi 100ml', 'qty' => 1],
                ['nama' => 'Ondansetron 4mg',           'qty' => 2],
                ['nama' => 'Promag Cair Sachet',        'qty' => 4],
            ],
            [
                ['nama' => 'Levofloxacin 500mg',           'qty' => 2],
                ['nama' => 'Siladex Antitussive 60ml',     'qty' => 1],
                ['nama' => 'Strepsils Cool Menthol 8s',    'qty' => 1],
            ],
            [
                ['nama' => 'Allopurinol 100mg', 'qty' => 3],
                ['nama' => 'Meloxicam 7.5mg',   'qty' => 2],
                ['nama' => 'Neurosanbe',        'qty' => 1],
            ],
            [
                ['nama' => 'Lacto-B Sachet',     'qty' => 5],
                ['nama' => 'Diapet Anak Sachet', 'qty' => 3],
                ['nama' => 'Zinc Sulfate 20mg',  'qty' => 1],
            ],
            [
                ['nama' => 'Cendo Xitrol 5ml',         'qty' => 1],
                ['nama' => 'Erlamycetin Salep Mata 3.5g', 'qty' => 1],
                ['nama' => 'Sanmol Forte 650mg',       'qty' => 1],
            ],
            [
                ['nama' => 'Plossa Eucalyptus Roll On',      'qty' => 1],
                ['nama' => 'Minyak Kayu Putih Cap Lang 120ml','qty' => 1],
                ['nama' => 'Antangin Habatussauda Cair',     'qty' => 4],
            ],
            [
                ['nama' => 'Laxing Kapsul',        'qty' => 2],
                ['nama' => 'Vegeta Herbal Sachet', 'qty' => 4],
                ['nama' => 'Dulcolax 5mg',         'qty' => 1],
            ],
            [
                ['nama' => 'Folavit 400mcg',            'qty' => 2],
                ['nama' => 'Tonikum Bayer 100ml',       'qty' => 1],
                ['nama' => 'SurBex-Z',                  'qty' => 1],
            ],
            [
                ['nama' => 'Tremenza',        'qty' => 2],
                ['nama' => 'Bromhexine 8mg',  'qty' => 2],
                ['nama' => 'Dexteem Plus',    'qty' => 2],
            ],
        ];

        // 24 slot jam operasional apotek (07:15 s/d 21:00)
        $jamTransaksi = [
            [7, 15],  [7, 50],  [8, 25],  [9, 0],
            [9, 35],  [10, 10], [10, 45], [11, 20],
            [11, 55], [12, 30], [13, 5],  [13, 40],
            [14, 15], [14, 50], [15, 25], [16, 0],
            [16, 35], [17, 10], [17, 45], [18, 20],
            [18, 55], [19, 30], [20, 5],  [20, 45],
        ];

        $jumlahObat = $obatList->count();
        $targetTotalTransaksi = 3000;
        $totalHari = 150; // 5 bulan (150 hari ke belakang)

        for ($hari = 15; $hari <= $totalHari; $hari++) {
            $sisaHari = $totalHari - $hari + 1;
            $sisaTarget = $targetTotalTransaksi - count($transaksiList);

            if ($sisaTarget <= 0) {
                break;
            }

            // Distribusi merata (~20-22 transaksi per hari) dan tepat 3.000 di hari ke-150
            $rataRata = (int) floor($sisaTarget / $sisaHari);
            $variasi = ($hari % 3) - 1; // -1, 0, atau +1
            $jumlahTrxHariIni = ($sisaHari === 1) ? $sisaTarget : max(1, min($sisaTarget, $rataRata + $variasi));

            for ($slot = 0; $slot < $jumlahTrxHariIni; $slot++) {
                [$jam, $menit] = $jamTransaksi[$slot % count($jamTransaksi)];
                $menitVariasi = ($menit + ($hari * 3) + ($slot * 7)) % 55;

                $paketIndex = ($hari * 7 + $slot * 5) % count($paketBelanja);
                $items = $paketBelanja[$paketIndex];

                // Tambahkan 1 item ekstra secara deterministik dari seluruh daftar obat
                // agar setiap transaksi semakin kaya dan bervariasi
                if (($hari + $slot) % 2 === 0 && $jumlahObat > 0) {
                    $obatEkstra = $obatList[(($hari * 13) + ($slot * 11)) % $jumlahObat];
                    $items[] = [
                        'nama' => $obatEkstra->nama_obat,
                        'qty'  => 1 + (($hari + $slot) % 2),
                    ];
                }

                $transaksiList[] = [
                    'waktu'         => Carbon::today()->subDays($hari)->addHours($jam)->addMinutes($menitVariasi),
                    'items'         => $items,
                    'nominal_bayar' => 0, // Otomatis dibulatkan ke pecahan uang terdekat di bawah
                ];
            }
        }

        // Ambil daftar no_transaksi yang sudah ada agar tidak perlu query berulang
        $existingTrx = Transaksi::pluck('no_transaksi')->flip();

        DB::transaction(function () use ($transaksiList, $user, $obatMap, $obatList, $existingTrx) {
            foreach ($transaksiList as $index => $trxData) {
                $waktu = $trxData['waktu'];
                // Generate kode unik konsisten berdasarkan urutan dan tanggal
                $suffix = strtoupper(substr(md5($index . '-' . $waktu->format('YmdHis')), 0, 5));
                $noTransaksi = 'TRX-' . $waktu->format('Ymd') . '-' . $suffix;

                // Jika transaksi sudah ada, lewati
                if ($existingTrx->has($noTransaksi)) {
                    continue;
                }

                $totalHarga = 0;
                $detailList = [];

                foreach ($trxData['items'] as $item) {
                    $obat = $obatMap->get($item['nama']) ?? $obatList[$index % $obatList->count()];
                    $hargaSatuan = (int) $obat->harga_jual;
                    $qty = (int) $item['qty'];
                    $subtotal = $hargaSatuan * $qty;
                    $totalHarga += $subtotal;

                    $detailList[] = [
                        'obat_id'      => $obat->id,
                        'qty'          => $qty,
                        'harga_satuan' => $hargaSatuan,
                        'subtotal'     => $subtotal,
                        'created_at'   => $waktu,
                        'updated_at'   => $waktu,
                    ];
                }

                // Hitung total bayar dan kembalian
                $totalBayar = $trxData['nominal_bayar'] ?? 0;
                if ($totalBayar < $totalHarga) {
                    // Bulatkan ke kelipatan 5.000, 10.000, atau 20.000 di atas total harga
                    $kelipatanList = [5000, 10000, 20000];
                    $kelipatan = $kelipatanList[$index % 3];
                    $totalBayar = (int) ceil($totalHarga / $kelipatan) * $kelipatan;
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

                // Buat detail transaksi sekaligus per transaksi agar jauh lebih cepat
                foreach ($detailList as &$detail) {
                    $detail['transaksi_id'] = $transaksi->id;
                }
                unset($detail);

                DetailTransaksi::insert($detailList);
            }
        });

        $this->command?->info('TransaksiSeeder selesai: total ' . count($transaksiList) . ' transaksi disiapkan (rentang Hari Ini s/d 150 Hari Lalu / 5 Bulan).');
    }
}
