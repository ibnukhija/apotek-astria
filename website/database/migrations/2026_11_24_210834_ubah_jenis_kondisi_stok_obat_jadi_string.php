<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kondisi_stok_obat', function (Blueprint $table) {
            $table->string('jenis', 30)->change();
        });
    }

    public function down(): void
    {
        // Sengaja kosong: kembali ke enum bisa gagal jika sudah ada data 'hilang'.
    }
};