<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ChatbotService
{
    private const PANDUAN = <<<TXT
Panduan Aplikasi Apotek Astria (untuk Owner):
- Beranda: ringkasan penjualan dan grafik.
- Stok: daftar obat; menu titik tiga untuk ubah cepat harga dan status obat.
- Riwayat: daftar transaksi penjualan, bisa diekspor sebagai laporan.
- Karyawan: kelola akun karyawan (aktif/nonaktif).
- Profil: data apotek.
- Notifikasi: pengaturan notifikasi stok menipis dan obat kadaluarsa.
TXT;

    private function ringkasPenjualan($mulai = null): array
    {
        $q = DB::table('transaksi');
        if ($mulai) {
            $q->where('created_at', '>=', $mulai);
        }
        $r = $q->selectRaw('COUNT(*) AS jumlah_transaksi, COALESCE(SUM(total_harga),0) AS omzet')->first();

        return ['jumlah_transaksi' => (int) $r->jumlah_transaksi, 'omzet' => (int) $r->omzet];
    }

    public function konteksData(): string
    {
        $hariIni = today();
        $tujuhHari = now()->subDays(7);
        $tigaPuluhHari = now()->subDays(30);

        $terlaris = DB::table('detail_transaksi AS d')
            ->join('transaksi AS t', 't.id', '=', 'd.transaksi_id')
            ->join('obat AS o', 'o.id', '=', 'd.obat_id')
            ->where('t.created_at', '>=', $tigaPuluhHari)
            ->groupBy('o.id', 'o.nama_obat')
            ->orderByRaw('SUM(d.qty) DESC')
            ->limit(5)
            ->get(['o.nama_obat', DB::raw('SUM(d.qty) AS terjual')]);

        $metodeBayar = DB::table('transaksi')
            ->where('created_at', '>=', $tigaPuluhHari)
            ->groupBy('metode_bayar')
            ->get(['metode_bayar', DB::raw('COUNT(*) AS jumlah'), DB::raw('SUM(total_harga) AS omzet')]);

        $stokMenipis = DB::table('obat')
            ->where('stok', '<=', 10)
            ->orderBy('stok')
            ->limit(10)
            ->get(['nama_obat', 'stok']);

        $segeraKadaluarsa = DB::table('obat')
            ->whereBetween('tgl_kadaluarsa', [today(), today()->addDays(60)])
            ->orderBy('tgl_kadaluarsa')
            ->limit(10)
            ->get(['nama_obat', 'tgl_kadaluarsa', 'stok']);

        return json_encode([
            'tanggal_hari_ini' => today()->toDateString(),
            'penjualan_hari_ini' => $this->ringkasPenjualan($hariIni),
            'penjualan_7_hari_terakhir' => $this->ringkasPenjualan($tujuhHari),
            'penjualan_30_hari_terakhir' => $this->ringkasPenjualan($tigaPuluhHari),
            'obat_terlaris_30_hari' => $terlaris,
            'metode_bayar_30_hari' => $metodeBayar,
            'stok_menipis_(<=10)' => $stokMenipis,
            'obat_kadaluarsa_60_hari_ke_depan' => $segeraKadaluarsa,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function tanya(string $pesan, array $riwayat = []): string
    {
        $system = "Kamu asisten untuk Owner Apotek Astria. Jawab singkat dan jelas dalam Bahasa Indonesia. "
            . "Untuk ringkasan penjualan atau stok, pakai HANYA data JSON di bawah, jangan mengarang angka. "
            . "Format rupiah seperti Rp 1.250.000. Untuk pertanyaan cara pakai aplikasi, pakai teks panduan. "
            . "Kalau di luar cakupan atau datanya tidak ada, katakan tidak tahu.\n\n"
            . self::PANDUAN . "\n\nDATA:\n" . $this->konteksData();

        $messages = [['role' => 'system', 'content' => $system]];
        foreach (array_slice($riwayat, -10) as $r) {
            $messages[] = [
                'role' => $r['role'] === 'user' ? 'user' : 'assistant',
                'content' => $r['text'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $pesan];

        $res = Http::timeout(30)
            ->withToken(config('services.groq.key'))
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => config('services.groq.model'),
                'messages' => $messages,
            ]);

        if ($res->failed()) {
            throw new \RuntimeException('Groq gagal: ' . $res->status() . ' ' . $res->body());
        }

        return $res->json('choices.0.message.content') ?? 'Maaf, tidak ada jawaban.';
    }
}