<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Obat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelolaProdukTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $role = 'karyawan'): void
    {
        $user = new User();
        $user->forceFill([
            'nama' => 'Tester', 'username' => 'tester_' . uniqid(),
            'password' => bcrypt('rahasia123'), 'role' => $role,
        ])->save();
        $this->actingAs($user);
    }

    private function dataObat(array $ubah = []): array
    {
        $kategori = Kategori::firstOrCreate(['nama_kategori' => 'Analgesik']);

        return array_merge([
            'kode_obat' => 'OBT-001', 'nama_obat' => 'Paracetamol 500mg',
            'kategori_id' => $kategori->id, 'satuan' => 'Strip',
            'harga_beli' => 8000, 'margin' => 50, 'tipe_margin' => 'persen',
            'stok' => 50, 'status' => 'tersedia',
        ], $ubah);
    }

    /* ---------- Normalisasi & duplikat kategori ---------- */

    public function test_normalisasi_nama_kategori(): void
    {
        $this->assertSame('Antialergi', Kategori::normalisasi('  ANTIALERGI '));
        $this->assertSame('Anti Alergi', Kategori::normalisasi('anti   alergi'));
        $this->assertSame('Batuk & Flu', Kategori::normalisasi('batuk & FLU'));
        $this->assertSame('Anti-Alergi', Kategori::normalisasi('anti-alergi'));
    }

    public function test_kategori_dengan_penulisan_berbeda_dianggap_sama(): void
    {
        $this->login();
        Kategori::create(['nama_kategori' => 'Antialergi']);

        foreach (['anti alergi', 'ANTI-ALERGI', 'antialergi', ' Anti  Alergi '] as $nama) {
            $this->post('/kategori', ['nama_kategori' => $nama])
                ->assertSessionHasErrors('nama_kategori');
        }
        $this->assertSame(1, Kategori::count());
    }

    public function test_kategori_baru_disimpan_dengan_penulisan_rapi(): void
    {
        $this->login();
        $this->post('/kategori', ['nama_kategori' => 'obat   MATA'])->assertRedirect('/kategori');
        $this->assertDatabaseHas('kategori', ['nama_kategori' => 'Obat Mata']);
    }

    public function test_tambah_kategori_ajax_membalas_json(): void
    {
        $this->login();
        Kategori::create(['nama_kategori' => 'Vitamin']);

        $this->postJson('/kategori/ajax', ['nama_kategori' => 'obat kumur'])
            ->assertOk()->assertJson(['success' => true, 'data' => ['nama_kategori' => 'Obat Kumur']]);

        $this->postJson('/kategori/ajax', ['nama_kategori' => 'VITAMIN'])
            ->assertStatus(422)->assertJson(['success' => false, 'message' => 'Kategori "Vitamin" sudah ada.']);
    }

    public function test_kategori_yang_dipakai_obat_tidak_bisa_dihapus(): void
    {
        $this->login();
        $this->post('/obat', $this->dataObat());
        $kategori = Kategori::first();

        $this->delete('/kategori/' . $kategori->id)->assertSessionHasErrors('hapus');
        $this->assertDatabaseHas('kategori', ['id' => $kategori->id]);
        $this->assertDatabaseCount('obat', 1);

        Obat::query()->delete();
        $this->delete('/kategori/' . $kategori->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('kategori', ['id' => $kategori->id]);
    }

    /* ---------- Harga jual otomatis ---------- */

    public function test_harga_jual_otomatis_margin_persen(): void
    {
        $this->assertSame(12000, Obat::hitungHargaJual(8000, 50, 'persen'));
        $this->assertSame(9100, Obat::hitungHargaJual(7000, 30, 'persen'));
    }

    public function test_harga_jual_otomatis_margin_nominal(): void
    {
        $this->assertSame(12500, Obat::hitungHargaJual(9000, 3500, 'nominal'));
    }

    public function test_simpan_obat_menghitung_harga_jual_di_server(): void
    {
        $this->login();
        // harga_jual kiriman browser harus diabaikan
        $this->post('/obat', $this->dataObat(['harga_jual' => 1]))->assertRedirect('/obat');

        $this->assertDatabaseHas('obat', ['kode_obat' => 'OBT-001', 'harga_jual' => 12000]);
    }

    public function test_ubah_margin_menghitung_ulang_harga_jual(): void
    {
        $this->login();
        $this->post('/obat', $this->dataObat());
        $obat = Obat::first();

        $this->put('/obat/' . $obat->id, $this->dataObat(['margin' => 2500, 'tipe_margin' => 'nominal']));

        $this->assertSame(10500, $obat->fresh()->harga_jual);
    }

    public function test_validasi_kode_obat_unik_dan_kategori_wajib(): void
    {
        $this->login();
        $this->post('/obat', $this->dataObat())->assertSessionHasNoErrors();
        $this->post('/obat', $this->dataObat())->assertSessionHasErrors('kode_obat');
        $this->post('/obat', $this->dataObat(['kode_obat' => 'OBT-009', 'kategori_id' => '']))
            ->assertSessionHasErrors('kategori_id');
    }

    public function test_hapus_produk(): void
    {
        $this->login();
        $this->post('/obat', $this->dataObat());
        $this->delete('/obat/' . Obat::first()->id)->assertRedirect('/obat');
        $this->assertDatabaseCount('obat', 0);
    }

    /* ---------- Status stok (merah / kuning / hijau) ---------- */

    public function test_batas_status_stok(): void
    {
        $this->assertSame('Hampir habis', Obat::infoStok(0)['label']);
        $this->assertSame('Hampir habis', Obat::infoStok(9)['label']);
        $this->assertSame('Menipis', Obat::infoStok(10)['label']);
        $this->assertSame('Menipis', Obat::infoStok(24)['label']);
        $this->assertSame('Cukup', Obat::infoStok(25)['label']);
        $this->assertSame('Cukup', Obat::infoStok(300)['label']);
    }

    /* ---------- Halaman ---------- */

    public function test_halaman_kelola_produk_menampilkan_kotak_ringkasan_dan_badge(): void
    {
        $this->login();
        foreach ([['A', 50], ['B', 24], ['C', 10], ['D', 7]] as [$kode, $stok]) {
            $this->post('/obat', $this->dataObat(['kode_obat' => $kode, 'nama_obat' => "Obat $kode", 'stok' => $stok]));
        }

        $this->get('/obat')->assertOk()
            ->assertSee('Total produk aktif')->assertSee('Stok cukup')
            ->assertSee('Stok menipis')->assertSee('Hampir habis')
            ->assertSee('50 (Cukup)', false)->assertSee('24 (Menipis)', false)
            ->assertSee('10 (Menipis)', false)->assertSee('7 (Hampir habis)', false)
            ->assertSee('Rp12.000', false)->assertSee('50%', false);
    }

    public function test_pencarian_dan_filter_kategori(): void
    {
        $this->login();
        $vit = Kategori::create(['nama_kategori' => 'Vitamin']);
        $this->post('/obat', $this->dataObat());
        $this->post('/obat', $this->dataObat(['kode_obat' => 'OBT-002', 'nama_obat' => 'Vitamin C', 'kategori_id' => $vit->id]));

        $this->get('/obat?q=vitamin')->assertSee('Vitamin C')->assertDontSee('Paracetamol 500mg', false);
        $this->get('/obat?kategori=' . $vit->id)->assertSee('Vitamin C')->assertDontSee('Paracetamol 500mg', false);
        $this->get('/obat?q=OBT-001')->assertSee('Paracetamol 500mg', false)->assertDontSee('Vitamin C');
    }

    public function test_halaman_kelola_kategori(): void
    {
        $this->login();
        $this->post('/obat', $this->dataObat());
        $this->get('/kategori')->assertOk()->assertSee('Analgesik')->assertSee('1 obat')
            ->assertSee('tidak dapat dihapus, masih dipakai obat');
    }

    public function test_hanya_karyawan_yang_boleh_mengakses(): void
    {
        $this->get('/obat')->assertRedirect('/');
        $this->login('owner');
        $this->get('/obat')->assertRedirect('/');
        $this->get('/kategori')->assertRedirect('/');
    }
}
