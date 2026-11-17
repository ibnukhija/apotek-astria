<?php

namespace Tests\Feature;

use App\Models\DetailTransaksi;
use App\Models\Kategori;
use App\Models\Obat;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiMobileTest extends TestCase
{
    use RefreshDatabase;

    private function buatObat(array $ubah = []): Obat
    {
        $kategori = Kategori::firstOrCreate(['nama_kategori' => 'Analgesik']);

        return Obat::create(array_merge([
            'kategori_id'    => $kategori->id,
            'kode_obat'      => 'OBT-' . strtoupper(uniqid()),
            'nama_obat'      => 'Paracetamol 500mg',
            'satuan'         => 'Strip',
            'harga_beli'     => 8000,
            'margin'         => 50,
            'tipe_margin'    => 'persen',
            'harga_jual'     => 12000,
            'stok'           => 30,
            'tgl_kadaluarsa' => now()->addMonths(6)->format('Y-m-d'),
            'status'         => 'tersedia',
        ], $ubah));
    }

    private function loginSebagaiOwner(array $ubah = []): User
    {
        $owner = User::create(array_merge([
            'nama'     => 'Owner Apotek',
            'username' => 'owner_' . uniqid(),
            'password' => Hash::make('rahasia123'),
            'role'     => 'owner',
            'status'   => 'aktif',
        ], $ubah));

        Sanctum::actingAs($owner);

        return $owner;
    }

    public function test_endpoint_owner_menolak_akses_tanpa_token(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
        $this->get('/api/dashboard')->assertStatus(401)->assertJson(['message' => 'Unauthenticated.']);
        $this->getJson('/api/stok')->assertStatus(401);
        $this->putJson('/api/obat/1', ['harga_jual' => 1000, 'status' => 'tersedia'])->assertStatus(401);
        $this->getJson('/api/riwayat')->assertStatus(401);
        $this->getJson('/api/profil')->assertStatus(401);
        $this->putJson('/api/profil', ['nama_apotek' => 'Tes', 'alamat' => 'Jl. Tes'])->assertStatus(401);
        $this->getJson('/api/karyawan')->assertStatus(401);
        $this->postJson('/api/karyawan', [])->assertStatus(401);
        $this->getJson('/api/notifikasi')->assertStatus(401);
        $this->putJson('/api/notifikasi', [])->assertStatus(401);
        $this->postJson('/api/notifikasi/tes')->assertStatus(401);
        $this->postJson('/api/logout-mobile')->assertStatus(401);
    }

    public function test_endpoint_owner_menolak_role_non_owner_dan_owner_nonaktif(): void
    {
        $karyawan = User::create([
            'nama'     => 'Kasir',
            'username' => 'kasir_tok',
            'password' => Hash::make('rahasia123'),
            'role'     => 'karyawan',
            'status'   => 'aktif',
        ]);
        Sanctum::actingAs($karyawan);
        $this->getJson('/api/dashboard')->assertStatus(403)->assertJson(['success' => false]);

        $ownerNonaktif = User::create([
            'nama'     => 'Owner Nonaktif',
            'username' => 'owner_off',
            'password' => Hash::make('rahasia123'),
            'role'     => 'owner',
            'status'   => 'nonaktif',
        ]);
        Sanctum::actingAs($ownerNonaktif);
        $this->getJson('/api/dashboard')->assertStatus(403)->assertJson(['success' => false]);
    }

    public function test_login_mobile_menerbitkan_token_sanctum_dan_logout_mencabut_token(): void
    {
        $owner = User::create([
            'nama'     => 'Owner Utama',
            'username' => 'owner_utama',
            'password' => Hash::make('rahasia123'),
            'role'     => 'owner',
            'status'   => 'aktif',
        ]);

        $login = $this->postJson('/api/login-mobile', [
            'username' => 'owner_utama',
            'password' => 'rahasia123',
        ])->assertOk()->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'nama', 'token'],
        ]);

        $token = $login->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withToken($token)
            ->postJson('/api/logout-mobile')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Reset guard cache dalam proses test yang sama agar verifikasi ulang token ke DB
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/dashboard')
            ->assertStatus(401);
    }

    public function test_dashboard_dan_stok_memakai_batas_stok_yang_konsisten(): void
    {
        $this->loginSebagaiOwner();

        $this->buatObat(['kode_obat' => 'OBT-001', 'nama_obat' => 'Zink Tablet', 'stok' => 9, 'status' => 'tersedia']);
        $this->buatObat(['kode_obat' => 'OBT-002', 'nama_obat' => 'Amoxicillin', 'stok' => 10, 'status' => 'tersedia']);
        $this->buatObat(['kode_obat' => 'OBT-003', 'nama_obat' => 'Cetirizine', 'stok' => 24, 'status' => 'tersedia']);
        $this->buatObat(['kode_obat' => 'OBT-004', 'nama_obat' => 'Vitamin C', 'stok' => 25, 'status' => 'tersedia']);
        $this->buatObat(['kode_obat' => 'OBT-005', 'nama_obat' => 'Obat Nonaktif', 'stok' => 2, 'status' => 'tidak']);

        $dashboard = $this->getJson('/api/dashboard')->assertOk()->json('data');
        $this->assertCount(1, $dashboard['peringatan_obat']);
        $this->assertSame('OBT-001', $dashboard['peringatan_obat'][0]['kode_obat']);

        $stokData = $this->getJson('/api/stok')->assertOk()->json('data');
        $statusByKode = collect($stokData)->pluck('status_stok', 'kode_obat')->all();

        $this->assertSame('Hampir habis', $statusByKode['OBT-001']);
        $this->assertSame('Menipis', $statusByKode['OBT-002']);
        $this->assertSame('Menipis', $statusByKode['OBT-003']);
        $this->assertSame('Cukup', $statusByKode['OBT-004']);

        // Terurut berdasarkan nama_obat secara alfabetis
        $this->assertSame('Amoxicillin', $stokData[0]['nama_obat']);
    }

    public function test_stok_mendukung_pencarian_nama_dan_kode_obat(): void
    {
        $this->loginSebagaiOwner();

        $this->buatObat(['kode_obat' => 'OBT-101', 'nama_obat' => 'Paracetamol']);
        $this->buatObat(['kode_obat' => 'OBT-202', 'nama_obat' => 'Ibuprofen']);

        $byKode = $this->getJson('/api/stok?search=OBT-202')->assertOk()->json('data');
        $this->assertCount(1, $byKode);
        $this->assertSame('Ibuprofen', $byKode[0]['nama_obat']);

        $byNama = $this->getJson('/api/stok?search=Paracet')->assertOk()->json('data');
        $this->assertCount(1, $byNama);
        $this->assertSame('OBT-101', $byNama[0]['kode_obat']);
    }

    public function test_login_mobile_memvalidasi_role_dan_status_aktif(): void
    {
        User::create([
            'nama'     => 'Owner Aktif',
            'username' => 'owner_aktif',
            'password' => Hash::make('rahasia123'),
            'role'     => 'owner',
            'status'   => 'aktif',
        ]);

        User::create([
            'nama'     => 'Owner Nonaktif',
            'username' => 'owner_nonaktif',
            'password' => Hash::make('rahasia123'),
            'role'     => 'owner',
            'status'   => 'nonaktif',
        ]);

        User::create([
            'nama'     => 'Kasir Aktif',
            'username' => 'kasir_aktif',
            'password' => Hash::make('rahasia123'),
            'role'     => 'karyawan',
            'status'   => 'aktif',
        ]);

        $this->postJson('/api/login-mobile', [
            'username' => 'owner_aktif',
            'password' => 'rahasia123',
        ])->assertOk()->assertJson([
            'success' => true,
            'data'    => ['nama' => 'Owner Aktif'],
        ]);

        $this->postJson('/api/login-mobile', [
            'username' => 'owner_nonaktif',
            'password' => 'rahasia123',
        ])->assertOk()->assertJson([
            'success' => false,
            'message' => 'Akun owner sedang dinonaktifkan',
        ]);

        $this->postJson('/api/login-mobile', [
            'username' => 'kasir_aktif',
            'password' => 'rahasia123',
        ])->assertOk()->assertJson([
            'success' => false,
        ]);
    }

    public function test_update_obat_cepat_validasi_dan_sinkronisasi_margin(): void
    {
        $this->loginSebagaiOwner();

        $obat = $this->buatObat([
            'harga_beli'  => 8000,
            'margin'      => 50,
            'tipe_margin' => 'persen',
            'harga_jual'  => 12000,
            'status'      => 'tersedia',
        ]);

        // Nilai negatif harus ditolak
        $this->putJson("/api/obat/{$obat->id}", [
            'harga_jual' => -500,
            'status'     => 'tersedia',
        ])->assertStatus(422);

        // Jika hanya status berubah dan harga_jual tetap, margin persen dipertahankan
        $this->putJson("/api/obat/{$obat->id}", [
            'harga_jual' => 12000,
            'status'     => 'tidak',
        ])->assertOk();

        $obat->refresh();
        $this->assertSame('tidak', $obat->status);
        $this->assertSame('persen', $obat->tipe_margin);
        $this->assertEquals(50.0, $obat->margin);

        // Jika harga_jual diubah, margin disinkronkan ke nominal
        $this->putJson("/api/obat/{$obat->id}", [
            'harga_jual' => 15000,
            'status'     => 'tersedia',
        ])->assertOk();

        $obat->refresh();
        $this->assertSame(15000, (int) $obat->harga_jual);
        $this->assertSame('nominal', $obat->tipe_margin);
        $this->assertEquals(7000.0, $obat->margin);
    }

    public function test_riwayat_memvalidasi_tanggal_dan_mengembalikan_total(): void
    {
        $this->loginSebagaiOwner();

        $kasir = User::create([
            'nama'     => 'Kasir 1',
            'username' => 'kasir1',
            'password' => Hash::make('rahasia123'),
            'role'     => 'karyawan',
            'status'   => 'aktif',
        ]);

        $t1 = Transaksi::create([
            'no_transaksi' => 'TRX-001',
            'user_id'      => $kasir->id,
            'total_harga'  => 10000,
            'total_bayar'  => 10000,
            'kembalian'    => 0,
        ]);
        $t1->forceFill(['created_at' => '2026-11-10 10:00:00'])->save();

        $t2 = Transaksi::create([
            'no_transaksi' => 'TRX-002',
            'user_id'      => $kasir->id,
            'total_harga'  => 20000,
            'total_bayar'  => 20000,
            'kembalian'    => 0,
        ]);
        $t2->forceFill(['created_at' => '2026-11-15 10:00:00'])->save();

        $this->getJson('/api/riwayat?tanggal_mulai=bukan-tanggal')
            ->assertStatus(422);

        $this->getJson('/api/riwayat?tanggal_mulai=2026-11-15&tanggal_selesai=2026-11-10')
            ->assertStatus(422);

        $this->getJson('/api/riwayat?tanggal_mulai=2026-11-12')
            ->assertOk()
            ->assertJson(['success' => true, 'total' => 1])
            ->assertJsonPath('data.0.no_transaksi', 'TRX-002');

        $this->getJson('/api/riwayat?tanggal_mulai=2026-11-10&tanggal_selesai=2026-11-15')
            ->assertOk()
            ->assertJson(['success' => true, 'total' => 2]);
    }

    public function test_tambah_karyawan_mengisi_status_aktif_secara_eksplisit(): void
    {
        $this->loginSebagaiOwner();

        $this->postJson('/api/karyawan', [
            'nama'     => 'Budi Kasir',
            'username' => 'budi_kasir',
            'password' => 'rahasia123',
        ])->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'username' => 'budi_kasir',
            'role'     => 'karyawan',
            'status'   => 'aktif',
        ]);
    }

    public function test_dashboard_dan_stok_menyertakan_peringatan_dan_status_kadaluarsa(): void
    {
        $this->loginSebagaiOwner();

        $this->buatObat([
            'kode_obat'      => 'OBT-EXP1',
            'nama_obat'      => 'Obat Kadaluarsa',
            'tgl_kadaluarsa' => now()->subDays(3)->format('Y-m-d'),
            'status'         => 'tersedia',
        ]);
        $this->buatObat([
            'kode_obat'      => 'OBT-EXP2',
            'nama_obat'      => 'Obat Hampir Kadaluarsa',
            'tgl_kadaluarsa' => now()->addDays(5)->format('Y-m-d'),
            'status'         => 'tersedia',
        ]);
        $this->buatObat([
            'kode_obat'      => 'OBT-AMAN',
            'nama_obat'      => 'Obat Aman',
            'tgl_kadaluarsa' => now()->addDays(90)->format('Y-m-d'),
            'status'         => 'tersedia',
        ]);
        $this->buatObat([
            'kode_obat'      => 'OBT-OFF',
            'nama_obat'      => 'Obat Nonaktif Kadaluarsa',
            'tgl_kadaluarsa' => now()->subDays(1)->format('Y-m-d'),
            'status'         => 'tidak',
        ]);

        $dashboard = $this->getJson('/api/dashboard')->assertOk()->json('data');
        $kodesKadaluarsa = collect($dashboard['peringatan_kadaluarsa'])->pluck('status_kadaluarsa', 'kode_obat')->all();

        $this->assertCount(2, $kodesKadaluarsa);
        $this->assertSame('Kadaluarsa', $kodesKadaluarsa['OBT-EXP1']);
        $this->assertSame('Hampir kadaluarsa', $kodesKadaluarsa['OBT-EXP2']);
        $this->assertArrayNotHasKey('OBT-AMAN', $kodesKadaluarsa);
        $this->assertArrayNotHasKey('OBT-OFF', $kodesKadaluarsa);

        $stokData = $this->getJson('/api/stok')->assertOk()->json('data');
        $statusStokKadaluarsa = collect($stokData)->pluck('status_kadaluarsa', 'kode_obat')->all();

        $this->assertSame('Kadaluarsa', $statusStokKadaluarsa['OBT-EXP1']);
        $this->assertSame('Hampir kadaluarsa', $statusStokKadaluarsa['OBT-EXP2']);
        $this->assertSame('Aman', $statusStokKadaluarsa['OBT-AMAN']);
    }

    public function test_dashboard_mendukung_filter_periode_harian_mingguan_bulanan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00'));

        try {
            $this->loginSebagaiOwner();

            $kasir = User::create([
                'nama'     => 'Kasir',
                'username' => 'kasir_periode',
                'password' => Hash::make('rahasia123'),
                'role'     => 'karyawan',
                'status'   => 'aktif',
            ]);

            $buatTrx = function (string $kode, int $total, string $waktu) use ($kasir): void {
                $t = Transaksi::create([
                    'no_transaksi' => $kode,
                    'user_id'      => $kasir->id,
                    'total_harga'  => $total,
                    'total_bayar'  => $total,
                    'kembalian'    => 0,
                ]);
                $t->forceFill(['created_at' => $waktu])->save();
            };

            $buatTrx('TRX-HARI', 10000, '2026-11-18 08:30:00');
            $buatTrx('TRX-MINGGU', 25000, '2026-11-16 14:00:00');
            $buatTrx('TRX-BULAN', 40000, '2026-11-05 12:00:00');
            $buatTrx('TRX-LALU', 99000, '2026-10-25 12:00:00');

            $this->getJson('/api/dashboard?periode=harian')
                ->assertOk()
                ->assertJsonPath('data.periode', 'harian')
                ->assertJsonPath('data.total_penjualan', 10000)
                ->assertJsonPath('data.jumlah_transaksi', 1);

            $this->getJson('/api/dashboard?periode=mingguan')
                ->assertOk()
                ->assertJsonPath('data.periode', 'mingguan')
                ->assertJsonPath('data.total_penjualan', 35000)
                ->assertJsonPath('data.jumlah_transaksi', 2);

            $this->getJson('/api/dashboard?periode=bulanan')
                ->assertOk()
                ->assertJsonPath('data.periode', 'bulanan')
                ->assertJsonPath('data.total_penjualan', 75000)
                ->assertJsonPath('data.jumlah_transaksi', 3);

            $this->getJson('/api/dashboard?periode=keseluruhan')
                ->assertOk()
                ->assertJsonPath('data.periode', 'keseluruhan')
                ->assertJsonPath('data.periode_grafik', 'harian')
                ->assertJsonPath('data.total_penjualan', 174000)
                ->assertJsonPath('data.jumlah_transaksi', 4);

            $this->getJson('/api/dashboard?periode=tahunan')
                ->assertStatus(422);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_mengembalikan_grafik_penjualan_harian_mingguan_bulanan_tahunan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00'));

        try {
            $this->loginSebagaiOwner();

            $kasir = User::create([
                'nama'     => 'Kasir Grafik',
                'username' => 'kasir_grafik',
                'password' => Hash::make('rahasia123'),
                'role'     => 'karyawan',
                'status'   => 'aktif',
            ]);

            $buatTrx = function (string $kode, int $total, string $waktu) use ($kasir): void {
                $t = Transaksi::create([
                    'no_transaksi' => $kode,
                    'user_id'      => $kasir->id,
                    'total_harga'  => $total,
                    'total_bayar'  => $total,
                    'kembalian'    => 0,
                ]);
                $t->forceFill(['created_at' => $waktu])->save();
            };

            $buatTrx('TRX-G1', 15000, '2026-11-18 09:00:00');
            $buatTrx('TRX-G2', 20000, '2026-11-16 14:00:00');
            $buatTrx('TRX-G3', 50000, '2026-10-15 11:00:00');
            $buatTrx('TRX-G4', 75000, '2024-06-10 10:00:00');

            // Harian: 7 hari terakhir (12/11 - 18/11)
            $resHarian = $this->getJson('/api/dashboard?periode=harian&periode_grafik=harian')
                ->assertOk()
                ->assertJsonPath('data.periode_grafik', 'harian')
                ->json('data.grafik_penjualan');
            $this->assertCount(7, $resHarian);
            $this->assertSame('18/11', $resHarian[6]['label']);
            $this->assertSame(15000, $resHarian[6]['total']);
            $this->assertSame(1, $resHarian[6]['jumlah_transaksi']);

            // Mingguan: 6 minggu terakhir (minggu ini berisi 15000 + 20000 = 35000)
            $resMingguan = $this->getJson('/api/dashboard?periode=harian&periode_grafik=mingguan')
                ->assertOk()
                ->assertJsonPath('data.periode', 'harian')
                ->assertJsonPath('data.total_penjualan', 15000)
                ->assertJsonPath('data.periode_grafik', 'mingguan')
                ->json('data.grafik_penjualan');
            $this->assertCount(6, $resMingguan);
            $this->assertSame('16/11', $resMingguan[5]['label']);
            $this->assertSame(35000, $resMingguan[5]['total']);
            $this->assertSame(2, $resMingguan[5]['jumlah_transaksi']);

            // Bulanan: bulan 1-12 tahun berjalan (Jan - Des 2026)
            $resBulanan = $this->getJson('/api/dashboard?periode_grafik=bulanan')
                ->assertOk()
                ->json('data.grafik_penjualan');
            $this->assertCount(12, $resBulanan);
            $this->assertSame('Jan', $resBulanan[0]['label']);
            $this->assertSame('Okt', $resBulanan[9]['label']);
            $this->assertSame(50000, $resBulanan[9]['total']);
            $this->assertSame('Nov', $resBulanan[10]['label']);
            $this->assertSame(35000, $resBulanan[10]['total']);
            $this->assertSame('Des', $resBulanan[11]['label']);
            $this->assertSame(0, $resBulanan[11]['total']);

            // Tahunan: lintas beberapa tahun (2023 - 2026)
            $resTahunan = $this->getJson('/api/dashboard?periode_grafik=tahunan')
                ->assertOk()
                ->assertJsonPath('data.periode_grafik', 'tahunan')
                ->json('data.grafik_penjualan');
            $this->assertCount(4, $resTahunan);
            $this->assertSame(['2023', '2024', '2025', '2026'], array_column($resTahunan, 'label'));
            $this->assertSame(0, $resTahunan[0]['total']);
            $this->assertSame(75000, $resTahunan[1]['total']);
            $this->assertSame(0, $resTahunan[2]['total']);
            $this->assertSame(85000, $resTahunan[3]['total']);

            $this->getJson('/api/dashboard?periode_grafik=tidak_valid')
                ->assertStatus(422);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_mengembalikan_top_5_obat_terlaris_keseluruhan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00'));

        try {
            $this->loginSebagaiOwner();

            $kasir = User::create([
                'nama'     => 'Kasir Terlaris',
                'username' => 'kasir_terlaris',
                'password' => Hash::make('rahasia123'),
                'role'     => 'karyawan',
                'status'   => 'aktif',
            ]);

            $daftarObat = [];
            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $idx => $huruf) {
                $daftarObat[$huruf] = $this->buatObat([
                    'kode_obat'  => 'TOP-00' . ($idx + 1),
                    'nama_obat'  => 'Obat ' . $huruf,
                    'harga_jual' => 10000,
                ]);
            }

            $simpanItem = function (string $kode, string $waktu, array $items) use ($kasir): void {
                $total = collect($items)->sum(fn ($i) => $i['qty'] * 10000);
                $trx = Transaksi::create([
                    'no_transaksi' => $kode,
                    'user_id'      => $kasir->id,
                    'total_harga'  => $total,
                    'total_bayar'  => $total,
                    'kembalian'    => 0,
                ]);
                $trx->forceFill(['created_at' => $waktu])->save();

                foreach ($items as $item) {
                    DetailTransaksi::create([
                        'transaksi_id' => $trx->id,
                        'obat_id'      => $item['obat']->id,
                        'qty'          => $item['qty'],
                        'harga_satuan' => 10000,
                        'subtotal'     => $item['qty'] * 10000,
                    ]);
                }
            };

            // Hari ini: B=4, A=2
            $simpanItem('TRX-TOP1', '2026-11-18 09:00:00', [
                ['obat' => $daftarObat['B'], 'qty' => 4],
                ['obat' => $daftarObat['A'], 'qty' => 2],
            ]);

            // Bulan lalu: C=10, D=8, E=6, F=5, A=1 (sehingga keseluruhan: C=10, D=8, E=6, F=5, B=4, A=3 -> B posisi 5, A tereliminasi)
            $simpanItem('TRX-TOP2', '2026-10-10 11:00:00', [
                ['obat' => $daftarObat['C'], 'qty' => 10],
                ['obat' => $daftarObat['D'], 'qty' => 8],
                ['obat' => $daftarObat['E'], 'qty' => 6],
                ['obat' => $daftarObat['F'], 'qty' => 5],
                ['obat' => $daftarObat['A'], 'qty' => 1],
            ]);

            // Meskipun periode ringkasan penjualan dipilih harian, top 5 obat terlaris tetap dihitung keseluruhan
            $topDariHarian = $this->getJson('/api/dashboard?periode=harian')
                ->assertOk()
                ->json('data.obat_terlaris');
            $this->assertCount(5, $topDariHarian);
            $this->assertSame(
                ['Obat C', 'Obat D', 'Obat E', 'Obat F', 'Obat B'],
                array_column($topDariHarian, 'nama_obat')
            );
            $this->assertSame([10, 8, 6, 5, 4], array_column($topDariHarian, 'total_terjual'));

            $topDariBulanan = $this->getJson('/api/dashboard?periode=bulanan')
                ->assertOk()
                ->json('data.obat_terlaris');
            $this->assertSame($topDariHarian, $topDariBulanan);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_stok_mendukung_paginasi_dan_filter_kategori(): void
    {
        $this->loginSebagaiOwner();

        $katA = Kategori::create(['nama_kategori' => 'Analgesik']);
        $katB = Kategori::create(['nama_kategori' => 'Vitamin']);

        for ($i = 1; $i <= 52; $i++) {
            $this->buatObat([
                'kategori_id'    => $katA->id,
                'kode_obat'      => sprintf('OBT-%03d', $i),
                'nama_obat'      => sprintf('Obat %03d', $i),
                'stok'           => $i <= 3 ? 5 : 30,
                'tgl_kadaluarsa' => ($i === 1 ? now()->subDay() : now()->addMonths(6))->format('Y-m-d'),
            ]);
        }

        $this->buatObat([
            'kategori_id' => $katB->id,
            'kode_obat'   => 'VIT-001',
            'nama_obat'   => 'Vitamin C 500mg',
            'stok'        => 40,
        ]);

        // Total 53 obat -> halaman 1 berisi 50, halaman 2 berisi 3
        $hal1 = $this->getJson('/api/stok?page=1')
            ->assertOk()
            ->assertJson([
                'success'                    => true,
                'current_page'               => 1,
                'last_page'                  => 2,
                'total'                      => 53,
                'hampir_habis_total'         => 3,
                'perhatian_kadaluarsa_total' => 1,
                'kategoris'                  => ['Analgesik', 'Vitamin'],
            ]);
        $this->assertCount(50, $hal1->json('data'));

        $hal2 = $this->getJson('/api/stok?page=2')
            ->assertOk()
            ->assertJson([
                'current_page' => 2,
                'last_page'    => 2,
            ]);
        $this->assertCount(3, $hal2->json('data'));

        // Filter kategori Vitamin tetap mengembalikan daftar kategori lengkap
        $filterKat = $this->getJson('/api/stok?kategori=Vitamin')
            ->assertOk()
            ->assertJson([
                'total'                      => 1,
                'last_page'                  => 1,
                'hampir_habis_total'         => 0,
                'perhatian_kadaluarsa_total' => 0,
                'kategoris'                  => ['Analgesik', 'Vitamin'],
            ]);
        $this->assertSame('VIT-001', $filterKat->json('data.0.kode_obat'));
    }

    public function test_preferensi_notifikasi_tersimpan_untuk_owner_yang_login(): void
    {
        $owner = $this->loginSebagaiOwner();

        $this->getJson('/api/notifikasi')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => [
                    'aktif'         => true,
                    'batas_stok'    => 10,
                    'jam_ringkasan' => null,
                ],
            ]);

        $this->putJson('/api/notifikasi', [
            'aktif'         => false,
            'batas_stok'    => 15,
            'jam_ringkasan' => '09:30',
            'fcm_token'     => 'token-fcm-contoh',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('pengaturan_notifikasi', [
            'user_id'       => $owner->id,
            'aktif'         => 0,
            'batas_stok'    => 15,
            'jam_ringkasan' => '09:30',
            'fcm_token'     => 'token-fcm-contoh',
        ]);

        $this->getJson('/api/notifikasi')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => [
                    'aktif'         => false,
                    'batas_stok'    => 15,
                    'jam_ringkasan' => '09:30',
                ],
            ]);
    }

    public function test_riwayat_menyertakan_metode_bayar_dan_filter_laporan_lengkap(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00'));

        try {
            $this->loginSebagaiOwner();

            $katAnalgesik = Kategori::firstOrCreate(['nama_kategori' => 'Analgesik']);
            $katVitamin = Kategori::firstOrCreate(['nama_kategori' => 'Vitamin']);

            $paracetamol = $this->buatObat([
                'kategori_id' => $katAnalgesik->id,
                'kode_obat'   => 'LAP-001',
                'nama_obat'   => 'Paracetamol Forte',
                'harga_jual'  => 10000,
            ]);
            $vitaminC = $this->buatObat([
                'kategori_id' => $katVitamin->id,
                'kode_obat'   => 'LAP-002',
                'nama_obat'   => 'Vitamin C 500mg',
                'harga_jual'  => 15000,
            ]);

            $kasir = User::create([
                'nama'     => 'Kasir Laporan',
                'username' => 'kasir_laporan',
                'password' => Hash::make('rahasia123'),
                'role'     => 'karyawan',
                'status'   => 'aktif',
            ]);

            $tCash = Transaksi::create([
                'no_transaksi' => 'TRX-CASH-01',
                'metode_bayar' => Transaksi::METODE_CASH,
                'user_id'      => $kasir->id,
                'total_harga'  => 30000,
                'total_bayar'  => 50000,
                'kembalian'    => 20000,
            ]);
            $tCash->forceFill(['created_at' => '2026-11-18 09:00:00'])->save();
            DetailTransaksi::create([
                'transaksi_id' => $tCash->id,
                'obat_id'      => $paracetamol->id,
                'qty'          => 3,
                'harga_satuan' => 10000,
                'subtotal'     => 30000,
            ]);

            $tQris = Transaksi::create([
                'no_transaksi' => 'TRX-QRIS-01',
                'metode_bayar' => Transaksi::METODE_QRIS,
                'user_id'      => $kasir->id,
                'total_harga'  => 30000,
                'total_bayar'  => 30000,
                'kembalian'    => 0,
            ]);
            $tQris->forceFill(['created_at' => '2026-11-05 14:00:00'])->save();
            DetailTransaksi::create([
                'transaksi_id' => $tQris->id,
                'obat_id'      => $vitaminC->id,
                'qty'          => 2,
                'harga_satuan' => 15000,
                'subtotal'     => 30000,
            ]);

            // 1. /api/riwayat menyertakan metode_bayar & metode_bayar_label
            $this->getJson('/api/riwayat')
                ->assertOk()
                ->assertJsonPath('data.0.no_transaksi', 'TRX-CASH-01')
                ->assertJsonPath('data.0.metode_bayar', 'cash')
                ->assertJsonPath('data.0.metode_bayar_label', 'Cash')
                ->assertJsonPath('data.1.no_transaksi', 'TRX-QRIS-01')
                ->assertJsonPath('data.1.metode_bayar', 'qris')
                ->assertJsonPath('data.1.metode_bayar_label', 'QRIS');

            // 2. /api/laporan filter periode hari_ini hanya mengambil TRX-CASH-01
            $this->getJson('/api/laporan?periode=hari_ini')
                ->assertOk()
                ->assertJsonPath('total', 1)
                ->assertJsonPath('ringkasan.total_pendapatan', 30000)
                ->assertJsonPath('ringkasan.total_cash', 30000)
                ->assertJsonPath('ringkasan.total_qris', 0)
                ->assertJsonPath('data.0.nama_obat', 'Paracetamol Forte');

            // 3. /api/laporan filter kategori + rentang tanggal + nama obat
            $this->getJson('/api/laporan?periode=bulan_ini&kategori=Vitamin&nama_obat=Vitamin%20C')
                ->assertOk()
                ->assertJsonPath('total', 1)
                ->assertJsonPath('ringkasan.total_pendapatan', 30000)
                ->assertJsonPath('ringkasan.total_qris', 30000)
                ->assertJsonPath('ringkasan.total_qty', 2)
                ->assertJsonPath('data.0.no_transaksi', 'TRX-QRIS-01')
                ->assertJsonPath('data.0.metode_bayar_label', 'QRIS');

            // 4. Ekspor Excel menghasilkan file XLSX valid sesuai filter
            $resExcel = $this->get('/api/laporan/export?format=excel&kategori=Vitamin')
                ->assertOk();
            $this->assertStringContainsString(
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                (string) $resExcel->headers->get('Content-Type')
            );
            $xlsxBytes = $resExcel->getContent();
            $this->assertSame("PK", substr($xlsxBytes, 0, 2));

            $tmpXlsx = tempnam(sys_get_temp_dir(), 'test_xlsx_');
            file_put_contents($tmpXlsx, $xlsxBytes);
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($tmpXlsx) === true);
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            @unlink($tmpXlsx);

            $this->assertIsString($sheetXml);
            $this->assertStringContainsString('Vitamin C 500mg', $sheetXml);
            $this->assertStringNotContainsString('Paracetamol Forte', $sheetXml);

            // 5. Ekspor PDF menghasilkan dokumen PDF valid sesuai filter
            $resPdf = $this->get('/api/laporan/export?format=pdf&nama_obat=Paracetamol')
                ->assertOk();
            $this->assertSame('application/pdf', $resPdf->headers->get('Content-Type'));
            $pdfBytes = $resPdf->getContent();
            $this->assertStringStartsWith('%PDF-1.4', $pdfBytes);
            $this->assertStringEndsWith('%%EOF', trim($pdfBytes));
            $this->assertStringContainsString('Paracetamol Forte', $pdfBytes);
            $this->assertStringNotContainsString('Vitamin C 500mg', $pdfBytes);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_profil_apotek_mendukung_jam_hari_24jam_dan_tutup_paksa(): void
    {
        try {
            $this->loginSebagaiOwner();

            // Rabu 10:00 (dalam jam default setiap hari 07:30 - 21:00) -> Buka
            Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00', 'Asia/Jakarta'));
            $this->getJson('/api/profil')
                ->assertOk()
                ->assertJsonPath('data.jam_buka', '07:30')
                ->assertJsonPath('data.jam_tutup', '21:00')
                ->assertJsonPath('data.buka_24_jam', false)
                ->assertJsonPath('data.tutup_paksa', false)
                ->assertJsonPath('data.is_buka', true)
                ->assertJsonPath('data.status_toko', 'Buka');

            // Rabu 22:00 (di luar jam 07:30 - 21:00) -> Tutup
            Carbon::setTestNow(Carbon::parse('2026-11-18 22:00:00', 'Asia/Jakarta'));
            $this->getJson('/api/profil')
                ->assertOk()
                ->assertJsonPath('data.is_buka', false)
                ->assertJsonPath('data.status_toko', 'Tutup')
                ->assertJsonPath('data.kode_status', 'tutup_jam');

            // Aktifkan 24 Jam -> pada pukul 22:00 tetap Buka
            $this->putJson('/api/profil', [
                'nama_apotek'      => 'Apotek Astria 2 Farma',
                'alamat'           => 'Jl. Mastrip No.79A, Kediri',
                'jam_buka'         => '07:30',
                'jam_tutup'        => '21:00',
                'buka_24_jam'      => true,
                'hari_operasional' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                'tutup_paksa'      => false,
            ])->assertOk()
                ->assertJsonPath('data.is_buka', true)
                ->assertJsonPath('data.status_toko', 'Buka')
                ->assertJsonPath('data.kode_status', 'buka_24_jam');

            // Aktifkan Tutup Paksa meskipun 24 jam -> status toko menjadi Tutup
            $this->putJson('/api/profil', [
                'nama_apotek'      => 'Apotek Astria 2 Farma',
                'alamat'           => 'Jl. Mastrip No.79A, Kediri',
                'buka_24_jam'      => true,
                'hari_operasional' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                'tutup_paksa'      => true,
                'alasan_tutup'     => 'Penjaga berhalangan hadir',
            ])->assertOk()
                ->assertJsonPath('data.is_buka', false)
                ->assertJsonPath('data.status_toko', 'Tutup')
                ->assertJsonPath('data.kode_status', 'tutup_paksa')
                ->assertJsonPath('data.keterangan_status', 'Penjaga berhalangan hadir');

            // Dashboard ikut menyertakan status toko terkini
            $this->getJson('/api/dashboard')
                ->assertOk()
                ->assertJsonPath('data.profil_apotek.is_buka', false)
                ->assertJsonPath('data.profil_apotek.status_toko', 'Tutup')
                ->assertJsonPath('data.profil_apotek.tutup_paksa', true);

            // Jika Tutup Paksa dimatikan dan Rabu tidak masuk hari operasional -> Libur operasional
            $this->putJson('/api/profil', [
                'nama_apotek'      => 'Apotek Astria 2 Farma',
                'alamat'           => 'Jl. Mastrip No.79A, Kediri',
                'buka_24_jam'      => false,
                'hari_operasional' => ['Senin', 'Selasa', 'Kamis', 'Jumat', 'Sabtu'],
                'tutup_paksa'      => false,
            ])->assertOk()
                ->assertJsonPath('data.is_buka', false)
                ->assertJsonPath('data.kode_status', 'libur');
        } finally {
            Carbon::setTestNow();
        }
    }
}
