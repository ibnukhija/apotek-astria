<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\KondisiStokObat;
use App\Models\Obat;
use App\Models\StokOpname;
use App\Models\User;
use App\Services\BatchStokService;
use App\Services\StokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StokOpnameTest extends TestCase
{
    use RefreshDatabase;

    private User $karyawan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->karyawan = User::create([
            'nama'     => 'Kasir Opname',
            'username' => 'kasir_opname',
            'password' => Hash::make('rahasia123'),
            'role'     => 'karyawan',
            'status'   => 'aktif',
        ]);
    }

    /** Obat dengan satu batch berisi $stok unit. */
    private function buatObat(int $stok, string $nama = 'Panadol Biru'): Obat
    {
        $kat = Kategori::firstOrCreate(['nama_kategori' => 'Analgesik']);

        $obat = Obat::create([
            'kategori_id'    => $kat->id,
            'kode_obat'      => 'OPN-' . uniqid(),
            'nama_obat'      => $nama,
            'satuan'         => 'Strip',
            'harga_beli'     => 10000,
            'margin'         => 50,
            'tipe_margin'    => 'persen',
            'harga_jual'     => 15000,
            'stok'           => 0,
            'status'         => 'tersedia',
            'tgl_kadaluarsa' => now()->addYear()->format('Y-m-d'),
        ]);

        BatchStokService::terima($obat->id, [
            'kode_batch'     => 'B-' . uniqid(),
            'jumlah'         => $stok,
            'tgl_kadaluarsa' => today()->addMonths(6)->toDateString(),
        ]);

        return $obat->fresh();
    }

    private function mulaiOpname(): StokOpname
    {
        $this->actingAs($this->karyawan)->post('/stok-opname')->assertRedirect();

        return StokOpname::firstOrFail();
    }

    private function isi(StokOpname $opname, array $data): void
    {
        $detail = $opname->details()->firstOrFail();

        $this->actingAs($this->karyawan)
            ->postJson(route('stok-opname.detail', [$opname->id, $detail->id]), $data)
            ->assertOk();
    }

    public function test_alur_dosen_sistem_80_fisik_75_rusak_2_hilang_3(): void
    {
        $obat = $this->buatObat(80);
        $opname = $this->mulaiOpname();

        $this->assertSame(80, (int) $opname->details()->first()->stok_sistem);

        $this->isi($opname, ['stok_fisik' => 75, 'jml_rusak' => 2, 'jml_hilang' => 3]);

        // Draft belum mengubah stok
        $this->assertSame(80, (int) $obat->fresh()->stok);

        $this->actingAs($this->karyawan)
            ->post(route('stok-opname.finalisasi', $opname->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(75, (int) $obat->fresh()->stok);
        $this->assertSame(75, (int) $obat->batches()->first()->stok);
        $this->assertSame('final', $opname->fresh()->status);

        $this->assertDatabaseHas('kondisi_stok_obat', [
            'obat_id' => $obat->id, 'jenis' => KondisiStokObat::JENIS_RUSAK, 'jumlah' => 2,
        ]);
        $this->assertDatabaseHas('kondisi_stok_obat', [
            'obat_id' => $obat->id, 'jenis' => KondisiStokObat::JENIS_HILANG, 'jumlah' => 3,
        ]);
    }

    public function test_rincian_tidak_sama_dengan_selisih_ditolak(): void
    {
        $obat = $this->buatObat(80);
        $opname = $this->mulaiOpname();

        $this->isi($opname, ['stok_fisik' => 75, 'jml_rusak' => 1, 'jml_hilang' => 1]);

        $this->actingAs($this->karyawan)
            ->post(route('stok-opname.finalisasi', $opname->id))
            ->assertSessionHasErrors('opname');

        $this->assertSame(80, (int) $obat->fresh()->stok);
        $this->assertSame('draft', $opname->fresh()->status);
        $this->assertDatabaseCount('kondisi_stok_obat', 0);
    }

    public function test_stok_fisik_belum_diisi_ditolak(): void
    {
        $this->buatObat(10);
        $opname = $this->mulaiOpname();

        $this->actingAs($this->karyawan)
            ->post(route('stok-opname.finalisasi', $opname->id))
            ->assertSessionHasErrors('opname');

        $this->assertSame('draft', $opname->fresh()->status);
    }

    public function test_finalisasi_dua_kali_ditolak(): void
    {
        $obat = $this->buatObat(80);
        $opname = $this->mulaiOpname();
        $this->isi($opname, ['stok_fisik' => 75, 'jml_rusak' => 2, 'jml_hilang' => 3]);

        $this->actingAs($this->karyawan)->post(route('stok-opname.finalisasi', $opname->id));
        $this->actingAs($this->karyawan)
            ->post(route('stok-opname.finalisasi', $opname->id))
            ->assertSessionHasErrors('opname');

        $this->assertSame(75, (int) $obat->fresh()->stok);
        $this->assertDatabaseCount('kondisi_stok_obat', 3);
    }

    public function test_penjualan_saat_penghitungan_memaksa_pengecekan_ulang(): void
    {
        $obat = $this->buatObat(80);
        $opname = $this->mulaiOpname();

        // Kasir menjual 2 unit setelah opname dimulai -> stok sistem jadi 78
        StokService::kurangi([['obat_id' => $obat->id, 'jumlah' => 2]]);
        $this->assertSame(78, (int) $obat->fresh()->stok);

        $this->isi($opname, ['stok_fisik' => 75, 'jml_rusak' => 3, 'jml_hilang' => 0]);

        // Percobaan 1: ditolak, snapshot tetap 80 sebelum petugas menekan "Segarkan Stok Sistem"
        $this->actingAs($this->karyawan)
            ->post(route('stok-opname.finalisasi', $opname->id))
            ->assertSessionHasErrors('opname');

        $this->assertSame(80, (int) $opname->details()->first()->stok_sistem);
        $this->assertSame('draft', $opname->fresh()->status);

        // Segarkan snapshot stok sistem -> terbarui ke 78
        $this->actingAs($this->karyawan)
            ->postJson(route('stok-opname.refresh-stok', $opname->id))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame(78, (int) $opname->details()->first()->stok_sistem);

        // Percobaan 2: selisih kini 3, rincian 3 -> berhasil
        $this->actingAs($this->karyawan)
            ->post(route('stok-opname.finalisasi', $opname->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(75, (int) $obat->fresh()->stok);
    }

    public function test_selisih_lebih_wajib_catatan_dan_menambah_stok(): void
    {
        $obat = $this->buatObat(10);
        $opname = $this->mulaiOpname();

        $this->isi($opname, ['stok_fisik' => 12]);
        $this->actingAs($this->karyawan)
            ->post(route('stok-opname.finalisasi', $opname->id))
            ->assertSessionHasErrors('opname');
        $this->assertSame(10, (int) $obat->fresh()->stok);

        $this->isi($opname, ['stok_fisik' => 12, 'catatan' => 'Ditemukan 2 strip terselip']);
        $this->actingAs($this->karyawan)
            ->post(route('stok-opname.finalisasi', $opname->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(12, (int) $obat->fresh()->stok);
        $this->assertSame(12, (int) $obat->batches()->first()->stok);
        $this->assertDatabaseHas('kondisi_stok_obat', [
            'obat_id' => $obat->id, 'jenis' => KondisiStokObat::JENIS_OPNAME, 'jumlah' => 2,
        ]);
    }

    public function test_hanya_satu_draft_dalam_satu_waktu(): void
    {
        $this->buatObat(5);
        $this->mulaiOpname();

        $this->actingAs($this->karyawan)->post('/stok-opname')->assertSessionHasErrors('opname');
        $this->assertSame(1, StokOpname::count());
    }

    public function test_owner_tidak_bisa_membuka_halaman_opname_web(): void
    {
        $owner = User::create([
            'nama'     => 'Owner',
            'username' => 'owner_opn',
            'password' => Hash::make('rahasia123'),
            'role'     => 'owner',
            'status'   => 'aktif',
        ]);

        $this->get('/stok-opname')->assertRedirect('/');
        $this->actingAs($owner)->get('/stok-opname')->assertRedirect('/');
    }
}