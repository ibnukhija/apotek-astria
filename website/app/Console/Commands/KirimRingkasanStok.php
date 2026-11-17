<?php

namespace App\Console\Commands;

use App\Models\PengaturanNotifikasi;
use App\Services\NotifikasiStokService;
use Illuminate\Console\Command;

class KirimRingkasanStok extends Command
{
    protected $signature = 'stok:ringkasan-harian';
    protected $description = 'Kirim ringkasan stok menipis, H-7/kadaluarsa, dan obat rusak sesuai preferensi owner';

    public function handle(): int
    {
        $sekarang = now('Asia/Jakarta')->format('H:i');

        PengaturanNotifikasi::whereNotNull('fcm_token')
            ->whereNotNull('jam_ringkasan')
            ->where(function ($q) {
                $q->where('aktif', true)
                    ->orWhere('notif_kadaluarsa', true)
                    ->orWhere('notif_rusak', true);
            })
            ->get()
            ->filter(fn ($p) => substr($p->jam_ringkasan, 0, 5) === $sekarang)
            ->each(fn ($p) => NotifikasiStokService::kirimRingkasan($p));

        return self::SUCCESS;
    }
}