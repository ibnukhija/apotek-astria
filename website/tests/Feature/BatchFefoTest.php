<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\KondisiStokObat;
use App\Models\Obat;
use App\Models\ProfilApotek;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\BatchStokService;
use App\Services\KondisiStokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BatchFefoTest extends TestCase
{
    use RefreshDatabase;

    private function buatObat(): Obat
    {
        $kat = Kategori::firstOrCreate(['nama_kategori' => 'Analgesik']);

        return Obat::create([
            'kategori_id'    => $kat->id,
            'kode_obat'      => 'FEFO-' . uniqid(),
            'nama_obat'      => 'Obat FEFO',
            'satuan'         => 'Strip',
            'harga_beli'     => 10000,
            'margin'         => 50,
            'tipe_margin'    => 'persen',
            'harga_jual'     => 15000,
            'stok'           => 0,
            'status'         => 'tersedia',
            'tgl_kadaluarsa' => now()->addYear()->format('Y-m-d'),
        ]);
    }

    private function buatKaryawan(): User
    {
        ProfilApotek::create([
            'nama_apotek' => 'Apotek Astria',
            'alamat'      => 'Jl. Contoh No. 1',
        ]);

        return User::create([
            'nama'     => 'Kasir FEFO',
            'username' => 'kasir_fefo',
            'password' => Hash::make('rahasia123'),
            'role'     => 'karyawan',
            'status'   => 'aktif',
        ]);
    }

    public function test_fefo_mengurangi_batch_kadaluarsa_terdekat_dulu(): void
    {
        $obat = $this->buatObat();
        $a = BatchStokService::terima($obat->id, [
            'kode_batch' => 'A', 'jumlah' => 10,
            'tgl_kadaluarsa' => today()->addMonths(2)->toDateString(),
        ]);
        $b = BatchStokService::terima($obat->id, [
            'kode_batch' => 'B', 'jumlah' => 10,
            'tgl_kadaluarsa' => today()->addMonths(6)->toDateString(),
        ]);

        $this->assertSame(20, (int) $obat->fresh()->stok);

        DB::transaction(fn () => BatchStokService::kurangiFefo($obat->id, 12));

        $this->assertSame(0, (int) $a->fresh()->stok);
        $this->assertSame(8, (int) $b->fresh()->stok);
    }

    public function test_batch_kadaluarsa_tidak_bisa_dijual(): void
    {
        $obat = $this->buatObat();
        BatchStokService::terima($obat->id, [
            'kode_batch' => 'X', 'jumlah' => 5,
            'tgl_kadaluarsa' => today()->subDay()->toDateString(),
        ]);

        $this->expectException(ValidationException::class);
        DB::transaction(fn () => BatchStokService::kurangiFefo($obat->id, 1));
    }

    public function test_musnah_per_batch_mengurangi_stok_batch_dan_obat(): void
    {
        $obat = $this->buatObat();
        $b = BatchStokService::terima($obat->id, [
            'kode_batch' => 'K', 'jumlah' => 7,
            'tgl_kadaluarsa' => today()->subDay()->toDateString(),
        ]);

        KondisiStokService::catat([
            'obat_id'  => $obat->id,
            'batch_id' => $b->id,
            'jenis'    => KondisiStokObat::JENIS_MUSNAH,
            'jumlah'   => 7,
            'penyebab' => 'Expired',
        ]);

        $this->assertSame(0, (int) $b->fresh()->stok);
        $this->assertSame(0, (int) $obat->fresh()->stok);
    }

    public function test_kasir_menjual_lintas_batch_dengan_fefo(): void
    {
        $obat = $this->buatObat();
        $a = BatchStokService::terima($obat->id, [
            'kode_batch' => 'A', 'jumlah' => 10,
            'tgl_kadaluarsa' => today()->addMonths(2)->toDateString(),
        ]);
        $b = BatchStokService::terima($obat->id, [
            'kode_batch' => 'B', 'jumlah' => 10,
            'tgl_kadaluarsa' => today()->addMonths(6)->toDateString(),
        ]);

        $this->actingAs($this->buatKaryawan())->postJson('/kasir/transaksi', [
            'items'        => [['obat_id' => $obat->id, 'jumlah' => 12]],
            'metode_bayar' => Transaksi::METODE_QRIS,
        ])->assertOk();

        $this->assertSame(0, (int) $a->fresh()->stok);
        $this->assertSame(8, (int) $b->fresh()->stok);
        $this->assertSame(8, (int) $obat->fresh()->stok);
    }

    public function test_kasir_menolak_jual_obat_yang_semua_batchnya_kadaluarsa(): void
    {
        $obat = $this->buatObat();
        $x = BatchStokService::terima($obat->id, [
            'kode_batch' => 'X', 'jumlah' => 5,
            'tgl_kadaluarsa' => today()->subDay()->toDateString(),
        ]);

        $this->actingAs($this->buatKaryawan())->postJson('/kasir/transaksi', [
            'items'        => [['obat_id' => $obat->id, 'jumlah' => 1]],
            'metode_bayar' => Transaksi::METODE_QRIS,
        ])->assertStatus(422);

        $this->assertSame(5, (int) $x->fresh()->stok);
        $this->assertSame(5, (int) $obat->fresh()->stok);
    }
}