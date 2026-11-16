<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Obat;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_dashboard_dan_stok_memakai_batas_stok_yang_konsisten(): void
    {
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
}
