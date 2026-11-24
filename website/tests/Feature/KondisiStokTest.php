<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\KondisiStokObat;
use App\Models\Obat;
use App\Models\ObatBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KondisiStokTest extends TestCase
{
    use RefreshDatabase;

    private User $karyawan;
    private Obat $obat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->karyawan = User::create([
            'nama'     => 'Kasir Kondisi',
            'username' => 'kasir_kondisi',
            'password' => Hash::make('rahasia123'),
            'role'     => 'karyawan',
            'status'   => 'aktif',
        ]);

        $kat = Kategori::firstOrCreate(['nama_kategori' => 'Analgesik']);

        $this->obat = Obat::create([
            'kategori_id'    => $kat->id,
            'kode_obat'      => 'KND-001',
            'nama_obat'      => 'Amoxicillin 500mg',
            'satuan'         => 'Strip',
            'harga_beli'     => 10000,
            'margin'         => 50,
            'tipe_margin'    => 'persen',
            'harga_jual'     => 15000,
            'stok'           => 20,
            'status'         => 'tersedia',
            'tgl_kadaluarsa' => now()->addDays(5)->format('Y-m-d'),
        ]);
    }

    private function buatBatch(Obat $obat, string $kode, int $stok, int $hariKadaluarsa): ObatBatch
    {
        return ObatBatch::create([
            'obat_id'        => $obat->id,
            'kode_batch'     => $kode,
            'tgl_terima'     => today()->toDateString(),
            'tgl_kadaluarsa' => now()->addDays($hariKadaluarsa)->format('Y-m-d'),
            'stok_awal'      => $stok,
            'stok'           => $stok,
            'harga_beli'     => 10000,
        ]);
    }

    public function test_halaman_kondisi_stok_hanya_bisa_diakses_karyawan(): void
    {
        $this->get('/kondisi-stok')->assertRedirect('/');

        $owner = User::create([
            'nama'     => 'Owner',
            'username' => 'owner_knd',
            'password' => Hash::make('rahasia123'),
            'role'     => 'owner',
            'status'   => 'aktif',
        ]);

        $this->actingAs($owner)->get('/kondisi-stok')->assertRedirect('/');
        $this->actingAs($this->karyawan)->get('/kondisi-stok')
            ->assertOk()
            ->assertSee('Kondisi Obat')
            ->assertSee('Stok Hampir Habis (&lt; 10)', false)
            ->assertSee('f_obat_search', false)
            ->assertSee('chkFilterEd', false)
            ->assertSee('Amoxicillin 500mg')
            ->assertSee('kelas_kadaluarsa', false)
            ->assertSee($this->obat->tgl_kadaluarsa->format('d/m/Y'));
    }

    public function test_catat_obat_rusak_retur_musnah_dan_stok_opname(): void
    {
        // 1. Tolak jika jumlah rusak melebihi stok sistem (20)
        $this->actingAs($this->karyawan)->post('/kondisi-stok', [
            'obat_id'  => $this->obat->id,
            'jenis'    => KondisiStokObat::JENIS_RUSAK,
            'jumlah'   => 25,
            'penyebab' => 'Kemasan sobek',
        ])->assertSessionHasErrors('jumlah');

        // 2. Catat obat rusak (3 unit -> stok jadi 17)
        $this->actingAs($this->karyawan)->post('/kondisi-stok', [
            'obat_id'  => $this->obat->id,
            'jenis'    => KondisiStokObat::JENIS_RUSAK,
            'jumlah'   => 3,
            'penyebab' => 'Kemasan sobek di rak',
            'catatan'  => 'Batch A1',
        ])->assertRedirect('/kondisi-stok');

        $this->assertSame(17, (int) $this->obat->fresh()->stok);

        // 3. Catat retur ke supplier (5 unit -> stok jadi 12)
        $this->actingAs($this->karyawan)->post('/kondisi-stok', [
            'obat_id'  => $this->obat->id,
            'jenis'    => KondisiStokObat::JENIS_RETUR,
            'jumlah'   => 5,
            'penyebab' => 'Retur mendekati expired H-5',
        ])->assertRedirect('/kondisi-stok');

        $this->assertSame(12, (int) $this->obat->fresh()->stok);

        // 4. Cek stok fisik / opname (fisik terhitung 15 -> selisih +3, stok jadi 15)
        $this->actingAs($this->karyawan)->post('/kondisi-stok', [
            'obat_id'    => $this->obat->id,
            'jenis'      => KondisiStokObat::JENIS_OPNAME,
            'stok_fisik' => 15,
            'penyebab'   => 'Stok opname bulanan ditemukan 3 strip terselip',
        ])->assertRedirect('/kondisi-stok');

        $this->assertSame(15, (int) $this->obat->fresh()->stok);

        $opname = KondisiStokObat::where('jenis', KondisiStokObat::JENIS_OPNAME)->firstOrFail();
        $this->assertSame(12, (int) $opname->stok_sebelum);
        $this->assertSame(15, (int) $opname->stok_sesudah);
        $this->assertSame(3, (int) $opname->jumlah);

        // 5. Catat pemusnahan expired (2 unit -> stok jadi 13)
        $this->actingAs($this->karyawan)->post('/kondisi-stok', [
            'obat_id'  => $this->obat->id,
            'jenis'    => KondisiStokObat::JENIS_MUSNAH,
            'jumlah'   => 2,
            'penyebab' => 'Dimusnahkan karena expired',
        ])->assertRedirect('/kondisi-stok');

        $this->assertSame(13, (int) $this->obat->fresh()->stok);

        // 6. Cek API Dashboard dan Stok Owner menyertakan peringatan rusak + opname
        $owner = User::create([
            'nama'     => 'Owner API',
            'username' => 'owner_api_knd',
            'password' => Hash::make('rahasia123'),
            'role'     => 'owner',
            'status'   => 'aktif',
        ]);
        Sanctum::actingAs($owner);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.ringkasan_rusak.total_unit', 3)
            ->assertJsonPath('data.ringkasan_rusak.jumlah_catatan', 1)
            ->assertJsonPath('data.peringatan_rusak.0.nama_obat', 'Amoxicillin 500mg')
            ->assertJsonPath('data.peringatan_rusak.0.penyebab', 'Kemasan sobek di rak')
            ->assertJsonPath('data.peringatan_kadaluarsa.0.saran_tindakan', 'Retur ke supplier');

        $this->getJson('/api/stok')
            ->assertOk()
            ->assertJsonPath('total_unit_rusak', 3)
            ->assertJsonPath('data.0.total_rusak', 3);
    }

    public function test_batch_milik_obat_lain_ditolak(): void
    {
        $kat = Kategori::firstOrCreate(['nama_kategori' => 'Analgesik']);
        $obatLain = Obat::create([
            'kategori_id'    => $kat->id,
            'kode_obat'      => 'KND-002',
            'nama_obat'      => 'Ibuprofen 400mg',
            'satuan'         => 'Strip',
            'harga_beli'     => 5000,
            'margin'         => 50,
            'tipe_margin'    => 'persen',
            'harga_jual'     => 7500,
            'stok'           => 10,
            'status'         => 'tersedia',
            'tgl_kadaluarsa' => now()->addDays(60)->format('Y-m-d'),
        ]);

        $this->buatBatch($this->obat, 'A-1', 20, 5);
        $batchLain = $this->buatBatch($obatLain, 'B-1', 10, 60);

        // Obat = Amoxicillin, tetapi batch milik Ibuprofen
        $this->actingAs($this->karyawan)->post('/kondisi-stok', [
            'obat_id'  => $this->obat->id,
            'batch_id' => $batchLain->id,
            'jenis'    => KondisiStokObat::JENIS_RUSAK,
            'jumlah'   => 2,
            'penyebab' => 'Percobaan batch obat lain',
        ])->assertSessionHasErrors('batch_id');

        $this->assertSame(20, (int) $this->obat->fresh()->stok);
        $this->assertSame(10, (int) $batchLain->fresh()->stok);
        $this->assertDatabaseCount('kondisi_stok_obat', 0);
    }

    public function test_catat_kondisi_dengan_batch_sendiri_mengurangi_batch_dan_tampil_di_riwayat(): void
    {
        $batch = $this->buatBatch($this->obat, 'A-1', 20, 5);

        $this->actingAs($this->karyawan)->post('/kondisi-stok', [
            'obat_id'  => $this->obat->id,
            'batch_id' => $batch->id,
            'jenis'    => KondisiStokObat::JENIS_RUSAK,
            'jumlah'   => 4,
            'penyebab' => 'Kemasan sobek',
        ])->assertRedirect('/kondisi-stok')->assertSessionHasNoErrors();

        $this->assertSame(16, (int) $batch->fresh()->stok);
        $this->assertSame(16, (int) $this->obat->fresh()->stok);
        $this->assertDatabaseHas('kondisi_stok_obat', [
            'obat_id'  => $this->obat->id,
            'batch_id' => $batch->id,
            'jenis'    => KondisiStokObat::JENIS_RUSAK,
        ]);

        $this->actingAs($this->karyawan)->get('/kondisi-stok')
            ->assertOk()
            ->assertSee('A-1');
    }
}