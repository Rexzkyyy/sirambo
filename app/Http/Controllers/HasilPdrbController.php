<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use App\Models\{
    Kategori,
    SubKategori,
    NilaiSubKategori,
    NilaiKategori,
    Tahun,
    Wilayah,
    Periode
};

class HasilPdrbController extends Controller
{
    // Konstanta
    const ID_TOTAL_PDRB = 21;

    public function __construct()
    {
        // Tidak ada helper functions yang dibutuhkan untuk ADHB/ADHK
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $pendekatan = $request->get('jenis', $request->get('pendekatan', 'lapangan_usaha'));
        if (!in_array($pendekatan, ['lapangan_usaha', 'pengeluaran'])) {
            $pendekatan = 'lapangan_usaha';
        }

        // Ambil data statis dengan caching
        $dataStatis = $this->getDataStatis($pendekatan);
        extract($dataStatis); // $tahun, $periode, $kategori, $sub, $allWilayahs

        // Hapus periode "Tahunan" dari loop agar tidak muncul sebagai kolom
        $periode = $periode->filter(function($p) {
            return strtolower($p->nama_periode) !== 'tahunan';
        })->values();

        // Ambil parameter filter
        $selectedTahun = $request->id_tahun;
        $selectedPeriodeRaw = $request->id_periode;
        $scopeWilayah = $request->scope_wilayah;
        $typePdrb = $request->tipe_pdrb ?: 'berlaku';
        $rentangTahun = $request->rentang_tahun;
        $totalSemuaKategoriTahunan = []; // Default kosong

        // Filter wilayah based on role
        if ($user && in_array($user->role, ['kabupaten', 'kota'])) {
            $allWilayahs = $allWilayahs->where('id_wilayah', $user->id_wilayah);
            $scopeWilayah = $user->id_wilayah;
        }

        // Tentukan wilayah
        $wilayahData = $this->getWilayahData($allWilayahs, $scopeWilayah, $user);
        extract($wilayahData); // $wilayahIds, $selectedWilayahs
        $wilayahIds = $this->resolveWilayahIdsForPengeluaran($wilayahIds, $selectedWilayahs, $pendekatan, $typePdrb);

        $baseTypes = $this->getBaseTypesForFilter($typePdrb);
        $kategoriIds = $kategori->pluck('id_kategori')->toArray();
        $subIds = $sub->pluck('id_sub_kategori')->toArray();
        $yearScope = $this->resolveYearScopeFromRequest($selectedTahun, $rentangTahun, $tahun);

        [$availableYearIds] = $this->getAvailableTahunPeriode($wilayahIds, $kategoriIds, $subIds, $baseTypes);
        [, $availablePeriodeIds] = $this->getAvailableTahunPeriode($wilayahIds, $kategoriIds, $subIds, $baseTypes, $yearScope);

        $tahunFilter = $tahun->whereIn('id_tahun', $availableYearIds)->values();
        if ($tahunFilter->isEmpty()) {
            $tahunFilter = $tahun;
        }

        $periodeFilter = $periode->whereIn('id_periode', $availablePeriodeIds)->values();
        if ($periodeFilter->isEmpty()) {
            $periodeFilter = $periode;
        }

        if ($selectedTahun && !$tahunFilter->contains('id_tahun', (int) $selectedTahun)) {
            $selectedTahun = null;
        }

        $selectedPeriode = $this->normalizeSelectedPeriode($selectedPeriodeRaw, $periodeFilter);
        if (is_numeric($selectedPeriode) && !$periodeFilter->contains('id_periode', (int) $selectedPeriode)) {
            $selectedPeriode = null;
        }

        // Tentukan tampilan dengan penanganan rentang tahun
        $tampilan = $this->getTampilanData($selectedTahun, $selectedPeriode, $rentangTahun, $tahunFilter);
        extract($tampilan); // $showAllTahun, $showAllPeriode, $selectedYears

        // Proses berdasarkan tipe PDRB
        $result = $this->processByTypePdrb(
            $typePdrb,
            $wilayahIds,
            $selectedYears,
            $selectedPeriode,
            $tahun,
            $periode,
            $kategori,
            $sub,
            $showAllTahun,
            $showAllPeriode
        );

        extract($result); // $nilaiKategori, $nilaiSub, $totalTahunanKategori, 
        // $totalTahunanSub, $totalSemuaKategoriTahunan

        // Map kategori_id untuk sub kategori
        $subMap = $sub->keyBy('id_sub_kategori');
        $nilaiSub = $nilaiSub->map(function ($item) use ($subMap) {
            $item->kategori_id = $subMap->get($item->id_sub_kategori)?->id_kategori;
            return $item;
        });

        // Tentukan tahun yang akan ditampilkan
        $tampilTahun = $showAllTahun
            ? $tahun->whereIn('id_tahun', $selectedYears)
            : $tahun->whereIn('id_tahun', $selectedYears);

        // Pre-fetch ADHK data for Comparative types to optimize view performance
        $dataADHKKategoriRaw = collect();
        $dataADHKSubRaw = collect();
        $isComparativeType = in_array($typePdrb, ['y-on-y', 'q-to-q', 'c-to-c']);
        
        if ($isComparativeType && $selectedWilayahs->isNotEmpty()) {
            $wilayah = $selectedWilayahs->first();
            $dataADHKKategoriRaw = \App\Models\NilaiKategori::whereIn('id_tahun', $tampilTahun->pluck('id_tahun'))
                ->where('tipe_pdrb', 'konstan')
                ->where('id_wilayah', $wilayah->id_wilayah)
                ->get();

            $dataADHKSubRaw = \App\Models\NilaiSubKategori::whereIn('id_tahun', $tampilTahun->pluck('id_tahun'))
                ->where('tipe_pdrb', 'konstan')
                ->where('id_wilayah', $wilayah->id_wilayah)
                ->get();
        }

        return view('pdrb.hasil', compact(
            'tahun',
            'periode',
            'kategori',
            'sub',
            'nilaiKategori',
            'nilaiSub',
            'selectedTahun',
            'selectedPeriode',
            'allWilayahs',
            'typePdrb',
            'scopeWilayah',
            'selectedWilayahs',
            'showAllTahun',
            'showAllPeriode',
            'tampilTahun',
            'totalTahunanKategori',
            'totalTahunanSub',
            'totalSemuaKategoriTahunan',
            'rentangTahun',
            'pendekatan',
            'dataADHKKategoriRaw',
            'dataADHKSubRaw'
        ))->with('ID_TOTAL_PDRB', self::ID_TOTAL_PDRB)
            ->with('tahunFilter', $tahunFilter)
            ->with('periodeFilter', $periodeFilter);
    }

    public function export(Request $request)
    {
        $user = auth()->user();

        $pendekatan = $request->get('jenis', $request->get('pendekatan', 'lapangan_usaha'));
        if (!in_array($pendekatan, ['lapangan_usaha', 'pengeluaran'])) {
            $pendekatan = 'lapangan_usaha';
        }

        $dataStatis = $this->getDataStatis($pendekatan);
        extract($dataStatis); // $tahun, $periode, $kategori, $sub, $allWilayahs

        // Hapus periode "Tahunan" dari loop export
        $periode = $periode->filter(function($p) {
            return strtolower($p->nama_periode) !== 'tahunan';
        })->values();

        $selectedTahun = $request->id_tahun;
        $selectedPeriode = $this->normalizeSelectedPeriode($request->id_periode, $periode);
        $scopeWilayah = $request->scope_wilayah;
        $typePdrb = $request->tipe_pdrb ?: 'berlaku';
        $rentangTahun = $request->rentang_tahun;

        // Filter wilayah based on role
        if ($user && in_array($user->role, ['kabupaten', 'kota'])) {
            $allWilayahs = $allWilayahs->where('id_wilayah', $user->id_wilayah);
            $scopeWilayah = $user->id_wilayah;
        }

        $wilayahData = $this->getWilayahData($allWilayahs, $scopeWilayah, $user);
        extract($wilayahData); // $wilayahIds, $selectedWilayahs
        $wilayahIds = $this->resolveWilayahIdsForPengeluaran($wilayahIds, $selectedWilayahs, $pendekatan, $typePdrb);

        $tampilan = $this->getTampilanData($selectedTahun, $selectedPeriode, $rentangTahun, $tahun);
        extract($tampilan); // $showAllTahun, $showAllPeriode, $selectedYears

        $result = $this->processByTypePdrb(
            $typePdrb,
            $wilayahIds,
            $selectedYears,
            $selectedPeriode,
            $tahun,
            $periode,
            $kategori,
            $sub,
            $showAllTahun,
            $showAllPeriode
        );
        extract($result); // $nilaiKategori, $nilaiSub, $totalTahunanKategori, $totalTahunanSub

        $subMap = $sub->keyBy('id_sub_kategori');
        $nilaiSub = $nilaiSub->map(function ($item) use ($subMap) {
            $item->kategori_id = $subMap->get($item->id_sub_kategori)?->id_kategori;
            return $item;
        });

        $tampilTahun = $showAllTahun
            ? $tahun->whereIn('id_tahun', $selectedYears)
            : $tahun->whereIn('id_tahun', $selectedYears);

        // Flags
        $isPercentageType = in_array($typePdrb, ['y-on-y', 'q-to-q', 'c-to-c', 'laju', 'indeks', 'distribusi']);
        $isDistribusiType = ($typePdrb === 'distribusi');
        $isComparativeType = in_array($typePdrb, ['y-on-y', 'q-to-q', 'c-to-c']);
        $isIndeksType = ($typePdrb === 'indeks');
        $isLajuType = ($typePdrb === 'laju');
        $showTotalColumn = $showAllPeriode;

        // Group data
        $groupedKategori = [];
        foreach ($nilaiKategori as $nk) {
            $groupedKategori[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
        }
        $groupedSub = [];
        foreach ($nilaiSub as $ns) {
            $groupedSub[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
        }

        // Total tahunan map (indeks & laju)
        $totalTahunanKategoriMap = [];
        foreach ($totalTahunanKategori as $row) {
            if (isset($row->id_kategori, $row->id_tahun)) {
                $totalTahunanKategoriMap[$row->id_kategori][$row->id_tahun] = $row->nilai;
            }
        }
        $totalTahunanSubMap = [];
        foreach ($totalTahunanSub as $row) {
            if (isset($row->id_sub_kategori, $row->id_tahun)) {
                $totalTahunanSubMap[$row->id_sub_kategori][$row->id_tahun] = $row->nilai;
            }
        }

        $periodeList = $showAllPeriode
            ? $periode->pluck('id_periode')->toArray()
            : [(int) $selectedPeriode];
        $periodIds = $periode->pluck('id_periode')->toArray();

        $selectedPeriodeName = null;
        if (!$showAllPeriode && $selectedPeriode) {
            $selectedPeriodeName = $periode->firstWhere('id_periode', (int) $selectedPeriode)?->nama_periode;
        }

        $formatValue = function ($nilai) {
            return $nilai === null ? '-' : $nilai; // biarkan angka tetap angka
        };


        $getLastPeriodNilai = function ($dataByPeriod, $periodIds) {
            $reversed = array_reverse($periodIds);
            foreach ($reversed as $pid) {
                if (isset($dataByPeriod[$pid]) && $dataByPeriod[$pid] !== null) {
                    return $dataByPeriod[$pid];
                }
            }
            return null;
        };

        // Build spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Hasil PDRB');

        $wilayahNama = $selectedWilayahs->first()?->nama_wilayah ?? 'Semua Wilayah';
        $judul = 'HASIL PDRB - ' . strtoupper(str_replace('_', ' ', $pendekatan));
        $subjudul = 'Wilayah: ' . $wilayahNama .
            ' | Tipe: ' . strtoupper($typePdrb) .
            ' | Periode: ' . ($showAllPeriode ? 'Semua' : ($selectedPeriodeName ?? '-')) .
            ' | Tahun: ' . ($showAllTahun ? 'Rentang' : ($tampilTahun->first()?->tahun ?? '-'));

        $headers = ['Kategori / Sub Kategori'];
        foreach ($tampilTahun as $t) {
            if ($showAllPeriode) {
                foreach ($periode as $p) {
                    $headers[] = $t->tahun . ' ' . $p->nama_periode;
                }
                if ($showTotalColumn) {
                    $headers[] = $t->tahun . ' Total';
                }
            } else {
                $headers[] = $t->tahun . ($selectedPeriodeName ? ' ' . $selectedPeriodeName : '');
            }
        }

        $lastColIndex = count($headers);
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);

        // Title rows
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $judul);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', $subjudul);

        // Header row
        $sheet->fromArray($headers, null, 'A3');

        // Styling
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A2:{$lastCol}2")->getFont()->setSize(10);
        $sheet->getStyle("A1:{$lastCol}2")->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F5597']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
            ]
        ]);

        $sheet->freezePane('B4');

        $row = 4;
        foreach ($kategori as $kat) {
            if ($isDistribusiType && ($kat->id_kategori == 21 || $kat->id_kategori == 22)) {
                continue;
            }

            $rowData = [];
            $rowData[] = $kat->nama_kategori;

            foreach ($tampilTahun as $t) {
                if ($showAllPeriode) {
                    $arrayNilaiPeriode = [];
                    foreach ($periodeList as $pid) {
                        $nilai = $groupedKategori[$kat->id_kategori][$t->id_tahun][$pid] ?? null;
                        $arrayNilaiPeriode[] = $nilai;
                        $rowData[] = $formatValue($nilai);
                    }

                    if ($showTotalColumn) {
                        if ($isComparativeType) {
                            $periodData = $groupedKategori[$kat->id_kategori][$t->id_tahun] ?? [];
                            $totalGrowth = $getLastPeriodNilai($periodData, $periodIds);
                            $rowData[] = $formatValue($totalGrowth);
                        } elseif ($isIndeksType || $isLajuType) {
                            $totalTahunan = $totalTahunanKategoriMap[$kat->id_kategori][$t->id_tahun] ?? null;
                            $rowData[] = $formatValue($totalTahunan);
                        } else {
                            $nilaiTampil = 0;
                            $countValid = 0;
                            foreach ($arrayNilaiPeriode as $v) {
                                if ($v !== null) {
                                    $nilaiTampil += $v;
                                    $countValid++;
                                }
                            }
                            if ($isPercentageType && $countValid > 0) {
                                $nilaiTampil = $nilaiTampil / $countValid;
                            }
                            $rowData[] = $countValid > 0 ? $formatValue($nilaiTampil) : '-';
                        }
                    }
                } else {
                    $pid = $periodeList[0] ?? null;
                    $nilai = $pid ? ($groupedKategori[$kat->id_kategori][$t->id_tahun][$pid] ?? null) : null;
                    $rowData[] = $formatValue($nilai);
                }
            }

            $sheet->fromArray($rowData, null, 'A' . $row);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('DCE6F1');
            $row++;

            foreach ($sub->where('id_kategori', $kat->id_kategori) as $s) {
                $subRow = [];
                $subRow[] = '  - ' . $s->nama_sub_kategori;

                foreach ($tampilTahun as $t) {
                    if ($showAllPeriode) {
                        $arrayNilaiSubPeriode = [];
                        foreach ($periodeList as $pid) {
                            $nilai = $groupedSub[$s->id_sub_kategori][$t->id_tahun][$pid] ?? null;
                            $arrayNilaiSubPeriode[] = $nilai;
                            $subRow[] = $formatValue($nilai);
                        }

                        if ($showTotalColumn) {
                            if ($isComparativeType) {
                                $periodData = $groupedSub[$s->id_sub_kategori][$t->id_tahun] ?? [];
                                $totalGrowth = $getLastPeriodNilai($periodData, $periodIds);
                                $subRow[] = $formatValue($totalGrowth);
                            } elseif ($isIndeksType || $isLajuType) {
                                $totalTahunan = $totalTahunanSubMap[$s->id_sub_kategori][$t->id_tahun] ?? null;
                                $subRow[] = $formatValue($totalTahunan);
                            } else {
                                $nilaiTampil = 0;
                                $countValid = 0;
                                foreach ($arrayNilaiSubPeriode as $v) {
                                    if ($v !== null) {
                                        $nilaiTampil += $v;
                                        $countValid++;
                                    }
                                }
                                if ($isPercentageType && $countValid > 0) {
                                    $nilaiTampil = $nilaiTampil / $countValid;
                                }
                                $subRow[] = $countValid > 0 ? $formatValue($nilaiTampil) : '-';
                            }
                        }
                    } else {
                        $pid = $periodeList[0] ?? null;
                        $nilai = $pid ? ($groupedSub[$s->id_sub_kategori][$t->id_tahun][$pid] ?? null) : null;
                        $subRow[] = $formatValue($nilai);
                    }
                }

                $sheet->fromArray($subRow, null, 'A' . $row);
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF2CC');
                $row++;
            }
        }

        // Borders & alignment for data area
        $lastDataRow = $row - 1;
        if ($lastDataRow >= 4) {
            // ========================
            // Format angka / persentase
            // ========================
            $numberRange = "B4:{$lastCol}{$lastDataRow}";

            if ($isPercentageType) {
                $sheet->getStyle($numberRange)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            } else {
                $sheet->getStyle($numberRange)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            }

            // Borders
            $sheet->getStyle("A4:{$lastCol}{$lastDataRow}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(45);
        for ($i = 2; $i <= $lastColIndex; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setWidth(16);
        }

        // Align data columns to right
        if ($lastColIndex >= 2 && $lastDataRow >= 4) {
            $sheet->getStyle("B4:{$lastCol}{$lastDataRow}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        }

        $filename = 'hasil_pdrb_' . $typePdrb . '_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $filename
        );
    }





    // =================================================================
    // METODE BANTUAN EXISTING
    // =================================================================

    /**
     * Ambil data statis dengan caching yang lebih baik
     */
    private function getDataStatis($pendekatan)
    {
        // Gunakan cache dengan tag untuk invalidation yang lebih baik
        $tahun = Cache::remember('tahun_all_v2', 86400, function () {
            return Tahun::orderBy('tahun')->get(['id_tahun', 'tahun']);
        });

        $periode = Cache::remember('periode_all_v2', 86400, function () {
            return Periode::orderBy('id_periode')->get(['id_periode', 'nama_periode']);
        });

        $kategori = Cache::remember("kategori_all_v2_{$pendekatan}", 86400, function () use ($pendekatan) {
            return Kategori::where('pendekatan', $pendekatan)
                ->get(['id_kategori', 'nama_kategori', 'pendekatan']);
        });
        $kategoriIds = $kategori->pluck('id_kategori')->toArray();

        $sub = Cache::remember("subkategori_all_v2_{$pendekatan}", 86400, function () use ($kategoriIds) {
            if (empty($kategoriIds)) {
                return collect();
            }
            return SubKategori::whereIn('id_kategori', $kategoriIds)
                ->get(['id_sub_kategori', 'nama_sub_kategori', 'id_kategori']);
        });

        $allWilayahs = Cache::remember('wilayah_all_v2', 86400, function () {
            return Wilayah::with(['provinsi:id_provinsi,nama_provinsi', 'kabupaten:id_kabupaten,nama_kabupaten'])
                ->orderBy('id_wilayah')
                ->get(['id_wilayah', 'tipe', 'id_provinsi', 'id_kabupaten'])
                ->map(function ($w) {
                    if ($w->tipe == 'provinsi' && $w->provinsi) {
                        $w->nama_wilayah = $w->provinsi->nama_provinsi;
                    } elseif (($w->tipe == 'kabupaten' || $w->tipe == 'kota') && $w->kabupaten) {
                        $w->nama_wilayah = $w->kabupaten->nama_kabupaten;
                    } else {
                        $w->nama_wilayah = 'Wilayah ' . $w->id_wilayah;
                    }
                    return $w;
                });
        });

        return compact('tahun', 'periode', 'kategori', 'sub', 'allWilayahs');
    }

    /**
     * Tentukan data wilayah
     */
    private function getWilayahData($allWilayahs, $scopeWilayah, $user)
    {
        // Enforce wilayah for kabupaten/kota roles
        if ($user && in_array($user->role, ['kabupaten', 'kota'])) {
            $wilayahId = $user->id_wilayah;
        } else {
            $wilayahId = $scopeWilayah && $scopeWilayah !== 'all'
                ? $scopeWilayah
                : ($user?->id_wilayah ?? null);
        }

        $wilayahIds = [$wilayahId];
        $selectedWilayahs = $allWilayahs->where('id_wilayah', $wilayahId);

        return compact('wilayahIds', 'selectedWilayahs');
    }

    /**
     * Fallback khusus pengeluaran:
     * jika wilayah provinsi dipilih tetapi data kategori tidak lengkap,
     * pakai agregasi data kabupaten/kota dalam provinsi tersebut.
     */
    private function resolveWilayahIdsForPengeluaran(array $wilayahIds, $selectedWilayahs, $pendekatan, $typePdrb): array
    {
        if ($pendekatan !== 'pengeluaran' || empty($wilayahIds) || count($wilayahIds) !== 1) {
            return $wilayahIds;
        }

        $selectedWilayah = $selectedWilayahs->first();
        if (!$selectedWilayah || $selectedWilayah->tipe !== 'provinsi') {
            return $wilayahIds;
        }

        $pengeluaranKategoriIds = Cache::remember('pengeluaran_kategori_ids_v1', 86400, function () {
            return Kategori::where('pendekatan', 'pengeluaran')->pluck('id_kategori')->toArray();
        });

        if (empty($pengeluaranKategoriIds)) {
            return $wilayahIds;
        }

        $baseTypes = $this->getBaseTypesForFilter($typePdrb);
        $checkType = $baseTypes[0] ?? 'berlaku';

        $existingKategoriCount = NilaiKategori::where('id_wilayah', $selectedWilayah->id_wilayah)
            ->where('tipe_pdrb', $checkType)
            ->whereIn('id_kategori', $pengeluaranKategoriIds)
            ->distinct('id_kategori')
            ->count('id_kategori');

        if ($existingKategoriCount >= count($pengeluaranKategoriIds)) {
            return $wilayahIds;
        }

        $childWilayahIds = Wilayah::where('id_provinsi', $selectedWilayah->id_provinsi)
            ->whereIn('tipe', ['kabupaten', 'kota'])
            ->pluck('id_wilayah')
            ->toArray();

        return !empty($childWilayahIds) ? $childWilayahIds : $wilayahIds;
    }

    /**
     * Tentukan tampilan data dengan penanganan rentang tahun
     */
    private function getTampilanData($selectedTahun, $selectedPeriode, $rentangTahun, $tahunModel)
    {
        $showAllTahun = false;
        $showAllPeriode = (!$selectedPeriode || $selectedPeriode === 'semua' || $selectedPeriode === '');

        // Tentukan tahun yang dipilih berdasarkan rentang tahun atau tahun tunggal
        $selectedYears = [];

        if ($rentangTahun) {
            // Handle rentang tahun
            $tahunRange = explode('-', $rentangTahun);
            $tahunAwal = intval($tahunRange[0] ?? 0);
            $tahunAkhir = intval($tahunRange[1] ?? 0);

            if ($tahunAwal > 0 && $tahunAkhir > 0 && $tahunAwal <= $tahunAkhir) {
                // Ambil semua tahun dalam rentang
                $selectedYears = $tahunModel
                    ->where('tahun', '>=', $tahunAwal)
                    ->where('tahun', '<=', $tahunAkhir)
                    ->pluck('id_tahun')
                    ->toArray();

                $showAllTahun = true; // Karena menampilkan multiple tahun
            }
        } elseif ($selectedTahun && $selectedTahun !== 'semua' && $selectedTahun !== '') {
            // Handle tahun tunggal
            $selectedYears = [(int) $selectedTahun];
            $showAllTahun = false;
        } else {
            // Jika tidak ada pilihan, ambil semua tahun
            $selectedYears = $tahunModel->pluck('id_tahun')->toArray();
            $showAllTahun = true;
        }

        return compact('showAllTahun', 'showAllPeriode', 'selectedYears');
    }

    /**
     * Normalisasi nilai periode dari request agar selalu berupa id_periode numerik.
     */
    private function normalizeSelectedPeriode($selectedPeriode, $periode)
    {
        if ($selectedPeriode === null || $selectedPeriode === '' || $selectedPeriode === 'semua') {
            return $selectedPeriode;
        }

        if (is_numeric($selectedPeriode)) {
            return (int) $selectedPeriode;
        }

        $normalized = strtolower(trim((string) $selectedPeriode));

        $match = $periode->first(function ($p) use ($normalized) {
            return strtolower(trim((string) $p->nama_periode)) === $normalized;
        });
        if ($match) {
            return (int) $match->id_periode;
        }

        if (preg_match('/\\b(1|2|3|4)\\b/', $normalized, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/\\b(i|ii|iii|iv)\\b/', $normalized, $m)) {
            $romanMap = ['i' => 1, 'ii' => 2, 'iii' => 3, 'iv' => 4];
            return $romanMap[strtolower($m[1])] ?? $selectedPeriode;
        }

        return $selectedPeriode;
    }

    /**
     * Version token untuk cache agar data segera update setelah import
     */
    private function getCacheVersionToken(array $wilayahIds)
    {
        $versions = [];
        foreach ($wilayahIds as $wid) {
            $versions[] = Cache::get("pdrb_cache_version_{$wid}", 1);
        }

        return implode('-', $versions);
    }

    /**
     * Proses berdasarkan tipe PDRB
     */
    private function processByTypePdrb(
        $typePdrb,
        $wilayahIds,
        $selectedYears,
        $selectedPeriode,
        $tahun,
        $periode,
        $kategori,
        $sub,
        $showAllTahun,
        $showAllPeriode
    ) {
        switch ($typePdrb) {
            case 'konstan':
                return $this->processKonstan(
                    $wilayahIds,
                    $selectedYears,
                    $selectedPeriode,
                    $tahun,
                    $periode,
                    $kategori,
                    $sub,
                    $showAllTahun,
                    $showAllPeriode
                );

            case 'y-on-y':
                return $this->processYonY(
                    $wilayahIds,
                    $selectedYears,
                    $selectedPeriode,
                    $tahun,
                    $periode,
                    $kategori,
                    $sub,
                    $showAllTahun,
                    $showAllPeriode
                );

            case 'q-to-q':
                return $this->processQtoQ(
                    $wilayahIds,
                    $selectedYears,
                    $selectedPeriode,
                    $tahun,
                    $periode,
                    $kategori,
                    $sub,
                    $showAllTahun,
                    $showAllPeriode
                );

            case 'c-to-c':
                return $this->processCtoC(
                    $wilayahIds,
                    $selectedYears,
                    $selectedPeriode,
                    $tahun,
                    $periode,
                    $kategori,
                    $sub,
                    $showAllTahun,
                    $showAllPeriode
                );

            case 'indeks':
                return $this->processIndeks(
                    $wilayahIds,
                    $selectedYears,
                    $selectedPeriode,
                    $tahun,
                    $periode,
                    $kategori,
                    $sub,
                    $showAllTahun,
                    $showAllPeriode
                );
            case 'laju':
                // Tambahkan nanti jika perlu
                return $this->processLaju(
                    $wilayahIds,
                    $selectedYears,
                    $selectedPeriode,
                    $tahun,
                    $periode,
                    $kategori,
                    $sub,
                    $showAllTahun,
                    $showAllPeriode
                );

            case 'berlaku':
            default:
                return $this->processBerlaku(
                    $wilayahIds,
                    $selectedYears,
                    $selectedPeriode,
                    $tahun,
                    $periode,
                    $kategori,
                    $sub,
                    $showAllTahun,
                    $showAllPeriode
                );

            case 'distribusi':
                // Tambahkan nanti jika perlu
                return $this->processDistribusi(
                    $wilayahIds,
                    $selectedYears,
                    $selectedPeriode,
                    $tahun,
                    $periode,
                    $kategori,
                    $sub,
                    $showAllTahun,
                    $showAllPeriode
                );
        }
    }

    private function getBaseTypesForFilter(?string $typePdrb): array
    {
        $type = $typePdrb ?: 'berlaku';

        return match ($type) {
            'konstan' => ['konstan'],
            'y-on-y', 'q-to-q', 'c-to-c' => ['konstan'],
            'indeks', 'laju' => ['berlaku', 'konstan'],
            'distribusi' => ['berlaku'],
            default => ['berlaku'],
        };
    }

    private function resolveYearScopeFromRequest($selectedTahun, $rentangTahun, $tahunModel): array
    {
        if ($rentangTahun) {
            $tahunRange = explode('-', $rentangTahun);
            $tahunAwal = intval($tahunRange[0] ?? 0);
            $tahunAkhir = intval($tahunRange[1] ?? 0);

            if ($tahunAwal > 0 && $tahunAkhir > 0 && $tahunAwal <= $tahunAkhir) {
                return $tahunModel
                    ->where('tahun', '>=', $tahunAwal)
                    ->where('tahun', '<=', $tahunAkhir)
                    ->pluck('id_tahun')
                    ->toArray();
            }
        }

        if ($selectedTahun && is_numeric($selectedTahun)) {
            $exists = $tahunModel->firstWhere('id_tahun', (int) $selectedTahun);
            return $exists ? [(int) $selectedTahun] : [];
        }

        return [];
    }

    private function getAvailableTahunPeriode(
        array $wilayahIds,
        array $kategoriIds,
        array $subIds,
        array $baseTypes,
        ?array $yearScope = null
    ): array {
        $combos = collect();

        $combos = $combos->merge(
            $this->getAvailableCombos('nilai_kategori', 'id_kategori', $kategoriIds, $wilayahIds, $baseTypes, $yearScope)
        );

        if (!empty($subIds)) {
            $combos = $combos->merge(
                $this->getAvailableCombos('nilai_sub_kategori', 'id_sub_kategori', $subIds, $wilayahIds, $baseTypes, $yearScope)
            );
        }

        if ($combos->isEmpty()) {
            return [collect(), collect()];
        }

        $unique = $combos->unique(fn($row) => $row->id_tahun . '|' . $row->id_periode)->values();
        $tahunIds = $unique->pluck('id_tahun')->unique()->sort()->values();
        $periodeIds = $unique->pluck('id_periode')->unique()->sort()->values();

        return [$tahunIds, $periodeIds];
    }

    private function getAvailableCombos(
        string $table,
        string $entityField,
        array $entityIds,
        array $wilayahIds,
        array $baseTypes,
        ?array $yearScope = null
    ) {
        if (empty($entityIds) || empty($wilayahIds) || empty($baseTypes)) {
            return collect();
        }

        $query = DB::table($table)
            ->select('id_tahun', 'id_periode')
            ->whereIn('id_wilayah', $wilayahIds)
            ->whereIn('tipe_pdrb', $baseTypes)
            ->whereIn($entityField, $entityIds)
            ->where('id_periode', '>', 0);

        if (!empty($yearScope)) {
            $query->whereIn('id_tahun', $yearScope);
        }

        if (count($baseTypes) > 1) {
            $query->groupBy('id_tahun', 'id_periode')
                ->havingRaw('COUNT(DISTINCT tipe_pdrb) >= ?', [count($baseTypes)]);
        } else {
            $query->distinct();
        }

        return $query->get();
    }

    /**
     * 1. ADHB (BERLAKU) - OPTIMIZED
     */
    private function processBerlaku(
        $wilayahIds,
        $selectedYears,
        $selectedPeriode,
        $tahun,
        $periode,
        $kategori,
        $sub,
        $showAllTahun,
        $showAllPeriode
    ) {
        $cacheVersion = $this->getCacheVersionToken($wilayahIds);
        $cacheKey = 'berlaku_v3_' . $cacheVersion . '_' . implode('_', $wilayahIds) . '_' .
            implode('_', $selectedYears) . '_' .
            ($selectedPeriode ?: 'all') . '_' .
            ($showAllTahun ? '1' : '0') . '_' .
            ($showAllPeriode ? '1' : '0');

        // Coba ambil dari cache dulu
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $nilaiKategori = $this->getNilaiKategoriOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'berlaku');
        $nilaiSub = $this->getNilaiSubOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'berlaku');

        $subMap = $sub->keyBy('id_sub_kategori');
        $nilaiSub = $nilaiSub->map(function ($item) use ($subMap) {
            $item->kategori_id = $subMap->get($item->id_sub_kategori)?->id_kategori;
            return $item;
        });

        // Hitung total tahunan
        $totalData = $this->calculateTotalTahunanOptimized(
            $nilaiKategori,
            $nilaiSub,
            $selectedYears,
            $kategori,
            $sub,
            $showAllTahun,
            $showAllPeriode
        );

        $result = array_merge([
            'nilaiKategori' => $nilaiKategori,
            'nilaiSub' => $nilaiSub,
        ], $totalData);

        // Cache hasil selama 5 menit
        Cache::put($cacheKey, $result, 300);

        return $result;
    }

    /**
     * 2. ADHK (KONSTAN) - OPTIMIZED
     */
    private function processKonstan(
        $wilayahIds,
        $selectedYears,
        $selectedPeriode,
        $tahun,
        $periode,
        $kategori,
        $sub,
        $showAllTahun,
        $showAllPeriode
    ) {
        $cacheVersion = $this->getCacheVersionToken($wilayahIds);
        $cacheKey = 'konstan_v3_' . $cacheVersion . '_' . implode('_', $wilayahIds) . '_' .
            implode('_', $selectedYears) . '_' .
            ($selectedPeriode ?: 'all') . '_' .
            ($showAllTahun ? '1' : '0') . '_' .
            ($showAllPeriode ? '1' : '0');

        // Coba ambil dari cache dulu
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $nilaiKategori = $this->getNilaiKategoriOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'konstan');
        $nilaiSub = $this->getNilaiSubOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'konstan');

        $subMap = $sub->keyBy('id_sub_kategori');
        $nilaiSub = $nilaiSub->map(function ($item) use ($subMap) {
            $item->kategori_id = $subMap->get($item->id_sub_kategori)?->id_kategori;
            return $item;
        });

        // Hitung total tahunan
        $totalData = $this->calculateTotalTahunanOptimized(
            $nilaiKategori,
            $nilaiSub,
            $selectedYears,
            $kategori,
            $sub,
            $showAllTahun,
            $showAllPeriode
        );

        $result = array_merge([
            'nilaiKategori' => $nilaiKategori,
            'nilaiSub' => $nilaiSub,
        ], $totalData);

        // Cache hasil selama 5 menit
        Cache::put($cacheKey, $result, 300);

        return $result;
    }

    /**
     * 3. Y-ON-Y (Year-on-Year Growth) - OPTIMIZED
     */
    private function processYonY(
        $wilayahIds,
        $selectedYears,
        $selectedPeriode,
        $tahun,
        $periode,
        $kategori,
        $sub,
        $showAllTahun,
        $showAllPeriode
    ) {
        $cacheVersion = $this->getCacheVersionToken($wilayahIds);
        $cacheKey = 'yony_' . $cacheVersion . '_' . implode('_', $wilayahIds) . '_' .
            implode('_', $selectedYears) . '_' .
            ($selectedPeriode ?: 'all') . '_' .
            ($showAllTahun ? '1' : '0') . '_' .
            ($showAllPeriode ? '1' : '0');

        // Coba ambil dari cache dulu
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $nilaiKategori = collect();
        $nilaiSub = collect();
        $totalTahunanKategori = collect();
        $totalTahunanSub = collect();

        // **PERUBAHAN: Ambil data ADHK (konstan) untuk Y-on-Y**
        $periodeToFetch = $selectedPeriode ? null : $selectedPeriode;
        $nilaiKategoriRaw = $this->getNilaiKategoriOptimized($wilayahIds, $selectedYears, $periodeToFetch, 'konstan');
        $nilaiSubRaw = $this->getNilaiSubOptimized($wilayahIds, $selectedYears, $periodeToFetch, 'konstan');

        // **DEBUG: Log data yang diambil**
        

        // Buat mapping tahun untuk akses cepat
        $tahunMap = $tahun->keyBy('id_tahun');
        $tahunByYear = $tahun->keyBy('tahun');

        // Kelompokkan data ADHK untuk performa lebih baik
        $kategoriData = [];
        $subData = [];

        // Kelompokkan data kategori ADHK
        foreach ($nilaiKategoriRaw as $nk) {
            if (!isset($kategoriData[$nk->id_kategori])) {
                $kategoriData[$nk->id_kategori] = [];
            }
            if (!isset($kategoriData[$nk->id_kategori][$nk->id_tahun])) {
                $kategoriData[$nk->id_kategori][$nk->id_tahun] = [];
            }
            $kategoriData[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
        }

        // Kelompokkan data sub kategori ADHK
        foreach ($nilaiSubRaw as $ns) {
            if (!isset($subData[$ns->id_sub_kategori])) {
                $subData[$ns->id_sub_kategori] = [];
            }
            if (!isset($subData[$ns->id_sub_kategori][$ns->id_tahun])) {
                $subData[$ns->id_sub_kategori][$ns->id_tahun] = [];
            }
            $subData[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
        }

        // **DEBUG: Log struktur data**
        

        // Tentukan periode yang akan diproses
        $periodeIdsToProcess = (!$selectedPeriode || $selectedPeriode === 'semua' || $selectedPeriode === '')
            ? [1, 2, 3, 4]
            : [(int) $selectedPeriode];

        $getPeriodValue = function (array $data, int $id, int $tahunId, int $periodeId): ?float {
            return $data[$id][$tahunId][$periodeId] ?? null;
        };

        // **PERBAIKAN: Ambil data untuk tahun sebelumnya juga**
        $allYearsToCheck = $selectedYears;
        foreach ($selectedYears as $tahunId) {
            $tahunSekarang = $tahunMap->get($tahunId);
            if ($tahunSekarang) {
                $tahunSebelumnya = $tahunByYear->get($tahunSekarang->tahun - 1);
                if ($tahunSebelumnya && !in_array($tahunSebelumnya->id_tahun, $allYearsToCheck)) {
                    $allYearsToCheck[] = $tahunSebelumnya->id_tahun;
                }
            }
        }

        // **PERBAIKAN: Ambil data ADHK untuk tahun sebelumnya juga**
        if (count($allYearsToCheck) > count($selectedYears)) {
            $additionalKategoriRaw = $this->getNilaiKategoriOptimized($wilayahIds, $allYearsToCheck, null, 'konstan');
            $additionalSubRaw = $this->getNilaiSubOptimized($wilayahIds, $allYearsToCheck, null, 'konstan');

            // Tambahkan data tambahan ke array
            foreach ($additionalKategoriRaw as $nk) {
                if (!isset($kategoriData[$nk->id_kategori])) {
                    $kategoriData[$nk->id_kategori] = [];
                }
                if (!isset($kategoriData[$nk->id_kategori][$nk->id_tahun])) {
                    $kategoriData[$nk->id_kategori][$nk->id_tahun] = [];
                }
                $kategoriData[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
            }

            foreach ($additionalSubRaw as $ns) {
                if (!isset($subData[$ns->id_sub_kategori])) {
                    $subData[$ns->id_sub_kategori] = [];
                }
                if (!isset($subData[$ns->id_sub_kategori][$ns->id_tahun])) {
                    $subData[$ns->id_sub_kategori][$ns->id_tahun] = [];
                }
                $subData[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
            }
        }

        // **PERBAIKAN: Proses untuk setiap tahun yang dipilih**
        // Jangan skip tahun pertama, tapi hanya proses jika ada tahun sebelumnya
        foreach ($selectedYears as $tahunId) {
            $tahunSekarang = $tahunMap->get($tahunId);
            if (!$tahunSekarang)
                continue;

            // Cari tahun sebelumnya
            $tahunSebelumnya = $tahunByYear->get($tahunSekarang->tahun - 1);
            if (!$tahunSebelumnya) {
                // **DEBUG: Tahun sebelumnya tidak ditemukan**
                

                // Tetap tambahkan data null untuk semua periode agar tampil di tabel
                foreach ($periodeIdsToProcess as $periodeId) {
                    foreach ($kategori as $kat) {
                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'yony',
                            'data_valid' => false
                        ]);
                    }

                    foreach ($sub as $s) {
                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'yony',
                            'data_valid' => false
                        ]);
                    }
                }
                continue;
            }

            $tahunSebelumnyaId = $tahunSebelumnya->id_tahun;

            // **DEBUG: Log tahun pembanding**
            

            foreach ($periodeIdsToProcess as $periodeId) {
                // Proses setiap kategori
                foreach ($kategori as $kat) {
                    $nilaiSekarang = $getPeriodValue($kategoriData, $kat->id_kategori, $tahunId, $periodeId);
                    $nilaiSebelum = $getPeriodValue($kategoriData, $kat->id_kategori, $tahunSebelumnyaId, $periodeId);

                    // **DEBUG: Nilai sekarang dan sebelumnya**
                    

                    if ($nilaiSekarang === null || $nilaiSebelum === null || $nilaiSebelum == 0) {
                        // Pembagi nol
                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'yony',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    // **HITUNG Y-ON-Y: (Nilai Sekarang / Nilai Tahun Lalu * 100) - 100**
                    $yony = (($nilaiSekarang / $nilaiSebelum) * 100) - 100;

                    // **DEBUG: Hasil perhitungan**
                    

                    $nilaiKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => $periodeId,
                        'nilai' => round($yony, 2),
                        'tipe_perhitungan' => 'yony',
                        'data_valid' => true
                    ]);
                }

                // Proses setiap sub kategori
                foreach ($sub as $s) {
                    $nilaiSekarang = $getPeriodValue($subData, $s->id_sub_kategori, $tahunId, $periodeId);
                    $nilaiSebelum = $getPeriodValue($subData, $s->id_sub_kategori, $tahunSebelumnyaId, $periodeId);

                    if ($nilaiSekarang === null || $nilaiSebelum === null || $nilaiSebelum == 0) {
                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'yony',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    $yony = (($nilaiSekarang / $nilaiSebelum) * 100) - 100;

                    $nilaiSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => $periodeId,
                        'nilai' => round($yony, 2),
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'yony',
                        'data_valid' => true
                    ]);
                }
            }
        }

        // **DEBUG: Cek hasil akhir**
        

        // Hitung Y-on-Y Tahunan jika semua periode ditampilkan
        if ($showAllPeriode && !$selectedPeriode && count($selectedYears) > 0) {
            $this->calculateYonYTahunanOptimized(
                $kategoriData,
                $subData,
                $selectedYears,
                $kategori,
                $sub,
                $tahun,
                $totalTahunanKategori,
                $totalTahunanSub
            );
        }

        $result = compact('nilaiKategori', 'nilaiSub', 'totalTahunanKategori', 'totalTahunanSub');

        // Cache hasil selama 5 menit
        Cache::put($cacheKey, $result, 300);

        return $result;
    }

    /**
     * Fungsi untuk menghitung Y-on-Y Tahunan
     */
    private function calculateYonYTahunanOptimized(
        $kategoriData,
        $subData,
        $selectedYears,
        $kategori,
        $sub,
        $tahun,
        &$totalTahunanKategori,
        &$totalTahunanSub
    ) {
        // Buat mapping tahun untuk akses cepat
        $tahunMap = $tahun->keyBy('id_tahun');
        $tahunByYear = $tahun->keyBy('tahun');

        // Proses setiap tahun yang dipilih (boleh termasuk tahun pertama jika ada tahun sebelumnya)
        foreach ($selectedYears as $tahunId) {
            $tahunSekarang = $tahunMap->get($tahunId);

            if (!$tahunSekarang)
                continue;

            // Cari tahun sebelumnya
            $tahunSebelumnya = $tahunByYear->get($tahunSekarang->tahun - 1);
            if (!$tahunSebelumnya)
                continue;

            $tahunSebelumnyaId = $tahunSebelumnya->id_tahun;

            // Hitung total tahunan untuk setiap kategori
            foreach ($kategori as $kat) {
                $totalSekarang = 0;
                $totalSebelum = 0;

                // Jumlahkan nilai untuk 4 periode
                for ($periode = 1; $periode <= 4; $periode++) {
                    $totalSekarang += $kategoriData[$kat->id_kategori][$tahunId][$periode] ?? 0;
                    $totalSebelum += $kategoriData[$kat->id_kategori][$tahunSebelumnyaId][$periode] ?? 0;
                }

                if ($totalSebelum != 0) {
                    $yonyTahunan = (($totalSekarang / $totalSebelum) * 100) - 100;

                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => 0, // 0 untuk tahunan
                        'nilai' => round($yonyTahunan, 2),
                        'tipe_perhitungan' => 'yony',
                        'data_valid' => true
                    ]);
                } else {
                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => 0,
                        'nilai' => null,
                        'tipe_perhitungan' => 'yony',
                        'data_valid' => false
                    ]);
                }
            }

            // Hitung total tahunan untuk setiap sub kategori
            foreach ($sub as $s) {
                $totalSekarang = 0;
                $totalSebelum = 0;

                // Jumlahkan nilai untuk 4 periode
                for ($periode = 1; $periode <= 4; $periode++) {
                    $totalSekarang += $subData[$s->id_sub_kategori][$tahunId][$periode] ?? 0;
                    $totalSebelum += $subData[$s->id_sub_kategori][$tahunSebelumnyaId][$periode] ?? 0;
                }

                if ($totalSebelum != 0) {
                    $yonyTahunan = (($totalSekarang / $totalSebelum) * 100) - 100;

                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => 0,
                        'nilai' => round($yonyTahunan, 2),
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'yony',
                        'data_valid' => true
                    ]);
                } else {
                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => 0,
                        'nilai' => null,
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'yony',
                        'data_valid' => false
                    ]);
                }
            }
        }
    }

    /**
     * 4. Q-TO-Q (Quarter-to-Quarter Growth) - OPTIMIZED
     */
    private function processQtoQ(
        $wilayahIds,
        $selectedYears,
        $selectedPeriode,
        $tahun,
        $periode,
        $kategori,
        $sub,
        $showAllTahun,
        $showAllPeriode
    ) {
        $cacheVersion = $this->getCacheVersionToken($wilayahIds);
        $cacheKey = 'qtoq_' . $cacheVersion . '_' . implode('_', $wilayahIds) . '_' .
            implode('_', $selectedYears) . '_' .
            ($selectedPeriode ?: 'all') . '_' .
            ($showAllTahun ? '1' : '0') . '_' .
            ($showAllPeriode ? '1' : '0');

        // Coba ambil dari cache dulu
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $nilaiKategori = collect();
        $nilaiSub = collect();
        $totalTahunanKategori = collect();
        $totalTahunanSub = collect();

        // **PERUBAHAN PENTING: Ambil data ADHK (konstan) untuk Q-to-Q**
        // JANGAN ambil data q-to-q, tapi ambil data ADHK yang akan dihitung
        $nilaiKategoriRaw = $this->getNilaiKategoriOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'konstan');
        $nilaiSubRaw = $this->getNilaiSubOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'konstan');

        // Debug: Cek data ADHK yang diambil
        

        // Buat mapping tahun untuk akses cepat
        $tahunMap = $tahun->keyBy('id_tahun');
        $tahunByYear = $tahun->keyBy('tahun');

        // Kelompokkan data ADHK untuk performa lebih baik
        $kategoriData = [];
        $subData = [];

        // Kelompokkan data kategori ADHK
        foreach ($nilaiKategoriRaw as $nk) {
            if (!isset($kategoriData[$nk->id_kategori])) {
                $kategoriData[$nk->id_kategori] = [];
            }
            if (!isset($kategoriData[$nk->id_kategori][$nk->id_tahun])) {
                $kategoriData[$nk->id_kategori][$nk->id_tahun] = [];
            }
            $kategoriData[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
        }

        // Kelompokkan data sub kategori ADHK
        foreach ($nilaiSubRaw as $ns) {
            if (!isset($subData[$ns->id_sub_kategori])) {
                $subData[$ns->id_sub_kategori] = [];
            }
            if (!isset($subData[$ns->id_sub_kategori][$ns->id_tahun])) {
                $subData[$ns->id_sub_kategori][$ns->id_tahun] = [];
            }
            $subData[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
        }

        // **PERUBAHAN: Untuk debugging, simpan log data yang tersedia**
        

        // Tentukan periode yang akan diproses
        $periodeIdsToProcess = (!$selectedPeriode || $selectedPeriode === 'semua' || $selectedPeriode === '')
            ? [1, 2, 3, 4]
            : [(int) $selectedPeriode];

        $getPeriodValue = function (array $data, int $id, int $tahunId, int $periodeId): ?float {
            return $data[$id][$tahunId][$periodeId] ?? null;
        };

        // **PERBAIKAN: Ambil data untuk tahun sebelumnya juga**
        $allYearsToCheck = $selectedYears;
        foreach ($selectedYears as $tahunId) {
            $tahunSekarang = $tahunMap->get($tahunId);
            if ($tahunSekarang) {
                $tahunSebelumnya = $tahunByYear->get($tahunSekarang->tahun - 1);
                if ($tahunSebelumnya && !in_array($tahunSebelumnya->id_tahun, $allYearsToCheck)) {
                    $allYearsToCheck[] = $tahunSebelumnya->id_tahun;
                }
            }
        }

        // **PERUBAHAN: Ambil data ADHK untuk tahun sebelumnya juga**
        if (count($allYearsToCheck) > count($selectedYears)) {
            $additionalKategoriRaw = $this->getNilaiKategoriOptimized($wilayahIds, $allYearsToCheck, null, 'konstan');
            $additionalSubRaw = $this->getNilaiSubOptimized($wilayahIds, $allYearsToCheck, null, 'konstan');

            // Tambahkan data tambahan ke array
            foreach ($additionalKategoriRaw as $nk) {
                if (!isset($kategoriData[$nk->id_kategori])) {
                    $kategoriData[$nk->id_kategori] = [];
                }
                if (!isset($kategoriData[$nk->id_kategori][$nk->id_tahun])) {
                    $kategoriData[$nk->id_kategori][$nk->id_tahun] = [];
                }
                $kategoriData[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
            }

            foreach ($additionalSubRaw as $ns) {
                if (!isset($subData[$ns->id_sub_kategori])) {
                    $subData[$ns->id_sub_kategori] = [];
                }
                if (!isset($subData[$ns->id_sub_kategori][$ns->id_tahun])) {
                    $subData[$ns->id_sub_kategori][$ns->id_tahun] = [];
                }
                $subData[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
            }
        }

        // Proses untuk setiap tahun yang dipilih
        foreach ($selectedYears as $tahunId) {
            $tahunSekarang = $tahunMap->get($tahunId);
            if (!$tahunSekarang)
                continue;

            foreach ($periodeIdsToProcess as $periodeId) {
                // **LOGIC Q-TO-Q YANG BENAR:**
                // - Q1 bandingkan dengan Q4 tahun sebelumnya
                // - Q2 bandingkan dengan Q1 tahun yang sama
                // - Q3 bandingkan dengan Q2 tahun yang sama
                // - Q4 bandingkan dengan Q3 tahun yang sama

                $tahunPembanding = $tahunId;
                $periodePembanding = null;

                if ($periodeId == 1) {
                    // Q1: bandingkan dengan Q4 tahun sebelumnya
                    $tahunSebelumnya = $tahunByYear->get($tahunSekarang->tahun - 1);
                    if ($tahunSebelumnya) {
                        $tahunPembanding = $tahunSebelumnya->id_tahun;
                        $periodePembanding = 4;
                    } else {
                        // Tahun sebelumnya tidak ada
                        $tahunPembanding = null;
                    }
                } else {
                    // Q2, Q3, Q4: bandingkan dengan periode sebelumnya
                    $tahunPembanding = $tahunId;
                    $periodePembanding = $periodeId - 1;
                }

                // Proses setiap kategori
                foreach ($kategori as $kat) {
                    $nilaiSekarang = $getPeriodValue($kategoriData, $kat->id_kategori, $tahunId, $periodeId);

                    // Cek apakah ada data pembanding
                    if ($tahunPembanding === null || $periodePembanding === null) {
                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'qtoq',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    $nilaiSebelumnya = $getPeriodValue($kategoriData, $kat->id_kategori, $tahunPembanding, $periodePembanding);

                    if ($nilaiSekarang === null || $nilaiSebelumnya === null || $nilaiSebelumnya == 0) {
                        // Data sebelumnya tidak ada atau nol
                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'qtoq',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    // **HITUNG Q-TO-Q: (Nilai Sekarang / Nilai Sebelumnya * 100) - 100**
                    $qtoq = (($nilaiSekarang / $nilaiSebelumnya) * 100) - 100;

                    $nilaiKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => $periodeId,
                        'nilai' => round($qtoq, 2),
                        'tipe_perhitungan' => 'qtoq',
                        'data_valid' => true
                    ]);
                }

                // Proses setiap sub kategori
                foreach ($sub as $s) {
                    $nilaiSekarang = $getPeriodValue($subData, $s->id_sub_kategori, $tahunId, $periodeId);

                    if ($tahunPembanding === null || $periodePembanding === null) {
                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'qtoq',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    $nilaiSebelumnya = $getPeriodValue($subData, $s->id_sub_kategori, $tahunPembanding, $periodePembanding);

                    if ($nilaiSekarang === null || $nilaiSebelumnya === null || $nilaiSebelumnya == 0) {
                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'qtoq',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    $qtoq = (($nilaiSekarang / $nilaiSebelumnya) * 100) - 100;

                    $nilaiSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => $periodeId,
                        'nilai' => round($qtoq, 2),
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'qtoq',
                        'data_valid' => true
                    ]);
                }
            }
        }

        // Hitung Q-to-Q Tahunan jika semua periode ditampilkan
        if ($showAllPeriode && !$selectedPeriode && count($selectedYears) > 0) {
            $this->calculateQtoQTahunanOptimized(
                $kategoriData,
                $subData,
                $selectedYears,
                $kategori,
                $sub,
                $tahun,
                $totalTahunanKategori,
                $totalTahunanSub
            );
        }

        $result = compact('nilaiKategori', 'nilaiSub', 'totalTahunanKategori', 'totalTahunanSub');

        // Cache hasil selama 5 menit
        Cache::put($cacheKey, $result, 300);

        return $result;
    }

    /**
     * Hitung Q-to-Q tahunan dengan optimasi
     */
    private function calculateQtoQTahunanOptimized(
        $kategoriData,
        $subData,
        $tahunList,
        $kategori,
        $sub,
        $tahun,
        &$totalTahunanKategori,
        &$totalTahunanSub
    ) {
        $tahunModelMap = $tahun->keyBy('id_tahun');

        foreach ($tahunList as $tahunId) {
            $tahunSekarang = $tahunModelMap->get($tahunId);
            if (!$tahunSekarang)
                continue;

            // Cek apakah semua 4 periode tersedia untuk tahun ini
            $allPeriodsAvailable = true;
            for ($periodeId = 1; $periodeId <= 4; $periodeId++) {
                $hasData = false;
                foreach ($kategori as $kat) {
                    if (isset($kategoriData[$kat->id_kategori][$tahunId][$periodeId])) {
                        $hasData = true;
                        break;
                    }
                }
                if (!$hasData) {
                    $allPeriodsAvailable = false;
                    break;
                }
            }

            if (!$allPeriodsAvailable) {
                // Jika tidak semua periode tersedia, skip perhitungan tahunan
                continue;
            }

            // Cek apakah data tahun sebelumnya tersedia untuk Q1
            $tahunSebelumnyaModel = $tahun->firstWhere('tahun', $tahunSekarang->tahun - 1);
            if (!$tahunSebelumnyaModel) {
                // Jika tahun sebelumnya tidak ada, skip perhitungan Q-to-Q tahunan
                continue;
            }

            $tahunSebelumnya = $tahunSebelumnyaModel->id_tahun;

            // Cek apakah Q4 tahun sebelumnya tersedia
            $q4SebelumnyaAvailable = false;
            foreach ($kategori as $kat) {
                if (isset($kategoriData[$kat->id_kategori][$tahunSebelumnya][4])) {
                    $q4SebelumnyaAvailable = true;
                    break;
                }
            }

            if (!$q4SebelumnyaAvailable) {
                // Jika Q4 tahun sebelumnya tidak ada, skip perhitungan tahunan
                continue;
            }

            // Hitung total tahunan untuk setiap kategori
            foreach ($kategori as $kat) {
                // Total nilai tahun ini (jumlah semua periode)
                $totalTahunIni = 0;
                for ($periodeId = 1; $periodeId <= 4; $periodeId++) {
                    $totalTahunIni += $kategoriData[$kat->id_kategori][$tahunId][$periodeId] ?? 0;
                }

                // Total nilai tahun sebelumnya
                $totalTahunSebelumnya = 0;
                for ($periodeId = 1; $periodeId <= 4; $periodeId++) {
                    $totalTahunSebelumnya += $kategoriData[$kat->id_kategori][$tahunSebelumnya][$periodeId] ?? 0;
                }

                if ($totalTahunSebelumnya != 0) {
                    $qtoqTahunan = ($totalTahunIni / $totalTahunSebelumnya * 100) - 100;

                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => round($qtoqTahunan, 2),
                        'tipe_perhitungan' => 'qtoq_tahunan',
                        'data_valid' => true
                    ]);
                } else {
                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => null,
                        'tipe_perhitungan' => 'qtoq_tahunan',
                        'data_valid' => false
                    ]);
                }
            }

            // Hitung total tahunan untuk setiap sub kategori
            foreach ($sub as $s) {
                // Total nilai tahun ini (jumlah semua periode)
                $totalTahunIni = 0;
                for ($periodeId = 1; $periodeId <= 4; $periodeId++) {
                    $totalTahunIni += $subData[$s->id_sub_kategori][$tahunId][$periodeId] ?? 0;
                }

                // Total nilai tahun sebelumnya
                $totalTahunSebelumnya = 0;
                for ($periodeId = 1; $periodeId <= 4; $periodeId++) {
                    $totalTahunSebelumnya += $subData[$s->id_sub_kategori][$tahunSebelumnya][$periodeId] ?? 0;
                }

                if ($totalTahunSebelumnya != 0) {
                    $qtoqTahunan = ($totalTahunIni / $totalTahunSebelumnya * 100) - 100;

                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => round($qtoqTahunan, 2),
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'qtoq_tahunan',
                        'data_valid' => true
                    ]);
                } else {
                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => null,
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'qtoq_tahunan',
                        'data_valid' => false
                    ]);
                }
            }
        }
    }
    /**
     * 5. C-TO-C (Contribution-to-Contribution Growth) - OPTIMIZED
     * Rumus: =IF(D86=0;" ";I86/D86*100-100)
     * SAMA PERSIS dengan Y-on-Y, menggunakan nilai ADHK (konstan) langsung
     */
    private function processCtoC(
        $wilayahIds,
        $selectedYears,
        $selectedPeriode,
        $tahun,
        $periode,
        $kategori,
        $sub,
        $showAllTahun,
        $showAllPeriode
    ) {
        $cacheVersion = $this->getCacheVersionToken($wilayahIds);
        $cacheKey = 'ctoc_v2_' . $cacheVersion . '_' . implode('_', $wilayahIds) . '_' .
            implode('_', $selectedYears) . '_' .
            ($selectedPeriode ?: 'all') . '_' .
            ($showAllTahun ? '1' : '0') . '_' .
            ($showAllPeriode ? '1' : '0');

        // Coba ambil dari cache dulu
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $nilaiKategori = collect();
        $nilaiSub = collect();
        $totalTahunanKategori = collect();
        $totalTahunanSub = collect();

        // **PERUBAHAN: Ambil data ADHK (konstan) untuk C-to-C**
        $nilaiKategoriRaw = $this->getNilaiKategoriOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'konstan');
        $nilaiSubRaw = $this->getNilaiSubOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'konstan');

        // **DEBUG: Log data yang diambil**
        

        // Buat mapping tahun untuk akses cepat
        $tahunMap = $tahun->keyBy('id_tahun');
        $tahunByYear = $tahun->keyBy('tahun');

        // Kelompokkan data ADHK untuk performa lebih baik
        $kategoriData = [];
        $subData = [];

        // Kelompokkan data kategori ADHK
        foreach ($nilaiKategoriRaw as $nk) {
            if (!isset($kategoriData[$nk->id_kategori])) {
                $kategoriData[$nk->id_kategori] = [];
            }
            if (!isset($kategoriData[$nk->id_kategori][$nk->id_tahun])) {
                $kategoriData[$nk->id_kategori][$nk->id_tahun] = [];
            }
            $kategoriData[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
        }

        // Kelompokkan data sub kategori ADHK
        foreach ($nilaiSubRaw as $ns) {
            if (!isset($subData[$ns->id_sub_kategori])) {
                $subData[$ns->id_sub_kategori] = [];
            }
            if (!isset($subData[$ns->id_sub_kategori][$ns->id_tahun])) {
                $subData[$ns->id_sub_kategori][$ns->id_tahun] = [];
            }
            $subData[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
        }

        // **DEBUG: Log struktur data**
        

        // Tentukan periode yang akan diproses
        $periodeIdsToProcess = (!$selectedPeriode || $selectedPeriode === 'semua' || $selectedPeriode === '')
            ? [1, 2, 3, 4]
            : [(int) $selectedPeriode];

        $sumUpToPeriod = function (array $data, int $id, int $tahunId, int $periodeId): float {
            $total = 0;
            for ($p = 1; $p <= $periodeId; $p++) {
                $total += $data[$id][$tahunId][$p] ?? 0;
            }
            return $total;
        };

        // **PERBAIKAN: Ambil data untuk tahun sebelumnya juga**
        $allYearsToCheck = $selectedYears;
        foreach ($selectedYears as $tahunId) {
            $tahunSekarang = $tahunMap->get($tahunId);
            if ($tahunSekarang) {
                $tahunSebelumnya = $tahunByYear->get($tahunSekarang->tahun - 1);
                if ($tahunSebelumnya && !in_array($tahunSebelumnya->id_tahun, $allYearsToCheck)) {
                    $allYearsToCheck[] = $tahunSebelumnya->id_tahun;
                }
            }
        }

        // **PERBAIKAN: Ambil data ADHK untuk tahun sebelumnya juga**
        if (count($allYearsToCheck) > count($selectedYears)) {
            $additionalKategoriRaw = $this->getNilaiKategoriOptimized($wilayahIds, $allYearsToCheck, null, 'konstan');
            $additionalSubRaw = $this->getNilaiSubOptimized($wilayahIds, $allYearsToCheck, null, 'konstan');

            // Tambahkan data tambahan ke array
            foreach ($additionalKategoriRaw as $nk) {
                if (!isset($kategoriData[$nk->id_kategori])) {
                    $kategoriData[$nk->id_kategori] = [];
                }
                if (!isset($kategoriData[$nk->id_kategori][$nk->id_tahun])) {
                    $kategoriData[$nk->id_kategori][$nk->id_tahun] = [];
                }
                $kategoriData[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
            }

            foreach ($additionalSubRaw as $ns) {
                if (!isset($subData[$ns->id_sub_kategori])) {
                    $subData[$ns->id_sub_kategori] = [];
                }
                if (!isset($subData[$ns->id_sub_kategori][$ns->id_tahun])) {
                    $subData[$ns->id_sub_kategori][$ns->id_tahun] = [];
                }
                $subData[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
            }
        }

        // **PERBAIKAN: Proses untuk setiap tahun yang dipilih**
        // Jangan skip tahun pertama, tapi hanya proses jika ada tahun sebelumnya
        foreach ($selectedYears as $tahunId) {
            $tahunSekarang = $tahunMap->get($tahunId);
            if (!$tahunSekarang)
                continue;

            // Cari tahun sebelumnya
            $tahunSebelumnya = $tahunByYear->get($tahunSekarang->tahun - 1);
            if (!$tahunSebelumnya) {
                // **DEBUG: Tahun sebelumnya tidak ditemukan**
                

                // Tetap tambahkan data null untuk semua periode agar tampil di tabel
                foreach ($periodeIdsToProcess as $periodeId) {
                    foreach ($kategori as $kat) {
                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'ctoc',
                            'data_valid' => false
                        ]);
                    }

                    foreach ($sub as $s) {
                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'ctoc',
                            'data_valid' => false
                        ]);
                    }
                }
                continue;
            }

            $tahunSebelumnyaId = $tahunSebelumnya->id_tahun;

            // **DEBUG: Log tahun pembanding**
            

            foreach ($periodeIdsToProcess as $periodeId) {
                // Proses setiap kategori
                foreach ($kategori as $kat) {
                    $nilaiSekarang = $sumUpToPeriod($kategoriData, $kat->id_kategori, $tahunId, $periodeId);
                    $nilaiSebelum = $sumUpToPeriod($kategoriData, $kat->id_kategori, $tahunSebelumnyaId, $periodeId);

                    // **DEBUG: Nilai sekarang dan sebelumnya**
                    

                    if ($nilaiSebelum == 0) {
                        // Pembagi nol
                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'ctoc',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    // **HITUNG C-to-C: (Nilai Sekarang / Nilai Tahun Lalu * 100) - 100**
                    $ctoc = (($nilaiSekarang / $nilaiSebelum) * 100) - 100;

                    // **DEBUG: Hasil perhitungan**
                    

                    $nilaiKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => $periodeId,
                        'nilai' => round($ctoc, 2),
                        'tipe_perhitungan' => 'ctoc',
                        'data_valid' => true
                    ]);
                }

                // Proses setiap sub kategori
                foreach ($sub as $s) {
                    $nilaiSekarang = $sumUpToPeriod($subData, $s->id_sub_kategori, $tahunId, $periodeId);
                    $nilaiSebelum = $sumUpToPeriod($subData, $s->id_sub_kategori, $tahunSebelumnyaId, $periodeId);

                    if ($nilaiSebelum == 0) {
                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'ctoc',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    $ctoc = (($nilaiSekarang / $nilaiSebelum) * 100) - 100;

                    $nilaiSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => $periodeId,
                        'nilai' => round($ctoc, 2),
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'ctoc',
                        'data_valid' => true
                    ]);
                }
            }
        }

        // **DEBUG: Cek hasil akhir**
        

        // Hitung C-to-C Tahunan jika semua periode ditampilkan
        if ($showAllPeriode && !$selectedPeriode && count($selectedYears) > 1) {
            $this->calculateCtoCTahunanOptimized(
                $kategoriData,
                $subData,
                $selectedYears,
                $kategori,
                $sub,
                $tahun,
                $totalTahunanKategori,
                $totalTahunanSub
            );
        }

        $result = compact('nilaiKategori', 'nilaiSub', 'totalTahunanKategori', 'totalTahunanSub');

        // Cache hasil selama 5 menit
        Cache::put($cacheKey, $result, 300);

        return $result;
    }

    /**
     * Fungsi untuk menghitung C-to-C Tahunan (SAMA dengan Y-on-Y)
     */
    private function calculateCtoCTahunanOptimized(
        $kategoriData,
        $subData,
        $selectedYears,
        $kategori,
        $sub,
        $tahun,
        &$totalTahunanKategori,
        &$totalTahunanSub
    ) {
        // Buat mapping tahun untuk akses cepat
        $tahunMap = $tahun->keyBy('id_tahun');
        $tahunByYear = $tahun->keyBy('tahun');

        // Proses setiap tahun yang dipilih (kecuali tahun pertama)
        for ($i = 1; $i < count($selectedYears); $i++) {
            $tahunId = $selectedYears[$i];
            $tahunSekarang = $tahunMap->get($tahunId);

            if (!$tahunSekarang)
                continue;

            // Cari tahun sebelumnya
            $tahunSebelumnya = $tahunByYear->get($tahunSekarang->tahun - 1);
            if (!$tahunSebelumnya)
                continue;

            $tahunSebelumnyaId = $tahunSebelumnya->id_tahun;

            // Hitung total tahunan untuk setiap kategori
            foreach ($kategori as $kat) {
                $totalSekarang = 0;
                $totalSebelum = 0;

                // Jumlahkan nilai untuk 4 periode
                for ($periode = 1; $periode <= 4; $periode++) {
                    $totalSekarang += $kategoriData[$kat->id_kategori][$tahunId][$periode] ?? 0;
                    $totalSebelum += $kategoriData[$kat->id_kategori][$tahunSebelumnyaId][$periode] ?? 0;
                }

                if ($totalSebelum != 0) {
                    $ctocTahunan = (($totalSekarang / $totalSebelum) * 100) - 100;

                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => 0, // 0 untuk tahunan
                        'nilai' => round($ctocTahunan, 2),
                        'tipe_perhitungan' => 'ctoc',
                        'data_valid' => true
                    ]);
                } else {
                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => 0,
                        'nilai' => null,
                        'tipe_perhitungan' => 'ctoc',
                        'data_valid' => false
                    ]);
                }
            }

            // Hitung total tahunan untuk setiap sub kategori
            foreach ($sub as $s) {
                $totalSekarang = 0;
                $totalSebelum = 0;

                // Jumlahkan nilai untuk 4 periode
                for ($periode = 1; $periode <= 4; $periode++) {
                    $totalSekarang += $subData[$s->id_sub_kategori][$tahunId][$periode] ?? 0;
                    $totalSebelum += $subData[$s->id_sub_kategori][$tahunSebelumnyaId][$periode] ?? 0;
                }

                if ($totalSebelum != 0) {
                    $ctocTahunan = (($totalSekarang / $totalSebelum) * 100) - 100;

                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => 0,
                        'nilai' => round($ctocTahunan, 2),
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'ctoc',
                        'data_valid' => true
                    ]);
                } else {
                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => 0,
                        'nilai' => null,
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'ctoc',
                        'data_valid' => false
                    ]);
                }
            }
        }
    }

    // =================================================================
    // METODE BANTUAN OPTIMIZED
    // =================================================================

    /**
     * Ambil nilai kategori dengan filter - OPTIMIZED
     */
    private function getNilaiKategoriOptimized($wilayahIds, $selectedYears, $selectedPeriode, $tipePdrb)
    {
        $wilayahIds = array_values(array_unique(array_map('intval', $wilayahIds)));
        sort($wilayahIds);
        $selectedYears = array_values(array_unique(array_map('intval', $selectedYears)));
        sort($selectedYears);

        $cacheVersion = $this->getCacheVersionToken($wilayahIds);
        $cacheKey = 'nilai_kategori_v3_' . $cacheVersion . '_' . implode('_', $wilayahIds) . '_' .
            implode('_', $selectedYears) . '_' .
            ($selectedPeriode ?: 'all') . '_' . $tipePdrb;

        return Cache::remember($cacheKey, 300, function () use ($wilayahIds, $selectedYears, $selectedPeriode, $tipePdrb) {
            $query = NilaiKategori::select(
                'id_nilai_kategori',
                'id_kategori',
                'id_tahun',
                'id_periode',
                'id_wilayah',
                'nilai',
                'tahap_data',
                'updated_at'
            )
                ->whereIn('id_wilayah', $wilayahIds)
                ->where('tipe_pdrb', $tipePdrb);

            if (!empty($selectedYears)) {
                $query->whereIn('id_tahun', $selectedYears);
            }

            if ($selectedPeriode && $selectedPeriode !== 'semua' && $selectedPeriode !== '') {
                $query->where('id_periode', $selectedPeriode);
            }

            $rows = $query->orderBy('id_tahun')
                ->orderBy('id_periode')
                ->get();

            $bestRows = $this->selectBestRowsByStage($rows, 'id_kategori', 'id_nilai_kategori');
            return $this->aggregateRowsAcrossWilayah($bestRows, 'id_kategori');
        });
    }

    /**
     * Ambil nilai sub kategori dengan filter - OPTIMIZED
     */
    private function getNilaiSubOptimized($wilayahIds, $selectedYears, $selectedPeriode, $tipePdrb)
    {
        $wilayahIds = array_values(array_unique(array_map('intval', $wilayahIds)));
        sort($wilayahIds);
        $selectedYears = array_values(array_unique(array_map('intval', $selectedYears)));
        sort($selectedYears);

        $cacheVersion = $this->getCacheVersionToken($wilayahIds);
        $cacheKey = 'nilai_sub_v3_' . $cacheVersion . '_' . implode('_', $wilayahIds) . '_' .
            implode('_', $selectedYears) . '_' .
            ($selectedPeriode ?: 'all') . '_' . $tipePdrb;

        return Cache::remember($cacheKey, 300, function () use ($wilayahIds, $selectedYears, $selectedPeriode, $tipePdrb) {
            $query = NilaiSubKategori::select(
                'id_nilai_sub_kategori',
                'id_sub_kategori',
                'id_tahun',
                'id_periode',
                'id_wilayah',
                'nilai',
                'tahap_data',
                'updated_at'
            )
                ->whereIn('id_wilayah', $wilayahIds)
                ->where('tipe_pdrb', $tipePdrb);

            if (!empty($selectedYears)) {
                $query->whereIn('id_tahun', $selectedYears);
            }

            if ($selectedPeriode && $selectedPeriode !== 'semua' && $selectedPeriode !== '') {
                $query->where('id_periode', $selectedPeriode);
            }

            $rows = $query->orderBy('id_tahun')
                ->orderBy('id_periode')
                ->get();

            $bestRows = $this->selectBestRowsByStage($rows, 'id_sub_kategori', 'id_nilai_sub_kategori');
            return $this->aggregateRowsAcrossWilayah($bestRows, 'id_sub_kategori');
        });
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
            // Jangan prioritaskan rekonsiliasi jika nilainya 0
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

    /**
     * Hitung total tahunan untuk ADHB/ADHK - OPTIMIZED
     */
    private function calculateTotalTahunanOptimized($nilaiKategori, $nilaiSub, $selectedYears, $kategori, $sub, $showAllTahun, $showAllPeriode)
    {
        $totalTahunanKategori = collect();
        $totalTahunanSub = collect();
        $totalSemuaKategoriTahunan = [];

        if ($showAllPeriode) {
            // Pre-group data untuk performa lebih baik
            $groupedKategori = [];
            $groupedSub = [];

            foreach ($nilaiKategori as $nk) {
                $groupedKategori[$nk->id_kategori][$nk->id_tahun][] = $nk->nilai;
            }

            foreach ($nilaiSub as $ns) {
                $groupedSub[$ns->id_sub_kategori][$ns->id_tahun][] = $ns->nilai;
            }

            foreach ($selectedYears as $tahunId) {
                $totalTahun = 0;

                // Hitung total untuk setiap kategori
                foreach ($kategori as $kat) {
                    $nilaiArray = $groupedKategori[$kat->id_kategori][$tahunId] ?? [];
                    $totalKat = array_sum($nilaiArray);

                    $totalTahun += $totalKat;

                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => $totalKat
                    ]);
                }

                // Simpan total semua kategori untuk tahun ini
                $totalSemuaKategoriTahunan[$tahunId] = $totalTahun;

                // Hitung total untuk setiap sub kategori
                foreach ($sub as $s) {
                    $nilaiArray = $groupedSub[$s->id_sub_kategori][$tahunId] ?? [];
                    $totalSub = array_sum($nilaiArray);

                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'nilai' => $totalSub,
                        'id_tahun' => $tahunId,
                        'kategori_id' => $s->id_kategori
                    ]);
                }
            }
        }

        return compact('totalTahunanKategori', 'totalTahunanSub', 'totalSemuaKategoriTahunan');
    }

    /**
     * Hitung Y-on-Y tahunan - OPTIMIZED
     */

    /**
     * Hitung Q-to-Q Total - OPTIMIZED
     */


    /**
     * Hitung C-to-C tahunan - OPTIMIZED (SAMA dengan Y-on-Y)
     */


    /**
     * Ambil daftar periode
     */
    private function getPeriodeList($periode, $showAllPeriode, $selectedPeriode)
    {
        return $showAllPeriode
            ? $periode->pluck('id_periode')->toArray()
            : [(int) $selectedPeriode];
    }

    /**
     * 7. LAJU IMPLISIT - OPTIMIZED
     * Rumus: ((Indeks sekarang - Indeks sebelumnya) / Indeks sebelumnya) * 100%
     * Dimana: Indeks = ADHB / ADHK * 100
     */
    private function processLaju(
        $wilayahIds,
        $selectedYears,
        $selectedPeriode,
        $tahun,
        $periode,
        $kategori,
        $sub,
        $showAllTahun,
        $showAllPeriode
    ) {
        $cacheVersion = $this->getCacheVersionToken($wilayahIds);
        $cacheKey = 'laju_' . $cacheVersion . '_' . implode('_', $wilayahIds) . '_' .
            implode('_', $selectedYears) . '_' .
            ($selectedPeriode ?: 'all') . '_' .
            ($showAllTahun ? '1' : '0') . '_' .
            ($showAllPeriode ? '1' : '0');

        // Coba ambil dari cache dulu
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $nilaiKategori = collect();
        $nilaiSub = collect();
        $totalTahunanKategori = collect();
        $totalTahunanSub = collect();

        // **PERBAIKAN: Tentukan semua tahun yang perlu diambil**
        // Untuk laju, kita perlu data periode sebelumnya juga
        $allYearsToCheck = $selectedYears;
        foreach ($selectedYears as $tahunId) {
            $tahunModel = $tahun->firstWhere('id_tahun', $tahunId);
            if ($tahunModel) {
                // Tambahkan tahun sebelumnya untuk perbandingan
                $tahunSebelumnya = $tahun->firstWhere('tahun', $tahunModel->tahun - 1);
                if ($tahunSebelumnya && !in_array($tahunSebelumnya->id_tahun, $allYearsToCheck)) {
                    $allYearsToCheck[] = $tahunSebelumnya->id_tahun;
                }

                // Jika hanya memilih 1 triwulan (bukan semua triwulan), 
                // kita perlu data triwulan sebelumnya dari tahun yang sama atau tahun sebelumnya
                if ($selectedPeriode) {
                    // Untuk Q1: butuh Q4 tahun sebelumnya
                    if ($selectedPeriode == 1) {
                        $tahunSebelumnya = $tahun->firstWhere('tahun', $tahunModel->tahun - 1);
                        if ($tahunSebelumnya && !in_array($tahunSebelumnya->id_tahun, $allYearsToCheck)) {
                            $allYearsToCheck[] = $tahunSebelumnya->id_tahun;
                        }
                    } else {
                        // Untuk Q2, Q3, Q4: butuh triwulan sebelumnya dari tahun yang sama
                        // Tahun yang sama sudah ada di $allYearsToCheck
                    }
                }
            }
        }

        // **PERBAIKAN: Ambil data ADHB (berlaku) dan ADHK (konstan) untuk semua tahun yang dibutuhkan**
        // Jika memilih periode spesifik, ambil semua periode karena mungkin butuh periode sebelumnya
        $periodeToFetch = $selectedPeriode ? null : $selectedPeriode;

        $nilaiKategoriBerlaku = $this->getNilaiKategoriOptimized($wilayahIds, $allYearsToCheck, $periodeToFetch, 'berlaku');
        $nilaiKategoriKonstan = $this->getNilaiKategoriOptimized($wilayahIds, $allYearsToCheck, $periodeToFetch, 'konstan');

        $nilaiSubBerlaku = $this->getNilaiSubOptimized($wilayahIds, $allYearsToCheck, $periodeToFetch, 'berlaku');
        $nilaiSubKonstan = $this->getNilaiSubOptimized($wilayahIds, $allYearsToCheck, $periodeToFetch, 'konstan');

        // **DEBUG: Log data yang diambil**
        

        // Buat mapping tahun untuk akses cepat
        $tahunMap = $tahun->keyBy('id_tahun');
        $tahunByYear = $tahun->keyBy('tahun');

        // **PERBAIKAN: Tentukan periode yang akan diproses dan ditampilkan**
        $periodeIdsToProcess = $selectedPeriode ? [$selectedPeriode] : [1, 2, 3, 4];
        $periodeIdsToDisplay = $selectedPeriode ? [$selectedPeriode] : [1, 2, 3, 4];

        // Pre-calculate data untuk performa lebih baik
        $kategoriDataBerlaku = [];
        $kategoriDataKonstan = [];
        $subDataBerlaku = [];
        $subDataKonstan = [];

        // Kelompokkan data kategori berdasarkan tahun dan periode
        foreach ($nilaiKategoriBerlaku as $nk) {
            if (!isset($kategoriDataBerlaku[$nk->id_kategori])) {
                $kategoriDataBerlaku[$nk->id_kategori] = [];
            }
            if (!isset($kategoriDataBerlaku[$nk->id_kategori][$nk->id_tahun])) {
                $kategoriDataBerlaku[$nk->id_kategori][$nk->id_tahun] = [];
            }
            $kategoriDataBerlaku[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
        }

        foreach ($nilaiKategoriKonstan as $nk) {
            if (!isset($kategoriDataKonstan[$nk->id_kategori])) {
                $kategoriDataKonstan[$nk->id_kategori] = [];
            }
            if (!isset($kategoriDataKonstan[$nk->id_kategori][$nk->id_tahun])) {
                $kategoriDataKonstan[$nk->id_kategori][$nk->id_tahun] = [];
            }
            $kategoriDataKonstan[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
        }

        // Kelompokkan data sub kategori berdasarkan tahun dan periode
        foreach ($nilaiSubBerlaku as $ns) {
            if (!isset($subDataBerlaku[$ns->id_sub_kategori])) {
                $subDataBerlaku[$ns->id_sub_kategori] = [];
            }
            if (!isset($subDataBerlaku[$ns->id_sub_kategori][$ns->id_tahun])) {
                $subDataBerlaku[$ns->id_sub_kategori][$ns->id_tahun] = [];
            }
            $subDataBerlaku[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
        }

        foreach ($nilaiSubKonstan as $ns) {
            if (!isset($subDataKonstan[$ns->id_sub_kategori])) {
                $subDataKonstan[$ns->id_sub_kategori] = [];
            }
            if (!isset($subDataKonstan[$ns->id_sub_kategori][$ns->id_tahun])) {
                $subDataKonstan[$ns->id_sub_kategori][$ns->id_tahun] = [];
            }
            $subDataKonstan[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
        }

        // **PERBAIKAN: Proses untuk setiap tahun yang dipilih**
        foreach ($selectedYears as $tahunId) {
            $tahunSekarang = $tahunMap->get($tahunId);
            if (!$tahunSekarang)
                continue;

            // **PERBAIKAN: Untuk 1 tahun dan 1 triwulan, kita tetap proses semua triwulan yang dipilih**
            foreach ($periodeIdsToProcess as $periodeId) {
                // **Tentukan periode sebelumnya untuk perhitungan laju Q-to-Q**
                // - Q1 bandingkan dengan Q4 tahun sebelumnya
                // - Q2 bandingkan dengan Q1 tahun yang sama
                // - Q3 bandingkan dengan Q2 tahun yang sama
                // - Q4 bandingkan dengan Q3 tahun yang sama

                $tahunPembanding = $tahunId;
                $periodePembanding = null;

                if ($periodeId == 1) {
                    // Q1: bandingkan dengan Q4 tahun sebelumnya
                    $tahunSebelumnya = $tahunByYear->get($tahunSekarang->tahun - 1);
                    if ($tahunSebelumnya) {
                        $tahunPembanding = $tahunSebelumnya->id_tahun;
                        $periodePembanding = 4;
                    } else {
                        // Tahun sebelumnya tidak ada
                        $tahunPembanding = null;
                        $periodePembanding = null;
                    }
                } else {
                    // Q2, Q3, Q4: bandingkan dengan periode sebelumnya
                    $tahunPembanding = $tahunId;
                    $periodePembanding = $periodeId - 1;
                }

                // **DEBUG: Log perhitungan**
                

                // Proses setiap kategori
                foreach ($kategori as $kat) {
                    // **PERBAIKAN: Cek apakah periode ini harus ditampilkan**
                    // Jika memilih periode spesifik, hanya tampilkan periode tersebut
                    if ($selectedPeriode && $periodeId != $selectedPeriode) {
                        continue;
                    }

                    // Cek apakah ada data pembanding
                    if ($tahunPembanding === null || $periodePembanding === null) {
                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'laju',
                            'data_valid' => false,
                            'reason' => 'no_comparison_data'
                        ]);
                        continue;
                    }

                    // Ambil data sekarang
                    $berlakuSekarang = $kategoriDataBerlaku[$kat->id_kategori][$tahunId][$periodeId] ?? null;
                    $konstanSekarang = $kategoriDataKonstan[$kat->id_kategori][$tahunId][$periodeId] ?? null;

                    // Ambil data sebelumnya
                    $berlakuSebelum = $kategoriDataBerlaku[$kat->id_kategori][$tahunPembanding][$periodePembanding] ?? null;
                    $konstanSebelum = $kategoriDataKonstan[$kat->id_kategori][$tahunPembanding][$periodePembanding] ?? null;

                    // **DEBUG: Nilai data**
                    

                    // Validasi data
                    if (
                        $berlakuSekarang === null || $konstanSekarang === null ||
                        $berlakuSebelum === null || $konstanSebelum === null
                    ) {
                        // Data tidak lengkap
                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'laju',
                            'data_valid' => false,
                            'reason' => 'incomplete_data'
                        ]);
                        continue;
                    }

                    if ($konstanSekarang == 0 || $konstanSebelum == 0) {
                        // Pembagi nol
                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'laju',
                            'data_valid' => false,
                            'reason' => 'zero_division'
                        ]);
                        continue;
                    }

                    // **HITUNG LAJU IMPLISIT:**
                    // 1. Hitung indeks implisit sekarang: (ADHB / ADHK) * 100
                    $indeksSekarang = ($berlakuSekarang / $konstanSekarang) * 100;

                    // 2. Hitung indeks implisit sebelumnya: (ADHB_sebelum / ADHK_sebelum) * 100
                    $indeksSebelum = ($berlakuSebelum / $konstanSebelum) * 100;

                    // 3. Hitung laju: ((Indeks sekarang - Indeks sebelumnya) / Indeks sebelumnya) * 100%
                    $laju = (($indeksSekarang - $indeksSebelum) / $indeksSebelum) * 100;

                    // **DEBUG: Hasil perhitungan**
                    
                    
                    

                    $nilaiKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => $periodeId,
                        'nilai' => round($laju, 2),
                        'tipe_perhitungan' => 'laju',
                        'data_valid' => true
                    ]);
                }

                // Proses setiap sub kategori
                foreach ($sub as $s) {
                    // **PERBAIKAN: Cek apakah periode ini harus ditampilkan**
                    // Jika memilih periode spesifik, hanya tampilkan periode tersebut
                    if ($selectedPeriode && $periodeId != $selectedPeriode) {
                        continue;
                    }

                    // Cek apakah ada data pembanding
                    if ($tahunPembanding === null || $periodePembanding === null) {
                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'laju',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    // Ambil data sekarang
                    $berlakuSekarang = $subDataBerlaku[$s->id_sub_kategori][$tahunId][$periodeId] ?? null;
                    $konstanSekarang = $subDataKonstan[$s->id_sub_kategori][$tahunId][$periodeId] ?? null;

                    // Ambil data sebelumnya
                    $berlakuSebelum = $subDataBerlaku[$s->id_sub_kategori][$tahunPembanding][$periodePembanding] ?? null;
                    $konstanSebelum = $subDataKonstan[$s->id_sub_kategori][$tahunPembanding][$periodePembanding] ?? null;

                    // Validasi data
                    if (
                        $berlakuSekarang === null || $konstanSekarang === null ||
                        $berlakuSebelum === null || $konstanSebelum === null
                    ) {
                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'laju',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    if ($konstanSekarang == 0 || $konstanSebelum == 0) {
                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'laju',
                            'data_valid' => false
                        ]);
                        continue;
                    }

                    // Hitung laju implisit untuk sub kategori
                    $indeksSekarang = ($berlakuSekarang / $konstanSekarang) * 100;
                    $indeksSebelum = ($berlakuSebelum / $konstanSebelum) * 100;
                    $laju = (($indeksSekarang - $indeksSebelum) / $indeksSebelum) * 100;

                    $nilaiSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => $periodeId,
                        'nilai' => round($laju, 2),
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'laju',
                        'data_valid' => true
                    ]);
                }
            }
        }

        // **DEBUG: Cek hasil akhir**
        

        // Hitung Laju Tahunan jika semua periode ditampilkan dan ada lebih dari 1 tahun
        if ($showAllPeriode && !$selectedPeriode && count($selectedYears) > 1) {
            $this->calculateLajuTahunanOptimized(
                $kategoriDataBerlaku,
                $kategoriDataKonstan,
                $subDataBerlaku,
                $subDataKonstan,
                $selectedYears,
                $kategori,
                $sub,
                $tahun,
                $totalTahunanKategori,
                $totalTahunanSub
            );
        }

        $result = compact('nilaiKategori', 'nilaiSub', 'totalTahunanKategori', 'totalTahunanSub');

        // Cache hasil selama 5 menit
        Cache::put($cacheKey, $result, 300);

        return $result;
    }

    /**
     * Fungsi untuk menghitung Laju Tahunan
     */
    private function calculateLajuTahunanOptimized(
        $kategoriDataBerlaku,
        $kategoriDataKonstan,
        $subDataBerlaku,
        $subDataKonstan,
        $selectedYears,
        $kategori,
        $sub,
        $tahun,
        &$totalTahunanKategori,
        &$totalTahunanSub
    ) {
        // Buat mapping tahun untuk akses cepat
        $tahunMap = $tahun->keyBy('id_tahun');
        $tahunByYear = $tahun->keyBy('tahun');

        // Proses setiap tahun yang dipilih (kecuali tahun pertama)
        for ($i = 1; $i < count($selectedYears); $i++) {
            $tahunId = $selectedYears[$i];
            $tahunSekarang = $tahunMap->get($tahunId);

            if (!$tahunSekarang)
                continue;

            // Cari tahun sebelumnya
            $tahunSebelumnya = $tahunByYear->get($tahunSekarang->tahun - 1);
            if (!$tahunSebelumnya)
                continue;

            $tahunSebelumnyaId = $tahunSebelumnya->id_tahun;

            // Hitung total tahunan untuk setiap kategori
            foreach ($kategori as $kat) {
                $totalBerlakuSekarang = 0;
                $totalKonstanSekarang = 0;
                $totalBerlakuSebelum = 0;
                $totalKonstanSebelum = 0;

                // Jumlahkan nilai untuk 4 periode
                for ($periode = 1; $periode <= 4; $periode++) {
                    $totalBerlakuSekarang += $kategoriDataBerlaku[$kat->id_kategori][$tahunId][$periode] ?? 0;
                    $totalKonstanSekarang += $kategoriDataKonstan[$kat->id_kategori][$tahunId][$periode] ?? 0;
                    $totalBerlakuSebelum += $kategoriDataBerlaku[$kat->id_kategori][$tahunSebelumnyaId][$periode] ?? 0;
                    $totalKonstanSebelum += $kategoriDataKonstan[$kat->id_kategori][$tahunSebelumnyaId][$periode] ?? 0;
                }

                // Validasi data
                if ($totalKonstanSekarang == 0 || $totalKonstanSebelum == 0) {
                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => 0,
                        'nilai' => null,
                        'tipe_perhitungan' => 'laju',
                        'data_valid' => false
                    ]);
                    continue;
                }

                // Hitung indeks implisit tahunan
                $indeksSekarang = ($totalBerlakuSekarang / $totalKonstanSekarang) * 100;
                $indeksSebelum = ($totalBerlakuSebelum / $totalKonstanSebelum) * 100;

                // Hitung laju implisit tahunan
                $lajuTahunan = (($indeksSekarang - $indeksSebelum) / $indeksSebelum) * 100;

                $totalTahunanKategori->push((object) [
                    'id_kategori' => $kat->id_kategori,
                    'id_tahun' => $tahunId,
                    'id_periode' => 0,
                    'nilai' => round($lajuTahunan, 2),
                    'tipe_perhitungan' => 'laju',
                    'data_valid' => true
                ]);
            }

            // Hitung total tahunan untuk setiap sub kategori
            foreach ($sub as $s) {
                $totalBerlakuSekarang = 0;
                $totalKonstanSekarang = 0;
                $totalBerlakuSebelum = 0;
                $totalKonstanSebelum = 0;

                // Jumlahkan nilai untuk 4 periode
                for ($periode = 1; $periode <= 4; $periode++) {
                    $totalBerlakuSekarang += $subDataBerlaku[$s->id_sub_kategori][$tahunId][$periode] ?? 0;
                    $totalKonstanSekarang += $subDataKonstan[$s->id_sub_kategori][$tahunId][$periode] ?? 0;
                    $totalBerlakuSebelum += $subDataBerlaku[$s->id_sub_kategori][$tahunSebelumnyaId][$periode] ?? 0;
                    $totalKonstanSebelum += $subDataKonstan[$s->id_sub_kategori][$tahunSebelumnyaId][$periode] ?? 0;
                }

                // Validasi data
                if ($totalKonstanSekarang == 0 || $totalKonstanSebelum == 0) {
                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'id_periode' => 0,
                        'nilai' => null,
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'laju',
                        'data_valid' => false
                    ]);
                    continue;
                }

                // Hitung indeks implisit tahunan
                $indeksSekarang = ($totalBerlakuSekarang / $totalKonstanSekarang) * 100;
                $indeksSebelum = ($totalBerlakuSebelum / $totalKonstanSebelum) * 100;

                // Hitung laju implisit tahunan
                $lajuTahunan = (($indeksSekarang - $indeksSebelum) / $indeksSebelum) * 100;

                $totalTahunanSub->push((object) [
                    'id_sub_kategori' => $s->id_sub_kategori,
                    'id_tahun' => $tahunId,
                    'id_periode' => 0,
                    'nilai' => round($lajuTahunan, 2),
                    'kategori_id' => $s->id_kategori,
                    'tipe_perhitungan' => 'laju',
                    'data_valid' => true
                ]);
            }
        }
    }
    /**
     * Hitung Laju Implisit tahunan - OPTIMIZED
     */

    /**
     * 6. INDEKS IMPLISIT - OPTIMIZED
     * Rumus: =IF(D86=0;" ";I86/D86*100)
     * ADHB (berlaku) / ADHK (konstan) * 100
     * 
     * 
     * 
     * 
     */


    private function processIndeks(
        $wilayahIds,
        $selectedYears,
        $selectedPeriode,
        $tahun,
        $periode,
        $kategori,
        $sub,
        $showAllTahun,
        $showAllPeriode
    ) {
        $cacheVersion = $this->getCacheVersionToken($wilayahIds);
        $cacheKey = 'indeks_' . $cacheVersion . '_' . implode('_', $wilayahIds) . '_' .
            implode('_', $selectedYears) . '_' .
            ($selectedPeriode ?: 'all') . '_' .
            ($showAllTahun ? '1' : '0') . '_' .
            ($showAllPeriode ? '1' : '0');

        // Coba ambil dari cache dulu
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $nilaiKategori = collect();
        $nilaiSub = collect();
        $totalTahunanKategori = collect();
        $totalTahunanSub = collect();
        $totalSemuaKategoriTahunan = [];

        // Ambil data ADHB (berlaku) dan ADHK (konstan)
        $nilaiKategoriBerlaku = $this->getNilaiKategoriOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'berlaku');
        $nilaiKategoriKonstan = $this->getNilaiKategoriOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'konstan');

        $nilaiSubBerlaku = $this->getNilaiSubOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'berlaku');
        $nilaiSubKonstan = $this->getNilaiSubOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'konstan');

        $subMap = $sub->keyBy('id_sub_kategori');

        // Pre-calculate data untuk performa lebih baik
        $kategoriDataBerlaku = [];
        $kategoriDataKonstan = [];
        $subDataBerlaku = [];
        $subDataKonstan = [];

        // Kelompokkan data kategori berdasarkan tahun dan periode
        foreach ($nilaiKategoriBerlaku as $nk) {
            $kategoriDataBerlaku[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
        }

        foreach ($nilaiKategoriKonstan as $nk) {
            $kategoriDataKonstan[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
        }

        // Kelompokkan data sub kategori berdasarkan tahun dan periode
        foreach ($nilaiSubBerlaku as $ns) {
            $subDataBerlaku[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
        }

        foreach ($nilaiSubKonstan as $ns) {
            $subDataKonstan[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
        }

        // Tentukan daftar tahun dan periode
        $tahunList = $selectedYears;
        $periodeList = $this->getPeriodeList($periode, $showAllPeriode, $selectedPeriode);

        foreach ($tahunList as $tahunId) {
            foreach ($periodeList as $periodeId) {
                // Hitung Indeks Implisit untuk setiap kategori
                foreach ($kategori as $kat) {
                    $nilaiBerlaku = $kategoriDataBerlaku[$kat->id_kategori][$tahunId][$periodeId] ?? 0;
                    $nilaiKonstan = $kategoriDataKonstan[$kat->id_kategori][$tahunId][$periodeId] ?? 0;

                    if ($nilaiKonstan != 0) {
                        // Rumus: ADHB / ADHK * 100
                        $indeks = ($nilaiBerlaku / $nilaiKonstan) * 100;

                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => round($indeks, 2),
                            'tipe_perhitungan' => 'indeks',
                            'data_valid' => true
                        ]);
                    } else {
                        $nilaiKategori->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'indeks',
                            'data_valid' => false
                        ]);
                    }
                }

                // Hitung Indeks Implisit untuk setiap sub kategori
                foreach ($sub as $s) {
                    $nilaiBerlaku = $subDataBerlaku[$s->id_sub_kategori][$tahunId][$periodeId] ?? 0;
                    $nilaiKonstan = $subDataKonstan[$s->id_sub_kategori][$tahunId][$periodeId] ?? 0;

                    if ($nilaiKonstan != 0) {
                        $indeks = ($nilaiBerlaku / $nilaiKonstan) * 100;

                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => round($indeks, 2),
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'indeks',
                            'data_valid' => true
                        ]);
                    } else {
                        $nilaiSub->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'indeks',
                            'data_valid' => false
                        ]);
                    }
                }
            }
        }

        // Hitung total tahunan untuk Indeks Implisit
        if ($showAllPeriode && count($tahunList) > 0) {
            $this->calculateIndeksTahunanOptimized(
                $kategoriDataBerlaku,
                $kategoriDataKonstan,
                $subDataBerlaku,
                $subDataKonstan,
                $tahunList,
                $kategori,
                $sub,
                $totalTahunanKategori,
                $totalTahunanSub
            );
        }

        $result = compact(
            'nilaiKategori',
            'nilaiSub',
            'totalTahunanKategori',
            'totalTahunanSub',
            'totalSemuaKategoriTahunan'
        );

        // Cache hasil selama 5 menit
        Cache::put($cacheKey, $result, 300);

        return $result;
    }

    /**
     * Hitung Indeks Implisit tahunan - OPTIMIZED
     */
    private function calculateIndeksTahunanOptimized(
        $kategoriDataBerlaku,
        $kategoriDataKonstan,
        $subDataBerlaku,
        $subDataKonstan,
        $tahunList,
        $kategori,
        $sub,
        &$totalTahunanKategori,
        &$totalTahunanSub
    ) {
        if (count($tahunList) == 0)
            return;

        foreach ($tahunList as $tahunId) {
            // Hitung Indeks Implisit untuk setiap kategori (tahunan)
            foreach ($kategori as $kat) {
                // Hitung total semua periode untuk ADHB (berlaku)
                $totalBerlaku = 0;
                if (isset($kategoriDataBerlaku[$kat->id_kategori][$tahunId])) {
                    $totalBerlaku = array_sum($kategoriDataBerlaku[$kat->id_kategori][$tahunId]);
                }

                // Hitung total semua periode untuk ADHK (konstan)
                $totalKonstan = 0;
                if (isset($kategoriDataKonstan[$kat->id_kategori][$tahunId])) {
                    $totalKonstan = array_sum($kategoriDataKonstan[$kat->id_kategori][$tahunId]);
                }

                if ($totalKonstan != 0) {
                    // Rumus: Total ADHB / Total ADHK * 100
                    $indeksTahunan = ($totalBerlaku / $totalKonstan) * 100;

                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => round($indeksTahunan, 2),
                        'tipe_perhitungan' => 'indeks_annual',
                        'data_valid' => true
                    ]);
                } else {
                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => null,
                        'tipe_perhitungan' => 'indeks_annual',
                        'data_valid' => false
                    ]);
                }
            }

            // Hitung Indeks Implisit untuk setiap sub kategori (tahunan)
            foreach ($sub as $s) {
                // Hitung total semua periode untuk ADHB (berlaku)
                $totalBerlaku = 0;
                if (isset($subDataBerlaku[$s->id_sub_kategori][$tahunId])) {
                    $totalBerlaku = array_sum($subDataBerlaku[$s->id_sub_kategori][$tahunId]);
                }

                // Hitung total semua periode untuk ADHK (konstan)
                $totalKonstan = 0;
                if (isset($subDataKonstan[$s->id_sub_kategori][$tahunId])) {
                    $totalKonstan = array_sum($subDataKonstan[$s->id_sub_kategori][$tahunId]);
                }

                if ($totalKonstan != 0) {
                    $indeksTahunan = ($totalBerlaku / $totalKonstan) * 100;

                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'nilai' => round($indeksTahunan, 2),
                        'id_tahun' => $tahunId,
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'indeks_annual',
                        'data_valid' => true
                    ]);
                } else {
                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'nilai' => null,
                        'id_tahun' => $tahunId,
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'indeks_annual',
                        'data_valid' => false
                    ]);
                }
            }
        }
    }

    /**
     * 8. DISTRIBUSI (Kontribusi Persentase) - OPTIMIZED
     * Rumus: =IF(D9=0;" ";D9/D$74*100)
     * Artinya: Nilai kategori / Total semua kategori * 100%
     * Menggunakan ADHB (berlaku) sebagai dasar perhitungan
     */
    /**
     * 8. DISTRIBUSI (Kontribusi Persentase) - OPTIMIZED
     * Rumus: =IF(D9=0;" ";D9/D$74*100)
     * Artinya: Nilai kategori / Total semua kategori * 100%
     * Menggunakan ADHB (berlaku) sebagai dasar perhitungan
     */
    private function processDistribusi(
        $wilayahIds,
        $selectedYears,
        $selectedPeriode,
        $tahun,
        $periode,
        $kategori,
        $sub,
        $showAllTahun,
        $showAllPeriode
    ) {
        $cacheVersion = $this->getCacheVersionToken($wilayahIds);
        $cacheKey = 'distribusi_' . $cacheVersion . '_' . implode('_', $wilayahIds) . '_' .
            implode('_', $selectedYears) . '_' .
            ($selectedPeriode ?: 'all') . '_' .
            ($showAllTahun ? '1' : '0') . '_' .
            ($showAllPeriode ? '1' : '0');

        // Coba ambil dari cache dulu
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $nilaiKategoriCollection = collect();
        $nilaiSubCollection = collect();
        $totalTahunanKategori = collect();
        $totalTahunanSub = collect();

        // Ambil data ADHB (berlaku) untuk distribusi
        $nilaiKategoriBerlaku = $this->getNilaiKategoriOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'berlaku');
        $nilaiSubBerlaku = $this->getNilaiSubOptimized($wilayahIds, $selectedYears, $selectedPeriode, 'berlaku');

        // Kelompokkan data dengan struktur yang lebih efisien
        $dataGrouped = $this->groupDataForDistribusi($nilaiKategoriBerlaku, $nilaiSubBerlaku);

        $kategoriDataBerlaku = $dataGrouped['kategori'];
        $subDataBerlaku = $dataGrouped['sub'];
        // Tentukan pembagi (denominator) berdasarkan pendekatan kategori yang sedang diproses
        $firstKat = $kategori->first();
        $pendekatan = $firstKat ? ($firstKat->pendekatan ?? 'lapangan_usaha') : 'lapangan_usaha';
        
        $pdrbDataBerlaku = ($pendekatan == 'pengeluaran') 
            ? $dataGrouped['pdrb_pengeluaran'] 
            : $dataGrouped['pdrb_lapus'];

        // Tentukan daftar tahun dan periode
        $tahunList = $selectedYears;
        $periodeList = $this->getPeriodeList($periode, $showAllPeriode, $selectedPeriode);

        // PERBAIKAN 1: Distribusi per periode (kuartal)
        foreach ($tahunList as $tahunId) {
            foreach ($periodeList as $periodeId) {
                // PERBAIKAN PENTING: Ambil nilai PDRB untuk periode ini saja (H$74 dalam Excel)
                $nilaiPdrbPeriode = $pdrbDataBerlaku[$tahunId][$periodeId] ?? 0;

                if ($nilaiPdrbPeriode == 0) {
                    // Coba dari kategori data jika PDRB tidak ada di data terpisah
                    $nilaiPdrbPeriode = $kategoriDataBerlaku[21][$tahunId][$periodeId] ??
                        $kategoriDataBerlaku[22][$tahunId][$periodeId] ??
                        $kategoriDataBerlaku[31][$tahunId][$periodeId] ??
                        $kategoriDataBerlaku[32][$tahunId][$periodeId] ?? 0;
                }

                // Logging untuk debugging
                
                

                // Hitung distribusi untuk setiap kategori
                foreach ($kategori as $kat) {


                    $nilaiKatPeriode = $kategoriDataBerlaku[$kat->id_kategori][$tahunId][$periodeId] ?? 0;

                    // PRIORITAS: Jika ini adalah kategori PDRB itu sendiri (total), paksa 100%
                    if (in_array($kat->id_kategori, [21, 31])) {
                        if ($nilaiPdrbPeriode != 0) {
                            $distribusi = 100.00;
                            $nilaiKatPeriode = $nilaiPdrbPeriode;
                            $nilaiKategoriCollection->push((object) [
                                'id_kategori' => $kat->id_kategori,
                                'id_tahun' => $tahunId,
                                'id_periode' => $periodeId,
                                'nilai' => round($distribusi, 2),
                                'nilai_adhb' => $nilaiKatPeriode, // simpan nilai asli untuk referensi
                                'pdrb_pembagi' => $nilaiPdrbPeriode, // simpan PDRB pembagi
                                'tipe_perhitungan' => 'distribusi',
                                'data_valid' => true
                            ]);
                            continue;
                        }
                    } elseif ($nilaiKatPeriode != 0 && $nilaiPdrbPeriode != 0) {
                        $distribusi = ($nilaiKatPeriode / $nilaiPdrbPeriode) * 100;

                        // Debug contoh perhitungan
                        if ($kat->id_kategori == 1 && $periodeId == 1) {
                            
                            
                            
                            
                            
                        }

                        $nilaiKategoriCollection->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => round($distribusi, 2),
                            'nilai_adhb' => $nilaiKatPeriode, // simpan nilai asli untuk referensi
                            'pdrb_pembagi' => $nilaiPdrbPeriode, // simpan PDRB pembagi
                            'tipe_perhitungan' => 'distribusi',
                            'data_valid' => true
                        ]);
                    } else {
                        // Jika nilai 0, return null/empty sesuai Excel formula
                        $nilaiKategoriCollection->push((object) [
                            'id_kategori' => $kat->id_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'tipe_perhitungan' => 'distribusi',
                            'data_valid' => false
                        ]);
                    }
                }

                // Hitung distribusi untuk sub kategori
                foreach ($sub as $s) {
                    $nilaiSubPeriode = $subDataBerlaku[$s->id_sub_kategori][$tahunId][$periodeId] ?? 0;

                    if ($nilaiSubPeriode != 0 && $nilaiPdrbPeriode != 0) {
                        $distribusi = ($nilaiSubPeriode / $nilaiPdrbPeriode) * 100;

                        $nilaiSubCollection->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => round($distribusi, 2),
                            'nilai_adhb' => $nilaiSubPeriode,
                            'pdrb_pembagi' => $nilaiPdrbPeriode,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'distribusi',
                            'data_valid' => true
                        ]);
                    } else {
                        $nilaiSubCollection->push((object) [
                            'id_sub_kategori' => $s->id_sub_kategori,
                            'id_tahun' => $tahunId,
                            'id_periode' => $periodeId,
                            'nilai' => null,
                            'kategori_id' => $s->id_kategori,
                            'tipe_perhitungan' => 'distribusi',
                            'data_valid' => false
                        ]);
                    }
                }
            }
        }

        // PERBAIKAN 3: Distribusi tahunan (Total kolom)
        if ($showAllPeriode && count($tahunList) > 0) {
            $this->calculateDistribusiTahunanOptimized(
                $kategoriDataBerlaku,
                $subDataBerlaku,
                $pdrbDataBerlaku,
                $tahunList,
                $kategori,
                $sub,
                $totalTahunanKategori,
                $totalTahunanSub
            );
        }

        $result = [
            'nilaiKategori' => $nilaiKategoriCollection,
            'nilaiSub' => $nilaiSubCollection,
            'totalTahunanKategori' => $totalTahunanKategori,
            'totalTahunanSub' => $totalTahunanSub,
            'metadata' => [
                'formula' => '=IF(H9=0;" ";H9/H$74*100)',
                'pembagi' => 'PDRB (ID: 21) per periode',
                'satuan' => 'persen (%)'
            ]
        ];

        // Cache hasil selama 5 menit
        Cache::put($cacheKey, $result, 300);

        return $result;
    }

    /**
     * Kelompokkan data untuk distribusi
     */
    private function groupDataForDistribusi($nilaiKategoriBerlaku, $nilaiSubBerlaku)
    {
        $kategoriData = [];
        $subData = [];
        $pdrbLapusData = [];
        $pdrbPengeluaranData = [];
        $sumPdrbLapus = [];
        $sumPdrbPengeluaran = [];

        // Kelompokkan data kategori
        foreach ($nilaiKategoriBerlaku as $nk) {
            $kategoriData[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;

            // Khusus data PDRB Lapus (ID: 21, 22)
            if (in_array($nk->id_kategori, [21, 22])) {
                if ($nk->id_kategori == 21 || !isset($pdrbLapusData[$nk->id_tahun][$nk->id_periode])) {
                    $pdrbLapusData[$nk->id_tahun][$nk->id_periode] = $nk->nilai;
                }
            }
            
            // Khusus data PDRB Pengeluaran (ID: 31, 32)
            if (in_array($nk->id_kategori, [31, 32])) {
                if ($nk->id_kategori == 31 || !isset($pdrbPengeluaranData[$nk->id_tahun][$nk->id_periode])) {
                    $pdrbPengeluaranData[$nk->id_tahun][$nk->id_periode] = $nk->nilai;
                }
            }

            // Hitung sum kategori 1-17 dan 19 (Pajak) sebagai cadangan PDRB Lapus
            if (($nk->id_kategori >= 1 && $nk->id_kategori <= 17) || $nk->id_kategori == 19) {
                if (!isset($sumPdrbLapus[$nk->id_tahun][$nk->id_periode])) {
                    $sumPdrbLapus[$nk->id_tahun][$nk->id_periode] = 0;
                }
                $sumPdrbLapus[$nk->id_tahun][$nk->id_periode] += $nk->nilai;
            }

            // Hitung sum kategori 23-28 sebagai cadangan PDRB Pengeluaran
            // Catatan: 23-27 adalah komponen domestik, 28 adalah Net Ekspor
            if ($nk->id_kategori >= 23 && $nk->id_kategori <= 28) {
                if (!isset($sumPdrbPengeluaran[$nk->id_tahun][$nk->id_periode])) {
                    $sumPdrbPengeluaran[$nk->id_tahun][$nk->id_periode] = 0;
                }
                $sumPdrbPengeluaran[$nk->id_tahun][$nk->id_periode] += $nk->nilai;
            }
        }

        // Jalankan fallback sum jika pdrbData masih kosong
        foreach ($sumPdrbLapus as $tId => $periods) {
            foreach ($periods as $pId => $val) {
                if (($pdrbLapusData[$tId][$pId] ?? 0) == 0) {
                    $pdrbLapusData[$tId][$pId] = $val;
                }
            }
        }
        foreach ($sumPdrbPengeluaran as $tId => $periods) {
            foreach ($periods as $pId => $val) {
                if (($pdrbPengeluaranData[$tId][$pId] ?? 0) == 0) {
                    $pdrbPengeluaranData[$tId][$pId] = $val;
                }
            }
        }

        // Kelompokkan data sub kategori
        foreach ($nilaiSubBerlaku as $ns) {
            $subData[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
        }

        return [
            'kategori' => $kategoriData,
            'sub' => $subData,
            'pdrb_lapus' => $pdrbLapusData,
            'pdrb_pengeluaran' => $pdrbPengeluaranData
        ];
    }

    /**
     * Hitung Distribusi tahunan - REVISI KONSEP
     * Distribusi tahunan = (Total ADHB kategori tahunan / Total ADHB PDRB tahunan) * 100%
     * TIDAK menggunakan total semua kategori, tapi total PDRB saja
     */
    private function calculateDistribusiTahunanOptimized(
        $kategoriDataBerlaku,
        $subDataBerlaku,
        $pdrbDataBerlaku,
        $tahunList,
        $kategori,
        $sub,
        &$totalTahunanKategori,
        &$totalTahunanSub
    ) {
        foreach ($tahunList as $tahunId) {
            // Hitung total PDRB untuk tahun ini (jumlah semua periode)
            $totalPdrbTahunan = 0;
            if (isset($pdrbDataBerlaku[$tahunId])) {
                $totalPdrbTahunan = array_sum($pdrbDataBerlaku[$tahunId]);
            }

            
            

            // Hitung distribusi tahunan untuk setiap kategori
            foreach ($kategori as $kat) {


                // Hitung total kategori untuk tahun ini
                $totalKategoriTahunan = 0;
                if (isset($kategoriDataBerlaku[$kat->id_kategori][$tahunId])) {
                    $totalKategoriTahunan = array_sum($kategoriDataBerlaku[$kat->id_kategori][$tahunId]);
                }

                if ($totalKategoriTahunan != 0 && $totalPdrbTahunan != 0) {
                    $distribusiTahunan = ($totalKategoriTahunan / $totalPdrbTahunan) * 100;

                    // Debug untuk kategori pertama
                    if ($kat->id_kategori == 1) {
                        
                        
                        
                        
                        
                    }

                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => round($distribusiTahunan, 2),
                        'total_adhb' => $totalKategoriTahunan,
                        'total_pdrb' => $totalPdrbTahunan,
                        'tipe_perhitungan' => 'distribusi_annual',
                        'data_valid' => true
                    ]);
                } else {
                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => null,
                        'tipe_perhitungan' => 'distribusi_annual',
                        'data_valid' => false
                    ]);
                }
            }

            // Hitung distribusi tahunan untuk sub kategori
            foreach ($sub as $s) {
                $totalSubTahunan = 0;
                if (isset($subDataBerlaku[$s->id_sub_kategori][$tahunId])) {
                    $totalSubTahunan = array_sum($subDataBerlaku[$s->id_sub_kategori][$tahunId]);
                }

                if ($totalSubTahunan != 0 && $totalPdrbTahunan != 0) {
                    $distribusiTahunan = ($totalSubTahunan / $totalPdrbTahunan) * 100;

                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'nilai' => round($distribusiTahunan, 2),
                        'id_tahun' => $tahunId,
                        'total_adhb' => $totalSubTahunan,
                        'total_pdrb' => $totalPdrbTahunan,
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'distribusi_annual',
                        'data_valid' => true
                    ]);
                } else {
                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'nilai' => null,
                        'id_tahun' => $tahunId,
                        'kategori_id' => $s->id_kategori,
                        'tipe_perhitungan' => 'distribusi_annual',
                        'data_valid' => false
                    ]);
                }
            }
        }
    }

}
