<?php

namespace App\Console\Commands;

use App\Models\PengaturanNotifikasi;
use App\Services\NotifikasiStokService;
use Illuminate\Console\Command;

class KirimRingkasanStok extends Command
{
    protected $signature = 'stok:ringkasan-harian';
    protected $description = 'Kirim ringkasan stok menipis ke owner sesuai jam yang dipilih';

    public function handle(): int
    {
        $sekarang = now('Asia/Jakarta')->format('H:i');

        PengaturanNotifikasi::where('aktif', true)
            ->whereNotNull('fcm_token')
            ->whereNotNull('jam_ringkasan')
            ->get()
            ->filter(fn ($p) => substr($p->jam_ringkasan, 0, 5) === $sekarang)
            ->each(fn ($p) => NotifikasiStokService::kirimRingkasan($p));

        return self::SUCCESS;
    }
}