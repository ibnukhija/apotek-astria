<?php

namespace App\Services;

class LaporanPdfService
{
    private const LEBAR_HALAMAN = 841.89; // A4 Landscape
    private const TINGGI_HALAMAN = 595.28;
    private const MARGIN_KIRI = 36.0;
    private const MARGIN_BAWAH = 38.0;

    private const KOLOM = [
        ['judul' => 'No',           'lebar' => 28.0,  'rata' => 'C'],
        ['judul' => 'Tanggal',      'lebar' => 86.0,  'rata' => 'L'],
        ['judul' => 'No. Nota',     'lebar' => 102.0, 'rata' => 'L'],
        ['judul' => 'Kasir',        'lebar' => 78.0,  'rata' => 'L'],
        ['judul' => 'Metode',       'lebar' => 46.0,  'rata' => 'C'],
        ['judul' => 'Kategori',     'lebar' => 90.0,  'rata' => 'L'],
        ['judul' => 'Nama Obat',    'lebar' => 156.0, 'rata' => 'L'],
        ['judul' => 'Qty',          'lebar' => 36.0,  'rata' => 'R'],
        ['judul' => 'Harga',        'lebar' => 72.0,  'rata' => 'R'],
        ['judul' => 'Subtotal',     'lebar' => 76.0,  'rata' => 'R'],
    ];

    /**
     * Bangun file PDF 1.4 multi-halaman A4 Landscape dari data laporan penjualan.
     */
    public function buatPdf(array $data): string
    {
        $halamanStreams = $this->bangunHalaman($data);
        return $this->rakitDokumenPdf($halamanStreams);
    }

    private function bangunHalaman(array $data): array
    {
        $filter = $data['filter'];
        $ringkasan = $data['ringkasan'];
        $items = $data['items'];

        $halaman = [];
        $cmds = [];
        $y = $this->tulisHeaderHalamanPertama($cmds, $data, $filter, $ringkasan);
        $y = $this->tulisHeaderTabel($cmds, $y);

        if (count($items) === 0) {
            $cmds[] = $this->teks(self::MARGIN_KIRI + 8, $y - 16, 'F1', 10, '0.4 0.45 0.5', 'Tidak ada data penjualan pada filter yang dipilih.');
            $y -= 28;
        } else {
            foreach ($items as $idx => $item) {
                if ($y - 20 < self::MARGIN_BAWAH + 22) {
                    $halaman[] = $cmds;
                    $cmds = [];
                    $y = self::TINGGI_HALAMAN - 40.0;
                    $cmds[] = $this->teks(self::MARGIN_KIRI, $y, 'F2', 11, '0.12 0.16 0.23', 'LAPORAN PENJUALAN - ' . $data['nama_apotek'] . ' (Lanjutan)');
                    $y -= 18;
                    $y = $this->tulisHeaderTabel($cmds, $y);
                }

                $y = $this->tulisBarisData($cmds, $y, $idx + 1, $item, $idx % 2 === 1);
            }
        }

        if ($y - 24 < self::MARGIN_BAWAH + 14) {
            $halaman[] = $cmds;
            $cmds = [];
            $y = self::TINGGI_HALAMAN - 40.0;
        }

        $this->tulisBarisTotal($cmds, $y, $ringkasan);
        $halaman[] = $cmds;

        $totalHal = count($halaman);
        foreach ($halaman as $i => &$pageCmds) {
            $noHal = $i + 1;
            $pageCmds[] = $this->teks(self::MARGIN_KIRI, 18.0, 'F1', 8.5, '0.45 0.5 0.56', 'Dicetak pada ' . $data['dicetak_pada']);
            $pageCmds[] = $this->teks(self::LEBAR_HALAMAN - 96.0, 18.0, 'F1', 8.5, '0.45 0.5 0.56', "Halaman {$noHal} dari {$totalHal}");
        }

        return array_map(fn ($list) => implode("\n", $list), $halaman);
    }

    private function tulisHeaderHalamanPertama(array &$cmds, array $data, array $filter, array $ringkasan): float
    {
        $y = self::TINGGI_HALAMAN - 36.0;
        $cmds[] = $this->teks(self::MARGIN_KIRI, $y, 'F2', 15, '0.165 0.616 0.561', 'LAPORAN PENJUALAN - ' . strtoupper($data['nama_apotek']));
        $y -= 15.0;
        $cmds[] = $this->teks(self::MARGIN_KIRI, $y, 'F1', 9, '0.35 0.4 0.47', $data['alamat']);
        $y -= 16.0;

        $katLabel = $filter['kategori'] !== '' ? $filter['kategori'] : 'Semua Kategori';
        $obatLabel = $filter['nama_obat'] !== '' ? $filter['nama_obat'] : 'Semua Obat';
        $cmds[] = $this->teks(
            self::MARGIN_KIRI,
            $y,
            'F1',
            9,
            '0.12 0.16 0.23',
            "Periode: {$filter['periode_label']}   |   Kategori: {$katLabel}   |   Obat: {$obatLabel}"
        );
        $y -= 18.0;

        $lebarBox = 770.0;
        $cmds[] = $this->kotak(self::MARGIN_KIRI, $y - 20.0, $lebarBox, 22.0, '0.91 0.957 0.949', '0.165 0.616 0.561');
        $ringkasStr = sprintf(
            'Total Penjualan: Rp %s   |   Terjual: %d unit (%d transaksi)   |   Cash: Rp %s   |   QRIS: Rp %s',
            $this->rupiah($ringkasan['total_pendapatan']),
            $ringkasan['total_qty'],
            $ringkasan['jumlah_transaksi'],
            $this->rupiah($ringkasan['total_cash']),
            $this->rupiah($ringkasan['total_qris'])
        );
        $cmds[] = $this->teks(self::MARGIN_KIRI + 8.0, $y - 12.0, 'F2', 9, '0.12 0.48 0.44', $ringkasStr);

        return $y - 30.0;
    }

    private function tulisHeaderTabel(array &$cmds, float $y): float
    {
        $tinggi = 20.0;
        $cmds[] = $this->kotak(self::MARGIN_KIRI, $y - $tinggi, 770.0, $tinggi, '0.165 0.616 0.561', '0.165 0.616 0.561');

        $x = self::MARGIN_KIRI;
        foreach (self::KOLOM as $kol) {
            $cmds[] = $this->teksKolom($x, $kol['lebar'], $y - 13.5, 'F2', 8.5, '1 1 1', $kol['judul'], $kol['rata']);
            $x += $kol['lebar'];
        }

        return $y - $tinggi;
    }

    private function tulisBarisData(array &$cmds, float $y, int $no, array $item, bool $belang): float
    {
        $tinggi = 18.0;
        $bg = $belang ? '0.973 0.980 0.988' : '1 1 1';
        $cmds[] = $this->kotak(self::MARGIN_KIRI, $y - $tinggi, 770.0, $tinggi, $bg, '0.886 0.910 0.941');

        $nilai = [
            (string) $no,
            $item['tanggal'],
            $item['no_transaksi'],
            $item['nama_kasir'],
            $item['metode_bayar_label'],
            $item['nama_kategori'],
            $item['nama_obat'],
            (string) $item['qty'],
            $this->rupiah($item['harga_satuan']),
            $this->rupiah($item['subtotal']),
        ];

        $x = self::MARGIN_KIRI;
        foreach (self::KOLOM as $i => $kol) {
            $cmds[] = $this->teksKolom($x, $kol['lebar'], $y - 12.5, 'F1', 8.0, '0.12 0.16 0.23', $nilai[$i], $kol['rata']);
            $x += $kol['lebar'];
        }

        return $y - $tinggi;
    }

    private function tulisBarisTotal(array &$cmds, float $y, array $ringkasan): void
    {
        $tinggi = 20.0;
        $cmds[] = $this->kotak(self::MARGIN_KIRI, $y - $tinggi, 770.0, $tinggi, '0.91 0.957 0.949', '0.165 0.616 0.561');

        $lebarSebelumQty = 28.0 + 86.0 + 102.0 + 78.0 + 46.0 + 90.0 + 156.0;
        $cmds[] = $this->teks(self::MARGIN_KIRI + 8.0, $y - 13.5, 'F2', 9.0, '0.12 0.16 0.23', 'TOTAL PENJUALAN');
        $cmds[] = $this->teksKolom(self::MARGIN_KIRI + $lebarSebelumQty, 36.0, $y - 13.5, 'F2', 9.0, '0.12 0.16 0.23', (string) $ringkasan['total_qty'], 'R');
        $cmds[] = $this->teksKolom(self::MARGIN_KIRI + $lebarSebelumQty + 36.0 + 72.0, 76.0, $y - 13.5, 'F2', 9.0, '0.12 0.48 0.44', 'Rp ' . $this->rupiah($ringkasan['total_pendapatan']), 'R');
    }

    private function kotak(float $x, float $y, float $w, float $h, string $fillRgb, string $strokeRgb): string
    {
        return sprintf("q %.3F w %s rg %s RG %.2F %.2F %.2F %.2F re B Q", 0.5, $fillRgb, $strokeRgb, $x, $y, $w, $h);
    }

    private function teksKolom(float $x, float $w, float $y, string $font, float $size, string $rgb, string $isi, string $rata): string
    {
        $maxChar = max(3, (int) floor(($w - 8.0) / ($size * 0.52)));
        $potong = $this->potong($isi, $maxChar);
        $lebarPerkiraan = strlen($potong) * ($size * 0.50);

        $posX = match ($rata) {
            'R'     => max($x + 4.0, $x + $w - 4.0 - $lebarPerkiraan),
            'C'     => max($x + 2.0, $x + ($w - $lebarPerkiraan) / 2.0),
            default => $x + 4.0,
        };

        return $this->teks($posX, $y, $font, $size, $rgb, $potong);
    }

    private function teks(float $x, float $y, string $font, float $size, string $rgb, string $isi): string
    {
        $aman = $this->bersihkanPdfString($isi);
        return sprintf("BT /%s %.1F Tf %s rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET", $font, $size, $rgb, $x, $y, $aman);
    }

    private function potong(string $teks, int $maks): string
    {
        if (mb_strlen($teks, 'UTF-8') <= $maks) {
            return $teks;
        }
        return rtrim(mb_substr($teks, 0, max(1, $maks - 2), 'UTF-8')) . '..';
    }

    private function bersihkanPdfString(string $teks): string
    {
        $ascii = preg_replace('/[^\x20-\x7E]/', ' ', $teks) ?? '';
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $ascii);
    }

    private function rupiah(int $nilai): string
    {
        return number_format($nilai, 0, ',', '.');
    }

    private function rakitDokumenPdf(array $streams): string
    {
        $objek = [];
        $objek[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objek[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objek[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

        $kidsRefs = [];
        $nextId = 6;
        foreach ($streams as $stream) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $kidsRefs[] = "{$pageId} 0 R";

            $objek[$pageId] = sprintf(
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents %d 0 R >>",
                self::LEBAR_HALAMAN,
                self::TINGGI_HALAMAN,
                $contentId
            );
            $len = strlen($stream);
            $objek[$contentId] = "<< /Length {$len} >>\nstream\n{$stream}\nendstream";
        }

        $objek[2] = "<< /Type /Pages /Kids [ " . implode(' ', $kidsRefs) . " ] /Count " . count($kidsRefs) . " >>";
        ksort($objek);

        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];
        foreach ($objek as $id => $isi) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$isi}\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $maxId = max(array_keys($objek));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxId; $i++) {
            $off = $offsets[$i] ?? 0;
            $flag = isset($offsets[$i]) ? 'n' : 'f';
            $pdf .= sprintf("%010d 00000 %s \n", $off, $flag);
        }

        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }
}
