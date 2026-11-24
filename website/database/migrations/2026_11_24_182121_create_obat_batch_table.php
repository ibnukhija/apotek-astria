<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obat_batch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->string('kode_batch', 50);
            $table->string('supplier', 100)->nullable();
            $table->date('tgl_terima');
            $table->date('tgl_kadaluarsa')->nullable();
            $table->integer('stok_awal');
            $table->integer('stok');
            $table->integer('harga_beli')->nullable();
            $table->timestamps();

            $table->unique(['obat_id', 'kode_batch']);
            $table->index(['obat_id', 'tgl_kadaluarsa']);
        });

        // Data lama: tiap obat yang punya stok jadi 1 batch awal,
        // memakai tanggal kadaluarsa aslinya, sehingga total stok tetap sama.
        foreach (DB::table('obat')->where('stok', '>', 0)->get() as $o) {
            DB::table('obat_batch')->insert([
                'obat_id'        => $o->id,
                'kode_batch'     => 'BATCH-AWAL',
                'tgl_terima'     => now()->toDateString(),
                'tgl_kadaluarsa' => $o->tgl_kadaluarsa ?? '2099-12-31',
                'stok_awal'      => $o->stok,
                'stok'           => $o->stok,
                'harga_beli'     => $o->harga_beli ?? null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('obat_batch');
    }
};