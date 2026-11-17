<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_apotek', function (Blueprint $table) {
            $table->string('jam_buka', 5)->default('07:30')->after('longitude');
            $table->string('jam_tutup', 5)->default('21:00')->after('jam_buka');
            $table->boolean('buka_24_jam')->default(false)->after('jam_tutup');
            $table->json('hari_operasional')->nullable()->after('buka_24_jam');
            $table->boolean('tutup_paksa')->default(false)->after('hari_operasional');
            $table->string('alasan_tutup')->nullable()->after('tutup_paksa');
        });
    }

    public function down(): void
    {
        Schema::table('profil_apotek', function (Blueprint $table) {
            $table->dropColumn([
                'jam_buka',
                'jam_tutup',
                'buka_24_jam',
                'hari_operasional',
                'tutup_paksa',
                'alasan_tutup',
            ]);
        });
    }
};
