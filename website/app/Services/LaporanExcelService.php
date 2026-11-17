<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class LaporanExcelService
{
    /**
     * Bangun file XLSX asli dari data laporan penjualan dan kembalikan isi binernya.
     */
    public function buatXlsx(array $data): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'lap_xlsx_');
        if ($tempPath === false) {
            throw new RuntimeException('Gagal membuat file sementara XLSX.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempPath);
            throw new RuntimeException('Gagal membuka arsip XLSX.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->relsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($data));
        $zip->close();

        $binary = file_get_contents($tempPath);
        @unlink($tempPath);

        if ($binary === false) {
            throw new RuntimeException('Gagal membaca hasil file XLSX.');
        }

        return $binary;
    }

    private function worksheetXml(array $data): string
    {
        $filter = $data['filter'];
        $ringkasan = $data['ringkasan'];
        $items = $data['items'];

        $kategoriLabel = $filter['kategori'] !== '' ? $filter['kategori'] : 'Semua Kategori';
        $obatLabel = $filter['nama_obat'] !== '' ? $filter['nama_obat'] : 'Semua Obat';

        $rows = [];
        $r = 1;

        $rows[] = $this->barisTeks($r++, [['col' => 'A', 'val' => 'LAPORAN PENJUALAN - ' . $data['nama_apotek'], 'style' => 1]]);
        $rows[] = $this->barisTeks($r++, [['col' => 'A', 'val' => 'Periode: ' . $filter['periode_label'] . '  |  Kategori: ' . $kategoriLabel . '  |  Obat: ' . $obatLabel, 'style' => 2]]);
        $rows[] = $this->barisTeks($r++, [['col' => 'A', 'val' => 'Dicetak pada: ' . $data['dicetak_pada'] . '  |  Transaksi: ' . $ringkasan['jumlah_transaksi'] . '  |  Cash: Rp ' . number_format($ringkasan['total_cash'], 0, ',', '.') . '  |  QRIS: Rp ' . number_format($ringkasan['total_qris'], 0, ',', '.'), 'style' => 2]]);
        $r++; // baris kosong pemisah

        $headers = ['No', 'Tanggal', 'No. Transaksi', 'Kasir', 'Metode', 'Kategori', 'Nama Obat', 'Qty', 'Harga Satuan', 'Subtotal'];
        $rows[] = $this->barisHeader($r++, $headers);

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        foreach ($items as $idx => $item) {
            $rowXml = '<row r="' . $r . '">';
            $rowXml .= $this->selAngka($cols[0] . $r, $idx + 1, 5);
            $rowXml .= $this->selTeks($cols[1] . $r, $item['tanggal'], 4);
            $rowXml .= $this->selTeks($cols[2] . $r, $item['no_transaksi'], 4);
            $rowXml .= $this->selTeks($cols[3] . $r, $item['nama_kasir'], 4);
            $rowXml .= $this->selTeks($cols[4] . $r, $item['metode_bayar_label'], 4);
            $rowXml .= $this->selTeks($cols[5] . $r, $item['nama_kategori'], 4);
            $rowXml .= $this->selTeks($cols[6] . $r, $item['nama_obat'], 4);
            $rowXml .= $this->selAngka($cols[7] . $r, $item['qty'], 5);
            $rowXml .= $this->selAngka($cols[8] . $r, $item['harga_satuan'], 5);
            $rowXml .= $this->selAngka($cols[9] . $r, $item['subtotal'], 5);
            $rowXml .= '</row>';
            $rows[] = $rowXml;
            $r++;
        }

        // Baris Total Keseluruhan
        $totalRow = '<row r="' . $r . '">';
        for ($c = 0; $c < 6; $c++) {
            $totalRow .= $this->selTeks($cols[$c] . $r, '', 6);
        }
        $totalRow .= $this->selTeks($cols[6] . $r, 'TOTAL PENJUALAN', 6);
        $totalRow .= $this->selAngka($cols[7] . $r, $ringkasan['total_qty'], 7);
        $totalRow .= $this->selTeks($cols[8] . $r, '', 6);
        $totalRow .= $this->selAngka($cols[9] . $r, $ringkasan['total_pendapatan'], 7);
        $totalRow .= '</row>';
        $rows[] = $totalRow;

        $widths = [6, 18, 22, 16, 10, 18, 30, 8, 15, 16];
        $colsXml = '<cols>';
        foreach ($widths as $i => $w) {
            $colNum = $i + 1;
            $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $w . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $colsXml
            . '<sheetData>' . implode('', $rows) . '</sheetData>'
            . '</worksheet>';
    }

    private function barisHeader(int $rowNum, array $headers): string
    {
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        $xml = '<row r="' . $rowNum . '" ht="22" customHeight="1">';
        foreach ($headers as $i => $judul) {
            $xml .= $this->selTeks($cols[$i] . $rowNum, $judul, 3);
        }
        return $xml . '</row>';
    }

    private function barisTeks(int $rowNum, array $cells): string
    {
        $xml = '<row r="' . $rowNum . '">';
        foreach ($cells as $cell) {
            $xml .= $this->selTeks($cell['col'] . $rowNum, $cell['val'], $cell['style']);
        }
        return $xml . '</row>';
    }

    private function selTeks(string $ref, string $nilai, int $style): string
    {
        $aman = htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $nilai) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t>' . $aman . '</t></is></c>';
    }

    private function selAngka(string $ref, int|float $nilai, int $style): string
    {
        return '<c r="' . $ref . '" s="' . $style . '"><v>' . $nilai . '</v></c>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Laporan Penjualan" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="13"/><color rgb="FF1E293B"/><name val="Calibri"/></font>'
            . '<font><sz val="10"/><color rgb="FF475569"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF2A9D8F"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8F4F2"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFE2E8F0"/></left>'
            . '<right style="thin"><color rgb="FFE2E8F0"/></right>'
            . '<top style="thin"><color rgb="FFE2E8F0"/></top>'
            . '<bottom style="thin"><color rgb="FFE2E8F0"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="8">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" horizontal="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="3" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" horizontal="right"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="3" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" horizontal="right"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
    }
}
