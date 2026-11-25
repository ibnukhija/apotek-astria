<?php

namespace App\Services;

use App\Models\KondisiStokObat;
use App\Models\Obat;
use App\Models\PengaturanNotifikasi;
use App\Models\StokOpname;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class NotifikasiStokService
{
    public const CHANNEL_STOK = 'stok_menipis';
    public const CHANNEL_KADALUARSA = 'kadaluarsa';
    public const CHANNEL_RUSAK = 'obat_rusak';

    /** Penanda agar peringatan stok menipis tidak dobel saat satu obat punya beberapa catatan opname. */
    private static array $menipisOpnameTerkirim = [];

    /** Kirim 1 push FCM ke perangkat owner pada channel, jenis, dan tujuan layar tertentu. */
    public static function kirim(
        PengaturanNotifikasi $p,
        string $judul,
        string $isi,
        string $channelId = self::CHANNEL_STOK,
        string $jenis = 'stok_menipis',
        string $tujuan = 'stok'
    ): bool {
        if (!$p->fcm_token) {
            return false;
        }

        try {
            $pesan = CloudMessage::new()->toToken($p->fcm_token)
                ->withNotification(Notification::create($judul, $isi))
                ->withData([
                    'tujuan' => $tujuan,
                    'jenis'  => $jenis,
                    'title'  => $judul,
                    'body'   => $isi,
                ])
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority'     => 'high',
                    'notification' => ['channel_id' => $channelId],
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

    /** Obat tersedia berstok > 0 yang sudah kadaluarsa atau mendekati expired (<= 7 hari). */
    public static function daftarKadaluarsa()
    {
        $batas = Carbon::today()->addDays(Obat::BATAS_HAMPIR_KADALUARSA);

        return Obat::where('status', 'tersedia')
            ->where('stok', '>', 0)
            ->whereNotNull('tgl_kadaluarsa')
            ->whereDate('tgl_kadaluarsa', '<=', $batas)
            ->orderBy('tgl_kadaluarsa')
            ->orderBy('nama_obat')
            ->get(['id', 'nama_obat', 'stok', 'satuan', 'tgl_kadaluarsa']);
    }

    /** Catatan obat rusak hari ini untuk ringkasan harian. */
    public static function daftarRusakHariIni()
    {
        return KondisiStokObat::with('obat:id,nama_obat,satuan')
            ->where('jenis', KondisiStokObat::JENIS_RUSAK)
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Ringkasan kondisi stok harian sesuai preferensi yang diaktifkan owner
     * (stok menipis, kadaluarsa H-7/expired, dan obat rusak).
     */
    public static function kirimRingkasan(PengaturanNotifikasi $p, bool $meskipunKosong = false): bool
    {
        $terkirim = 0;

        if ($p->aktif) {
            $daftar = self::daftarMenipis((int) $p->batas_stok);
            if ($daftar->isNotEmpty()) {
                if (self::kirim($p, 'Ringkasan stok menipis', self::ringkas($daftar), self::CHANNEL_STOK, 'stok_menipis')) {
                    $terkirim++;
                }
            }
        }

        if ($p->notif_kadaluarsa) {
            $kadaluarsa = self::daftarKadaluarsa();
            if ($kadaluarsa->isNotEmpty()) {
                if (self::kirim($p, 'Peringatan obat kadaluarsa & H-7', self::ringkasKadaluarsa($kadaluarsa), self::CHANNEL_KADALUARSA, 'kadaluarsa')) {
                    $terkirim++;
                }
            }
        }

        if ($p->notif_rusak) {
            $rusak = self::daftarRusakHariIni();
            if ($rusak->isNotEmpty()) {
                if (self::kirim($p, 'Ringkasan obat rusak hari ini', self::ringkasRusak($rusak), self::CHANNEL_RUSAK, 'obat_rusak')) {
                    $terkirim++;
                }
            }
        }

        if ($terkirim === 0 && $meskipunKosong) {
            return self::kirim(
                $p,
                'Semua kondisi stok aman',
                'Tidak ada peringatan untuk jenis notifikasi yang aktif saat ini.',
                self::CHANNEL_STOK,
                'stok_menipis'
            );
        }

        return $terkirim > 0;
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
                    self::kirim($p, 'Stok obat menipis', self::ringkas($baru), self::CHANNEL_STOK, 'stok_menipis');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Cek stok setelah transaksi gagal: ' . $e->getMessage());
        }
    }

    /**
     * Dipanggil setelah pencatatan kondisi obat atau stok opname oleh kasir.
     * Mengirim notif obat rusak/hilang/opname dan memeriksa apakah stok ikut turun melewati batas.
     * Catatan yang berasal dari opname bulanan tidak dikirim satu per satu
     * (cukup ringkasan lewat kirimHasilOpname), tapi cek stok menipis tetap jalan.
     */
    public static function cekSetelahKondisiStok(KondisiStokObat $catat, int $stokSebelum): void
    {
        try {
            $obat = Obat::find($catat->obat_id);
            if (!$obat) {
                return;
            }

            $pengaturan = PengaturanNotifikasi::whereNotNull('fcm_token')
                ->where(fn ($q) => $q->where('aktif', true)->orWhere('notif_rusak', true))
                ->get();

            if ($pengaturan->isEmpty()) {
                return;
            }

            $stokSesudah = (int) $catat->stok_sesudah;
            // Opname bulanan: item per item tidak dinotifikasi, cukup 1 ringkasan hasil opname.
            $dariOpnameBulanan = self::dariOpnameBulanan($catat);

            foreach ($pengaturan as $p) {
                $jenisDilaporkan = [
                    KondisiStokObat::JENIS_RUSAK,
                    KondisiStokObat::JENIS_HILANG,
                    KondisiStokObat::JENIS_OPNAME,
                ];

                if (!$dariOpnameBulanan && $p->notif_rusak && in_array($catat->jenis, $jenisDilaporkan, true)) {
                    if ($catat->jenis === KondisiStokObat::JENIS_RUSAK) {
                        $judul = 'Laporan obat rusak';
                        $isi = "{$obat->nama_obat} rusak {$catat->jumlah} {$obat->satuan} ({$catat->penyebab}). Sisa stok: {$stokSesudah}.";
                    } elseif ($catat->jenis === KondisiStokObat::JENIS_HILANG) {
                        $judul = 'Laporan obat hilang';
                        $isi = "{$obat->nama_obat} hilang {$catat->jumlah} {$obat->satuan} ({$catat->penyebab}). Sisa stok: {$stokSesudah}.";
                    } else {
                        $selisih = $stokSesudah - $stokSebelum;
                        $tanda = $selisih > 0 ? "+{$selisih}" : (string) $selisih;
                        $judul = 'Hasil cek stok fisik (opname)';
                        $isi = $selisih === 0
                            ? "Stok opname {$obat->nama_obat}: stok fisik sesuai ({$stokSesudah} {$obat->satuan})."
                            : "Stok opname {$obat->nama_obat}: {$stokSebelum} -> {$stokSesudah} {$obat->satuan} (selisih {$tanda}, {$catat->penyebab}).";
                    }

                    self::kirim($p, $judul, $isi, self::CHANNEL_RUSAK, 'obat_rusak');
                }

                $batas = (int) $p->batas_stok;
                if ($p->aktif && $stokSesudah <= $batas && $stokSebelum > $batas) {
                    // Saat opname bulanan satu obat bisa punya beberapa catatan (rusak, hilang, opname);
                    // kirim peringatan menipis cukup sekali per obat per proses.
                    if ($dariOpnameBulanan) {
                        if (isset(self::$menipisOpnameTerkirim[$p->id][$obat->id])) {
                            continue;
                        }
                        self::$menipisOpnameTerkirim[$p->id][$obat->id] = true;
                    }

                    self::kirim($p, 'Stok obat menipis', self::ringkas(collect([$obat])), self::CHANNEL_STOK, 'stok_menipis');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Cek notifikasi kondisi stok gagal: ' . $e->getMessage());
        }
    }

    /** Ringkasan hasil opname bulanan untuk Owner (sekali setelah finalisasi). */
    public static function kirimHasilOpname(StokOpname $opname): void
    {
        try {
            $opname->loadMissing('details');
            $details = $opname->details;
            $kurang  = (int) $details->sum(fn ($d) => max(0, (int) $d->stok_sistem - (int) $d->stok_fisik));
            $lebih   = (int) $details->sum(fn ($d) => max(0, (int) $d->stok_fisik - (int) $d->stok_sistem));
            $rusak   = (int) $details->sum('jml_rusak');
            $hilang  = (int) $details->sum('jml_hilang');
            $obatSelisih = $details->filter(fn ($d) => (int) $d->stok_fisik !== (int) $d->stok_sistem)->count();

            $isi = $obatSelisih === 0
                ? "Opname {$opname->kode} selesai: semua stok fisik sesuai sistem."
                : "Opname {$opname->kode} selesai: {$obatSelisih} obat selisih. Kurang {$kurang} (rusak {$rusak}, hilang {$hilang}), lebih {$lebih}.";

            PengaturanNotifikasi::whereNotNull('fcm_token')
                ->where('notif_opname', true)
                ->get()
                ->each(fn ($p) => self::kirim($p, 'Hasil stok opname bulanan', $isi, self::CHANNEL_RUSAK, 'stok_opname', 'opname'));
        } catch (\Throwable $e) {
            Log::warning('Notifikasi hasil opname gagal: ' . $e->getMessage());
        }
    }

    /** True jika catatan kondisi stok dibuat otomatis oleh finalisasi opname bulanan. */
    private static function dariOpnameBulanan(KondisiStokObat $catat): bool
    {
        return (bool) preg_match('/^Stok opname OPN-\S+/', (string) $catat->penyebab);
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

    private static function ringkasKadaluarsa($daftar): string
    {
        $hariIni = Carbon::today();
        [$sudah, $hampir] = $daftar->partition(
            fn (Obat $o) => Carbon::parse($o->tgl_kadaluarsa)->startOfDay()->lt($hariIni)
        );

        $bagian = [];
        if ($sudah->isNotEmpty()) {
            $contoh = $sudah->take(3)->pluck('nama_obat')->implode(', ');
            $sisa = $sudah->count() - 3;
            $bagian[] = $sudah->count() . ' sudah expired (musnahkan): ' . $contoh . ($sisa > 0 ? " +{$sisa} lainnya" : '');
        }
        if ($hampir->isNotEmpty()) {
            $contoh = $hampir->take(3)->map(function (Obat $o) use ($hariIni) {
                $hari = $hariIni->diffInDays(Carbon::parse($o->tgl_kadaluarsa)->startOfDay(), false);
                return $hari === 0 ? "{$o->nama_obat} (hari ini)" : "{$o->nama_obat} (H-{$hari})";
            })->implode(', ');
            $sisa = $hampir->count() - 3;
            $bagian[] = $hampir->count() . ' mendekati expired <=7 hari (bisa retur supplier): ' . $contoh . ($sisa > 0 ? " +{$sisa} lainnya" : '');
        }

        return implode(' | ', $bagian);
    }

    private static function ringkasRusak($daftar): string
    {
        $totalUnit = (int) $daftar->sum('jumlah');
        $contoh = $daftar->take(3)
            ->map(fn (KondisiStokObat $k) => ($k->obat?->nama_obat ?? 'Obat') . " ({$k->jumlah} " . ($k->obat?->satuan ?? 'unit') . ": {$k->penyebab})")
            ->implode(', ');
        $sisa = $daftar->count() - 3;

        return "{$totalUnit} unit obat rusak hari ini: {$contoh}" . ($sisa > 0 ? " +{$sisa} lainnya" : '');
    }
}