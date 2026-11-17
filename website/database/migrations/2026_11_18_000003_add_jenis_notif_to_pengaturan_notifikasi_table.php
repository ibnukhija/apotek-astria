<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_notifikasi', function (Blueprint $table) {
            $table->boolean('notif_kadaluarsa')->default(true)->after('aktif');
            $table->boolean('notif_rusak')->default(true)->after('notif_kadaluarsa');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_notifikasi', function (Blueprint $table) {
            $table->dropColumn(['notif_kadaluarsa', 'notif_rusak']);
        });
    }
};
