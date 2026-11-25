<?php

namespace Tests\Feature;

use App\Models\KondisiStokObat;
use App\Models\Obat;
use App\Models\ObatBatch;
use App\Models\PengaturanNotifikasi;
use App\Models\StokOpname;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_mengisi_batch_kondisi_opname_dan_aman_dijalankan_ulang(): void
    {
        $this->seed();

        $jumlahObatAwal = Obat::count();
        $jumlahBatchAwal = ObatBatch::count();
        $jumlahOpnameAwal = StokOpname::count();
        $jumlahKondisiAwal = KondisiStokObat::count();

        $this->assertGreaterThan(0, $jumlahObatAwal);
        $this->assertGreaterThan($jumlahObatAwal, $jumlahBatchAwal); // ada produk multi-batch
        $this->assertSame(2, $jumlahOpnameAwal);                     // 2 sesi opname final
        $this->assertGreaterThan(0, $jumlahKondisiAwal);

        // Semua obat tersedia berbatch memiliki total stok = jumlah stok semua batch-nya
        $ambroxol = Obat::where('nama_obat', 'Ambroxol 30mg')->firstOrFail();
        $this->assertSame((int) $ambroxol->stok, (int) $ambroxol->batches()->sum('stok'));
        $this->assertGreaterThan(0, $ambroxol->batches()->kadaluarsaBelumDitindak()->count());

        $owner = User::where('role', 'owner')->firstOrFail();
        $pref = PengaturanNotifikasi::where('user_id', $owner->id)->firstOrFail();
        $this->assertTrue($pref->notif_opname);

        // Jalankan seeder kedua kalinya -> tidak duplikat atau error unique constraint
        $this->seed();

        $this->assertSame($jumlahObatAwal, Obat::count());
        $this->assertSame($jumlahBatchAwal, ObatBatch::count());
        $this->assertSame($jumlahOpnameAwal, StokOpname::count());
        $this->assertSame($jumlahKondisiAwal, KondisiStokObat::count());

        foreach (['Paracetamol 500mg', 'Ambroxol 30mg', 'Sanmol 500mg', 'Oskadon', 'Bodrex'] as $nama) {
            $obat = Obat::where('nama_obat', $nama)->firstOrFail();
            $this->assertSame((int) $obat->stok, (int) $obat->batches()->sum('stok'), "Stok batch {$nama} tidak sinkron");
        }
    }
}
