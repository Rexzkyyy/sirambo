<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Models\{
    Kategori,
    SubKategori,
    NilaiKategori,
    NilaiSubKategori,
    Penduduk,
    Tahun,
    Periode,
    Wilayah
};

class DynamicTableController extends Controller
{
    private const PERKAPITA_SCALE = 1000000;

    public function index(Request $request)
    {
        return view('dynamic.index', $this->gatherContext($request));
    }

    public function preview(Request $request)
    {
        return view('dynamic.partials.table', $this->gatherContext($request));
    }

    public function export(Request $request)
    {
        $context = $this->gatherContext($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tabel');

        $cellAddress = function ($col, $row) {
            return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
        };

        $setCell = function ($col, $row, $value) use ($sheet, $cellAddress) {
            $sheet->setCellValue($cellAddress($col, $row), $value);
        };

        $mergeCells = function ($colStart, $rowStart, $colEnd, $rowEnd) use ($sheet, $cellAddress) {
            $sheet->mergeCells($cellAddress($colStart, $rowStart) . ':' . $cellAddress($colEnd, $rowEnd));
        };

        $columnMode = $context['columnMode'] ?? 'time';
        $rowMode = $context['rowMode'] ?? 'kategori';
        $calcMode = $context['calcMode'] ?? null;

        $tahunTampil = $context['tahunTampil'] ?? collect();
        $periodeTampil = $context['periodeTampil'] ?? collect();
        $periodeList = $context['periodeList'] ?? collect();
        $periodeMaster = $context['periodeMaster'] ?? $periodeList;
        $tahunMaster = $context['tahunMaster'] ?? collect();
        $showTotalColumn = $context['showTotalColumn'] ?? false;

        $rowItems = $context['rowItems'] ?? collect();
        $subFiltered = $context['subFiltered'] ?? collect();
        $columnItems = $context['columnItems'] ?? collect();
        $kategori = $context['kategori'] ?? collect();

        $kategoriData = $context['kategoriData'] ?? [];
        $subData = $context['subData'] ?? [];
        $wilayahData = $context['wilayahData'] ?? [];
        $kategoriWilayahData = $context['kategoriWilayahData'] ?? [];
        $subWilayahData = $context['subWilayahData'] ?? [];
        $kategoriDataBerlaku = $context['kategoriDataBerlaku'] ?? [];
        $kategoriDataKonstan = $context['kategoriDataKonstan'] ?? [];
        $subDataBerlaku = $context['subDataBerlaku'] ?? [];
        $subDataKonstan = $context['subDataKonstan'] ?? [];
        $pdrbData = $context['pdrbData'] ?? [];
        $pendudukData = $context['pendudukData'] ?? [];
        $pendudukWilayahData = $context['pendudukWilayahData'] ?? [];
        $pdrbKategoriIds = $context['pdrbKategoriIds'] ?? [];
        $sektorDataBerlaku = $context['sektorDataBerlaku'] ?? [];
        $sektorDataKonstan = $context['sektorDataKonstan'] ?? [];
        $focusIndicator = $context['focusIndicator'] ?? [];

        $periodeTotalList = $showTotalColumn ? $periodeList : $periodeTampil;
        $periodOrder = $periodeMaster->pluck('id_periode')->values()->all();
        $periodIndexMap = array_flip($periodOrder);
        $tahunById = $tahunMaster->keyBy('id_tahun');
        $tahunByValue = $tahunMaster->keyBy(fn($t) => (int) $t->tahun);

        $perkapitaScale = self::PERKAPITA_SCALE;

        $computeGrowth = function ($data, $tahunId, $periodeId, $wilayahId = null) use ($calcMode, $tahunById, $tahunByValue, $periodOrder, $periodIndexMap, $pdrbData, $pendudukData, $pendudukWilayahData, $perkapitaScale) {
            if (!$calcMode) {
                return $data[$tahunId][$periodeId] ?? null;
            }
            $current = $data[$tahunId][$periodeId] ?? null;
            if ($current === null) {
                return null;
            }

            if ($calcMode === 'perkapita') {
                $penduduk = $wilayahId !== null
                    ? ($pendudukWilayahData[$wilayahId][$tahunId] ?? null)
                    : ($pendudukData[$tahunId] ?? null);
                if ($current == 0 || $penduduk === null || $penduduk == 0) {
                    return null;
                }
                return ($current / $penduduk) * $perkapitaScale;
            }

            if ($calcMode === 'distribusi') {
                $pdrb = $pdrbData[$tahunId][$periodeId] ?? null;
                if ($current == 0 || $pdrb === null || $pdrb == 0) {
                    return null;
                }
                return ($current / $pdrb) * 100;
            }

            $tahunValue = $tahunById->get($tahunId)?->tahun;
            if (!$tahunValue) {
                return null;
            }

            if ($calcMode === 'y-on-y') {
                $prevYearId = $tahunByValue->get($tahunValue - 1)?->id_tahun;
                $prev = $prevYearId ? ($data[$prevYearId][$periodeId] ?? null) : null;
                if ($prev === null || $prev == 0) {
                    return null;
                }
                return (($current - $prev) / $prev) * 100;
            }

            if ($calcMode === 'q-to-q') {
                $periodIndex = $periodIndexMap[$periodeId] ?? null;
                if ($periodIndex === null) {
                    return null;
                }
                if ($periodIndex > 0) {
                    $prevPeriodId = $periodOrder[$periodIndex - 1];
                    $prevYearId = $tahunId;
                } else {
                    $prevPeriodId = $periodOrder[count($periodOrder) - 1] ?? null;
                    $prevYearId = $tahunByValue->get($tahunValue - 1)?->id_tahun;
                }
                if (!$prevPeriodId || !$prevYearId) {
                    return null;
                }
                $prev = $data[$prevYearId][$prevPeriodId] ?? null;
                if ($prev === null || $prev == 0) {
                    return null;
                }
                return (($current - $prev) / $prev) * 100;
            }

            if ($calcMode === 'c-to-c') {
                $periodIndex = $periodIndexMap[$periodeId] ?? null;
                if ($periodIndex === null) {
                    return null;
                }
                $prevYearId = $tahunByValue->get((int) $tahunValue - 1)?->id_tahun;
                if (!$prevYearId) {
                    return null;
                }
                $sumNow = 0;
                $sumPrev = 0;
                for ($i = 0; $i <= $periodIndex; $i++) {
                    $pid = $periodOrder[$i];
                    $valNow = $data[$tahunId][$pid] ?? null;
                    $valPrev = $data[$prevYearId][$pid] ?? null;
                    if ($valNow !== null) {
                        $sumNow += $valNow;
                    }
                    if ($valPrev !== null) {
                        $sumPrev += $valPrev;
                    }
                }
                if ($sumPrev == 0) {
                    return null;
                }
                return (($sumNow - $sumPrev) / $sumPrev) * 100;
            }

            return null;
        };

        $computeIndeks = function ($dataBerlaku, $dataKonstan, $tahunId, $periodeId) {
            $berlaku = $dataBerlaku[$tahunId][$periodeId] ?? null;
            $konstan = $dataKonstan[$tahunId][$periodeId] ?? null;
            if ($berlaku === null || $konstan === null || $konstan == 0) {
                return null;
            }
            return ($berlaku / $konstan) * 100;
        };

        $computeLaju = function ($dataBerlaku, $dataKonstan, $tahunId, $periodeId) use ($tahunById, $tahunByValue) {
            $tahunValue = $tahunById->get($tahunId)?->tahun;
            if (!$tahunValue) {
                return null;
            }

            $prevYearId = null;
            $prevPeriodId = null;

            if ($periodeId == 1) { // Triwulan I
                $prevPeriodId = 4; // Kembalikan ke Triwulan IV
                $prevYearItem = $tahunByValue->get((int) $tahunValue - 1);
                $prevYearId = $prevYearItem ? $prevYearItem->id_tahun : null;
            } elseif ($periodeId >= 2 && $periodeId <= 4) { // Triwulan II - IV
                $prevPeriodId = $periodeId - 1;
                $prevYearId = $tahunId;
            } elseif ($periodeId == 5) { // Tahunan
                $prevPeriodId = 5; // Bandingkan dengan Tahunan tahun sebelumnya
                $prevYearItem = $tahunByValue->get((int) $tahunValue - 1);
                $prevYearId = $prevYearItem ? $prevYearItem->id_tahun : null;
            }

            if (!$prevPeriodId || !$prevYearId) {
                return null;
            }

            $berlakuSekarang = $dataBerlaku[$tahunId][$periodeId] ?? null;
            $konstanSekarang = $dataKonstan[$tahunId][$periodeId] ?? null;
            $berlakuSebelum = $dataBerlaku[$prevYearId][$prevPeriodId] ?? null;
            $konstanSebelum = $dataKonstan[$prevYearId][$prevPeriodId] ?? null;

            if ($berlakuSekarang === null || $konstanSekarang === null || $berlakuSebelum === null || $konstanSebelum === null) {
                // DEBUG: Log specific missing data points
                if ($berlakuSekarang !== null && $konstanSekarang !== null) {
                    \Illuminate\Support\Facades\Log::info("=== LAJU IMPLISIT DEBUG === Missing previous data", [
                        'tahun' => $tahunValue,
                        'periode' => $periodeId,
                        'looking_for_prev_tahun_id' => $prevYearId,
                        'looking_for_prev_periode_id' => $prevPeriodId,
                        'has_berlaku_prev' => $berlakuSebelum !== null,
                        'has_konstan_prev' => $konstanSebelum !== null,
                    ]);
                }
                return null;
            }
            if ($konstanSekarang == 0 || $konstanSebelum == 0) {
                return null;
            }

            $indeksSekarang = ($berlakuSekarang / $konstanSekarang) * 100;
            $indeksSebelum = ($berlakuSebelum / $konstanSebelum) * 100;
            if ($indeksSebelum == 0) {
                return null;
            }
            return (($indeksSekarang - $indeksSebelum) / $indeksSebelum) * 100;
        };

        $computeTotalGrowth = function ($data, $tahunId) use ($calcMode, $tahunById, $tahunByValue, $periodOrder, $pdrbData, $pendudukData, $perkapitaScale) {
            if (!$calcMode) {
                return null;
            }
            if ($calcMode === 'perkapita') {
                $sumNow = 0;
                foreach ($periodOrder as $pid) {
                    $valNow = $data[$tahunId][$pid] ?? null;
                    if ($valNow !== null) {
                        $sumNow += $valNow;
                    }
                }
                $penduduk = $pendudukData[$tahunId] ?? null;
                if ($sumNow == 0 || $penduduk === null || $penduduk == 0) {
                    return null;
                }
                return ($sumNow / $penduduk) * $perkapitaScale;
            }
            if ($calcMode === 'distribusi') {
                $sumNow = 0;
                $sumPdrb = 0;
                foreach ($periodOrder as $pid) {
                    $valNow = $data[$tahunId][$pid] ?? null;
                    $valPdrb = $pdrbData[$tahunId][$pid] ?? null;
                    if ($valNow !== null) {
                        $sumNow += $valNow;
                    }
                    if ($valPdrb !== null) {
                        $sumPdrb += $valPdrb;
                    }
                }
                if ($sumNow == 0 || $sumPdrb == 0) {
                    return null;
                }
                return ($sumNow / $sumPdrb) * 100;
            }
            $tahunValue = $tahunById->get($tahunId)?->tahun;
            if (!$tahunValue) {
                return null;
            }
            $prevYearId = $tahunByValue->get((int) $tahunValue - 1)?->id_tahun;
            if (!$prevYearId) {
                return null;
            }
            $sumNow = 0;
            $sumPrev = 0;
            foreach ($periodOrder as $pid) {
                $valNow = $data[$tahunId][$pid] ?? null;
                $valPrev = $data[$prevYearId][$pid] ?? null;
                if ($valNow !== null) {
                    $sumNow += $valNow;
                }
                if ($valPrev !== null) {
                    $sumPrev += $valPrev;
                }
            }
            if ($sumPrev == 0) {
                return null;
            }
            return (($sumNow - $sumPrev) / $sumPrev) * 100;
        };

        $computeIndeksTotal = function ($dataBerlaku, $dataKonstan, $tahunId) use ($periodOrder) {
            $totalBerlaku = 0;
            $totalKonstan = 0;
            foreach ($periodOrder as $pid) {
                $valB = $dataBerlaku[$tahunId][$pid] ?? null;
                $valK = $dataKonstan[$tahunId][$pid] ?? null;
                if ($valB !== null) {
                    $totalBerlaku += $valB;
                }
                if ($valK !== null) {
                    $totalKonstan += $valK;
                }
            }
            if ($totalKonstan == 0) {
                return null;
            }
            return ($totalBerlaku / $totalKonstan) * 100;
        };

        $computeLajuTotal = function ($dataBerlaku, $dataKonstan, $tahunId) use ($tahunById, $tahunByValue, $periodOrder) {
            $tahunValue = $tahunById->get($tahunId)?->tahun;
            if (!$tahunValue) {
                return null;
            }
            $prevYearId = $tahunByValue->get((int) $tahunValue - 1)?->id_tahun;
            if (!$prevYearId) {
                return null;
            }
            $totalBerlakuNow = 0;
            $totalKonstanNow = 0;
            $totalBerlakuPrev = 0;
            $totalKonstanPrev = 0;
            foreach ($periodOrder as $pid) {
                $valB = $dataBerlaku[$tahunId][$pid] ?? null;
                $valK = $dataKonstan[$tahunId][$pid] ?? null;
                $valBPrev = $dataBerlaku[$prevYearId][$pid] ?? null;
                $valKPrev = $dataKonstan[$prevYearId][$pid] ?? null;
                if ($valB !== null) {
                    $totalBerlakuNow += $valB;
                }
                if ($valK !== null) {
                    $totalKonstanNow += $valK;
                }
                if ($valBPrev !== null) {
                    $totalBerlakuPrev += $valBPrev;
                }
                if ($valKPrev !== null) {
                    $totalKonstanPrev += $valKPrev;
                }
            }
            if ($totalKonstanNow == 0 || $totalKonstanPrev == 0) {
                return null;
            }
            $indeksNow = ($totalBerlakuNow / $totalKonstanNow) * 100;
            $indeksPrev = ($totalBerlakuPrev / $totalKonstanPrev) * 100;
            if ($indeksPrev == 0) {
                return null;
            }
            return (($indeksNow - $indeksPrev) / $indeksPrev) * 100;
        };

        $indexToCode = function ($num) {
            $code = '';
            while ($num > 0) {
                $num--;
                $code = chr(65 + ($num % 26)) . $code;
                $num = intdiv($num, 26);
            }
            return $code;
        };

        $getKategoriPrefix = function ($nama) {
            $map = [
                'Jasa Perusahaan' => 'M,N',
                'Jasa lainnya' => 'R,S,T,U',
                'Produk Domestik Regional Bruto' => 'PDRB',
                'Produk Domestik Regional Bruto Non Migas' => 'NON MIGAS',
            ];

            foreach ($map as $key => $prefix) {
                if (strcasecmp($nama, $key) === 0) {
                    return $prefix;
                }
            }

            return '';
        };

        $getKategoriCode = function ($nama, $defaultCode) {
            $specialMap = [
                'Pertanian, Peternakan, Perburuan dan Jasa Pertanian' => 'A1',
                'Kehutanan dan Penebangan Kayu' => '2',
                'Perikanan' => '3',
            ];

            return $specialMap[$nama] ?? $defaultCode;
        };

        $getSubCode = function ($subId, $subIndex, $catCode) {
            $customMap = [
                1 => '1',
                2 => 'A1A',
                3 => 'A1B',
                4 => 'A1C',
                5 => 'A1D',
                6 => 'A1E',
                7 => 'A1F',
                8 => 'A1G',
                9 => '2',
                10 => '3',
                23 => 'A1A',
                24 => 'A1B',
            ];

            if (isset($customMap[$subId])) {
                return $customMap[$subId];
            }

            $rangeList = array_merge([22], range(25, 39));
            $pos = array_search($subId, $rangeList, true);
            if ($pos !== false) {
                return (string) ($pos + 1);
            }

            return (string) $subIndex;
        };

        $setCellValue = function ($col, $row, $value) use ($sheet, $calcMode, $setCell, $cellAddress) {
            if ($value === null || $value === '') {
                $setCell($col, $row, '-');
                return;
            }
            if (is_string($value)) {
                $raw = trim($value);
                if ($raw !== '') {
                    $raw = str_replace([' ', '%'], '', $raw);
                    if (str_contains($raw, '.') && str_contains($raw, ',')) {
                        $raw = str_replace('.', '', $raw);
                        $raw = str_replace(',', '.', $raw);
                    } elseif (str_contains($raw, ',')) {
                        $raw = str_replace(',', '.', $raw);
                    }
                    $parsed = is_numeric($raw) ? (float) $raw : null;
                    if ($parsed !== null) {
                        $value = $parsed;
                    }
                }
            }

            $setCell($col, $row, $value);
            if ($calcMode) {
                if ($calcMode === 'perkapita') {
                    $sheet->getStyle($cellAddress($col, $row))->getNumberFormat()->setFormatCode('0.00');
                    return;
                }
                $sheet->getStyle($cellAddress($col, $row))->getNumberFormat()->setFormatCode('0.00"%"');
            } else {
                $sheet->getStyle($cellAddress($col, $row))->getNumberFormat()->setFormatCode('0.0');
            }
        };

        $rowOffset = 1;
        $row = 1 + $rowOffset;
        $col = 1;
        $rowLabel = 'Baris Data';

        if ($columnMode === 'wilayah') {
            $setCell($col, $row, $rowLabel);
            $mergeCells(1, $row, 1, $row + 1);
            $col = 2;

            foreach ($tahunTampil as $tahun) {
                foreach ($periodeTampil as $periode) {
                    $label = $tahun->tahun . ' ' . $periode->nama_periode;
                    $start = $col;
                    $end = $col + max($columnItems->count(), 1) - 1;
                    $setCell($start, $row, $label);
                    if ($end > $start) {
                        $mergeCells($start, $row, $end, $row);
                    }
                    foreach ($columnItems as $colWil) {
                        $setCell($col, $row + 1, $colWil->nama_wilayah);
                        $col++;
                    }
                }
                if ($showTotalColumn) {
                    $label = $tahun->tahun . ' Total';
                    $start = $col;
                    $end = $col + max($columnItems->count(), 1) - 1;
                    $setCell($start, $row, $label);
                    if ($end > $start) {
                        $mergeCells($start, $row, $end, $row);
                    }
                    foreach ($columnItems as $colWil) {
                        $setCell($col, $row + 1, $colWil->nama_wilayah);
                        $col++;
                    }
                }
            }

            $row += 2;
        } else {
            $setCell($col, $row, $rowLabel);
            $col = 2;
            foreach ($tahunTampil as $tahun) {
                foreach ($periodeTampil as $periode) {
                    $setCell($col, $row, $tahun->tahun . ' ' . $periode->nama_periode);
                    $col++;
                }
                if ($showTotalColumn) {
                    $setCell($col, $row, $tahun->tahun . ' Total');
                    $col++;
                }
            }
            $row++;
        }

        $headerStartRow = 1 + $rowOffset;
        $headerEndRow = ($columnMode === 'wilayah' ? 2 : 1) + $rowOffset;
        $headerLastCol = max(1, $col - 1);
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($headerLastCol);
        $dataStartRow = $row;

        $title = null;
        if (!empty($context['focusIndicator'])) {
            $fi = $context['focusIndicator'];
            $title = trim(($fi['group'] ?? '') . ' - ' . ($fi['label'] ?? $fi['label_short'] ?? ''));
        }
        if ($title) {
            $setCell(1, 1, $title);
            $mergeCells(1, 1, $headerLastCol, 1);
            $sheet->getStyle($cellAddress(1, 1) . ':' . $cellAddress($headerLastCol, 1))->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(20);
        }

        $headerRange = $cellAddress(1, $headerStartRow) . ':' . $cellAddress($headerLastCol, $headerEndRow);
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
        ]);
        $sheet->getRowDimension($headerStartRow)->setRowHeight(22);
        if ($headerEndRow > $headerStartRow) {
            $sheet->getRowDimension($headerEndRow)->setRowHeight(22);
        }

        $sheet->freezePane($cellAddress(2, $dataStartRow));
        $sheet->setAutoFilter($cellAddress(1, $headerEndRow) . ':' . $cellAddress($headerLastCol, $headerEndRow));

        $sheet->getColumnDimension('A')->setWidth(45);
        for ($i = 2; $i <= $headerLastCol; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setWidth(16);
        }

        $styleRow = function ($rowIndex, $style) use ($sheet, $lastColLetter) {
            $range = "A{$rowIndex}:{$lastColLetter}{$rowIndex}";
            if ($style === 'category') {
                $sheet->getStyle($range)->getFont()->setBold(true);
                $sheet->getStyle($range)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('DCE6F1');
            } elseif ($style === 'sub') {
                $sheet->getStyle($range)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF2CC');
            }
        };

        if ($rowMode === 'wilayah') {
            foreach ($rowItems as $wil) {
                $col = 1;
                $setCell($col, $row, $wil->nama_wilayah);
                $col++;
                foreach ($tahunTampil as $tahun) {
                    foreach ($periodeTampil as $periode) {
                        $nilai = $wilayahData[$wil->id_wilayah][$tahun->id_tahun][$periode->id_periode] ?? null;
                        $setCellValue($col, $row, $nilai);
                        $col++;
                    }
                    if ($showTotalColumn) {
                        $sum = 0;
                        $count = 0;
                        foreach ($periodeTotalList as $periodeTotal) {
                            $nilaiTotal = $wilayahData[$wil->id_wilayah][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                            if ($nilaiTotal !== null) {
                                $sum += $nilaiTotal;
                                $count++;
                            }
                        }
                        $setCellValue($col, $row, $count > 0 ? $sum : null);
                        $col++;
                    }
                }
                $row++;
            }
        } elseif ($columnMode === 'wilayah' && $rowMode === 'sub') {
            $subOnlyCounter = 0;
            foreach ($rowItems as $subRow) {
                $subOnlyCounter++;
                $col = 1;
                $setCell($col, $row, $subOnlyCounter . ' ' . $subRow->nama_sub_kategori);
                $styleRow($row, 'sub');
                $col++;
                foreach ($tahunTampil as $tahun) {
                    foreach ($periodeTampil as $periode) {
                        foreach ($columnItems as $colWil) {
                            $nilai = $computeGrowth($subWilayahData[$subRow->id_sub_kategori][$colWil->id_wilayah] ?? [], $tahun->id_tahun, $periode->id_periode, $colWil->id_wilayah);
                            $setCellValue($col, $row, $nilai);
                            $col++;
                        }
                    }
                    if ($showTotalColumn) {
                        foreach ($columnItems as $colWil) {
                            $sum = 0;
                            $count = 0;
                            foreach ($periodeTotalList as $periodeTotal) {
                                $nilaiTotal = $subWilayahData[$subRow->id_sub_kategori][$colWil->id_wilayah][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                if ($nilaiTotal !== null) {
                                    $sum += $nilaiTotal;
                                    $count++;
                                }
                            }
                            if ($calcMode === 'perkapita') {
                                $penduduk = $pendudukWilayahData[$colWil->id_wilayah][$tahun->id_tahun] ?? null;
                                $setCellValue($col, $row, ($count > 0 && $penduduk) ? (($sum / $penduduk) * $perkapitaScale) : null);
                            } else {
                                $setCellValue($col, $row, $count > 0 ? $sum : null);
                            }
                            $col++;
                        }
                    }
                }
                $row++;
            }
        } elseif ($columnMode === 'wilayah') {
            $catCounter = 0;
            foreach ($rowItems as $kategoriRow) {
                if ($calcMode === 'distribusi' && in_array($kategoriRow->id_kategori, $pdrbKategoriIds, true)) {
                    continue;
                }
                $catCounter++;
                $col = 1;
                $catCode = $getKategoriCode($kategoriRow->nama_kategori, $indexToCode($catCounter));
                $catPrefix = $getKategoriPrefix($kategoriRow->nama_kategori);
                $labelCode = $catPrefix ?: $catCode;
                $setCell($col, $row, $labelCode . ' ' . $kategoriRow->nama_kategori);
                $styleRow($row, 'category');
                $col++;
                foreach ($tahunTampil as $tahun) {
                    foreach ($periodeTampil as $periode) {
                        foreach ($columnItems as $colWil) {
                            $nilai = $computeGrowth($kategoriWilayahData[$kategoriRow->id_kategori][$colWil->id_wilayah] ?? [], $tahun->id_tahun, $periode->id_periode, $colWil->id_wilayah);
                            $setCellValue($col, $row, $nilai);
                            $col++;
                        }
                    }
                    if ($showTotalColumn) {
                        foreach ($columnItems as $colWil) {
                            $sum = 0;
                            $count = 0;
                            foreach ($periodeTotalList as $periodeTotal) {
                                $nilaiTotal = $kategoriWilayahData[$kategoriRow->id_kategori][$colWil->id_wilayah][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                if ($nilaiTotal !== null) {
                                    $sum += $nilaiTotal;
                                    $count++;
                                }
                            }
                            if ($calcMode === 'perkapita') {
                                $penduduk = $pendudukWilayahData[$colWil->id_wilayah][$tahun->id_tahun] ?? null;
                                $setCellValue($col, $row, ($count > 0 && $penduduk) ? (($sum / $penduduk) * $perkapitaScale) : null);
                            } else {
                                $setCellValue($col, $row, $count > 0 ? $sum : null);
                            }
                            $col++;
                        }
                    }
                }
                $row++;

                $subIndex = 0;
                foreach ($subFiltered->where('id_kategori', $kategoriRow->id_kategori) as $subRow) {
                    $subIndex++;
                    $col = 1;
                    $subCode = $getSubCode($subRow->id_sub_kategori, $subIndex, $catCode);
                    $setCell($col, $row, $subCode . ' ' . $subRow->nama_sub_kategori);
                    $styleRow($row, 'sub');
                    $col++;
                    foreach ($tahunTampil as $tahun) {
                        foreach ($periodeTampil as $periode) {
                            foreach ($columnItems as $colWil) {
                                $nilai = $computeGrowth($subWilayahData[$subRow->id_sub_kategori][$colWil->id_wilayah] ?? [], $tahun->id_tahun, $periode->id_periode, $colWil->id_wilayah);
                                $setCellValue($col, $row, $nilai);
                                $col++;
                            }
                        }
                        if ($showTotalColumn) {
                            foreach ($columnItems as $colWil) {
                                $sum = 0;
                                $count = 0;
                                foreach ($periodeTotalList as $periodeTotal) {
                                    $nilaiTotal = $subWilayahData[$subRow->id_sub_kategori][$colWil->id_wilayah][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                    if ($nilaiTotal !== null) {
                                        $sum += $nilaiTotal;
                                        $count++;
                                    }
                                }
                                if ($calcMode === 'perkapita') {
                                    $penduduk = $pendudukWilayahData[$colWil->id_wilayah][$tahun->id_tahun] ?? null;
                                    $setCellValue($col, $row, ($count > 0 && $penduduk) ? (($sum / $penduduk) * $perkapitaScale) : null);
                                } else {
                                    $setCellValue($col, $row, $count > 0 ? $sum : null);
                                }
                                $col++;
                            }
                        }
                    }
                    $row++;
                }
            }
        } elseif ($rowMode === 'sub') {
            $subOnlyCounter = 0;
            foreach ($rowItems as $subRow) {
                $subOnlyCounter++;
                $col = 1;
                $setCell($col, $row, $subOnlyCounter . ' ' . $subRow->nama_sub_kategori);
                $styleRow($row, 'sub');
                $col++;
                foreach ($tahunTampil as $tahun) {
                    foreach ($periodeTampil as $periode) {
                        $nilai = $computeGrowth($subData[$subRow->id_sub_kategori] ?? [], $tahun->id_tahun, $periode->id_periode);
                        $setCellValue($col, $row, $nilai);
                        $col++;
                    }
                    if ($showTotalColumn) {
                        if ($calcMode) {
                            $nilaiTotal = $computeTotalGrowth($subData[$subRow->id_sub_kategori] ?? [], $tahun->id_tahun);
                            $setCellValue($col, $row, $nilaiTotal);
                        } else {
                            $sum = 0;
                            $count = 0;
                            foreach ($periodeTotalList as $periodeTotal) {
                                $nilaiTotal = $subData[$subRow->id_sub_kategori][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                if ($nilaiTotal !== null) {
                                    $sum += $nilaiTotal;
                                    $count++;
                                }
                            }
                            $setCellValue($col, $row, $count > 0 ? $sum : null);
                        }
                        $col++;
                    }
                }
                $row++;
            }
        } elseif ($rowMode === 'sektor' || $rowMode === 'sektor_pengeluaran') {
            foreach ($rowItems as $sektorRow) {
                $col = 1;
                $sektorId = $sektorRow->id_sektor;
                $setCell($col, $row, $sektorRow->nama_sektor);
                $styleRow($row, 'category');
                $col++;
                foreach ($tahunTampil as $tahun) {
                    foreach ($periodeTampil as $periode) {
                        if ($calcMode === 'indeks') {
                            $nilai = $computeIndeks($sektorDataBerlaku[$sektorId] ?? [], $sektorDataKonstan[$sektorId] ?? [], $tahun->id_tahun, $periode->id_periode);
                        } elseif ($calcMode === 'laju') {
                            $nilai = $computeLaju($sektorDataBerlaku[$sektorId] ?? [], $sektorDataKonstan[$sektorId] ?? [], $tahun->id_tahun, $periode->id_periode);
                        } else {
                            $dataSrc = $focusIndicator['type'] === 'berlaku' ? ($sektorDataBerlaku[$sektorId] ?? []) : ($sektorDataKonstan[$sektorId] ?? []);
                            $nilai = $computeGrowth($dataSrc, $tahun->id_tahun, $periode->id_periode);
                        }
                        $setCellValue($col, $row, $nilai);
                        $col++;
                    }
                    if ($showTotalColumn) {
                        if ($calcMode === 'indeks') {
                            $nilaiTotal = $computeIndeksTotal($sektorDataBerlaku[$sektorId] ?? [], $sektorDataKonstan[$sektorId] ?? [], $tahun->id_tahun);
                        } elseif ($calcMode === 'laju') {
                            $nilaiTotal = $computeLajuTotal($sektorDataBerlaku[$sektorId] ?? [], $sektorDataKonstan[$sektorId] ?? [], $tahun->id_tahun);
                        } elseif ($calcMode) {
                            $dataSrc = $focusIndicator['type'] === 'berlaku' ? ($sektorDataBerlaku[$sektorId] ?? []) : ($sektorDataKonstan[$sektorId] ?? []);
                            $nilaiTotal = $computeTotalGrowth($dataSrc, $tahun->id_tahun);
                        } else {
                            $sum = 0;
                            $count = 0;
                            $dataSrc = $focusIndicator['type'] === 'berlaku' ? ($sektorDataBerlaku[$sektorId] ?? []) : ($sektorDataKonstan[$sektorId] ?? []);
                            foreach ($periodeTotalList as $periodeTotal) {
                                $val = $dataSrc[$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                if ($val !== null) {
                                    $sum += $val;
                                    $count++;
                                }
                            }
                            $nilaiTotal = $count > 0 ? $sum : null;
                        }
                        $setCellValue($col, $row, $nilaiTotal);
                        $col++;
                    }
                }
                $row++;
            }

            // TOTAL ROW for Sektor
            $col = 1;
            $setCell($col, $row, 'TOTAL');
            $styleRow($row, 'category');
            $col++;

            foreach ($tahunTampil as $tahun) {
                $totalBerlakuData = [];
                $totalKonstanData = [];
                $tahunValue = $tahunById->get($tahun->id_tahun)?->tahun;
                $prevYearId = $tahunByValue->get($tahunValue - 1)?->id_tahun;
                $yearsToSum = [$tahun->id_tahun];
                if ($prevYearId)
                    $yearsToSum[] = $prevYearId;

                foreach ($yearsToSum as $tId) {
                    foreach ($periodeMaster as $p) {
                        $sumB = 0;
                        $sumK = 0;
                        foreach ($rowItems as $sRow) {
                            $sumB += ($sektorDataBerlaku[$sRow->id_sektor][$tId][$p->id_periode] ?? 0);
                            $sumK += ($sektorDataKonstan[$sRow->id_sektor][$tId][$p->id_periode] ?? 0);
                        }
                        $totalBerlakuData[$tId][$p->id_periode] = $sumB;
                        $totalKonstanData[$tId][$p->id_periode] = $sumK;
                    }
                }

                $dataSrcTotal = ($context['focusIndicator']['type'] ?? 'berlaku') === 'berlaku' ? $totalBerlakuData : $totalKonstanData;

                foreach ($periodeTampil as $periode) {
                    if ($calcMode === 'indeks') {
                        $nilai = $computeIndeks($totalBerlakuData, $totalKonstanData, $tahun->id_tahun, $periode->id_periode);
                    } elseif ($calcMode === 'laju') {
                        $nilai = $computeLaju($totalBerlakuData, $totalKonstanData, $tahun->id_tahun, $periode->id_periode);
                    } else {
                        $nilai = $computeGrowth($dataSrcTotal, $tahun->id_tahun, $periode->id_periode);
                    }
                    $setCellValue($col, $row, $nilai);
                    $col++;
                }

                if ($showTotalColumn) {
                    if ($calcMode === 'indeks') {
                        $nilaiTotal = $computeIndeksTotal($totalBerlakuData, $totalKonstanData, $tahun->id_tahun);
                    } elseif ($calcMode === 'laju') {
                        $nilaiTotal = $computeLajuTotal($totalBerlakuData, $totalKonstanData, $tahun->id_tahun);
                    } elseif ($calcMode) {
                        $nilaiTotal = $computeTotalGrowth($dataSrcTotal, $tahun->id_tahun);
                    } else {
                        $sum = 0;
                        $count = 0;
                        foreach ($periodeTotalList as $periodeTotal) {
                            $val = $dataSrcTotal[$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                            if ($val !== null) {
                                $sum += $val;
                                $count++;
                            }
                        }
                        $nilaiTotal = $count > 0 ? $sum : null;
                    }
                    $setCellValue($col, $row, $nilaiTotal);
                    $col++;
                }
            }
            $row++;
        } else {
            $catCounter = 0;
            foreach ($rowItems as $kategoriRow) {
                if ($calcMode === 'distribusi' && in_array($kategoriRow->id_kategori, $pdrbKategoriIds, true)) {
                    continue;
                }
                $catCounter++;
                $col = 1;
                $catCode = $getKategoriCode($kategoriRow->nama_kategori, $indexToCode($catCounter));
                $catPrefix = $getKategoriPrefix($kategoriRow->nama_kategori);
                $labelCode = $catPrefix ?: $catCode;
                $setCell($col, $row, $labelCode . ' ' . $kategoriRow->nama_kategori);
                $styleRow($row, 'category');
                $col++;
                foreach ($tahunTampil as $tahun) {
                    foreach ($periodeTampil as $periode) {
                        if ($calcMode === 'indeks') {
                            $nilai = $computeIndeks($kategoriDataBerlaku[$kategoriRow->id_kategori] ?? [], $kategoriDataKonstan[$kategoriRow->id_kategori] ?? [], $tahun->id_tahun, $periode->id_periode);
                        } elseif ($calcMode === 'laju') {
                            $nilai = $computeLaju($kategoriDataBerlaku[$kategoriRow->id_kategori] ?? [], $kategoriDataKonstan[$kategoriRow->id_kategori] ?? [], $tahun->id_tahun, $periode->id_periode);
                        } else {
                            $nilai = $computeGrowth($kategoriData[$kategoriRow->id_kategori] ?? [], $tahun->id_tahun, $periode->id_periode);
                        }
                        $setCellValue($col, $row, $nilai);
                        $col++;
                    }
                    if ($showTotalColumn) {
                        if ($calcMode === 'indeks') {
                            $nilaiTotal = $computeIndeksTotal($kategoriDataBerlaku[$kategoriRow->id_kategori] ?? [], $kategoriDataKonstan[$kategoriRow->id_kategori] ?? [], $tahun->id_tahun);
                            $setCellValue($col, $row, $nilaiTotal);
                        } elseif ($calcMode === 'laju') {
                            $nilaiTotal = $computeLajuTotal($kategoriDataBerlaku[$kategoriRow->id_kategori] ?? [], $kategoriDataKonstan[$kategoriRow->id_kategori] ?? [], $tahun->id_tahun);
                            $setCellValue($col, $row, $nilaiTotal);
                        } elseif ($calcMode) {
                            $nilaiTotal = $computeTotalGrowth($kategoriData[$kategoriRow->id_kategori] ?? [], $tahun->id_tahun);
                            $setCellValue($col, $row, $nilaiTotal);
                        } else {
                            $sum = 0;
                            $count = 0;
                            foreach ($periodeTotalList as $periodeTotal) {
                                $nilaiTotal = $kategoriData[$kategoriRow->id_kategori][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                if ($nilaiTotal !== null) {
                                    $sum += $nilaiTotal;
                                    $count++;
                                }
                            }
                            $setCellValue($col, $row, $count > 0 ? $sum : null);
                        }
                        $col++;
                    }
                }
                $row++;

                $subIndex = 0;
                foreach ($subFiltered->where('id_kategori', $kategoriRow->id_kategori) as $subRow) {
                    $subIndex++;
                    $col = 1;
                    $subCode = $getSubCode($subRow->id_sub_kategori, $subIndex, $catCode);
                    $setCell($col, $row, $subCode . ' ' . $subRow->nama_sub_kategori);
                    $styleRow($row, 'sub');
                    $col++;
                    foreach ($tahunTampil as $tahun) {
                        foreach ($periodeTampil as $periode) {
                            if ($calcMode === 'indeks') {
                                $nilai = $computeIndeks($subDataBerlaku[$subRow->id_sub_kategori] ?? [], $subDataKonstan[$subRow->id_sub_kategori] ?? [], $tahun->id_tahun, $periode->id_periode);
                            } elseif ($calcMode === 'laju') {
                                $nilai = $computeLaju($subDataBerlaku[$subRow->id_sub_kategori] ?? [], $subDataKonstan[$subRow->id_sub_kategori] ?? [], $tahun->id_tahun, $periode->id_periode);
                            } else {
                                $nilai = $computeGrowth($subData[$subRow->id_sub_kategori] ?? [], $tahun->id_tahun, $periode->id_periode);
                            }
                            $setCellValue($col, $row, $nilai);
                            $col++;
                        }
                        if ($showTotalColumn) {
                            if ($calcMode === 'indeks') {
                                $nilaiTotal = $computeIndeksTotal($subDataBerlaku[$subRow->id_sub_kategori] ?? [], $subDataKonstan[$subRow->id_sub_kategori] ?? [], $tahun->id_tahun);
                                $setCellValue($col, $row, $nilaiTotal);
                            } elseif ($calcMode === 'laju') {
                                $nilaiTotal = $computeLajuTotal($subDataBerlaku[$subRow->id_sub_kategori] ?? [], $subDataKonstan[$subRow->id_sub_kategori] ?? [], $tahun->id_tahun);
                                $setCellValue($col, $row, $nilaiTotal);
                            } elseif ($calcMode) {
                                $nilaiTotal = $computeTotalGrowth($subData[$subRow->id_sub_kategori] ?? [], $tahun->id_tahun);
                                $setCellValue($col, $row, $nilaiTotal);
                            } else {
                                $sum = 0;
                                $count = 0;
                                foreach ($periodeTotalList as $periodeTotal) {
                                    $nilaiTotal = $subData[$subRow->id_sub_kategori][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                    if ($nilaiTotal !== null) {
                                        $sum += $nilaiTotal;
                                        $count++;
                                    }
                                }
                                $setCellValue($col, $row, $count > 0 ? $sum : null);
                            }
                            $col++;
                        }
                    }
                    $row++;
                }
            }
        }

        if ($calcMode === 'distribusi' && !empty($pdrbKategoriIds)) {
            $pdrbRows = collect($pdrbKategoriIds)->map(function ($id) use ($rowItems, $kategori) {
                return $rowItems->firstWhere('id_kategori', $id) ?? $kategori->firstWhere('id_kategori', $id);
            })->filter();

            foreach ($pdrbRows as $kategoriRow) {
                $col = 1;
                $setCell($col, $row, $kategoriRow->nama_kategori);
                $styleRow($row, 'category');
                $col++;

                if ($columnMode === 'wilayah') {
                    foreach ($tahunTampil as $tahun) {
                        foreach ($periodeTampil as $periode) {
                            foreach ($columnItems as $colWil) {
                                $nilai = $computeGrowth($kategoriWilayahData[$kategoriRow->id_kategori][$colWil->id_wilayah] ?? [], $tahun->id_tahun, $periode->id_periode, $colWil->id_wilayah);
                                $setCellValue($col, $row, $nilai);
                                $col++;
                            }
                        }
                        if ($showTotalColumn) {
                            foreach ($columnItems as $colWil) {
                                $nilaiTotal = $computeTotalGrowth($kategoriWilayahData[$kategoriRow->id_kategori][$colWil->id_wilayah] ?? [], $tahun->id_tahun);
                                $setCellValue($col, $row, $nilaiTotal);
                                $col++;
                            }
                        }
                    }
                } else {
                    foreach ($tahunTampil as $tahun) {
                        foreach ($periodeTampil as $periode) {
                            $nilai = $computeGrowth($kategoriData[$kategoriRow->id_kategori] ?? [], $tahun->id_tahun, $periode->id_periode);
                            $setCellValue($col, $row, $nilai);
                            $col++;
                        }
                        if ($showTotalColumn) {
                            $nilaiTotal = $computeTotalGrowth($kategoriData[$kategoriRow->id_kategori] ?? [], $tahun->id_tahun);
                            $setCellValue($col, $row, $nilaiTotal);
                            $col++;
                        }
                    }
                }

                $row++;
            }
        }

        $lastDataRow = $row - 1;
        if ($lastDataRow >= $headerStartRow) {
            $sheet->getStyle("A{$headerStartRow}:{$lastColLetter}{$lastDataRow}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            if ($lastDataRow >= $dataStartRow) {
                $sheet->getStyle("B{$dataStartRow}:{$lastColLetter}{$lastDataRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            $sheet->getStyle("A{$dataStartRow}:A{$lastDataRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'tabel-dinamis-' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $filename
        );
    }

    private function gatherContext(Request $request): array
    {
        $user = auth()->user();

        $pendekatan = $request->get('jenis', $request->get('pendekatan', 'lapangan_usaha'));
        if (!in_array($pendekatan, ['lapangan_usaha', 'pengeluaran'], true)) {
            $pendekatan = 'lapangan_usaha';
        }

        $level = $request->get('level', 'kategori');
        if (!in_array($level, ['kategori', 'sub'], true)) {
            $level = 'kategori';
        }

        $tahapData = $request->get('tahap_data', 'awal');
        if (!in_array($tahapData, ['awal', 'rekonsiliasi'], true)) {
            $tahapData = 'awal';
        }

        $scopeWilayah = $request->get('scope_wilayah') ?: ($user?->id_wilayah ?? null);
        if ($user && in_array($user->role, ['kabupaten', 'kota'], true)) {
            $scopeWilayah = $user->id_wilayah;
        }
        $selectedYears = (array) $request->get('tahun_ids', []);
        $selectedPeriodIds = (array) $request->get('periode_checks', []);
        $includeTotal = $request->boolean('include_total');
        $selectedComponentIds = (array) $request->get('component_ids', []);
        $selectedYears = array_values(array_filter(array_map('intval', $selectedYears)));
        $selectedPeriodIds = array_values(array_filter(array_map('intval', $selectedPeriodIds)));
        $selectedComponentIds = array_values(array_filter($selectedComponentIds, fn($v) => $v !== null && $v !== ''));
        // Convert to int only if they look like digits, to preserve sector string IDs
        $selectedComponentIds = array_map(fn($v) => is_numeric($v) ? (int) $v : $v, $selectedComponentIds);

        $tahunMaster = Tahun::orderBy('tahun')->get(['id_tahun', 'tahun']);
        $tahunList = $tahunMaster;
        $periodeList = Periode::orderBy('id_periode')->get(['id_periode', 'nama_periode']);
        $periodeMaster = $periodeList;

        $kategori = Kategori::orderBy('id_kategori')
            ->get(['id_kategori', 'nama_kategori', 'pendekatan', 'kode_kategori']);
        $kategoriIds = $kategori->pluck('id_kategori')->toArray();

        $sub = SubKategori::whereIn('id_kategori', $kategoriIds)
            ->orderBy('id_sub_kategori')
            ->get(['id_sub_kategori', 'nama_sub_kategori', 'id_kategori']);

        $indicatorSelection = $request->get('indicator', 'lu_konstan');
        $indicatorDefinitions = collect([
            [
                'key' => 'lu_konstan',
                'label' => 'Konstan Menurut Lapangan Usaha',
                'label_short' => 'Konstan (ADHK)',
                'group' => 'PDRB Menurut Lapangan Usaha',
                'group_order' => 1,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
            ],
            [
                'key' => 'lu_berlaku',
                'label' => 'Berlaku Menurut Lapangan Usaha',
                'label_short' => 'Berlaku (ADHB)',
                'group' => 'PDRB Menurut Lapangan Usaha',
                'group_order' => 1,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'lapangan_usaha',
            ],
            [
                'key' => 'lu_berlaku_wilayah',
                'label' => 'PDRB Lapangan Usaha Berlaku per Wilayah',
                'label_short' => 'Berlaku - Kategori',
                'group' => 'PDRB Menurut Lapangan Usaha (Per Wilayah)',
                'group_order' => 2,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'lapangan_usaha',
                'column_mode' => 'wilayah',
                'row_mode' => 'kategori',
                'show_sub' => false,
            ],
            [
                'key' => 'lu_konstan_wilayah',
                'label' => 'PDRB Lapangan Usaha Konstan per Wilayah',
                'label_short' => 'Konstan - Kategori',
                'group' => 'PDRB Menurut Lapangan Usaha (Per Wilayah)',
                'group_order' => 2,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
                'column_mode' => 'wilayah',
                'row_mode' => 'kategori',
                'show_sub' => false,
            ],
            [
                'key' => 'peng_berlaku_wilayah',
                'label' => 'PDRB Pengeluaran Berlaku per Wilayah',
                'label_short' => 'Berlaku - Komponen',
                'group' => 'PDRB Menurut Pengeluaran (Per Wilayah)',
                'group_order' => 8,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'pengeluaran',
                'column_mode' => 'wilayah',
                'row_mode' => 'kategori',
                'show_sub' => false,
            ],
            [
                'key' => 'peng_konstan_wilayah',
                'label' => 'PDRB Pengeluaran Konstan per Wilayah',
                'label_short' => 'Konstan - Komponen',
                'group' => 'PDRB Menurut Pengeluaran (Per Wilayah)',
                'group_order' => 8,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
                'column_mode' => 'wilayah',
                'row_mode' => 'kategori',
                'show_sub' => false,
            ],
            [
                'key' => 'lu_yony',
                'label' => 'Pertumbuhan Ekonomi Lapangan Usaha (Y-on-Y)',
                'label_short' => 'Y-on-Y',
                'group' => 'Pertumbuhan Ekonomi Menurut Lapangan Usaha',
                'group_order' => 3,
                'item_order' => 1,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
                'calc' => 'y-on-y',
            ],
            [
                'key' => 'lu_qtoq',
                'label' => 'Pertumbuhan Ekonomi Lapangan Usaha (Q-to-Q)',
                'label_short' => 'Q-to-Q',
                'group' => 'Pertumbuhan Ekonomi Menurut Lapangan Usaha',
                'group_order' => 3,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
                'calc' => 'q-to-q',
            ],
            [
                'key' => 'lu_ctoc',
                'label' => 'Pertumbuhan Ekonomi Lapangan Usaha (C-to-C)',
                'label_short' => 'C-to-C',
                'group' => 'Pertumbuhan Ekonomi Menurut Lapangan Usaha',
                'group_order' => 3,
                'item_order' => 3,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
                'calc' => 'c-to-c',
            ],
            [
                'key' => 'lu_laju',
                'label' => 'Laju Implisit Lapangan Usaha',
                'label_short' => 'Laju Implisit',
                'group' => 'Pertumbuhan Implisit/Distribusi (Lapangan Usaha)',
                'group_order' => 4,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'lapangan_usaha',
                'calc' => 'laju',
            ],
            [
                'key' => 'lu_indeks',
                'label' => 'Indeks Implisit Lapangan Usaha',
                'label_short' => 'Indeks Implisit',
                'group' => 'Pertumbuhan Implisit/Distribusi (Lapangan Usaha)',
                'group_order' => 4,
                'item_order' => 2,
                'type' => 'berlaku',
                'pendekatan' => 'lapangan_usaha',
                'calc' => 'indeks',
            ],
            [
                'key' => 'lu_distribusi_sultra',
                'label' => 'Distribusi PDRB Lapangan Usaha',
                'label_short' => 'Distribusi',
                'group' => 'Pertumbuhan Implisit/Distribusi (Lapangan Usaha)',
                'group_order' => 4,
                'item_order' => 3,
                'type' => 'berlaku',
                'pendekatan' => 'lapangan_usaha',
                'calc' => 'distribusi',
            ],
            [
                'key' => 'lu_sektor_berlaku',
                'label' => '1. PDRB Harga Berlaku Menurut Lapangan Usaha (Sektoral)',
                'label_short' => '1. PDRB Harga Berlaku',
                'group' => 'PDRB Menurut Lapangan Usaha (Sektoral)',
                'group_order' => 1,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'lapangan_usaha',
                'row_mode' => 'sektor',
                'show_sub' => false,
            ],
            [
                'key' => 'lu_sektor_konstan',
                'label' => '2. PDRB Harga Konstan Menurut Lapangan Usaha (Sektoral)',
                'label_short' => '2. PDRB Harga Konstan',
                'group' => 'PDRB Menurut Lapangan Usaha (Sektoral)',
                'group_order' => 1,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
                'row_mode' => 'sektor',
                'show_sub' => false,
            ],
            [
                'key' => 'lu_sektor_qtoq',
                'label' => '3. Laju Pertumbuhan Ekonomi (Q-to-Q) (Sektoral)',
                'label_short' => '3. Laju (Q-to-Q)',
                'group' => 'PDRB Menurut Lapangan Usaha (Sektoral)',
                'group_order' => 1,
                'item_order' => 3,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
                'row_mode' => 'sektor',
                'calc' => 'q-to-q',
                'show_sub' => false,
            ],
            [
                'key' => 'lu_sektor_yony',
                'label' => '4. Laju Pertumbuhan Ekonomi (Y-on-Y) (Sektoral)',
                'label_short' => '4. Laju (Y-on-Y)',
                'group' => 'PDRB Menurut Lapangan Usaha (Sektoral)',
                'group_order' => 1,
                'item_order' => 4,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
                'row_mode' => 'sektor',
                'calc' => 'y-on-y',
                'show_sub' => false,
            ],
            [
                'key' => 'lu_sektor_ctoc',
                'label' => '5. Laju Pertumbuhan Ekonomi (C-to-C) (Sektoral)',
                'label_short' => '5. Laju (C-to-C)',
                'group' => 'PDRB Menurut Lapangan Usaha (Sektoral)',
                'group_order' => 1,
                'item_order' => 5,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
                'row_mode' => 'sektor',
                'calc' => 'c-to-c',
                'show_sub' => false,
            ],
            [
                'key' => 'lu_sektor_indeks',
                'label' => '6. Indeks Implisit (Sektoral)',
                'label_short' => '6. Indeks Implisit',
                'group' => 'PDRB Menurut Lapangan Usaha (Sektoral)',
                'group_order' => 1,
                'item_order' => 6,
                'type' => 'berlaku',
                'pendekatan' => 'lapangan_usaha',
                'row_mode' => 'sektor',
                'calc' => 'indeks',
                'show_sub' => false,
            ],
            [
                'key' => 'lu_sektor_laju_indeks',
                'label' => '7. Laju Indeks Implisit (Q-to-Q) (Sektoral)',
                'label_short' => '7. Laju Indeks (Q-to-Q)',
                'group' => 'PDRB Menurut Lapangan Usaha (Sektoral)',
                'group_order' => 1,
                'item_order' => 7,
                'type' => 'berlaku',
                'pendekatan' => 'lapangan_usaha',
                'row_mode' => 'sektor',
                'calc' => 'laju',
                'show_sub' => false,
            ],
            [
                'key' => 'peng_konstan',
                'label' => 'Konstan Menurut Pengeluaran',
                'label_short' => 'Konstan (ADHK)',
                'group' => 'PDRB Menurut Pengeluaran',
                'group_order' => 7,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
            ],
            [
                'key' => 'peng_berlaku',
                'label' => 'Berlaku Menurut Pengeluaran',
                'label_short' => 'Berlaku (ADHB)',
                'group' => 'PDRB Menurut Pengeluaran',
                'group_order' => 7,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'pengeluaran',
            ],
            [
                'key' => 'peng_yony',
                'label' => 'Pertumbuhan Ekonomi Pengeluaran (Y-on-Y)',
                'label_short' => 'Y-on-Y',
                'group' => 'Pertumbuhan Ekonomi Menurut Pengeluaran',
                'group_order' => 9,
                'item_order' => 1,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
                'calc' => 'y-on-y',
            ],
            [
                'key' => 'peng_qtoq',
                'label' => 'Pertumbuhan Ekonomi Pengeluaran (Q-to-Q)',
                'label_short' => 'Q-to-Q',
                'group' => 'Pertumbuhan Ekonomi Menurut Pengeluaran',
                'group_order' => 9,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
                'calc' => 'q-to-q',
            ],
            [
                'key' => 'peng_ctoc',
                'label' => 'Pertumbuhan Ekonomi Pengeluaran (C-to-C)',
                'label_short' => 'C-to-C',
                'group' => 'Pertumbuhan Ekonomi Menurut Pengeluaran',
                'group_order' => 9,
                'item_order' => 3,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
                'calc' => 'c-to-c',
            ],
            [
                'key' => 'peng_distribusi',
                'label' => 'Distribusi PDRB Pengeluaran',
                'label_short' => 'Distribusi',
                'group' => 'Pertumbuhan Implisit/Distribus (Pengeluaran)',
                'group_order' => 10,
                'item_order' => 3,
                'type' => 'berlaku',
                'pendekatan' => 'pengeluaran',
                'calc' => 'distribusi',
            ],
            [
                'key' => 'peng_laju',
                'label' => 'Laju Implisit Pengeluaran',
                'label_short' => 'Laju Implisit',
                'group' => 'Pertumbuhan Implisit/Distribus (Pengeluaran)',
                'group_order' => 10,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'pengeluaran',
                'calc' => 'laju',
            ],
            [
                'key' => 'peng_indeks',
                'label' => 'Indeks Implisit Pengeluaran',
                'label_short' => 'Indeks Implisit',
                'group' => 'Pertumbuhan Implisit/Distribus (Pengeluaran)',
                'group_order' => 10,
                'item_order' => 2,
                'type' => 'berlaku',
                'pendekatan' => 'pengeluaran',
                'calc' => 'indeks',
            ],
            // Sektoral Pengeluaran
            [
                'key' => 'peng_sektor_berlaku',
                'label' => '1. PDRB Harga Berlaku Menurut Pengeluaran (Sektoral)',
                'label_short' => '1. PDRB Harga Berlaku',
                'group' => 'PDRB Menurut Pengeluaran (Sektoral)',
                'group_order' => 8,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'pengeluaran',
                'row_mode' => 'sektor_pengeluaran',
                'show_sub' => false,
            ],
            [
                'key' => 'peng_sektor_konstan',
                'label' => '2. PDRB Harga Konstan Menurut Pengeluaran (Sektoral)',
                'label_short' => '2. PDRB Harga Konstan',
                'group' => 'PDRB Menurut Pengeluaran (Sektoral)',
                'group_order' => 8,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
                'row_mode' => 'sektor_pengeluaran',
                'show_sub' => false,
            ],
            [
                'key' => 'peng_sektor_qtoq',
                'label' => '3. Laju Pertumbuhan PDRB Pengeluaran (Q-to-Q) (Sektoral)',
                'label_short' => '3. Laju (Q-to-Q)',
                'group' => 'PDRB Menurut Pengeluaran (Sektoral)',
                'group_order' => 8,
                'item_order' => 3,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
                'row_mode' => 'sektor_pengeluaran',
                'calc' => 'q-to-q',
                'show_sub' => false,
            ],
            [
                'key' => 'peng_sektor_yony',
                'label' => '4. Laju Pertumbuhan PDRB Pengeluaran (Y-on-Y) (Sektoral)',
                'label_short' => '4. Laju (Y-on-Y)',
                'group' => 'PDRB Menurut Pengeluaran (Sektoral)',
                'group_order' => 8,
                'item_order' => 4,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
                'row_mode' => 'sektor_pengeluaran',
                'calc' => 'y-on-y',
                'show_sub' => false,
            ],
            [
                'key' => 'peng_sektor_ctoc',
                'label' => '5. Laju Pertumbuhan PDRB Pengeluaran (C-to-C) (Sektoral)',
                'label_short' => '5. Laju (C-to-C)',
                'group' => 'PDRB Menurut Pengeluaran (Sektoral)',
                'group_order' => 8,
                'item_order' => 5,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
                'row_mode' => 'sektor_pengeluaran',
                'calc' => 'c-to-c',
                'show_sub' => false,
            ],
            [
                'key' => 'peng_sektor_indeks',
                'label' => '6. Indeks Implisit Pengeluaran (Sektoral)',
                'label_short' => '6. Indeks Implisit',
                'group' => 'PDRB Menurut Pengeluaran (Sektoral)',
                'group_order' => 8,
                'item_order' => 6,
                'type' => 'berlaku',
                'pendekatan' => 'pengeluaran',
                'row_mode' => 'sektor_pengeluaran',
                'calc' => 'indeks',
                'show_sub' => false,
            ],
            [
                'key' => 'peng_sektor_laju_indeks',
                'label' => '7. Laju Indeks Implisit Pengeluaran (Q-to-Q) (Sektoral)',
                'label_short' => '7. Laju Indeks (Q-to-Q)',
                'group' => 'PDRB Menurut Pengeluaran (Sektoral)',
                'group_order' => 8,
                'item_order' => 7,
                'type' => 'berlaku',
                'pendekatan' => 'pengeluaran',
                'row_mode' => 'sektor_pengeluaran',
                'calc' => 'laju',
                'show_sub' => false,
            ],
            [
                'key' => 'perkapita',
                'label' => 'PDRB Per Kapita',
                'label_short' => 'Berlaku (ADHB)',
                'group' => 'PDRB Per Kapita Menurut Lapangan Usaha',
                'group_order' => 5,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'lapangan_usaha',
                'calc' => 'perkapita',
                'show_sub' => false,
            ],
            [
                'key' => 'perkapita_konstan',
                'label' => 'PDRB Per Kapita (Konstan)',
                'label_short' => 'Konstan (ADHK)',
                'group' => 'PDRB Per Kapita Menurut Lapangan Usaha',
                'group_order' => 5,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
                'calc' => 'perkapita',
                'show_sub' => false,
            ],
            [
                'key' => 'perkapita_peng_berlaku',
                'label' => 'PDRB Per Kapita Pengeluaran',
                'label_short' => 'Berlaku (ADHB)',
                'group' => 'PDRB Per Kapita Menurut Pengeluaran',
                'group_order' => 11,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'pengeluaran',
                'calc' => 'perkapita',
                'show_sub' => false,
            ],
            [
                'key' => 'perkapita_peng_konstan',
                'label' => 'PDRB Per Kapita Pengeluaran (Konstan)',
                'label_short' => 'Konstan (ADHK)',
                'group' => 'PDRB Per Kapita Menurut Pengeluaran',
                'group_order' => 11,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
                'calc' => 'perkapita',
                'show_sub' => false,
            ],
            [
                'key' => 'kabkota_berlaku',
                'label' => 'PDRB Kabupaten/Kota Menurut Lapangan Usaha (Berlaku)',
                'label_short' => 'Berlaku (ADHB)',
                'group' => 'PDRB Per Kabupaten/Kota Menurut Lapangan Usaha',
                'group_order' => 6,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'lapangan_usaha',
            ],
            [
                'key' => 'kabkota_konstan',
                'label' => 'PDRB Kabupaten/Kota Menurut Lapangan Usaha (Konstan)',
                'label_short' => 'Konstan (ADHK)',
                'group' => 'PDRB Per Kabupaten/Kota Menurut Lapangan Usaha',
                'group_order' => 6,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'lapangan_usaha',
            ],
            [
                'key' => 'kabkota_peng_berlaku',
                'label' => 'PDRB Kabupaten/Kota Menurut Pengeluaran (Berlaku)',
                'label_short' => 'Berlaku (ADHB)',
                'group' => 'PDRB Kabupaten/Kota Menurut Pengeluaran',
                'group_order' => 12,
                'item_order' => 1,
                'type' => 'berlaku',
                'pendekatan' => 'pengeluaran',
            ],
            [
                'key' => 'kabkota_peng_konstan',
                'label' => 'PDRB Kabupaten/Kota Menurut Pengeluaran (Konstan)',
                'label_short' => 'Konstan (ADHK)',
                'group' => 'PDRB Kabupaten/Kota Menurut Pengeluaran',
                'group_order' => 12,
                'item_order' => 2,
                'type' => 'konstan',
                'pendekatan' => 'pengeluaran',
            ],
        ]);

        $indicatorOptions = $indicatorDefinitions->map(function ($definition) use ($kategori) {
            $kategoriIds = $kategori
                ->where('pendekatan', $definition['pendekatan'])
                ->pluck('id_kategori')
                ->toArray();

            return [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'label_short' => $definition['label_short'] ?? $definition['label'],
                'group' => $definition['group'] ?? 'Lainnya',
                'group_order' => $definition['group_order'] ?? 99,
                'item_order' => $definition['item_order'] ?? 99,
                'type' => $definition['type'],
                'pendekatan' => $definition['pendekatan'],
                'calc' => $definition['calc'] ?? null,
                'column_mode' => $definition['column_mode'] ?? null,
                'row_mode' => $definition['row_mode'] ?? null,
                'fixed_scope_wilayah' => $definition['fixed_scope_wilayah'] ?? null,
                'show_sub' => $definition['show_sub'] ?? true,
                'ids' => $kategoriIds,
            ];
        });

        $focusIndicator = $indicatorOptions->firstWhere('key', $indicatorSelection)
            ?? $indicatorOptions->first();

        $tipePdrb = $focusIndicator['type'] ?? 'berlaku';
        $pendekatan = $focusIndicator['pendekatan'] ?? $pendekatan;
        $calcMode = $focusIndicator['calc'] ?? null;
        $columnMode = $focusIndicator['column_mode'] ?? 'time';
        if (!empty($focusIndicator['fixed_scope_wilayah'])) {
            $scopeWilayah = (int) $focusIndicator['fixed_scope_wilayah'];
        }

        $kategori = $kategori->where('pendekatan', $pendekatan)->values();
        $kategoriIds = $kategori->pluck('id_kategori')->toArray();
        $sub = $sub->whereIn('id_kategori', $kategoriIds)->values();

        $pdrbKategoriIds = $kategori
            ->filter(function ($kat) {
                $kode = strtoupper(trim((string) ($kat->kode_kategori ?? '')));
                $nama = strtolower(trim((string) ($kat->nama_kategori ?? '')));
                if ($kode === 'PDRB') {
                    return true;
                }
                return str_contains($nama, 'produk domestik regional bruto');
            })
            ->pluck('id_kategori')
            ->toArray();
        if (empty($pdrbKategoriIds)) {
            $pdrbKategoriIds = $calcMode === 'distribusi' ? [] : $kategoriIds;
        }

        if (
            $pendekatan === 'lapangan_usaha'
            && in_array($focusIndicator['key'] ?? '', ['kabkota_berlaku', 'kabkota_konstan'], true)
        ) {
            if (in_array(21, $kategoriIds, true)) {
                $pdrbKategoriIds = [21];
            }
        }

        $pdrbPrimaryId = null;
        if (in_array(21, $pdrbKategoriIds, true)) {
            $pdrbPrimaryId = 21;
        } elseif (in_array(22, $pdrbKategoriIds, true)) {
            $pdrbPrimaryId = 22;
        } elseif (!empty($pdrbKategoriIds)) {
            $pdrbPrimaryId = $pdrbKategoriIds[0];
        }
        if ($calcMode === 'perkapita' && !$pdrbPrimaryId) {
            $fallback = $kategori->first(function ($kat) {
                $kode = strtoupper(trim((string) ($kat->kode_kategori ?? '')));
                $nama = strtolower(trim((string) ($kat->nama_kategori ?? '')));
                if ($kode === 'PDRB') {
                    return true;
                }
                return str_contains($nama, 'produk domestik regional bruto');
            });
            if ($fallback) {
                $pdrbPrimaryId = $fallback->id_kategori;
            }
        }
        if ($calcMode === 'perkapita' && ($focusIndicator['pendekatan'] ?? '') === 'pengeluaran') {
            if (in_array(31, $kategoriIds, true)) {
                $pdrbPrimaryId = 31;
            }
        }

        $isKabkotaIndicator = in_array($focusIndicator['key'] ?? '', ['kabkota_berlaku', 'kabkota_konstan', 'kabkota_peng_berlaku', 'kabkota_peng_konstan'], true);
        $rowMode = $focusIndicator['row_mode'] ?? ($isKabkotaIndicator ? 'wilayah' : 'kategori');
        $showSubRows = $focusIndicator['show_sub'] ?? true;

        $allWilayahs = Wilayah::with(['provinsi:id_provinsi,nama_provinsi', 'kabupaten:id_kabupaten,nama_kabupaten'])
            ->orderBy('id_wilayah')
            ->get(['id_wilayah', 'tipe', 'id_provinsi', 'id_kabupaten'])
            ->map(function ($w) {
                if ($w->tipe === 'provinsi' && $w->provinsi) {
                    $w->nama_wilayah = $w->provinsi->nama_provinsi;
                } elseif (($w->tipe === 'kabupaten' || $w->tipe === 'kota') && $w->kabupaten) {
                    $w->nama_wilayah = $w->kabupaten->nama_kabupaten;
                } else {
                    $w->nama_wilayah = 'Wilayah ' . $w->id_wilayah;
                }
                return $w;
            });

        if ($user && in_array($user->role, ['kabupaten', 'kota'], true)) {
            $allWilayahs = $allWilayahs->where('id_wilayah', $user->id_wilayah);
        }

        $scopeWilayahIds = $scopeWilayah ? [(int) $scopeWilayah] : [];
        $selectedWilayah = $scopeWilayah ? $allWilayahs->firstWhere('id_wilayah', (int) $scopeWilayah) : null;
        if ($pendekatan === 'pengeluaran' && $rowMode !== 'sektor_pengeluaran' && $selectedWilayah && $selectedWilayah->tipe === 'provinsi' && $calcMode !== 'perkapita') {
            $pengeluaranKategoriIds = Kategori::where('pendekatan', 'pengeluaran')->pluck('id_kategori')->toArray();
            if (!empty($pengeluaranKategoriIds)) {
                $existingKategoriCount = NilaiKategori::where('id_wilayah', $selectedWilayah->id_wilayah)
                    ->where('tipe_pdrb', $tipePdrb)
                    ->whereIn('id_kategori', $pengeluaranKategoriIds)
                    ->distinct('id_kategori')
                    ->count('id_kategori');

                if ($existingKategoriCount === 0) {
                    $childWilayahIds = Wilayah::where('id_provinsi', $selectedWilayah->id_provinsi)
                        ->whereIn('tipe', ['kabupaten', 'kota'])
                        ->pluck('id_wilayah')
                        ->toArray();
                    if (!empty($childWilayahIds)) {
                        $scopeWilayahIds = $childWilayahIds;
                    }
                }
            }
        }

        $tahunAwal = $request->get('tahun_awal');
        $tahunAkhir = $request->get('tahun_akhir');

        $kabkotaRows = collect();
        $rowItems = collect();
        $columnItems = collect();

        if ($rowMode === 'wilayah') {
            if ($user && in_array($user->role, ['provinsi', 'provinsi_supervisor'], true) && $user->id_wilayah) {
                $kabkotaRows = \App\Models\Kabupaten::join('wilayah', 'wilayah.id_kabupaten', '=', 'kabupaten.id_kabupaten')
                    ->where('kabupaten.id_provinsi', $user->id_wilayah)
                    ->whereIn('kabupaten.tipe', ['kabupaten', 'kota'])
                    ->select('kabupaten.nama_kabupaten', 'wilayah.id_wilayah')
                    ->get()
                    ->map(function ($row) {
                        $row->nama_wilayah = $row->nama_kabupaten;
                        return $row;
                    });
            } elseif ($user && in_array($user->role, ['kabupaten', 'kota'], true) && $user->id_wilayah) {
                $kabkotaRows = Wilayah::with(['kabupaten:id_kabupaten,nama_kabupaten'])
                    ->where('id_wilayah', $user->id_wilayah)
                    ->get(['id_wilayah', 'tipe', 'id_kabupaten'])
                    ->map(function ($w) {
                        $w->nama_wilayah = $w->kabupaten?->nama_kabupaten ?? 'Wilayah ' . $w->id_wilayah;
                        return $w;
                    });
            } else {
                $kabkotaRows = Wilayah::with(['kabupaten:id_kabupaten,nama_kabupaten'])
                    ->whereIn('tipe', ['kabupaten', 'kota'])
                    ->get(['id_wilayah', 'tipe', 'id_kabupaten'])
                    ->map(function ($w) {
                        $w->nama_wilayah = $w->kabupaten?->nama_kabupaten ?? 'Wilayah ' . $w->id_wilayah;
                        return $w;
                    });
            }

            $rowItems = $kabkotaRows->sortBy('id_wilayah')->values();
        }

        if ($columnMode === 'wilayah') {
            if ($user && in_array($user->role, ['provinsi', 'provinsi_supervisor'], true) && $user->id_wilayah) {
                $columnKabkota = \App\Models\Kabupaten::join('wilayah', 'wilayah.id_kabupaten', '=', 'kabupaten.id_kabupaten')
                    ->where('kabupaten.id_provinsi', $user->id_wilayah)
                    ->whereIn('kabupaten.tipe', ['kabupaten', 'kota'])
                    ->select('kabupaten.nama_kabupaten', 'wilayah.id_wilayah')
                    ->get()
                    ->map(function ($row) {
                        $row->nama_wilayah = $row->nama_kabupaten;
                        return $row;
                    });
                $provRow = $allWilayahs->firstWhere('id_wilayah', $user->id_wilayah);
                if ($provRow) {
                    $columnItems = collect([$provRow])->merge($columnKabkota)->values();
                } else {
                    $columnItems = $columnKabkota->values();
                }
            } elseif ($user && in_array($user->role, ['kabupaten', 'kota'], true) && $user->id_wilayah) {
                $columnItems = Wilayah::with(['kabupaten:id_kabupaten,nama_kabupaten'])
                    ->where('id_wilayah', $user->id_wilayah)
                    ->get(['id_wilayah', 'tipe', 'id_kabupaten'])
                    ->map(function ($w) {
                        $w->nama_wilayah = $w->kabupaten?->nama_kabupaten ?? 'Wilayah ' . $w->id_wilayah;
                        return $w;
                    });
            } else {
                $columnItems = Wilayah::with(['kabupaten:id_kabupaten,nama_kabupaten'])
                    ->whereIn('tipe', ['kabupaten', 'kota'])
                    ->get(['id_wilayah', 'tipe', 'id_kabupaten'])
                    ->map(function ($w) {
                        $w->nama_wilayah = $w->kabupaten?->nama_kabupaten ?? 'Wilayah ' . $w->id_wilayah;
                        return $w;
                    });
            }
        }

        if ($rowMode === 'wilayah' && $rowItems->isNotEmpty()) {
            $rowWilayahIds = $rowItems->pluck('id_wilayah')->toArray();
            $availableTahunIds = NilaiKategori::whereIn('id_wilayah', $rowWilayahIds)
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', $tahapData)
                ->whereIn('id_kategori', $pdrbKategoriIds)
                ->distinct()
                ->pluck('id_tahun')
                ->toArray();
            $availablePeriodeIds = NilaiKategori::whereIn('id_wilayah', $rowWilayahIds)
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', $tahapData)
                ->whereIn('id_kategori', $pdrbKategoriIds)
                ->distinct()
                ->pluck('id_periode')
                ->toArray();

            if (!empty($availableTahunIds)) {
                $tahunList = $tahunList->whereIn('id_tahun', $availableTahunIds)->values();
            }
            if (!empty($availablePeriodeIds)) {
                $periodeList = $periodeList->whereIn('id_periode', $availablePeriodeIds)->values();
            }
        } elseif ($columnMode === 'wilayah' && $columnItems->isNotEmpty()) {
            $columnWilayahIds = $columnItems->pluck('id_wilayah')->toArray();
            $availableTahunIds = [];
            $availablePeriodeIds = [];

            $kategoriTahunIds = NilaiKategori::whereIn('id_wilayah', $columnWilayahIds)
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', $tahapData)
                ->whereIn('id_kategori', $kategoriIds)
                ->distinct()
                ->pluck('id_tahun')
                ->toArray();

            $kategoriPeriodeIds = NilaiKategori::whereIn('id_wilayah', $columnWilayahIds)
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', $tahapData)
                ->whereIn('id_kategori', $kategoriIds)
                ->distinct()
                ->pluck('id_periode')
                ->toArray();

            $subIds = $sub->pluck('id_sub_kategori')->toArray();
            $subTahunIds = NilaiSubKategori::whereIn('id_wilayah', $columnWilayahIds)
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', $tahapData)
                ->whereIn('id_sub_kategori', $subIds)
                ->distinct()
                ->pluck('id_tahun')
                ->toArray();

            $subPeriodeIds = NilaiSubKategori::whereIn('id_wilayah', $columnWilayahIds)
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', $tahapData)
                ->whereIn('id_sub_kategori', $subIds)
                ->distinct()
                ->pluck('id_periode')
                ->toArray();

            $availableTahunIds = array_values(array_unique(array_merge($kategoriTahunIds, $subTahunIds)));
            $availablePeriodeIds = array_values(array_unique(array_merge($kategoriPeriodeIds, $subPeriodeIds)));

            if (!empty($availableTahunIds)) {
                $tahunList = $tahunList->whereIn('id_tahun', $availableTahunIds)->values();
            }
            if (!empty($availablePeriodeIds)) {
                $periodeList = $periodeList->whereIn('id_periode', $availablePeriodeIds)->values();
            }
        } elseif (!empty($scopeWilayahIds)) {
            $availableTahunIds = [];
            $availablePeriodeIds = [];

            $kategoriTahunIds = NilaiKategori::whereIn('id_wilayah', $scopeWilayahIds)
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', $tahapData)
                ->whereIn('id_kategori', $kategoriIds)
                ->distinct()
                ->pluck('id_tahun')
                ->toArray();

            $kategoriPeriodeIds = NilaiKategori::whereIn('id_wilayah', $scopeWilayahIds)
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', $tahapData)
                ->whereIn('id_kategori', $kategoriIds)
                ->distinct()
                ->pluck('id_periode')
                ->toArray();

            $subIds = $sub->pluck('id_sub_kategori')->toArray();
            $subTahunIds = NilaiSubKategori::whereIn('id_wilayah', $scopeWilayahIds)
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', $tahapData)
                ->whereIn('id_sub_kategori', $subIds)
                ->distinct()
                ->pluck('id_tahun')
                ->toArray();

            $subPeriodeIds = NilaiSubKategori::whereIn('id_wilayah', $scopeWilayahIds)
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', $tahapData)
                ->whereIn('id_sub_kategori', $subIds)
                ->distinct()
                ->pluck('id_periode')
                ->toArray();

            $availableTahunIds = array_values(array_unique(array_merge($kategoriTahunIds, $subTahunIds)));
            $availablePeriodeIds = array_values(array_unique(array_merge($kategoriPeriodeIds, $subPeriodeIds)));

            if (!empty($availableTahunIds)) {
                $tahunList = $tahunList->whereIn('id_tahun', $availableTahunIds)->values();
            }
            if (!empty($availablePeriodeIds)) {
                $periodeList = $periodeList->whereIn('id_periode', $availablePeriodeIds)->values();
            }
        }

        if ($calcMode === 'perkapita') {
            $pendudukWilayahIds = [];
            if ($columnMode === 'wilayah' && $columnItems->isNotEmpty()) {
                $pendudukWilayahIds = $columnItems->pluck('id_wilayah')->toArray();
            } elseif (!empty($scopeWilayahIds)) {
                $pendudukWilayahIds = $scopeWilayahIds;
            }

            $hasPendudukScope = !empty($pendudukWilayahIds);
            $pendudukYearsQuery = Penduduk::query();
            if ($hasPendudukScope) {
                $pendudukYearsQuery->whereIn('id_wilayah', $pendudukWilayahIds);
            }
            $availablePendudukYears = $pendudukYearsQuery
                ->distinct()
                ->pluck('tahun')
                ->toArray();
            $pendudukTahunIds = $tahunMaster
                ->whereIn('tahun', $availablePendudukYears)
                ->pluck('id_tahun')
                ->toArray();

            $pdrbBaseIds = !empty($pdrbPrimaryId) ? [$pdrbPrimaryId] : $pdrbKategoriIds;
            $availablePdrbYears = [];
            if (!empty($pdrbBaseIds)) {
                $pdrbYearsQuery = NilaiKategori::query()
                    ->where('tipe_pdrb', $tipePdrb)
                    ->whereIn('id_kategori', $pdrbBaseIds);
                if ($hasPendudukScope) {
                    $pdrbYearsQuery->whereIn('id_wilayah', $pendudukWilayahIds);
                }
                $availablePdrbYears = $pdrbYearsQuery
                    ->distinct()
                    ->pluck('id_tahun')
                    ->toArray();
            }

            if (!empty($pendudukTahunIds) && !empty($availablePdrbYears)) {
                $intersectionYears = array_values(array_intersect($pendudukTahunIds, $availablePdrbYears));
                if (!empty($intersectionYears)) {
                    $tahunList = $tahunList->whereIn('id_tahun', $intersectionYears)->values();
                } else {
                    $tahunList = $tahunList->take(0)->values();
                }
            } else {
                $tahunList = $tahunList->take(0)->values();
            }
        }

        $validTahunIds = $tahunList->pluck('id_tahun')->toArray();
        $validPeriodeIds = $periodeList->pluck('id_periode')->toArray();
        $selectedYears = array_values(array_intersect($selectedYears, $validTahunIds));
        $selectedPeriodIds = array_values(array_intersect($selectedPeriodIds, $validPeriodeIds));

        if ($calcMode === 'perkapita') {
            $selectedPeriodIds = [];
            $includeTotal = true;
        }

        $tahunIds = $selectedYears;
        if ($tahunAwal || $tahunAkhir) {
            $tahunIds = $tahunList
                ->filter(function ($tahunModel) use ($tahunAwal, $tahunAkhir) {
                    if ($tahunAwal && $tahunModel->tahun < (int) $tahunAwal) {
                        return false;
                    }
                    if ($tahunAkhir && $tahunModel->tahun > (int) $tahunAkhir) {
                        return false;
                    }
                    return true;
                })
                ->pluck('id_tahun')
                ->toArray();
        } elseif (empty($tahunIds)) {
            $tahunIds = $tahunList->pluck('id_tahun')->toArray();
        }

        $fetchYears = $tahunIds;
        if ($calcMode) {
            $tahunByIdAll = $tahunMaster->keyBy('id_tahun');
            $tahunByValueAll = $tahunMaster->keyBy(fn($t) => (int) $t->tahun);
            foreach ($tahunIds as $tahunId) {
                $tahunValue = $tahunByIdAll->get($tahunId)?->tahun;
                if ($tahunValue === null) {
                    continue;
                }
                $prevId = $tahunByValueAll->get((int) $tahunValue - 1)?->id_tahun;
                if ($prevId && !in_array($prevId, $fetchYears, true)) {
                    $fetchYears[] = $prevId;
                }
            }
        }

        $displayPeriodIds = $selectedPeriodIds;
        $periodeIdsToFetch = $selectedPeriodIds;
        if ($includeTotal) {
            $periodeIds = $periodeList->pluck('id_periode')->toArray();
            $periodeIdsToFetch = $periodeIds;
        } else {
            $periodeIds = $selectedPeriodIds;
            if (empty($periodeIds)) {
                $periodeIds = $periodeList->pluck('id_periode')->toArray();
                $displayPeriodIds = $periodeIds;
                $periodeIdsToFetch = $periodeIds;
            }
        }

        if ($calcMode && !empty($selectedPeriodIds)) {
            $periodOrder = $periodeMaster->pluck('id_periode')->values()->all();
            $periodIndexMap = array_flip($periodOrder);
            $extraPeriodIds = [];

            if ($calcMode === 'q-to-q' || $calcMode === 'laju') {
                foreach ($selectedPeriodIds as $pid) {
                    $index = $periodIndexMap[$pid] ?? null;
                    if ($index === null) {
                        continue;
                    }
                    if ($index > 0) {
                        $extraPeriodIds[] = $periodOrder[$index - 1];
                    } else {
                        $lastPeriod = $periodOrder[count($periodOrder) - 1] ?? null;
                        if ($lastPeriod !== null) {
                            $extraPeriodIds[] = $lastPeriod;
                        }
                    }
                }
            } elseif ($calcMode === 'c-to-c') {
                foreach ($selectedPeriodIds as $pid) {
                    $index = $periodIndexMap[$pid] ?? null;
                    if ($index === null) {
                        continue;
                    }
                    for ($i = 0; $i <= $index; $i++) {
                        $extraPeriodIds[] = $periodOrder[$i];
                    }
                }
            }

            if (!empty($extraPeriodIds)) {
                $periodeIdsToFetch = array_values(array_unique(array_merge($periodeIdsToFetch, $extraPeriodIds)));
            }
        }

        // Untuk laju dan q-to-q, Triwulan I selalu membutuhkan data Triwulan IV (periode terakhir)
        // dari tahun sebelumnya sebagai pembanding. Pastikan semua periode master selalu di-fetch
        // agar data pembanding tersedia — terlepas dari periode mana yang dipilih user.
        // Tanpa ini, di environment hosting Triwulan IV bisa tidak ter-fetch dan Triwulan I selalu tampil '-'.
        if (in_array($calcMode, ['laju', 'q-to-q'], true)) {
            $allMasterPeriodeIds = $periodeMaster->pluck('id_periode')->values()->all();
            $periodeIdsToFetch = array_values(array_unique(array_merge($periodeIdsToFetch, $allMasterPeriodeIds)));

            \Illuminate\Support\Facades\Log::info("=== LAJU IMPLISIT DEBUG === Fetch Config", [
                'calcMode' => $calcMode,
                'fetchYears' => $fetchYears,
                'periodeIdsToFetch' => $periodeIdsToFetch,
                'tahapData' => $tahapData,
                'tipePdrb' => $tipePdrb
            ]);
        }

        $pendudukData = [];
        $pendudukWilayahData = [];
        if ($calcMode === 'perkapita' && !empty($tahunIds)) {
            $tahunIdByValue = $tahunMaster->keyBy('tahun');
            $tahunValues = $tahunList
                ->whereIn('id_tahun', $tahunIds)
                ->pluck('tahun')
                ->toArray();

            if ($columnMode === 'wilayah' && $columnItems->isNotEmpty()) {
                $columnWilayahIds = $columnItems->pluck('id_wilayah')->toArray();
                $pendudukRows = Penduduk::select('id_wilayah', 'tahun', 'jumlah')
                    ->whereIn('id_wilayah', $columnWilayahIds)
                    ->whereIn('tahun', $tahunValues)
                    ->get();
                foreach ($pendudukRows as $row) {
                    $tahunId = $tahunIdByValue->get((int) $row->tahun)?->id_tahun;
                    if (!$tahunId) {
                        continue;
                    }
                    $pendudukWilayahData[$row->id_wilayah][$tahunId] =
                        ($pendudukWilayahData[$row->id_wilayah][$tahunId] ?? 0) + (float) ($row->jumlah ?? 0);
                }
            } elseif (!empty($scopeWilayahIds)) {
                $pendudukRows = Penduduk::select('id_wilayah', 'tahun', 'jumlah')
                    ->whereIn('id_wilayah', $scopeWilayahIds)
                    ->whereIn('tahun', $tahunValues)
                    ->get();
                foreach ($pendudukRows as $row) {
                    $tahunId = $tahunIdByValue->get((int) $row->tahun)?->id_tahun;
                    if (!$tahunId) {
                        continue;
                    }
                    $pendudukData[$tahunId] =
                        ($pendudukData[$tahunId] ?? 0) + (float) ($row->jumlah ?? 0);
                }
            }
        }

        $perkapitaSingleRow = $calcMode === 'perkapita'
            && in_array($focusIndicator['key'] ?? '', ['perkapita', 'perkapita_konstan', 'perkapita_peng_berlaku', 'perkapita_peng_konstan'], true);

        $subjects = $kategori->when($focusIndicator, function ($collection) use ($focusIndicator) {
            return $focusIndicator['ids']
                ? $collection->whereIn('id_kategori', $focusIndicator['ids'])
                : $collection;
        })->values();
        if ($perkapitaSingleRow) {
            if (!empty($pdrbPrimaryId)) {
                $subjects = $kategori->whereIn('id_kategori', [$pdrbPrimaryId])->values();
            }
        }
        $kategoriSelectable = $subjects;

        $subFiltered = collect();
        if ($rowMode !== 'wilayah') {
            $rowItems = $subjects;
            $subFiltered = $sub->whereIn('id_kategori', $subjects->pluck('id_kategori')->toArray())->values();
        }
        if ($calcMode === 'perkapita') {
            $rowMode = 'kategori';
            $rowItems = $subjects;
            $subFiltered = collect();
        }
        if ($rowMode === 'sektor') {
            $rowItems = collect([
                (object) ['id_sektor' => 'primer', 'nama_sektor' => '1. Sektor Primer/Primer Sector', 'kategori_ids' => [1, 2]],
                (object) ['id_sektor' => 'sekunder', 'nama_sektor' => '2. Sektor Sekunder/Secondary Sector', 'kategori_ids' => [3, 5, 6, 7]],
                (object) ['id_sektor' => 'tersier', 'nama_sektor' => '3. Sektor Tersier/Tertiary Sector', 'kategori_ids' => [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]],
            ]);
            $subjects = $kategori->where('pendekatan', 'lapangan_usaha')->values();
        } elseif ($rowMode === 'sektor_pengeluaran') {
            $rowItems = collect([
                (object) ['id_sektor' => 'pkrt', 'nama_sektor' => '1. PKRT', 'kategori_ids' => [23]],
                (object) ['id_sektor' => 'pkp', 'nama_sektor' => '2. PKP', 'kategori_ids' => [25]],
                (object) ['id_sektor' => 'pmtb', 'nama_sektor' => '3. PMTB', 'kategori_ids' => [26]],
                (object) ['id_sektor' => 'lainnya', 'nama_sektor' => '4. Lainnya', 'kategori_ids' => [24, 27, 28, 29, 30]],
            ]);
            $subjects = $kategori->where('pendekatan', 'pengeluaran')->values();
        } elseif ($rowMode === 'sub') {
            $rowItems = $subFiltered;
        }

        if (!empty($selectedComponentIds)) {
            if ($rowMode === 'wilayah') {
                $filtered = $rowItems->whereIn('id_wilayah', $selectedComponentIds)->values();
                if ($filtered->isNotEmpty()) {
                    $rowItems = $filtered;
                } else {
                    $selectedComponentIds = [];
                }
            } elseif ($rowMode === 'sub') {
                $rowItems = $rowItems->whereIn('id_sub_kategori', $selectedComponentIds)->values();
                $subFiltered = $rowItems;
            } elseif ($rowMode === 'sektor' || $rowMode === 'sektor_pengeluaran') {
                $filtered = $rowItems->whereIn('id_sektor', $selectedComponentIds)->values();
                if ($filtered->isNotEmpty()) {
                    $rowItems = $filtered;
                } else {
                    $selectedComponentIds = [];
                }
                $allKatIds = $rowItems->pluck('kategori_ids')->flatten()->toArray();
                $subFiltered = $sub->whereIn('id_kategori', $allKatIds)->values();
            } else {
                $rowItems = $rowItems->whereIn('id_kategori', $selectedComponentIds)->values();
                $subFiltered = $sub->whereIn('id_kategori', $rowItems->pluck('id_kategori')->toArray())->values();
            }
        } elseif ($rowMode === 'sub') {
            $subFiltered = $rowItems;
        }

        if ($calcMode === 'perkapita') {
            $rowMode = 'kategori';
            if ($perkapitaSingleRow) {
                if (empty($pdrbPrimaryId)) {
                    $fallback = $kategori->first(function ($kat) {
                        $kode = strtoupper(trim((string) ($kat->kode_kategori ?? '')));
                        $nama = strtolower(trim((string) ($kat->nama_kategori ?? '')));
                        if ($kode === 'PDRB') {
                            return true;
                        }
                        return str_contains($nama, 'produk domestik regional bruto');
                    });
                    if ($fallback) {
                        $pdrbPrimaryId = $fallback->id_kategori;
                    }
                }
                if (!empty($pdrbPrimaryId)) {
                    $rowItems = $kategori->where('id_kategori', $pdrbPrimaryId)->values();
                } else {
                    $rowItems = $kategori->take(1)->values();
                }
            } else {
                $rowItems = $subjects->values();
            }
            $subFiltered = collect();
        }

        $perkapitaScale = self::PERKAPITA_SCALE;

        $kategoriData = [];
        $subData = [];
        $wilayahData = [];
        $pdrbData = [];
        $kategoriDataBerlaku = [];
        $kategoriDataKonstan = [];
        $subDataBerlaku = [];
        $subDataKonstan = [];
        $kategoriWilayahData = [];
        $subWilayahData = [];

        if (!empty($tahunIds) && $rowItems->isNotEmpty()) {
            if ($rowMode === 'wilayah') {
                $rowWilayahIds = $rowItems->pluck('id_wilayah')->toArray();
                $rowsWilayah = NilaiKategori::select('id_wilayah', 'id_tahun', 'id_periode', DB::raw('SUM(nilai) as total'))
                    ->whereIn('id_wilayah', $rowWilayahIds)
                    ->where('tipe_pdrb', $tipePdrb)
                    ->where('tahap_data', $tahapData)
                    ->whereIn('id_tahun', $fetchYears)
                    ->whereIn('id_periode', $periodeIdsToFetch)
                    ->whereIn('id_kategori', $pdrbKategoriIds)
                    ->groupBy('id_wilayah', 'id_tahun', 'id_periode')
                    ->get();

                foreach ($rowsWilayah as $row) {
                    $wilayahData[$row->id_wilayah][$row->id_tahun][$row->id_periode] = $row->total;
                }
            } elseif ($columnMode === 'wilayah' && $columnItems->isNotEmpty()) {
                $columnWilayahIds = $columnItems->pluck('id_wilayah')->toArray();
                if ($rowMode === 'sub') {
                    $rowKategoriIds = [];
                    $rowSubIds = $rowItems->pluck('id_sub_kategori')->toArray();
                } else {
                    $rowKategoriIds = $rowItems->pluck('id_kategori')->toArray();
                    $rowSubIds = $subFiltered->pluck('id_sub_kategori')->toArray();
                }

                if ($calcMode === 'distribusi' && !empty($pdrbKategoriIds)) {
                    $rowKategoriIds = array_values(array_unique(array_merge($rowKategoriIds, $pdrbKategoriIds)));
                }

                if (!empty($rowKategoriIds)) {
                    if (in_array($calcMode, ['distribusi', 'perkapita'], true)) {
                        $rowsKategori = NilaiKategori::select(
                            'id_nilai_kategori',
                            'id_kategori',
                            'id_wilayah',
                            'id_tahun',
                            'id_periode',
                            'nilai',
                            'tahap_data',
                            'updated_at'
                        )
                            ->whereIn('id_wilayah', $columnWilayahIds)
                            ->where('tipe_pdrb', $tipePdrb)
                            ->whereIn('id_tahun', $fetchYears)
                            ->whereIn('id_periode', $periodeIdsToFetch)
                            ->whereIn('id_kategori', $rowKategoriIds)
                            ->get();
                        $rowsKategori = $this->selectBestRowsByStage($rowsKategori, 'id_kategori', 'id_nilai_kategori');
                    } else {
                        $rowsKategori = NilaiKategori::select('id_kategori', 'id_wilayah', 'id_tahun', 'id_periode', 'nilai')
                            ->whereIn('id_wilayah', $columnWilayahIds)
                            ->where('tipe_pdrb', $tipePdrb)
                            ->where('tahap_data', $tahapData)
                            ->whereIn('id_tahun', $fetchYears)
                            ->whereIn('id_periode', $periodeIdsToFetch)
                            ->whereIn('id_kategori', $rowKategoriIds)
                            ->get();
                    }

                    foreach ($rowsKategori as $row) {
                        $kategoriWilayahData[$row->id_kategori][$row->id_wilayah][$row->id_tahun][$row->id_periode] = $row->nilai;
                    }
                }

                if (!empty($rowSubIds)) {
                    if (in_array($calcMode, ['distribusi', 'perkapita'], true)) {
                        $rowsSub = NilaiSubKategori::select(
                            'id_nilai_sub_kategori',
                            'id_sub_kategori',
                            'id_wilayah',
                            'id_tahun',
                            'id_periode',
                            'nilai',
                            'tahap_data',
                            'updated_at'
                        )
                            ->whereIn('id_wilayah', $columnWilayahIds)
                            ->where('tipe_pdrb', $tipePdrb)
                            ->whereIn('id_tahun', $fetchYears)
                            ->whereIn('id_periode', $periodeIdsToFetch)
                            ->whereIn('id_sub_kategori', $rowSubIds)
                            ->get();
                        $rowsSub = $this->selectBestRowsByStage($rowsSub, 'id_sub_kategori', 'id_nilai_sub_kategori');
                    } else {
                        $rowsSub = NilaiSubKategori::select('id_sub_kategori', 'id_wilayah', 'id_tahun', 'id_periode', 'nilai')
                            ->whereIn('id_wilayah', $columnWilayahIds)
                            ->where('tipe_pdrb', $tipePdrb)
                            ->where('tahap_data', $tahapData)
                            ->whereIn('id_tahun', $fetchYears)
                            ->whereIn('id_periode', $periodeIdsToFetch)
                            ->whereIn('id_sub_kategori', $rowSubIds)
                            ->get();
                    }

                    foreach ($rowsSub as $row) {
                        $subWilayahData[$row->id_sub_kategori][$row->id_wilayah][$row->id_tahun][$row->id_periode] = $row->nilai;
                    }
                }
            } else {
                if (!empty($scopeWilayahIds)) {
                    $isMultiWilayah = count($scopeWilayahIds) > 1;

                    $kategoriFetchIds = [];
                    if ($rowMode === 'sektor' || $rowMode === 'sektor_pengeluaran') {
                        $kategoriFetchIds = $subjects->pluck('id_kategori')->toArray();
                    } elseif ($rowMode !== 'sub') {
                        $kategoriFetchIds = $rowItems->pluck('id_kategori')->toArray();
                    }
                    if ($calcMode === 'distribusi' && !empty($pdrbKategoriIds)) {
                        $kategoriFetchIds = array_values(array_unique(array_merge($kategoriFetchIds, $pdrbKategoriIds)));
                    }

                    if (!empty($kategoriFetchIds)) {
                        if (in_array($calcMode, ['distribusi', 'perkapita'], true)) {
                            $rowsKategori = NilaiKategori::select(
                                'id_nilai_kategori',
                                'id_kategori',
                                'id_tahun',
                                'id_periode',
                                'id_wilayah',
                                'nilai',
                                'tahap_data',
                                'updated_at'
                            )
                                ->whereIn('id_wilayah', $scopeWilayahIds)
                                ->where('tipe_pdrb', $tipePdrb)
                                ->whereIn('id_tahun', $fetchYears)
                                ->whereIn('id_periode', $periodeIdsToFetch)
                                ->whereIn('id_kategori', $kategoriFetchIds)
                                ->get();

                            $rowsKategori = $this->selectBestRowsByStage($rowsKategori, 'id_kategori', 'id_nilai_kategori');
                            $rowsKategori = $this->aggregateRowsAcrossWilayah($rowsKategori, 'id_kategori');

                            foreach ($rowsKategori as $row) {
                                $kategoriData[$row->id_kategori][$row->id_tahun][$row->id_periode] = $row->nilai;
                            }
                        } else {
                            $rowsKategoriQuery = NilaiKategori::select('id_kategori', 'id_tahun', 'id_periode');
                            if ($isMultiWilayah) {
                                $rowsKategoriQuery->selectRaw('SUM(nilai) as nilai');
                            } else {
                                $rowsKategoriQuery->addSelect('nilai');
                            }

                            $rowsKategori = $rowsKategoriQuery
                                ->whereIn('id_wilayah', $scopeWilayahIds)
                                ->where('tipe_pdrb', $tipePdrb)
                                ->where('tahap_data', $tahapData)
                                ->whereIn('id_tahun', $fetchYears)
                                ->whereIn('id_periode', $periodeIdsToFetch)
                                ->whereIn('id_kategori', $kategoriFetchIds)
                                ->when($isMultiWilayah, function ($q) {
                                    return $q->groupBy('id_kategori', 'id_tahun', 'id_periode');
                                })
                                ->get();

                            foreach ($rowsKategori as $row) {
                                $kategoriData[$row->id_kategori][$row->id_tahun][$row->id_periode] = $row->nilai;
                            }
                        }
                    }

                    $subIdsToFetch = $rowMode === 'sub'
                        ? $rowItems->pluck('id_sub_kategori')->toArray()
                        : $subFiltered->pluck('id_sub_kategori')->toArray();

                    if (!empty($subIdsToFetch)) {
                        if (in_array($calcMode, ['distribusi', 'perkapita'], true)) {
                            $rowsSub = NilaiSubKategori::select(
                                'id_nilai_sub_kategori',
                                'id_sub_kategori',
                                'id_tahun',
                                'id_periode',
                                'id_wilayah',
                                'nilai',
                                'tahap_data',
                                'updated_at'
                            )
                                ->whereIn('id_wilayah', $scopeWilayahIds)
                                ->where('tipe_pdrb', $tipePdrb)
                                ->whereIn('id_tahun', $fetchYears)
                                ->whereIn('id_periode', $periodeIdsToFetch)
                                ->whereIn('id_sub_kategori', $subIdsToFetch)
                                ->get();

                            $rowsSub = $this->selectBestRowsByStage($rowsSub, 'id_sub_kategori', 'id_nilai_sub_kategori');
                            $rowsSub = $this->aggregateRowsAcrossWilayah($rowsSub, 'id_sub_kategori');

                            foreach ($rowsSub as $row) {
                                $subData[$row->id_sub_kategori][$row->id_tahun][$row->id_periode] = $row->nilai;
                            }
                        } else {
                            $rowsSubQuery = NilaiSubKategori::select('id_sub_kategori', 'id_tahun', 'id_periode');
                            if ($isMultiWilayah) {
                                $rowsSubQuery->selectRaw('SUM(nilai) as nilai');
                            } else {
                                $rowsSubQuery->addSelect('nilai');
                            }

                            $rowsSub = $rowsSubQuery
                                ->whereIn('id_wilayah', $scopeWilayahIds)
                                ->where('tipe_pdrb', $tipePdrb)
                                ->where('tahap_data', $tahapData)
                                ->whereIn('id_tahun', $fetchYears)
                                ->whereIn('id_periode', $periodeIdsToFetch)
                                ->whereIn('id_sub_kategori', $subIdsToFetch)
                                ->when($isMultiWilayah, function ($q) {
                                    return $q->groupBy('id_sub_kategori', 'id_tahun', 'id_periode');
                                })
                                ->get();

                            foreach ($rowsSub as $row) {
                                $subData[$row->id_sub_kategori][$row->id_tahun][$row->id_periode] = $row->nilai;
                            }
                        }
                    }
                }
            }
        }

        if (in_array($calcMode, ['laju', 'indeks'], true) && !empty($scopeWilayahIds) && $rowMode !== 'wilayah' && !empty($tahunIds)) {
            $rowKategoriIds = ($rowMode === 'sektor' || $rowMode === 'sektor_pengeluaran')
                ? $subjects->pluck('id_kategori')->toArray()
                : $rowItems->pluck('id_kategori')->toArray();
            $rowSubIds = $subFiltered->pluck('id_sub_kategori')->toArray();
            $isMultiWilayah = count($scopeWilayahIds) > 1;

            // Fetch Berlaku with fallback support
            $rowsKategoriBerlaku = NilaiKategori::select('id_kategori', 'id_tahun', 'id_periode', 'id_wilayah', 'nilai', 'tahap_data', 'updated_at', 'id_nilai_kategori')
                ->whereIn('id_wilayah', $scopeWilayahIds)
                ->where('tipe_pdrb', 'berlaku')
                ->whereIn('id_tahun', $fetchYears)
                ->whereIn('id_periode', $periodeIdsToFetch)
                ->whereIn('id_kategori', $rowKategoriIds)
                ->get();

            $bestKategoriBerlaku = $this->selectBestRowsByStage($rowsKategoriBerlaku, 'id_kategori', 'id_nilai_kategori');
            if ($isMultiWilayah) {
                $bestKategoriBerlaku = $this->aggregateRowsAcrossWilayah($bestKategoriBerlaku, 'id_kategori');
            }

            foreach ($bestKategoriBerlaku as $row) {
                $kategoriDataBerlaku[$row->id_kategori][$row->id_tahun][$row->id_periode] = $row->nilai;
            }

            // Fetch Konstan with fallback support
            $rowsKategoriKonstan = NilaiKategori::select('id_kategori', 'id_tahun', 'id_periode', 'id_wilayah', 'nilai', 'tahap_data', 'updated_at', 'id_nilai_kategori')
                ->whereIn('id_wilayah', $scopeWilayahIds)
                ->where('tipe_pdrb', 'konstan')
                ->whereIn('id_tahun', $fetchYears)
                ->whereIn('id_periode', $periodeIdsToFetch)
                ->whereIn('id_kategori', $rowKategoriIds)
                ->get();

            $bestKategoriKonstan = $this->selectBestRowsByStage($rowsKategoriKonstan, 'id_kategori', 'id_nilai_kategori');
            if ($isMultiWilayah) {
                $bestKategoriKonstan = $this->aggregateRowsAcrossWilayah($bestKategoriKonstan, 'id_kategori');
            }

            foreach ($bestKategoriKonstan as $row) {
                $kategoriDataKonstan[$row->id_kategori][$row->id_tahun][$row->id_periode] = $row->nilai;
            }

            if (!empty($rowSubIds)) {
                // Fetch Sub Berlaku with fallback
                $rowsSubBerlaku = NilaiSubKategori::select('id_sub_kategori', 'id_tahun', 'id_periode', 'id_wilayah', 'nilai', 'tahap_data', 'updated_at', 'id_nilai_sub_kategori')
                    ->whereIn('id_wilayah', $scopeWilayahIds)
                    ->where('tipe_pdrb', 'berlaku')
                    ->whereIn('id_tahun', $fetchYears)
                    ->whereIn('id_periode', $periodeIdsToFetch)
                    ->whereIn('id_sub_kategori', $rowSubIds)
                    ->get();

                $bestSubBerlaku = $this->selectBestRowsByStage($rowsSubBerlaku, 'id_sub_kategori', 'id_id_nilai_sub_kategori');
                if ($isMultiWilayah) {
                    $bestSubBerlaku = $this->aggregateRowsAcrossWilayah($bestSubBerlaku, 'id_sub_kategori');
                }

                foreach ($bestSubBerlaku as $row) {
                    $subDataBerlaku[$row->id_sub_kategori][$row->id_tahun][$row->id_periode] = $row->nilai;
                }

                // Fetch Sub Konstan with fallback
                $rowsSubKonstan = NilaiSubKategori::select('id_sub_kategori', 'id_tahun', 'id_periode', 'id_wilayah', 'nilai', 'tahap_data', 'updated_at', 'id_nilai_sub_kategori')
                    ->whereIn('id_wilayah', $scopeWilayahIds)
                    ->where('tipe_pdrb', 'konstan')
                    ->whereIn('id_tahun', $fetchYears)
                    ->whereIn('id_periode', $periodeIdsToFetch)
                    ->whereIn('id_sub_kategori', $rowSubIds)
                    ->get();

                $bestSubKonstan = $this->selectBestRowsByStage($rowsSubKonstan, 'id_sub_kategori', 'id_id_nilai_sub_kategori');
                if ($isMultiWilayah) {
                    $bestSubKonstan = $this->aggregateRowsAcrossWilayah($bestSubKonstan, 'id_sub_kategori');
                }

                foreach ($bestSubKonstan as $row) {
                    $subDataKonstan[$row->id_sub_kategori][$row->id_tahun][$row->id_periode] = $row->nilai;
                }
            }
        }

        $sektorDataBerlaku = [];
        $sektorDataKonstan = [];
        if ($rowMode === 'sektor' || $rowMode === 'sektor_pengeluaran') {
            $subByKategori = $sub->groupBy('id_kategori');
            foreach ($rowItems as $sektor) {
                if (!empty($selectedComponentIds) && !in_array($sektor->id_sektor, $selectedComponentIds)) {
                    continue;
                }
                foreach ($sektor->kategori_ids as $katId) {
                    $catSubs = $subByKategori->get($katId, collect());
                    // Special multiplier for Impor in Pengeluaran
                    $multiplier = ($rowMode === 'sektor_pengeluaran' && $katId == 30) ? -1 : 1;

                    if (count($catSubs) > 0) {
                        foreach ($catSubs as $s) {
                            $sId = $s->id_sub_kategori;
                            $bsData = $subDataBerlaku[$sId] ?? ($tipePdrb === 'berlaku' ? ($subData[$sId] ?? null) : null);
                            $ksData = $subDataKonstan[$sId] ?? ($tipePdrb === 'konstan' ? ($subData[$sId] ?? null) : null);

                            if ($bsData) {
                                foreach ($bsData as $tId => $periods) {
                                    foreach ($periods as $pId => $val) {
                                        $sektorDataBerlaku[$sektor->id_sektor][$tId][$pId] = ($sektorDataBerlaku[$sektor->id_sektor][$tId][$pId] ?? 0) + ((float) $val * $multiplier);
                                    }
                                }
                            }
                            if ($ksData) {
                                foreach ($ksData as $tId => $periods) {
                                    foreach ($periods as $pId => $val) {
                                        $sektorDataKonstan[$sektor->id_sektor][$tId][$pId] = ($sektorDataKonstan[$sektor->id_sektor][$tId][$pId] ?? 0) + ((float) $val * $multiplier);
                                    }
                                }
                            }
                        }
                    } else {
                        $bData = $kategoriDataBerlaku[$katId] ?? ($tipePdrb === 'berlaku' ? ($kategoriData[$katId] ?? null) : null);
                        $kData = $kategoriDataKonstan[$katId] ?? ($tipePdrb === 'konstan' ? ($kategoriData[$katId] ?? null) : null);

                        if ($bData) {
                            foreach ($bData as $tId => $periods) {
                                foreach ($periods as $pId => $val) {
                                    $sektorDataBerlaku[$sektor->id_sektor][$tId][$pId] = ($sektorDataBerlaku[$sektor->id_sektor][$tId][$pId] ?? 0) + ((float) $val * $multiplier);
                                }
                            }
                        }
                        if ($kData) {
                            foreach ($kData as $tId => $periods) {
                                foreach ($periods as $pId => $val) {
                                    $sektorDataKonstan[$sektor->id_sektor][$tId][$pId] = ($sektorDataKonstan[$sektor->id_sektor][$tId][$pId] ?? 0) + ((float) $val * $multiplier);
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($calcMode === 'distribusi' && !empty($tahunIds) && !empty($scopeWilayahIds)) {
            $pdrbBaseIds = $pdrbPrimaryId ? [$pdrbPrimaryId] : $pdrbKategoriIds;
            if (!empty($pdrbBaseIds)) {
                $pdrbRows = NilaiKategori::select(
                    'id_nilai_kategori',
                    'id_kategori',
                    'id_tahun',
                    'id_periode',
                    'id_wilayah',
                    'nilai',
                    'tahap_data',
                    'updated_at'
                )
                    ->whereIn('id_wilayah', $scopeWilayahIds)
                    ->where('tipe_pdrb', $tipePdrb)
                    ->whereIn('id_tahun', $fetchYears)
                    ->whereIn('id_periode', $periodeIdsToFetch)
                    ->whereIn('id_kategori', $pdrbBaseIds)
                    ->get();

                $pdrbRows = $this->selectBestRowsByStage($pdrbRows, 'id_kategori', 'id_nilai_kategori');
                $pdrbRows = $this->aggregateRowsAcrossWilayah($pdrbRows, 'id_kategori');

                foreach ($pdrbRows as $row) {
                    $pdrbData[$row->id_tahun][$row->id_periode] = ($pdrbData[$row->id_tahun][$row->id_periode] ?? 0) + $row->nilai;
                }
            }
        }

        $judulBaris = $request->get('judul_baris', 'nama');

        $tahunTampil = $tahunList->whereIn('id_tahun', $tahunIds)->values();
        $periodeTampil = $periodeList->whereIn('id_periode', $displayPeriodIds)->values();
        $showTotalColumn = $includeTotal;
        $allYearsSelected = $tahunList->isNotEmpty() && count($selectedYears) === $tahunList->count();
        $allComponentsSelected = $rowItems->isNotEmpty() && empty($selectedComponentIds);

        return compact(
            'pendekatan',
            'level',
            'tipePdrb',
            'tahapData',
            'tahunList',
            'periodeList',
            'periodeMaster',
            'kategori',
            'kategoriSelectable',
            'sub',
            'subFiltered',
            'allWilayahs',
            'scopeWilayah',
            'rowItems',
            'kategoriData',
            'subData',
            'wilayahData',
            'kategoriDataBerlaku',
            'kategoriDataKonstan',
            'subDataBerlaku',
            'subDataKonstan',
            'kategoriWilayahData',
            'subWilayahData',
            'sektorDataBerlaku',
            'sektorDataKonstan',
            'pdrbData',
            'pendudukData',
            'pendudukWilayahData',
            'pdrbKategoriIds',
            'perkapitaScale',
            'columnMode',
            'columnItems',
            'judulBaris',
            'tahunAwal',
            'tahunAkhir',
            'tahunTampil',
            'periodeTampil',
            'showTotalColumn',
            'selectedYears',
            'selectedPeriodIds',
            'selectedComponentIds',
            'allYearsSelected',
            'allComponentsSelected',
            'includeTotal',
            'indicatorOptions',
            'indicatorSelection',
            'focusIndicator',
            'rowMode',
            'calcMode',
            'tahunMaster',
            'showSubRows'
        );
    }

    /**
     * Pilih satu row terbaik per (entity+tahun+periode+wilayah) dengan prioritas:
     * rekonsiliasi > awal > null/empty, lalu updated_at terbaru.
     */
    private function selectBestRowsByStage($rows, string $entityField, string $pkField)
    {
        $grouped = collect($rows)->groupBy(function ($row) use ($entityField) {
            return $row->{$entityField} . '|' . $row->id_tahun . '|' . $row->id_periode . '|' . $row->id_wilayah;
        });

        $resolved = [];

        foreach ($grouped as $group) {
            $rek = $this->pickLatestByStage($group, 'rekonsiliasi');
            $awal = $this->pickLatestByStage($group, 'awal');

            if ($rek || $awal) {
                $nilai = $this->resolveRekonValue($rek, $awal);
                $baseRow = $rek ?: $awal;
                if ($baseRow) {
                    $row = clone $baseRow;
                    $row->nilai = $nilai;
                    $resolved[] = $row;
                    continue;
                }
            }

            // Fallback: pilih row terbaik berdasarkan prioritas tahap_data
            $best = null;
            foreach ($group as $row) {
                if ($best === null || $this->isPreferredRow($row, $best, $pkField)) {
                    $best = $row;
                }
            }
            if ($best) {
                $resolved[] = $best;
            }
        }

        return collect($resolved);
    }

    /**
     * Agregasi nilai antar wilayah untuk key (entity+tahun+periode).
     */
    private function aggregateRowsAcrossWilayah($rows, string $entityField)
    {
        $aggregated = [];

        foreach ($rows as $row) {
            $key = $row->{$entityField} . '|' . $row->id_tahun . '|' . $row->id_periode;

            if (!isset($aggregated[$key])) {
                $aggregated[$key] = (object) [
                    $entityField => (int) $row->{$entityField},
                    'id_tahun' => (int) $row->id_tahun,
                    'id_periode' => (int) $row->id_periode,
                    'nilai' => 0.0,
                ];
            }

            $aggregated[$key]->nilai += (float) ($row->nilai ?? 0);
        }

        return collect(array_values($aggregated))
            ->sortBy([
                ['id_tahun', 'asc'],
                ['id_periode', 'asc'],
            ])
            ->values();
    }

    private function isPreferredRow($candidate, $current, string $pkField): bool
    {
        $candidatePriority = $this->tahapDataPriority($candidate);
        $currentPriority = $this->tahapDataPriority($current);

        if ($candidatePriority !== $currentPriority) {
            return $candidatePriority > $currentPriority;
        }

        $candidateUpdatedAt = $candidate->updated_at ? strtotime((string) $candidate->updated_at) : 0;
        $currentUpdatedAt = $current->updated_at ? strtotime((string) $current->updated_at) : 0;

        if ($candidateUpdatedAt !== $currentUpdatedAt) {
            return $candidateUpdatedAt > $currentUpdatedAt;
        }

        return (int) ($candidate->{$pkField} ?? 0) > (int) ($current->{$pkField} ?? 0);
    }

    private function pickLatestByStage($rows, string $stage)
    {
        $filtered = $rows->where('tahap_data', $stage);
        if ($filtered->isEmpty()) {
            return null;
        }
        return $filtered->sortByDesc(function ($row) {
            return $row->updated_at ? strtotime((string) $row->updated_at) : 0;
        })->first();
    }

    private function resolveRekonValue($rek, $awal): float
    {
        $rekVal = $rek->nilai ?? 0;
        $awalVal = $awal->nilai ?? 0;

        if ($awal !== null && (float) $awalVal != 0.0) {
            return (float) $awalVal;
        }

        if ((float) $rekVal != 0.0) {
            return (float) $rekVal;
        }

        return (float) $awalVal;
    }

    private function tahapDataPriority($row): int
    {
        $tahapData = $row->tahap_data ?? null;
        $nilai = (float) ($row->nilai ?? 0);

        if ($tahapData === 'rekonsiliasi') {
            return $nilai != 0.0 ? 3 : 1;
        }

        if ($tahapData === 'awal') {
            return 2;
        }

        if ($tahapData === null || $tahapData === '') {
            return 1;
        }

        return 0;
    }
}





