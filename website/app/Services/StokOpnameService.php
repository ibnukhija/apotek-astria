<?php

namespace App\Services;

use App\Models\KondisiStokObat;
use App\Models\Obat;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StokOpnameService
{
    /** Buat sesi opname + snapshot stok sistem semua obat berstatus tersedia. */
    public static function mulai(?int $userId, ?string $catatan = null): StokOpname
    {
        return DB::transaction(function () use ($userId, $catatan) {
            if (StokOpname::where('status', StokOpname::STATUS_DRAFT)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'opname' => 'Masih ada opname berstatus draft. Lanjutkan atau selesaikan dulu.',
                ]);
            }

            $now = now('Asia/Jakarta');

            $opname = StokOpname::create([
                'kode'    => 'OPN-' . $now->format('Ymd-His'),
                'periode' => $now->format('Y-m'),
                'tanggal' => $now->toDateString(),
                'user_id' => $userId,
                'status'  => StokOpname::STATUS_DRAFT,
                'catatan' => $catatan,
            ]);

            $baris = Obat::where('status', 'tersedia')
                ->orderBy('nama_obat')
                ->get(['id', 'stok'])
                ->map(fn ($o) => [
                    'stok_opname_id' => $opname->id,
                    'obat_id'        => $o->id,
                    'stok_sistem'    => (int) $o->stok,
                    'jml_rusak'      => 0,
                    'jml_hilang'     => 0,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);

            foreach ($baris->chunk(200) as $potong) {
                StokOpnameDetail::insert($potong->values()->all());
            }

            return $opname;
        });
    }

    /** Simpan isian satu baris (AJAX). Stok BELUM berubah. */
    public static function simpanDetail(StokOpname $opname, StokOpnameDetail $detail, array $data): StokOpnameDetail
    {
        if (! $opname->isDraft()) {
            throw ValidationException::withMessages(['opname' => 'Opname ini sudah final dan tidak bisa diubah.']);
        }

        $detail->update([
            'stok_fisik' => isset($data['stok_fisik']) && $data['stok_fisik'] !== '' ? (int) $data['stok_fisik'] : null,
            'jml_rusak'  => (int) ($data['jml_rusak'] ?? 0),
            'jml_hilang' => (int) ($data['jml_hilang'] ?? 0),
            'catatan'    => filled($data['catatan'] ?? null) ? trim($data['catatan']) : null,
        ]);

        return $detail;
    }

    /**
     * Samakan snapshot stok_sistem dengan stok sekarang (ada penjualan saat penghitungan).
     * @return Collection<int,string> daftar obat yang berubah
     */
    public static function segarkanStokSistem(StokOpname $opname): Collection
    {
        $berubah = collect();

        $opname->details()->with('obat:id,nama_obat,stok')->get()->each(function ($d) use ($berubah) {
            $sekarang = (int) $d->obat->stok;
            if ($sekarang !== (int) $d->stok_sistem) {
                $berubah->push("{$d->obat->nama_obat}: {$d->stok_sistem} → {$sekarang}");
                $d->update(['stok_sistem' => $sekarang]);
            }
        });

        return $berubah;
    }

    /**
     * Finalisasi: stok aktif disamakan dengan stok fisik.
     *  - selisih kurang = rusak + hilang (wajib dirinci)
     *  - selisih lebih  = penyesuaian stok (opname)
     */
    public static function finalisasi(StokOpname $opname, ?int $userId = null): StokOpname
    {
        if (! $opname->isDraft()) {
            throw ValidationException::withMessages(['opname' => 'Opname ini sudah difinalisasi.']);
        }

        $belumDiisi = $opname->details()->whereNull('stok_fisik')->count();
        if ($belumDiisi > 0) {
            throw ValidationException::withMessages([
                'opname' => "Masih ada {$belumDiisi} obat yang stok fisiknya belum diisi.",
            ]);
        }

        // Di luar transaksi supaya snapshot baru tetap tersimpan walau finalisasi ditolak
        $berubah = self::segarkanStokSistem($opname);
        if ($berubah->isNotEmpty()) {
            throw ValidationException::withMessages([
                'opname' => 'Stok sistem berubah karena ada penjualan saat penghitungan ('
                    . $berubah->take(5)->implode('; ')
                    . ($berubah->count() > 5 ? '; dst.' : '')
                    . '). Stok sistem sudah diperbarui, periksa kembali selisih lalu finalisasi lagi.',
            ]);
        }

        return DB::transaction(function () use ($opname, $userId) {
            $opname = StokOpname::lockForUpdate()->findOrFail($opname->id);
            if (! $opname->isDraft()) {
                throw ValidationException::withMessages(['opname' => 'Opname ini sudah difinalisasi.']);
            }

            foreach ($opname->details()->get() as $d) {
                $obat   = Obat::lockForUpdate()->findOrFail($d->obat_id);
                $sistem = (int) $obat->stok;

                if ($sistem !== (int) $d->stok_sistem) {
                    throw ValidationException::withMessages([
                        'opname' => "Stok {$obat->nama_obat} baru saja berubah ({$d->stok_sistem} → {$sistem}). Coba finalisasi lagi.",
                    ]);
                }

                $fisik   = (int) $d->stok_fisik;
                $selisih = $fisik - $sistem;
                $rusak   = (int) $d->jml_rusak;
                $hilang  = (int) $d->jml_hilang;
                $ket     = "Stok opname {$opname->kode}";

                if ($selisih < 0) {
                    if ($rusak + $hilang !== -$selisih) {
                        throw ValidationException::withMessages([
                            'opname' => "{$obat->nama_obat}: selisih kurang " . (-$selisih)
                                . ' harus dirinci (rusak + hilang), sekarang baru ' . ($rusak + $hilang) . '.',
                        ]);
                    }
                } else {
                    if ($rusak + $hilang > 0) {
                        throw ValidationException::withMessages([
                            'opname' => "{$obat->nama_obat}: tidak ada selisih kurang, kosongkan kolom rusak/hilang.",
                        ]);
                    }
                    if ($selisih > 0 && ! filled($d->catatan)) {
                        throw ValidationException::withMessages([
                            'opname' => "{$obat->nama_obat}: selisih lebih {$selisih}, isi catatan penjelasannya.",
                        ]);
                    }
                }

                if ($rusak > 0) {
                    KondisiStokService::catat([
                        'obat_id'  => $obat->id,
                        'jenis'    => KondisiStokObat::JENIS_RUSAK,
                        'jumlah'   => $rusak,
                        'penyebab' => "{$ket}: rusak",
                        'catatan'  => $d->catatan,
                    ], $userId);
                }

                if ($hilang > 0) {
                    KondisiStokService::catat([
                        'obat_id'  => $obat->id,
                        'jenis'    => KondisiStokObat::JENIS_HILANG,
                        'jumlah'   => $hilang,
                        'penyebab' => "{$ket}: hilang / selisih pencatatan",
                        'catatan'  => $d->catatan,
                    ], $userId);
                }

                if ($selisih > 0) {
                    KondisiStokService::catat([
                        'obat_id'    => $obat->id,
                        'jenis'      => KondisiStokObat::JENIS_OPNAME,
                        'stok_fisik' => $fisik,
                        'penyebab'   => "{$ket}: selisih lebih",
                        'catatan'    => $d->catatan,
                    ], $userId);
                }
            }

            $opname->update([
                'status'       => StokOpname::STATUS_FINAL,
                'finalized_at' => now(),
            ]);

            DB::afterCommit(fn () => NotifikasiStokService::kirimHasilOpname($opname->fresh('details')));

            return $opname;
        });
    }
}