<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class ProfilApotek extends Model
{
    public const SEMUA_HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    public const DEFAULT_JAM_BUKA = '07:30';
    public const DEFAULT_JAM_TUTUP = '21:00';

    protected $table = 'profil_apotek';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'buka_24_jam'      => 'boolean',
            'tutup_paksa'      => 'boolean',
            'hari_operasional' => 'array',
        ];
    }

    public static function ambilAtauDefault(): self
    {
        $profil = self::first();
        if ($profil) {
            return $profil;
        }

        return new self([
            'nama_apotek'      => 'Apotek Astria 2 Farma',
            'alamat'           => 'Jl. Mastrip No.79A, Sukorame, Kec. Mojoroto, Kota Kediri',
            'latitude'         => '-7.8166',
            'longitude'        => '112.0116',
            'jam_buka'         => self::DEFAULT_JAM_BUKA,
            'jam_tutup'        => self::DEFAULT_JAM_TUTUP,
            'buka_24_jam'      => false,
            'hari_operasional' => self::SEMUA_HARI,
            'tutup_paksa'      => false,
            'alasan_tutup'     => null,
        ]);
    }

    public function hariAktif(): array
    {
        $hari = $this->hari_operasional;
        if (!is_array($hari) || count($hari) === 0) {
            return self::SEMUA_HARI;
        }

        return array_values(array_intersect(self::SEMUA_HARI, $hari));
    }

    public function jadwalRingkas(): string
    {
        $hari = $this->hariAktif();
        $labelHari = count($hari) === count(self::SEMUA_HARI)
            ? 'Setiap hari'
            : implode(', ', array_map(fn ($h) => substr($h, 0, 3), $hari));

        if ($this->buka_24_jam) {
            return "{$labelHari} • 24 Jam";
        }

        $buka = $this->jam_buka ?: self::DEFAULT_JAM_BUKA;
        $tutup = $this->jam_tutup ?: self::DEFAULT_JAM_TUTUP;
        return "{$labelHari} • {$buka} - {$tutup}";
    }

    public function statusSaatIni(?CarbonInterface $waktu = null): array
    {
        $sekarang = $waktu ? Carbon::instance($waktu)->copy()->timezone('Asia/Jakarta') : now('Asia/Jakarta');
        $hariSekarang = self::SEMUA_HARI[$sekarang->dayOfWeekIso - 1];
        $hariAktif = $this->hariAktif();
        $jamBuka = $this->jam_buka ?: self::DEFAULT_JAM_BUKA;
        $jamTutup = $this->jam_tutup ?: self::DEFAULT_JAM_TUTUP;

        if ($this->tutup_paksa) {
            return [
                'is_buka'           => false,
                'status_toko'       => 'Tutup',
                'kode_status'       => 'tutup_paksa',
                'keterangan_status' => $this->alasan_tutup ?: 'Tutup paksa sementara oleh owner',
            ];
        }

        if (!in_array($hariSekarang, $hariAktif, true)) {
            return [
                'is_buka'           => false,
                'status_toko'       => 'Tutup',
                'kode_status'       => 'libur',
                'keterangan_status' => "Libur operasional ({$hariSekarang})",
            ];
        }

        if ($this->buka_24_jam) {
            return [
                'is_buka'           => true,
                'status_toko'       => 'Buka',
                'kode_status'       => 'buka_24_jam',
                'keterangan_status' => 'Buka 24 Jam',
            ];
        }

        $jamIni = $sekarang->format('H:i');
        $dalamJamBuka = $jamBuka <= $jamTutup
            ? ($jamIni >= $jamBuka && $jamIni < $jamTutup)
            : ($jamIni >= $jamBuka || $jamIni < $jamTutup);

        if ($dalamJamBuka) {
            return [
                'is_buka'           => true,
                'status_toko'       => 'Buka',
                'kode_status'       => 'buka',
                'keterangan_status' => "Jam operasional {$jamBuka} - {$jamTutup}",
            ];
        }

        return [
            'is_buka'           => false,
            'status_toko'       => 'Tutup',
            'kode_status'       => 'tutup_jam',
            'keterangan_status' => "Di luar jam operasional ({$jamBuka} - {$jamTutup})",
        ];
    }

    public function terserialisasi(?CarbonInterface $waktu = null): array
    {
        $status = $this->statusSaatIni($waktu);

        return [
            'id'                => $this->id,
            'nama_apotek'       => $this->nama_apotek,
            'alamat'            => $this->alamat,
            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,
            'jam_buka'          => $this->jam_buka ?: self::DEFAULT_JAM_BUKA,
            'jam_tutup'         => $this->jam_tutup ?: self::DEFAULT_JAM_TUTUP,
            'buka_24_jam'       => (bool) $this->buka_24_jam,
            'hari_operasional'  => $this->hariAktif(),
            'tutup_paksa'       => (bool) $this->tutup_paksa,
            'alasan_tutup'      => $this->alasan_tutup,
            'jadwal_ringkas'    => $this->jadwalRingkas(),
            'is_buka'           => $status['is_buka'],
            'status_toko'       => $status['status_toko'],
            'kode_status'       => $status['kode_status'],
            'keterangan_status' => $status['keterangan_status'],
        ];
    }
}
