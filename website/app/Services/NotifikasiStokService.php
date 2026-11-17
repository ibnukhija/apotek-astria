<?php

namespace App\Services;

use App\Models\Obat;
use App\Models\PengaturanNotifikasi;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class NotifikasiStokService
{
    /** Kirim 1 push FCM ke perangkat owner. */
    public static function kirim(PengaturanNotifikasi $p, string $judul, string $isi): bool
    {
        if (!$p->fcm_token) {
            return false;
        }

        try {
            $pesan = CloudMessage::withTarget('token', $p->fcm_token)
                ->withNotification(Notification::create($judul, $isi))
                ->withData(['tujuan' => 'stok'])
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority'     => 'high',
                    'notification' => ['channel_id' => 'stok_menipis'],
                ]));

            Firebase::messaging()->send($pesan);
            return true;
        } catch (NotFound $e) {
            $p->update(['fcm_token' => null]); // token sudah tidak berlaku
            return false;
        } catch (\Throwable $e) {
            Log::warning('FCM gagal: ' . $e->getMessage());
            return false;
        }
    }

    /** Obat tersedia dengan stok <= batas, paling sedikit dulu. */
    public static function daftarMenipis(int $batas)
    {
        return Obat::where('status', 'tersedia')
            ->where('stok', '<=', $batas)
            ->orderBy('stok')
            ->get(['nama_obat', 'stok']);
    }

    /** Ringkasan stok menipis (dipakai jam ringkasan harian dan tombol uji). */
    public static function kirimRingkasan(PengaturanNotifikasi $p, bool $meskipunKosong = false): bool
    {
        $daftar = self::daftarMenipis((int) $p->batas_stok);

        if ($daftar->isEmpty()) {
            if (!$meskipunKosong) {
                return false;
            }
            return self::kirim($p, 'Stok aman', "Tidak ada obat dengan stok di bawah atau sama dengan {$p->batas_stok}.");
        }

        return self::kirim($p, 'Ringkasan stok menipis', self::ringkas($daftar));
    }

    /**
     * Dipanggil setelah transaksi kasir. Hanya obat yang BARU melewati batas
     * pada transaksi ini yang dinotifikasikan (supaya tidak spam).
     */
    public static function cekSetelahTransaksi(array $items): void
    {
        try {
            $pengaturan = PengaturanNotifikasi::where('aktif', true)->whereNotNull('fcm_token')->get();
            if ($pengaturan->isEmpty()) {
                return;
            }

            $obats = Obat::whereIn('id', collect($items)->pluck('obat_id'))->get()->keyBy('id');

            foreach ($pengaturan as $p) {
                $batas = (int) $p->batas_stok;

                $baru = collect($items)
                    ->filter(function ($item) use ($obats, $batas) {
                        $obat = $obats->get($item['obat_id']);
                        if (!$obat) {
                            return false;
                        }
                        $sekarang = (int) $obat->stok;
                        return $sekarang <= $batas && $sekarang + (int) $item['jumlah'] > $batas;
                    })
                    ->map(fn ($item) => $obats->get($item['obat_id']));

                if ($baru->isNotEmpty()) {
                    self::kirim($p, 'Stok obat menipis', self::ringkas($baru));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Cek stok setelah transaksi gagal: ' . $e->getMessage());
        }
    }

    private static function ringkas($daftar): string
    {
        $baris = $daftar->take(5)
            ->map(fn ($o) => "{$o->nama_obat} ({$o->stok})")
            ->implode(', ');
        $sisa = $daftar->count() - 5;

        return $daftar->count() . ' obat di bawah batas: ' . $baris
            . ($sisa > 0 ? " dan $sisa lainnya" : '');
    }
}