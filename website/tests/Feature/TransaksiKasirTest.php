<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Obat;
use App\Models\ProfilApotek;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransaksiKasirTest extends TestCase
{
    use RefreshDatabase;

    private User $karyawan;
    private Obat $obat;

    protected function setUp(): void
    {
        parent::setUp();

        ProfilApotek::create([
            'nama_apotek' => 'Apotek Astria',
            'alamat'      => 'Jl. Contoh No. 1',
        ]);

        $this->karyawan = User::create([
            'nama'     => 'Kasir Uji',
            'username' => 'kasir_uji',
            'password' => Hash::make('rahasia123'),
            'role'     => 'karyawan',
            'status'   => 'aktif',
        ]);

        $kategori = Kategori::firstOrCreate([
            'nama_kategori' => 'Analgesik',
        ]);

        $this->obat = Obat::create([
            'kategori_id'    => $kategori->id,
            'kode_obat'      => 'OBT-001',
            'nama_obat'      => 'Paracetamol 500mg',
            'satuan'         => 'Strip',
            'harga_beli'     => 10000,
            'margin'         => 50,
            'tipe_margin'    => 'persen',
            'harga_jual'     => 15000,
            'stok'           => 30,
            'status'         => 'tersedia',
            'tgl_kadaluarsa' => now()->addYear()->toDateString(),
        ]);
    }

    public function test_halaman_kasir_menampilkan_pilihan_metode_bayar_cash_dan_qris(): void
    {
        $response = $this->actingAs($this->karyawan)->get('/kasir');

        $response->assertOk()
            ->assertSee('btnMetodeCash', false)
            ->assertSee('btnMetodeQris', false)
            ->assertSee('modalQris', false)
            ->assertSee('Cash')
            ->assertSee('QRIS');
    }

    public function test_transaksi_cash_menolak_bayar_kurang_dan_menyimpan_kembalian(): void
    {
        $gagal = $this->actingAs($this->karyawan)->postJson('/kasir/transaksi', [
            'items' => [
                ['obat_id' => $this->obat->id, 'jumlah' => 2],
            ],
            'metode_bayar' => Transaksi::METODE_CASH,
            'total_bayar'  => 20000,
        ]);

        $gagal->assertStatus(422);
        $this->assertSame(30, (int) $this->obat->fresh()->stok);

        $sukses = $this->actingAs($this->karyawan)->postJson('/kasir/transaksi', [
            'items' => [
                ['obat_id' => $this->obat->id, 'jumlah' => 2],
            ],
            'metode_bayar' => Transaksi::METODE_CASH,
            'total_bayar'  => 50000,
        ]);

        $sukses->assertOk()->assertJson(['success' => true]);
        $transaksiId = $sukses->json('transaksi_id');

        $transaksi = Transaksi::findOrFail($transaksiId);
        $this->assertSame(Transaksi::METODE_CASH, $transaksi->metode_bayar);
        $this->assertSame('Cash', $transaksi->metode_bayar_label);
        $this->assertSame(30000, (int) $transaksi->total_harga);
        $this->assertSame(50000, (int) $transaksi->total_bayar);
        $this->assertSame(20000, (int) $transaksi->kembalian);
        $this->assertSame(28, (int) $this->obat->fresh()->stok);

        $this->actingAs($this->karyawan)
            ->get('/kasir/riwayat')
            ->assertOk()
            ->assertSee($transaksi->no_transaksi)
            ->assertSee('Cash');

        $this->actingAs($this->karyawan)
            ->get('/kasir/struk/' . $transaksi->id)
            ->assertOk()
            ->assertSee($transaksi->no_transaksi)
            ->assertSee('Metode   : Cash')
            ->assertSee('Bayar (Cash)')
            ->assertSee('<td>Kembali</td>', false);
    }

    public function test_transaksi_qris_otomatis_uang_pas_tanpa_kembalian(): void
    {
        $response = $this->actingAs($this->karyawan)->postJson('/kasir/transaksi', [
            'items' => [
                ['obat_id' => $this->obat->id, 'jumlah' => 2],
            ],
            'metode_bayar' => Transaksi::METODE_QRIS,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $transaksi = Transaksi::findOrFail($response->json('transaksi_id'));

        $this->assertSame(Transaksi::METODE_QRIS, $transaksi->metode_bayar);
        $this->assertSame('QRIS', $transaksi->metode_bayar_label);
        $this->assertSame(30000, (int) $transaksi->total_harga);
        $this->assertSame(30000, (int) $transaksi->total_bayar);
        $this->assertSame(0, (int) $transaksi->kembalian);
        $this->assertSame(28, (int) $this->obat->fresh()->stok);

        // Meskipun total_bayar dikirim berbeda dari total_harga, server tetap paksa uang pas.
        $overrideResponse = $this->actingAs($this->karyawan)->postJson('/kasir/transaksi', [
            'items' => [
                ['obat_id' => $this->obat->id, 'jumlah' => 1],
            ],
            'metode_bayar' => Transaksi::METODE_QRIS,
            'total_bayar'  => 50000,
        ]);

        $overrideResponse->assertOk()->assertJson(['success' => true]);
        $trxOverride = Transaksi::findOrFail($overrideResponse->json('transaksi_id'));
        $this->assertSame(15000, (int) $trxOverride->total_harga);
        $this->assertSame(15000, (int) $trxOverride->total_bayar);
        $this->assertSame(0, (int) $trxOverride->kembalian);

        $this->actingAs($this->karyawan)
            ->get('/kasir/riwayat')
            ->assertOk()
            ->assertSee($transaksi->no_transaksi)
            ->assertSee('QRIS');

        $this->actingAs($this->karyawan)
            ->get('/kasir/struk/' . $transaksi->id)
            ->assertOk()
            ->assertSee($transaksi->no_transaksi)
            ->assertSee('Metode   : QRIS')
            ->assertSee('Bayar (QRIS)')
            ->assertDontSee('<td>Kembali</td>', false);
    }

    public function test_transaksi_tanpa_metode_bayar_default_ke_cash(): void
    {
        $response = $this->actingAs($this->karyawan)->postJson('/kasir/transaksi', [
            'items' => [
                ['obat_id' => $this->obat->id, 'jumlah' => 1],
            ],
            'total_bayar' => 20000,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $transaksi = Transaksi::findOrFail($response->json('transaksi_id'));

        $this->assertSame(Transaksi::METODE_CASH, $transaksi->metode_bayar);
        $this->assertSame(15000, (int) $transaksi->total_harga);
        $this->assertSame(20000, (int) $transaksi->total_bayar);
        $this->assertSame(5000, (int) $transaksi->kembalian);
        $this->assertSame(29, (int) $this->obat->fresh()->stok);
    }
}
