<?php

namespace Database\Seeders;

use App\Models\KondisiStokObat;
use App\Models\Obat;
use App\Models\ObatBatch;
use App\Models\PengaturanNotifikasi;
use App\Models\ProfilApotek;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use App\Models\User;
use App\Services\BatchStokService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BatchDanOpnameSeeder extends Seeder
{
    public function run(): void
    {
        if (Obat::count() === 0) {
            $this->call(ObatSeeder::class);
        }

        $karyawan = User::where('role', 'karyawan')->first();
        if (! $karyawan) {
            $this->call(UserSeeder::class);
            $karyawan = User::where('role', 'karyawan')->first();
        }
        $owner = User::where('role', 'owner')->first();

        DB::transaction(function () use ($karyawan, $owner) {
            ProfilApotek::ambilAtauDefault();
            $this->siapkanBatchAwal();
            $this->siapkanMultiBatchContoh();
            $this->siapkanKondisiStokContoh($karyawan?->id);
            $this->siapkanStokOpnameContoh($karyawan?->id);
            $this->sinkronStokSemuaObatBerbatch();
            if ($owner) {
                $this->siapkanPreferensiNotifikasi($owner->id);
            }
        });
    }

    /** Pastikan stok dan tgl_kadaluarsa tabel obat selalu sinkron dengan akumulasi batch-nya. */
    private function sinkronStokSemuaObatBerbatch(): void
    {
        Obat::has('batches')
            ->withSum('batches as total_stok_batch', 'stok')
            ->get()
            ->each(function (Obat $obat) {
                $totalBatch = (int) ($obat->total_stok_batch ?? 0);
                if ((int) $obat->stok !== $totalBatch) {
                    $obat->update(['stok' => $totalBatch]);
                }
                BatchStokService::sinkronTanggal($obat->fresh());
            });
    }

    /** Pastikan setiap obat berstok > 0 yang belum punya batch memiliki BATCH-AWAL. */
    private function siapkanBatchAwal(): void
    {
        $hariIni = Carbon::today()->toDateString();

        Obat::where('stok', '>', 0)
            ->doesntHave('batches')
            ->get()
            ->each(function (Obat $obat) use ($hariIni) {
                ObatBatch::create([
                    'obat_id'        => $obat->id,
                    'kode_batch'     => 'BATCH-AWAL',
                    'tgl_terima'     => $hariIni,
                    'tgl_kadaluarsa' => $obat->tgl_kadaluarsa,
                    'stok_awal'      => (int) $obat->stok,
                    'stok'           => (int) $obat->stok,
                    'harga_beli'     => $obat->harga_beli,
                ]);
            });
    }

    /** Tambahkan beberapa batch lanjutan (termasuk H-5 dan expired) untuk demonstrasi FEFO. */
    private function siapkanMultiBatchContoh(): void
    {
        $hariIni = Carbon::today();

        $contohBatch = [
            'Paracetamol 500mg' => [
                ['kode' => 'PCM-2601', 'supplier' => 'PT Kimia Farma',         'terima' => -40, 'ed' => 180, 'stok' => 25],
                ['kode' => 'PCM-2602', 'supplier' => 'PT Anugrah Argon',       'terima' => -10, 'ed' => 365, 'stok' => 30],
            ],
            'Amoxicillin 500mg' => [
                ['kode' => 'AMX-H05',  'supplier' => 'PT Bina San Prima',      'terima' => -20, 'ed' => 5,   'stok' => 6],
                ['kode' => 'AMX-2609', 'supplier' => 'PT Bina San Prima',      'terima' => -5,  'ed' => 240, 'stok' => 20],
            ],
            'Promag' => [
                ['kode' => 'PRM-2603', 'supplier' => 'PT Kalbe Farma',         'terima' => -15, 'ed' => 210, 'stok' => 15],
            ],
            'Ambroxol 30mg' => [
                ['kode' => 'AMB-EXP',  'supplier' => 'PT Phapros',             'terima' => -60, 'ed' => -3,  'stok' => 4],
                ['kode' => 'AMB-2611', 'supplier' => 'PT Phapros',             'terima' => -7,  'ed' => 270, 'stok' => 25],
            ],
            'Sanmol 500mg' => [
                ['kode' => 'SNM-2610', 'supplier' => 'PT Sanbe Farma',         'terima' => -12, 'ed' => 300, 'stok' => 20],
            ],
        ];

        foreach ($contohBatch as $namaObat => $daftar) {
            $obat = Obat::where('nama_obat', $namaObat)->first();
            if (! $obat) {
                continue;
            }

            $adaBaru = false;
            foreach ($daftar as $row) {
                $batch = ObatBatch::firstOrCreate(
                    ['obat_id' => $obat->id, 'kode_batch' => $row['kode']],
                    [
                        'supplier'       => $row['supplier'],
                        'tgl_terima'     => $hariIni->copy()->addDays($row['terima'])->toDateString(),
                        'tgl_kadaluarsa' => $hariIni->copy()->addDays($row['ed'])->toDateString(),
                        'stok_awal'      => $row['stok'],
                        'stok'           => $row['stok'],
                        'harga_beli'     => $obat->harga_beli,
                    ]
                );
                if ($batch->wasRecentlyCreated) {
                    $adaBaru = true;
                }
            }

            if ($adaBaru || $obat->batches()->exists()) {
                $totalStok = (int) $obat->batches()->sum('stok');
                $obat->update(['stok' => $totalStok]);
                BatchStokService::sinkronTanggal($obat->fresh());
            }
        }
    }

    /** Catat contoh riwayat rusak, retur supplier, dan pemusnahan terkait batch. */
    private function siapkanKondisiStokContoh(?int $userId): void
    {
        $skenario = [
            [
                'nama'     => 'Paracetamol 500mg',
                'kode_b'   => 'PCM-2601',
                'jenis'    => KondisiStokObat::JENIS_RUSAK,
                'jumlah'   => 2,
                'penyebab' => 'Kemasan blister penyok saat penataan rak',
                'catatan'  => 'Ditemukan pada pengecekan rak depan',
            ],
            [
                'nama'     => 'Amoxicillin 500mg',
                'kode_b'   => 'AMX-H05',
                'jenis'    => KondisiStokObat::JENIS_RETUR,
                'jumlah'   => 2,
                'penyebab' => 'Retur sebagian batch mendekati kadaluarsa ke PBF',
                'catatan'  => 'Nota retur terlampir ke PT Bina San Prima',
            ],
            [
                'nama'     => 'Ambroxol 30mg',
                'kode_b'   => 'AMB-EXP',
                'jenis'    => KondisiStokObat::JENIS_MUSNAH,
                'jumlah'   => 1,
                'penyebab' => 'Pemusnahan 1 strip kedaluwarsa sesuai prosedur apotek',
                'catatan'  => 'Sisa batch menunggu tindak lanjut lanjutan',
            ],
        ];

        foreach ($skenario as $row) {
            if (KondisiStokObat::where('penyebab', $row['penyebab'])->exists()) {
                continue;
            }

            $obat = Obat::where('nama_obat', $row['nama'])->lockForUpdate()->first();
            if (! $obat || (int) $obat->stok < $row['jumlah']) {
                continue;
            }

            $batch = ObatBatch::where('obat_id', $obat->id)
                ->where('kode_batch', $row['kode_b'])
                ->lockForUpdate()
                ->first();

            if ($batch && (int) $batch->stok >= $row['jumlah']) {
                $batch->update(['stok' => (int) $batch->stok - $row['jumlah']]);
            }

            $sebelum = (int) $obat->stok;
            $sesudah = $sebelum - $row['jumlah'];
            $obat->update(['stok' => $sesudah]);
            BatchStokService::sinkronTanggal($obat->fresh());

            KondisiStokObat::create([
                'obat_id'      => $obat->id,
                'batch_id'     => $batch?->id,
                'user_id'      => $userId,
                'jenis'        => $row['jenis'],
                'jumlah'       => $row['jumlah'],
                'stok_sebelum' => $sebelum,
                'stok_sesudah' => $sesudah,
                'penyebab'     => $row['penyebab'],
                'catatan'      => $row['catatan'],
            ]);
        }
    }

    /** Buat 2 sesi stok opname bulanan berstatus final (bulan lalu rapi, bulan ini ada selisih). */
    private function siapkanStokOpnameContoh(?int $userId): void
    {
        $obatsAktif = Obat::where('status', 'tersedia')->orderBy('nama_obat')->get();
        if ($obatsAktif->isEmpty()) {
            return;
        }

        // Sesi 1: Bulan lalu (semua stok sesuai sistem)
        $bulanLalu = Carbon::now('Asia/Jakarta')->subMonthNoOverflow();
        $kodeLalu = 'OPN-' . $bulanLalu->format('Ym') . '-001';
        if (StokOpname::where('kode', $kodeLalu)->doesntExist()) {
            $waktuLalu = $bulanLalu->copy()->endOfMonth()->setTime(20, 0);
            $opnLalu = StokOpname::create([
                'kode'         => $kodeLalu,
                'periode'      => $bulanLalu->format('Y-m'),
                'tanggal'      => $waktuLalu->toDateString(),
                'user_id'      => $userId,
                'status'       => StokOpname::STATUS_FINAL,
                'catatan'      => 'Stok opname rutin akhir bulan lalu',
                'finalized_at' => $waktuLalu,
            ]);

            $barisLalu = $obatsAktif->take(20)->map(fn (Obat $o) => [
                'stok_opname_id' => $opnLalu->id,
                'obat_id'        => $o->id,
                'stok_sistem'    => (int) $o->stok,
                'stok_fisik'     => (int) $o->stok,
                'jml_rusak'      => 0,
                'jml_hilang'     => 0,
                'catatan'        => null,
                'created_at'     => $waktuLalu,
                'updated_at'     => $waktuLalu,
            ])->all();
            StokOpnameDetail::insert($barisLalu);
        }

        // Sesi 2: Bulan ini (final, berisi contoh selisih kurang rusak+hilang dan selisih lebih)
        $sekarang = Carbon::now('Asia/Jakarta');
        $kodeIni = 'OPN-' . $sekarang->format('Ym') . '-001';
        if (StokOpname::where('kode', $kodeIni)->exists()) {
            return;
        }

        $waktuIni = $sekarang->copy()->subDays(1)->setTime(19, 30);
        $opnIni = StokOpname::create([
            'kode'         => $kodeIni,
            'periode'      => $sekarang->format('Y-m'),
            'tanggal'      => $waktuIni->toDateString(),
            'user_id'      => $userId,
            'status'       => StokOpname::STATUS_FINAL,
            'catatan'      => 'Stok opname bulanan periode ' . $sekarang->format('Y-m'),
            'finalized_at' => $waktuIni,
        ]);

        $selisihContoh = [
            'Sanmol 500mg' => [
                'delta'   => -5,
                'rusak'   => 2,
                'hilang'  => 3,
                'catatan' => '2 strip rusak kemasan, 3 strip selisih rak depan',
            ],
            'Oskadon' => [
                'delta'   => -2,
                'rusak'   => 1,
                'hilang'  => 1,
                'catatan' => '1 strip lembab, 1 strip kurang di laci kasir',
            ],
            'Bodrex' => [
                'delta'   => 2,
                'rusak'   => 0,
                'hilang'  => 0,
                'catatan' => 'Ditemukan 2 strip terselip di rak cadangan',
            ],
        ];

        $detailRows = [];
        foreach ($obatsAktif as $obat) {
            $sistem = (int) $obat->stok;
            $cfg = $selisihContoh[$obat->nama_obat] ?? null;

            if ($cfg && ($sistem + $cfg['delta']) >= 0) {
                $fisik = $sistem + $cfg['delta'];
                $rusak = $cfg['rusak'];
                $hilang = $cfg['hilang'];
                $catatan = $cfg['catatan'];

                $this->terapkanPenyesuaianOpname($obat, $kodeIni, $userId, $sistem, $fisik, $rusak, $hilang, $catatan);
            } else {
                $fisik = $sistem;
                $rusak = 0;
                $hilang = 0;
                $catatan = null;
            }

            $detailRows[] = [
                'stok_opname_id' => $opnIni->id,
                'obat_id'        => $obat->id,
                'stok_sistem'    => $sistem,
                'stok_fisik'     => $fisik,
                'jml_rusak'      => $rusak,
                'jml_hilang'     => $hilang,
                'catatan'        => $catatan,
                'created_at'     => $waktuIni,
                'updated_at'     => $waktuIni,
            ];
        }

        foreach (array_chunk($detailRows, 200) as $potong) {
            StokOpnameDetail::insert($potong);
        }
    }

    /** Terapkan selisih opname ke batch & obat serta catat di kondisi_stok_obat tanpa push FCM. */
    private function terapkanPenyesuaianOpname(
        Obat $obat,
        string $kodeOpname,
        ?int $userId,
        int $sistem,
        int $fisik,
        int $rusak,
        int $hilang,
        string $catatan
    ): void {
        $ket = "Stok opname {$kodeOpname}";
        $punyaBatch = $obat->batches()->exists();

        if ($rusak > 0) {
            if ($punyaBatch) {
                BatchStokService::kurangiFefo($obat->id, $rusak, false);
            }
            KondisiStokObat::create([
                'obat_id'      => $obat->id,
                'user_id'      => $userId,
                'jenis'        => KondisiStokObat::JENIS_RUSAK,
                'jumlah'       => $rusak,
                'stok_sebelum' => $sistem,
                'stok_sesudah' => $sistem - $rusak,
                'penyebab'     => "{$ket}: rusak",
                'catatan'      => $catatan,
            ]);
        }

        if ($hilang > 0) {
            $awalHilang = $sistem - $rusak;
            if ($punyaBatch) {
                BatchStokService::kurangiFefo($obat->id, $hilang, false);
            }
            KondisiStokObat::create([
                'obat_id'      => $obat->id,
                'user_id'      => $userId,
                'jenis'        => KondisiStokObat::JENIS_HILANG,
                'jumlah'       => $hilang,
                'stok_sebelum' => $awalHilang,
                'stok_sesudah' => $awalHilang - $hilang,
                'penyebab'     => "{$ket}: hilang / selisih pencatatan",
                'catatan'      => $catatan,
            ]);
        }

        $selisih = $fisik - $sistem;
        if ($selisih > 0 && $punyaBatch) {
            $batch = ObatBatch::where('obat_id', $obat->id)
                ->orderByDesc('tgl_kadaluarsa')
                ->orderByDesc('id')
                ->first();
            $batch?->update(['stok' => (int) $batch->stok + $selisih]);
            BatchStokService::sinkronTanggal($obat->fresh());
        }

        $obat->update(['stok' => $fisik]);
        KondisiStokObat::create([
            'obat_id'      => $obat->id,
            'user_id'      => $userId,
            'jenis'        => KondisiStokObat::JENIS_OPNAME,
            'jumlah'       => abs($selisih),
            'stok_sebelum' => $selisih < 0 ? $fisik : $sistem,
            'stok_sesudah' => $fisik,
            'penyebab'     => $selisih > 0
                ? "{$ket}: selisih lebih"
                : "{$ket}: selisih kurang (rusak {$rusak} + hilang {$hilang})",
            'catatan'      => $catatan,
        ]);
    }

    private function siapkanPreferensiNotifikasi(int $ownerId): void
    {
        PengaturanNotifikasi::firstOrCreate(
            ['user_id' => $ownerId],
            [
                'aktif'            => true,
                'notif_kadaluarsa' => true,
                'notif_rusak'      => true,
                'notif_opname'     => true,
                'batas_stok'       => 10,
                'jam_ringkasan'    => '08:00',
            ]
        );
    }
}
