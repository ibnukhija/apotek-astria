<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_opname', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 40)->unique();
            $table->string('periode', 7);                    // contoh: 2026-11
            $table->date('tanggal');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 10)->default('draft');  // draft | final
            $table->text('catatan')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stok_opname_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_opname_id')->constrained('stok_opname')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->integer('stok_sistem');                  // snapshot saat opname dimulai
            $table->integer('stok_fisik')->nullable();       // hasil hitung riil
            $table->integer('jml_rusak')->default(0);
            $table->integer('jml_hilang')->default(0);
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->unique(['stok_opname_id', 'obat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_opname_detail');
        Schema::dropIfExists('stok_opname');
    }
};