<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kondisi_stok_obat', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('obat_id')
                ->constrained('obat_batch')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kondisi_stok_obat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });
    }
};