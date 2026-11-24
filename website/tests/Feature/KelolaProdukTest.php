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
            'stok' => 50, 'tgl_kadaluarsa' => now()->addYear()->format('Y-m-d'),
            'status' => 'tersedia',
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

    public function test_batas_status_kadaluarsa(): void
    {
        $this->assertSame('Kadaluarsa', Obat::infoKadaluarsa(now()->subDay()->format('Y-m-d'))['label']);
        $this->assertSame('Hampir kadaluarsa', Obat::infoKadaluarsa(now()->format('Y-m-d'))['label']);
        $this->assertSame('Hampir kadaluarsa', Obat::infoKadaluarsa(now()->addDays(7)->format('Y-m-d'))['label']);
        $this->assertSame('Aman', Obat::infoKadaluarsa(now()->addDays(8)->format('Y-m-d'))['label']);
    }

    public function test_validasi_dan_simpan_tgl_kadaluarsa(): void
    {
        $this->login();
        $this->post('/obat', $this->dataObat(['tgl_kadaluarsa' => '']))
            ->assertSessionHasErrors('tgl_kadaluarsa');

        $tglBaru = now()->addMonths(6)->format('Y-m-d');
        $this->post('/obat', $this->dataObat(['tgl_kadaluarsa' => $tglBaru]))
            ->assertRedirect('/obat');

        $obat = Obat::first();
        $this->assertSame($tglBaru, $obat->tgl_kadaluarsa->format('Y-m-d'));

        // Obat ini sudah punya BATCH-AWAL (stok 50), jadi tanggal kadaluarsa
        // diturunkan dari batch dan tidak berubah lewat form edit.
        $tglUpdate = now()->addDays(15)->format('Y-m-d');
        $this->put('/obat/' . $obat->id, $this->dataObat(['tgl_kadaluarsa' => $tglUpdate]))
            ->assertRedirect('/obat');

        $this->assertSame($tglBaru, $obat->fresh()->tgl_kadaluarsa->format('Y-m-d'));
    }

    /* ---------- BARU: batch awal & penguncian stok/kadaluarsa ---------- */

    public function test_obat_baru_dengan_stok_otomatis_punya_batch_awal(): void
    {
        $this->login();
        $this->post('/obat', $this->dataObat(['stok' => 10]));
        $obat = Obat::firstOrFail();

        $this->assertDatabaseHas('obat_batch', [
            'obat_id'    => $obat->id,
            'kode_batch' => 'BATCH-AWAL',
            'stok'       => 10,
        ]);
    }

    public function test_stok_dan_kadaluarsa_obat_berbatch_tidak_berubah_lewat_form_edit(): void
    {
        $this->login();
        $this->post('/obat', $this->dataObat(['stok' => 50]));
        $obat = Obat::firstOrFail();
        $tglAsli = $obat->tgl_kadaluarsa->format('Y-m-d');

        $this->put('/obat/' . $obat->id, $this->dataObat([
            'nama_obat'      => 'Paracetamol Baru',
            'stok'           => 5,
            'tgl_kadaluarsa' => now()->addDays(3)->format('Y-m-d'),
        ]))->assertRedirect('/obat');

        $obat = $obat->fresh();
        $this->assertSame('Paracetamol Baru', $obat->nama_obat);   // field lain tetap berubah
        $this->assertEquals(50, $obat->stok);                       // stok dikunci
        $this->assertSame($tglAsli, $obat->tgl_kadaluarsa->format('Y-m-d'));
    }

    public function test_tgl_kadaluarsa_obat_tanpa_batch_bisa_diedit(): void
    {
        $this->login();
        $this->post('/obat', $this->dataObat(['stok' => 0]));   // stok 0 -> tanpa batch
        $obat = Obat::firstOrFail();
        $this->assertDatabaseCount('obat_batch', 0);

        $tglUpdate = now()->addDays(15)->format('Y-m-d');
        $this->put('/obat/' . $obat->id, $this->dataObat(['stok' => 0, 'tgl_kadaluarsa' => $tglUpdate]))
            ->assertRedirect('/obat');

        $this->assertSame($tglUpdate, $obat->fresh()->tgl_kadaluarsa->format('Y-m-d'));
    }

    public function test_terima_stok_menambah_batch_dan_total_stok(): void
    {
        $this->login();
        $this->post('/obat', $this->dataObat(['stok' => 50]));
        $obat = Obat::firstOrFail();

        $this->post('/obat/' . $obat->id . '/terima-stok', [
            'kode_batch'     => 'B-2026-11',
            'supplier'       => 'PT Contoh',
            'tgl_kadaluarsa' => now()->addMonths(18)->format('Y-m-d'),
            'jumlah'         => 20,
            'harga_beli'     => 8000,
        ])->assertRedirect('/obat')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('obat_batch', ['obat_id' => $obat->id, 'kode_batch' => 'B-2026-11', 'stok' => 20]);
        $this->assertEquals(70, $obat->fresh()->stok);

        // Kode batch yang sama untuk obat yang sama ditolak
        $this->post('/obat/' . $obat->id . '/terima-stok', [
            'kode_batch'     => 'B-2026-11',
            'tgl_kadaluarsa' => now()->addMonths(18)->format('Y-m-d'),
            'jumlah'         => 5,
        ])->assertSessionHasErrors('kode_batch');
    }

    /* ---------- Halaman ---------- */

    public function test_halaman_kelola_produk_menampilkan_kotak_ringkasan_dan_badge(): void
    {
        $this->login();
        foreach ([
            ['A', 50, now()->addDays(90)],
            ['B', 24, now()->addDays(5)],
            ['C', 10, now()->subDays(2)],
            ['D', 7,  now()->addDays(60)],
        ] as [$kode, $stok, $tgl]) {
            $this->post('/obat', $this->dataObat([
                'kode_obat' => $kode,
                'nama_obat' => "Obat $kode",
                'stok' => $stok,
                'tgl_kadaluarsa' => $tgl->format('Y-m-d'),
            ]));
        }

        $this->get('/obat')->assertOk()
            ->assertSee('Total produk aktif')->assertSee('Stok cukup')
            ->assertSee('Stok menipis')->assertSee('Hampir habis')
            ->assertSee('50 (Cukup)', false)->assertSee('24 (Menipis)', false)
            ->assertSee('10 (Menipis)', false)->assertSee('7 (Hampir habis)', false)
            ->assertSee('Hampir kadaluarsa')->assertSee('Kadaluarsa')->assertSee('Aman')
            ->assertSee('Rp12.000', false)->assertSee('50%', false);

        $this->get('/kasir')->assertOk()
            ->assertSee('Hampir kadaluarsa')
            ->assertSee('Kadaluarsa')
            ->assertSee('data-kadaluarsa="1"', false)
            ->assertSee('Peringatan Obat Kadaluarsa');
    }

    public function test_pencarian_dan_filter_kategori(): void
    {
        $this->login();
        $vit = Kategori::create(['nama_kategori' => 'Vitamin']);
        $this->post('/obat', $this->dataObat(['stok' => 30, 'tgl_kadaluarsa' => now()->addDays(45)->format('Y-m-d')]));
        $this->post('/obat', $this->dataObat([
            'kode_obat'      => 'OBT-002',
            'nama_obat'      => 'Vitamin C',
            'kategori_id'    => $vit->id,
            'stok'           => 5,
            'tgl_kadaluarsa' => now()->addDays(3)->format('Y-m-d'),
        ]));
        $this->post('/obat', $this->dataObat([
            'kode_obat'      => 'OBT-003',
            'nama_obat'      => 'Tablet Menipis',
            'kategori_id'    => $vit->id,
            'stok'           => 15,
            'tgl_kadaluarsa' => now()->addDays(45)->format('Y-m-d'),
        ]));

        $this->get('/obat?q=vitamin')->assertSee('Vitamin C')->assertDontSee('Paracetamol 500mg', false);
        $this->get('/obat?kategori=' . $vit->id)->assertSee('Vitamin C')->assertDontSee('Paracetamol 500mg', false);
        $this->get('/obat?q=OBT-001')->assertSee('Paracetamol 500mg', false)->assertDontSee('Vitamin C');

        // Drill-down filter status stok & kadaluarsa pada Kelola Produk dan Dashboard Web
        $this->get('/obat?status_stok=cukup')
            ->assertOk()
            ->assertSee('Paracetamol 500mg')
            ->assertDontSee('Vitamin C')
            ->assertDontSee('Tablet Menipis');

        $this->get('/obat?status_stok=menipis')
            ->assertOk()
            ->assertSee('Tablet Menipis')
            ->assertDontSee('Paracetamol 500mg')
            ->assertDontSee('Vitamin C');

        $this->get('/obat?status_stok=hampir_habis')
            ->assertOk()
            ->assertSee('Vitamin C')
            ->assertDontSee('Tablet Menipis');

        $this->get('/obat?status_stok=perlu_restok')
            ->assertOk()
            ->assertSee('Vitamin C')
            ->assertSee('Tablet Menipis')
            ->assertDontSee('Paracetamol 500mg');

        $this->get('/obat?status_kadaluarsa=perhatian')
            ->assertOk()
            ->assertSee('Vitamin C')
            ->assertDontSee('Paracetamol 500mg');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Operasional')
            ->assertSee('status_stok=perlu_restok', false);
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