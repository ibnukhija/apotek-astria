<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyesuaikan tabel `obat` dan `kategori` dengan ERD pada laporan:
 *  - obat     : + margin, tipe_margin (persen/nominal), status (tersedia/tidak), foto_url
 *  - kategori : nama_kategori dibuat UNIQUE
 *  - FK kategori_id diubah dari CASCADE menjadi RESTRICT supaya menghapus kategori
 *    tidak ikut menghapus semua obat di dalamnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obat', function (Blueprint $table) {
            $table->decimal('margin', 10, 2)->default(0)->after('harga_beli');
            $table->enum('tipe_margin', ['persen', 'nominal'])->default('persen')->after('margin');
            $table->enum('status', ['tersedia', 'tidak'])->default('tersedia')->after('stok');
            $table->string('foto_url')->nullable()->after('status');
        });

        // Data lama: margin diturunkan dari selisih harga jual dan harga beli (nominal).
        DB::table('obat')->update([
            'tipe_margin' => 'nominal',
            'margin'      => DB::raw('harga_jual - harga_beli'),
        ]);

        Schema::table('kategori', function (Blueprint $table) {
            $table->unique('nama_kategori');
        });

        Schema::table('obat', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->foreign('kategori_id')->references('id')->on('kategori')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('obat', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->foreign('kategori_id')->references('id')->on('kategori')->cascadeOnDelete();
        });

        Schema::table('kategori', function (Blueprint $table) {
            $table->dropUnique(['nama_kategori']);
        });

        Schema::table('obat', function (Blueprint $table) {
            $table->dropColumn(['margin', 'tipe_margin', 'status', 'foto_url']);
        });
    }
};
