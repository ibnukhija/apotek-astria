<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->id();
            // Jika transaksi dihapus, detailnya otomatis terhapus (cascade)
            $table->foreignId('transaksi_id')->constrained('transaksi')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->restrictOnDelete();
            $table->integer('qty');
            $table->integer('harga_satuan');
            $table->integer('subtotal');
            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_transaksi');
    }
};