<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


use App\Models\{
    Kategori,
    SubKategori,
    NilaiSubKategori,
    NilaiKategori,
    Tahun,
    Wilayah,
    Periode,
    PdrbLock
};

class PdrbController extends Controller
{
    // Properties untuk caching
    private $allKategoris;
    private $allSubKategoris;
    private $tahunCache;
    private $periodeCache;
    private $templateMinYear = 2010;

    private function getCurrentYear()
    {
        return (int) now()->year;
    }

    private function getCurrentQuarter()
    {
        return (int) ceil(now()->month / 3);
    }

    private function getTemplateYearBounds()
    {
        return [$this->templateMinYear, $this->getCurrentYear()];
    }

    private function ensureReferenceYears($minYear = null, $maxYear = null)
    {
        $minYear = $minYear ?? $this->templateMinYear;
        $maxYear = $maxYear ?? $this->getCurrentYear();

        for ($year = $minYear; $year <= $maxYear; $year++) {
            Tahun::firstOrCreate(['tahun' => $year]);
        }
    }

    private function ensureReferencePeriodes()
    {
        $periodes = ['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV'];
        foreach ($periodes as $periode) {
            Periode::firstOrCreate(['nama_periode' => $periode]);
        }
    }

    private function getTemplateSubCode($subId, $subIndex)
    {
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
    }

    private function getTemplateCategoryCode($namaKategori, $defaultCode)
    {
        $map = [
            'Jasa Perusahaan' => 'M,N',
            'Jasa lainnya' => 'R,S,T,U',
            'Produk Domestik Regional Bruto' => 'PDRB',
            'Produk Domestik Regional Bruto Non Migas' => 'NON MIGAS',
        ];

        return $map[$namaKategori] ?? $defaultCode;
    }

    // =============================
    // VIEW & MENU
    // =============================
    public function index(Request $request)
    {
        $pendekatan = $request->get('pendekatan', 'lapangan_usaha'); // default

        if (!in_array($pendekatan, ['lapangan_usaha', 'pengeluaran'])) {
            abort(404);
        }

        $this->ensureReferenceYears();
        $this->ensureReferencePeriodes();

        $currentYear = $this->getCurrentYear();
        $currentQuarter = $this->getCurrentQuarter();

        $tahun = Tahun::orderBy('tahun')->get();
        $periode = Periode::orderBy('id_periode')->get();

        $wilayah = Wilayah::withNama()
            ->orderBy('nama_wilayah')
            ->get();

        $canImportMulti = auth()->user()?->role === 'provinsi';

        $pendekatan = $request->get('jenis', 'lapangan_usaha');

        return view('pdrb.index', compact(
            'tahun',
            'periode',
            'wilayah',
            'canImportMulti',
            'pendekatan',
            'currentYear',
            'currentQuarter'
        ));
    }




    public function menu()
    {
        $tahunList = Tahun::orderBy('tahun', 'desc')->get();

        return view('pdrb.menu', compact('tahunList'));
    }
    // =============================
    // TEMPLATE DOWNLOAD

    // =============================

    /**
     * Download template single tahun
     */
    public function template(Request $request)
    {
        $jenis = $request->get('jenis', 'lapangan_usaha'); // lapangan_usaha | pengeluaran

        $spreadsheet = new Spreadsheet();
        $kategoriColor = 'DCE6F1';
        $subKategoriColor = 'FFE6CC';

        /* ================= SHEET BERLAKU ================= */
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Berlaku');

        // Judul
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue(
            'A1',
            $jenis === 'pengeluaran'
            ? 'TEMPLATE INPUT DATA PDRB MENURUT PENGELUARAN'
            : 'TEMPLATE INPUT DATA PDRB MENURUT LAPANGAN USAHA'
        );
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // Header
        $headers = [
            'A3' => 'ID',
            'B3' => 'Kategori / Sub Kategori',
            'C3' => 'Nilai'
        ];
        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $sheet->getStyle('A3:C3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);

        foreach (['A' => 12, 'B' => 55, 'C' => 18] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->getStyle('B:B')->getAlignment()->setWrapText(true);

        // Ambil kategori & subkategori
        $kategori = Kategori::where('pendekatan', $jenis)
            ->with('subKategori')
            ->orderBy('id_kategori')
            ->get();

        // Isi data sheet Berlaku
        $row = 4;
        $catIndex = 1;
        foreach ($kategori as $kat) {
            $catCode = Coordinate::stringFromColumnIndex($catIndex);

            // Kategori
            $sheet->setCellValue("A$row", $this->getTemplateCategoryCode($kat->nama_kategori, $catCode));
            $sheet->setCellValue("B$row", $kat->nama_kategori);
            $sheet->getStyle("A$row:C$row")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($kategoriColor);
            $sheet->getStyle("A$row:C$row")->getFont()->setBold(true);
            $row++;

            // Subkategori
            $subIndex = 1;
            foreach ($kat->subKategori as $sub) {
                $sheet->setCellValue("A$row", $this->getTemplateSubCode($sub->id_sub_kategori, $subIndex));
                $sheet->setCellValue("B$row", $sub->nama_sub_kategori);
                $sheet->setCellValue("C$row", '');
                $sheet->getStyle("A$row:C$row")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($subKategoriColor);
                $row++;
                $subIndex++;
            }
            $catIndex++;
        }

        // Border sheet Berlaku
        $lastRow = $row - 1;
        if ($lastRow >= 4) {
            $sheet->getStyle("A4:C{$lastRow}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        /* ================= SHEET KONSTAN ================= */
        $sheetKonstan = $spreadsheet->createSheet();
        $sheetKonstan->setTitle('Konstan');

        // Salin kolom width
        foreach ($sheet->getColumnIterator() as $col) {
            $colIndex = $col->getColumnIndex();
            $sheetKonstan->getColumnDimension($colIndex)
                ->setWidth($sheet->getColumnDimension($colIndex)->getWidth());
        }

        // Judul
        $sheetKonstan->mergeCells('A1:C1');
        $sheetKonstan->setCellValue('A1', 'TEMPLATE KONSTAN PDRB');
        $sheetKonstan->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheetKonstan->getStyle('A1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Header
        foreach ($headers as $cell => $text) {
            $sheetKonstan->setCellValue($cell, $text);
        }
        $sheetKonstan->getStyle('A3:C3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);

        $sheetKonstan->getStyle('B:B')->getAlignment()->setWrapText(true);

        // Isi kategori & subkategori (nilai kosong) + rumus SUM
        $row = 4;
        $catIndex = 1;
        foreach ($kategori as $kat) {
            $catCode = Coordinate::stringFromColumnIndex($catIndex);

            // Kategori
            $sheetKonstan->setCellValue("A$row", $this->getTemplateCategoryCode($kat->nama_kategori, $catCode));
            $sheetKonstan->setCellValue("B$row", $kat->nama_kategori);
            $sheetKonstan->getStyle("A$row:C$row")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($kategoriColor);
            $sheetKonstan->getStyle("A$row:C$row")->getFont()->setBold(true);
            $row++;

            // Subkategori
            $subIndex = 1;
            foreach ($kat->subKategori as $sub) {
                $sheetKonstan->setCellValue("A$row", $this->getTemplateSubCode($sub->id_sub_kategori, $subIndex));
                $sheetKonstan->setCellValue("B$row", $sub->nama_sub_kategori);
                $sheetKonstan->setCellValue("C$row", '');
                $sheetKonstan->getStyle("A$row:C$row")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($subKategoriColor);
                $row++;
                $subIndex++;
            }
            $catIndex++;
        }

        // Border sheet Konstan
        $lastRow = $row - 1;
        if ($lastRow >= 4) {
            $sheetKonstan->getStyle("A4:C{$lastRow}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        /* ================= DOWNLOAD FILE ================= */
        $spreadsheet->setActiveSheetIndex(0); // buka sheet Berlaku
        $filename = $jenis === 'pengeluaran'
            ? 'template_pdrb_pengeluaran.xlsx'
            : 'template_pdrb_lapangan_usaha.xlsx';

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $filename
        );
    }

    /**
     * Download template multi tahun (full)
     */
    public function templateFull(Request $request)
    {
        $jenis = $request->get('jenis', 'lapangan_usaha'); // lapangan_usaha | pengeluaran

        $spreadsheet = new Spreadsheet();
        [$minYear, $maxYear] = $this->getTemplateYearBounds();
        $currentQuarter = $this->getCurrentQuarter();
        $tahunList = range($minYear, $maxYear);
        $triwulan = ['I', 'II', 'III', 'IV'];

        $kategoriData = Kategori::where('pendekatan', $jenis)
            ->with('subKategori')
            ->orderBy('id_kategori')
            ->get();

        $kategoriColor = 'DCE6F1';
        $subKategoriColor = 'FFE6CC';

        foreach (['Berlaku', 'Konstan'] as $sheetIndex => $sheetName) {

            $sheet = $sheetIndex === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->addSheet(new Worksheet($spreadsheet, $sheetName));

            $sheet->setTitle($sheetName);

            $columnsPerYear = [];
            foreach ($tahunList as $tahun) {
                $twCount = ($tahun === $maxYear) ? $currentQuarter : 4;
                $columnsPerYear[] = $twCount + 1;
            }

            $lastColIndex = 2 + array_sum($columnsPerYear);
            $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);

            /* ================= TITLE ================= */
            $sheet->mergeCells("A1:{$lastCol}1");
            $sheet->setCellValue(
                'A1',
                "TEMPLATE INPUT DATA PDRB " .
                strtoupper(str_replace('_', ' ', $jenis)) .
                " ($sheetName) {$minYear}-{$maxYear}"
            );

            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            /* ================= HEADER ================= */
            $sheet->mergeCells('A2:A3');
            $sheet->setCellValue('A2', 'ID');
            $sheet->mergeCells('B2:B3');
            $sheet->setCellValue('B2', 'Kategori / Sub Kategori');

            $col = 3;
            foreach ($tahunList as $tahun) {
                $twCount = ($tahun === $maxYear) ? $currentQuarter : 4;
                $startCol = $col;
                $sheet->mergeCells(
                    Coordinate::stringFromColumnIndex($startCol) . '2:' .
                    Coordinate::stringFromColumnIndex($startCol + $twCount) . '2'
                );
                $sheet->setCellValue(
                    Coordinate::stringFromColumnIndex($startCol) . '2',
                    $tahun
                );

                $twList = array_slice($triwulan, 0, $twCount);
                foreach ($twList as $tw) {
                    $sheet->setCellValue(
                        Coordinate::stringFromColumnIndex($col) . '3',
                        "TW $tw"
                    );
                    $col++;
                }

                $sheet->setCellValue(
                    Coordinate::stringFromColumnIndex($col) . '3',
                    'TOTAL'
                );
                $col++;
            }

            /* ================= STYLE HEADER ================= */
            $sheet->getStyle("A2:{$lastCol}3")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '305496']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            /* ================= COLUMN WIDTH ================= */
            $sheet->getColumnDimension('A')->setWidth(12);
            $sheet->getColumnDimension('B')->setWidth(55);

            for ($i = 3; $i <= $lastColIndex; $i++) {
                $sheet->getColumnDimension(
                    Coordinate::stringFromColumnIndex($i)
                )->setWidth(12);
            }

            /* ================= DATA ================= */
            $row = 4;

            $catIndex = 1;
            foreach ($kategoriData as $kat) {
                $catCode = Coordinate::stringFromColumnIndex($catIndex);
                $sheet->setCellValue("A$row", $this->getTemplateCategoryCode($kat->nama_kategori, $catCode));
                $sheet->setCellValue("B$row", $kat->nama_kategori);
                $sheet->getStyle("A$row:B$row")->getFont()->setBold(true);
                $sheet->getStyle("A$row:{$lastCol}$row")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($kategoriColor);
                $row++;

                $subIndex = 1;
                foreach ($kat->subKategori as $sub) {
                    $sheet->setCellValue("A$row", $this->getTemplateSubCode($sub->id_sub_kategori, $subIndex));
                    $sheet->setCellValue("B$row", $sub->nama_sub_kategori);
                    $sheet->getStyle("A$row:{$lastCol}$row")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($subKategoriColor);
                    $row++;
                    $subIndex++;
                }
                $catIndex++;
            }

            $sheet->getStyle("A2:{$lastCol}" . ($row - 1))
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            "template_pdrb_full_{$jenis}_{$minYear}_{$maxYear}.xlsx"
        );
    }


    // =============================
    // IMPORT DATA
    // =============================

    /**
     * Import single tahun
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'id_tahun' => 'required',
            'id_periode' => 'required'
        ]);

        $jenis = $request->get('jenis', 'lapangan_usaha');
        $tahun = $request->id_tahun;
        $periode = $request->id_periode;
        $user = auth()->user();

        if (!$user || !$user->id_wilayah) {
            return back()->with('error', 'ID wilayah user tidak ditemukan.');
        }

        $wilayah = $user->id_wilayah;

        // === CEK KUNCI PDRB ===
        if ($user->role !== 'provinsi') {
            if (PdrbLock::isLocked($jenis, $wilayah)) {
                return back()->with('error', 'Import gagal. Import PDRB untuk wilayah Anda telah dikunci oleh Provinsi. Silakan hubungi Provinsi untuk membuka kunci.');
            }
        }
        // === END CEK KUNCI ===

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }

        $this->preloadReferenceData($jenis);

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetName = strtolower(trim($sheet->getTitle()));

            if ($sheetName === 'berlaku') {
                $tipe = 'berlaku';
            } elseif ($sheetName === 'konstan') {
                $tipe = 'konstan';
            } else {
                continue;
            }

            $highestRow = $sheet->getHighestRow();

            // Ambil header untuk menentukan layout
            $headerB = strtolower(trim((string) $sheet->getCell("B3")->getValue()));
            $headerC = strtolower(trim((string) $sheet->getCell("C3")->getValue()));
            $isCompactLayout = str_contains($headerB, 'kategori') && str_contains($headerB, 'sub') && str_contains($headerC, 'nilai');

            $kategoriIds = [];
            $subKategoriIds = [];
            $currentCategoryId = null;

            // Loop pertama: hapus data lama
            for ($row = 4; $row <= $highestRow; $row++) {
                $cellA = $sheet->getCell("A$row")->getValue();
                $cellB = $sheet->getCell("B$row")->getValue();
                $cellC = $sheet->getCell("C$row")->getValue();

                $nilai = $this->convertToNumeric($cellC ?? null);
                if ($nilai === null)
                    continue;

                if ($isCompactLayout) {
                    $idCode = $this->cleanCellValue($cellA ?? '');
                    $nama = $this->cleanCellValue($cellB ?? '');
                    $rowType = $this->detectCompactRowType($idCode);

                    if ($rowType === 'kategori') {
                        $currentCategoryId = $this->findCategoryId('', $nama);
                        if ($currentCategoryId) {
                            $kategoriIds[] = $currentCategoryId;
                        }
                    } elseif ($rowType === 'sub') {
                        if ($currentCategoryId) {
                            $subKategori = $this->findSubCategory($currentCategoryId, '', $nama);
                            if ($subKategori) {
                                $subKategoriIds[] = $subKategori->id_sub_kategori;
                            }
                        }
                    }
                } else {
                    $idKategori = trim($cellA ?? '');
                    $idSubKategori = trim($sheet->getCell("C$row")->getValue() ?? '');

                    if (!empty($idSubKategori)) {
                        $subKategoriIds[] = $idSubKategori;
                    } elseif (!empty($idKategori)) {
                        $kategoriIds[] = $idKategori;
                    }
                }
            }

            $kategoriIds = array_values(array_unique($kategoriIds));
            $subKategoriIds = array_values(array_unique($subKategoriIds));

            if (!empty($kategoriIds)) {
                NilaiKategori::where([
                    'id_tahun' => $tahun,
                    'id_periode' => $periode,
                    'id_wilayah' => $wilayah,
                    'tipe_pdrb' => $tipe,
                    'tahap_data' => 'awal'
                ])
                    ->whereIn('id_kategori', $kategoriIds)
                    ->delete();
            }

            if (!empty($subKategoriIds)) {
                NilaiSubKategori::where([
                    'id_tahun' => $tahun,
                    'id_periode' => $periode,
                    'id_wilayah' => $wilayah,
                    'tipe_pdrb' => $tipe,
                    'tahap_data' => 'awal'
                ])
                    ->whereIn('id_sub_kategori', $subKategoriIds)
                    ->delete();
            }

            // Loop kedua: insert/update data baru
            $currentCategoryId = null;
            for ($row = 4; $row <= $highestRow; $row++) {
                $cellA = $sheet->getCell("A$row")->getValue();
                $cellB = $sheet->getCell("B$row")->getValue();
                $cellC = $sheet->getCell("C$row")->getValue();

                $nilai = $this->convertToNumeric($cellC ?? null);
                if ($nilai === null)
                    continue;

                if ($isCompactLayout) {
                    $idCode = $this->cleanCellValue($cellA ?? '');
                    $nama = $this->cleanCellValue($cellB ?? '');
                    $rowType = $this->detectCompactRowType($idCode);

                    if ($rowType === 'kategori') {
                        $currentCategoryId = $this->findCategoryId('', $nama);
                        if ($currentCategoryId) {
                            NilaiKategori::updateOrCreate(
                                [
                                    'id_kategori' => $currentCategoryId,
                                    'id_tahun' => $tahun,
                                    'id_periode' => $periode,
                                    'id_wilayah' => $wilayah,
                                    'tipe_pdrb' => $tipe,
                                    'tahap_data' => 'awal'
                                ],
                                [
                                    'nilai' => $nilai, // nilai asli
                                    'updated_at' => now(),
                                    'tahap_data' => 'awal'
                                ]
                            );
                        }
                    } elseif ($rowType === 'sub') {
                        if ($currentCategoryId) {
                            $subKategori = $this->findSubCategory($currentCategoryId, '', $nama);
                            if ($subKategori) {
                                NilaiSubKategori::updateOrCreate(
                                    [
                                        'id_sub_kategori' => $subKategori->id_sub_kategori,
                                        'id_tahun' => $tahun,
                                        'id_periode' => $periode,
                                        'id_wilayah' => $wilayah,
                                        'tipe_pdrb' => $tipe,
                                        'tahap_data' => 'awal'
                                    ],
                                    [
                                        'nilai' => $nilai, // nilai asli
                                        'updated_at' => now(),
                                        'tahap_data' => 'awal'
                                    ]
                                );
                            }
                        }
                    }
                } else {
                    $idKategori = trim($cellA ?? '');
                    $idSubKategori = trim($sheet->getCell("C$row")->getValue() ?? '');

                    if ($idSubKategori) {
                        NilaiSubKategori::updateOrCreate(
                            [
                                'id_sub_kategori' => $idSubKategori,
                                'id_tahun' => $tahun,
                                'id_periode' => $periode,
                                'id_wilayah' => $wilayah,
                                'tipe_pdrb' => $tipe,
                                'tahap_data' => 'awal'
                            ],
                            [
                                'nilai' => $nilai,
                                'updated_at' => now(),
                                'tahap_data' => 'awal'
                            ]
                        );
                    } elseif ($idKategori) {
                        NilaiKategori::updateOrCreate(
                            [
                                'id_kategori' => $idKategori,
                                'id_tahun' => $tahun,
                                'id_periode' => $periode,
                                'id_wilayah' => $wilayah,
                                'tipe_pdrb' => $tipe,
                                'tahap_data' => 'awal'
                            ],
                            [
                                'nilai' => $nilai,
                                'updated_at' => now(),
                                'tahap_data' => 'awal'
                            ]
                        );
                    }
                }
            }
        }

        $this->bumpHasilCacheVersion($wilayah);

        \App\Models\PdrbImportLog::create([
            'id_wilayah' => $wilayah,
            'id_tahun' => $tahun,
            'id_periode' => $periode,
            'pendekatan' => $jenis,
            'tipe_import' => 'single',
            'user_id' => $user->id
        ]);

        return back()->with('success', 'Data PDRB Berlaku & Konstan berhasil diimport');
    }

    /**
     * Import multi tahun (full) - OPTIMIZED VERSION
     */
    public function importFull(Request $request)
    {
        \Log::info('IMPORT FULL DIPANGGIL');
        $user = auth()->user();

        if (!$user || $user->role !== 'provinsi') {
            abort(403, 'Anda tidak memiliki akses import multi tahun');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'id_wilayah' => 'required|exists:wilayah,id_wilayah'
        ]);

        \Log::info('VALIDASI LOLOS', $request->all());

        $jenis = $request->get('jenis', 'lapangan_usaha');
        $wilayahId = $request->id_wilayah;

        // SET TIMEOUT DAN MEMORY LIMIT
        set_time_limit(0);
        ini_set('memory_limit', '2048M');


        try {
            $filePath = $request->file('file')->getPathname();
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }

        $importStats = [
            'berlaku' => ['kategori' => 0, 'subkategori' => 0],
            'konstan' => ['kategori' => 0, 'subkategori' => 0]
        ];
        $errors = [];
        $successMessages = [];

        \DB::beginTransaction();
        try {
            // 1. Preload semua data referensi
            $this->preloadReferenceData($jenis);

            // 2. Process sheets
            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $sheetName = trim($sheet->getTitle());
                $lowerSheetName = strtolower($sheetName);

                if (str_contains($lowerSheetName, 'berlaku')) {
                    $tipe = 'berlaku';
                } elseif (str_contains($lowerSheetName, 'konstan')) {
                    $tipe = 'konstan';
                } else {
                    continue;
                }

                $successMessages[] = "📊 Processing sheet: <strong>$sheetName</strong> ($tipe)";

                // Process sheet dengan optimasi
                $result = $this->processSheet($sheet, $tipe, $wilayahId);

                if (isset($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }

                if (isset($result['stats'])) {
                    $importStats[$tipe]['kategori'] += $result['stats']['kategori'];
                    $importStats[$tipe]['subkategori'] += $result['stats']['subkategori'];
                }

                $successMessages[] = "Processed {$result['rows']} rows, {$result['values']} values";
                if (isset($result['debug'])) {
                    $successMessages[] = "   Debug: mapping=" . $result['debug']['mapping_count']
                        . ", total_cols=" . $result['debug']['total_columns_count']
                        . ", total_values=" . $result['debug']['total_value_count'];
                }

                // Clear memory
                \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance()->clearCalculationCache();
                gc_collect_cycles();
            }

            \DB::commit();
            $this->bumpHasilCacheVersion($wilayahId);

            \App\Models\PdrbImportLog::create([
                'id_wilayah' => $wilayahId,
                'id_tahun' => null, // Multi-tahun imports cover multiple years
                'id_periode' => null,
                'pendekatan' => $jenis,
                'tipe_import' => 'multi_tahun',
                'user_id' => $user->id
            ]);

            // Build success message
            $message = $this->buildSuccessMessage($importStats, $successMessages, $errors);

            return back()->with('success_full', nl2br($message));

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Import Full Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            $errorMessage = "Import gagal. Pastikan file sesuai template dan kolom TW I-IV terisi.";
            return back()->with('error', $errorMessage);
        }
    }

    // =============================
    // HELPER METHODS UNTUK IMPORT FULL
    // =============================

    /**
     * Preload semua data referensi untuk mengurangi query database
     */
    private function preloadReferenceData($jenis = null)
    {
        [$minYear, $maxYear] = $this->getTemplateYearBounds();
        $this->ensureReferenceYears($minYear, $maxYear);
        $this->ensureReferencePeriodes();

        // Load semua kategori dan subkategori sekaligus
        $kategoriQuery = Kategori::with('subKategori')
            ->orderBy('id_kategori');

        if ($jenis) {
            $kategoriQuery->where('pendekatan', $jenis);
        }

        $this->allKategoris = $kategoriQuery
            ->orderBy('id_kategori')
            ->get()
            ->keyBy('id_kategori');

        $kategoriIds = $this->allKategoris->keys()->toArray();
        $this->allSubKategoris = SubKategori::when(!empty($kategoriIds), function ($q) use ($kategoriIds) {
            $q->whereIn('id_kategori', $kategoriIds);
        })
            ->get()
            ->groupBy('id_kategori');

        // Cache tahun dan periode
        $this->tahunCache = Tahun::all()->keyBy('tahun');
        $this->periodeCache = Periode::all()->keyBy('nama_periode');
    }

    /**
     * Process sheet dengan optimasi
     */
    private function processSheet($sheet, $tipe, $wilayahId)
    {
        $errors = [];
        $stats = ['kategori' => 0, 'subkategori' => 0];

        // Gunakan toArray() untuk membaca semua data sekaligus
        $data = $sheet->toArray();

        if (count($data) < 4) {
            return ['errors' => ['Sheet tidak memiliki data yang cukup'], 'rows' => 0, 'values' => 0];
        }

        // Parse headers
        $headers = $this->parseHeaders($data);
        if (empty($headers['mapping'])) {
            return ['errors' => ['Tidak bisa memparsing header sheet. Pastikan format sesuai template.'], 'rows' => 0, 'values' => 0];
        }

        $columnMapping = $headers['mapping'];
        $totalColumns = $headers['total_columns'] ?? [];
        $dataStartCol = empty($columnMapping) ? 4 : min(array_keys($columnMapping));

        $yearRow = $data[1] ?? [];
        $layoutYear = $this->cleanCellValue($yearRow[2] ?? '');
        [$minYear, $maxYear] = $this->getTemplateYearBounds();
        $isCompactLayout = is_numeric($layoutYear) && $layoutYear >= $minYear && $layoutYear <= $maxYear;

        $kategoriDeleteMap = [];
        $subDeleteMap = [];
        $currentCategoryId = null;

        for ($i = 3; $i < count($data); $i++) {
            $row = $data[$i];

            if (
                empty(array_filter($row, function ($val) {
                    return $val !== null && $val !== '' && $val !== 0;
                }))
            ) {
                continue;
            }

            $idKategori = $this->cleanCellValue($row[0] ?? '');
            $namaKategori = $this->cleanCellValue($row[1] ?? '');
            $idSubKategori = $isCompactLayout ? '' : $this->cleanCellValue($row[2] ?? '');
            $namaSubKategori = $isCompactLayout ? '' : $this->cleanCellValue($row[3] ?? '');

            $isKategoriRow = false;
            $isSubRow = false;
            if ($isCompactLayout) {
                if (preg_match('/[A-Z]/i', $idKategori) && !preg_match('/\\d/', $idKategori)) {
                    $isKategoriRow = true;
                } elseif (preg_match('/^([A-Z]+\\d+[A-Z]*|\\d+)$/i', $idKategori)) {
                    $isSubRow = true;
                }
            } else {
                $isKategoriRow = !empty($idKategori) && empty($idSubKategori);
                $isSubRow = !empty($idSubKategori);
            }

            if ($isKategoriRow) {
                $currentCategoryId = $this->findCategoryId($isCompactLayout ? '' : $idKategori, $namaKategori);
                if (!$currentCategoryId) {
                    continue;
                }

                foreach ($columnMapping as $colIndex => $meta) {
                    if ($colIndex < $dataStartCol)
                        continue;
                    $cellValue = $row[$colIndex] ?? null;
                    $numericValue = $this->convertToNumeric($cellValue);
                    if ($numericValue !== null) {
                        $key = $currentCategoryId . '_' . $meta['id_tahun'] . '_' . $meta['id_periode'];
                        $kategoriDeleteMap[$key] = [
                            'id_kategori' => $currentCategoryId,
                            'id_tahun' => $meta['id_tahun'],
                            'id_periode' => $meta['id_periode']
                        ];
                    }
                }
            } elseif ($isSubRow) {
                if (!$currentCategoryId) {
                    continue;
                }

                $subKategori = $this->findSubCategory(
                    $currentCategoryId,
                    $isCompactLayout ? '' : $idSubKategori,
                    $isCompactLayout ? $namaKategori : $namaSubKategori
                );
                if (!$subKategori) {
                    continue;
                }

                foreach ($columnMapping as $colIndex => $meta) {
                    if ($colIndex < $dataStartCol)
                        continue;
                    $cellValue = $row[$colIndex] ?? null;
                    $numericValue = $this->convertToNumeric($cellValue);
                    if ($numericValue !== null) {
                        $key = $subKategori->id_sub_kategori . '_' . $meta['id_tahun'] . '_' . $meta['id_periode'];
                        $subDeleteMap[$key] = [
                            'id_sub_kategori' => $subKategori->id_sub_kategori,
                            'id_tahun' => $meta['id_tahun'],
                            'id_periode' => $meta['id_periode']
                        ];
                    }
                }
            }
        }

        $this->deleteCompositeKeys('kategori', array_values($kategoriDeleteMap), $wilayahId, $tipe);
        $this->deleteCompositeKeys('subkategori', array_values($subDeleteMap), $wilayahId, $tipe);

        // Prepare bulk inserts
        $bulkKategori = [];
        $bulkSubKategori = [];
        $batchSize = 500;

        $rowCount = 0;
        $valueCount = 0;
        $totalValueCount = 0;
        $currentCategoryId = null;

        // Process rows
        for ($i = 3; $i < count($data); $i++) {
            $row = $data[$i];

            // Skip empty rows
            if (
                empty(array_filter($row, function ($val) {
                    return $val !== null && $val !== '' && $val !== 0;
                }))
            ) {
                continue;
            }

            $rowCount++;

            $idKategori = $this->cleanCellValue($row[0] ?? '');
            $namaKategori = $this->cleanCellValue($row[1] ?? '');
            $idSubKategori = $isCompactLayout ? '' : $this->cleanCellValue($row[2] ?? '');
            $namaSubKategori = $isCompactLayout ? '' : $this->cleanCellValue($row[3] ?? '');

            $isKategoriRow = false;
            $isSubRow = false;
            if ($isCompactLayout) {
                if (preg_match('/[A-Z]/i', $idKategori) && !preg_match('/\\d/', $idKategori)) {
                    $isKategoriRow = true;
                } elseif (preg_match('/^([A-Z]+\\d+[A-Z]*|\\d+)$/i', $idKategori)) {
                    $isSubRow = true;
                }
            } else {
                $isKategoriRow = !empty($idKategori) && empty($idSubKategori);
                $isSubRow = !empty($idSubKategori);
            }

            // Baris Kategori (tidak ada subkategori)
            if ($isKategoriRow) {
                $currentCategoryId = $this->findCategoryId($isCompactLayout ? '' : $idKategori, $namaKategori);
                if (!$currentCategoryId) {
                    $errors[] = "Row " . ($i + 1) . ": Kategori '$idKategori' ($namaKategori) tidak ditemukan";
                    continue;
                }

                foreach ($totalColumns as $totalColIndex) {
                    $cellValue = $row[$totalColIndex] ?? null;
                    if ($this->convertToNumeric($cellValue) !== null) {
                        $totalValueCount++;
                    }
                }

                // Process values for this category
                foreach ($columnMapping as $colIndex => $meta) {
                    if ($colIndex < $dataStartCol)
                        continue;

                    $cellValue = $row[$colIndex] ?? null;
                    $numericValue = $this->convertToNumeric($cellValue);

                    if ($numericValue !== null) {
                        $bulkKategori[] = [
                            'id_kategori' => $currentCategoryId,
                            'id_tahun' => $meta['id_tahun'],
                            'id_periode' => $meta['id_periode'],
                            'id_wilayah' => $wilayahId,
                            'tipe_pdrb' => $tipe,
                            'nilai' => $numericValue,
                            'updated_at' => now(),
                            'tahap_data' => 'awal'
                        ];
                        $valueCount++;
                    }
                }
            }
            // Baris Subkategori (ada id subkategori)
            elseif ($isSubRow) {
                $displaySubName = $isCompactLayout ? $namaKategori : $namaSubKategori;
                if (!$currentCategoryId) {
                    $errors[] = "Row " . ($i + 1) . ": Subkategori '$displaySubName' tanpa kategori induk";
                    continue;
                }

                $subKategori = $this->findSubCategory(
                    $currentCategoryId,
                    $isCompactLayout ? '' : $idSubKategori,
                    $isCompactLayout ? $namaKategori : $namaSubKategori
                );
                if (!$subKategori) {
                    $errors[] = "Row " . ($i + 1) . ": Subkategori '$displaySubName' tidak ditemukan di bawah kategori $currentCategoryId";
                    continue;
                }

                foreach ($totalColumns as $totalColIndex) {
                    $cellValue = $row[$totalColIndex] ?? null;
                    if ($this->convertToNumeric($cellValue) !== null) {
                        $totalValueCount++;
                    }
                }

                // Process values for this subcategory
                foreach ($columnMapping as $colIndex => $meta) {
                    if ($colIndex < $dataStartCol)
                        continue;

                    $cellValue = $row[$colIndex] ?? null;
                    $numericValue = $this->convertToNumeric($cellValue);

                    if ($numericValue !== null) {
                        $bulkSubKategori[] = [
                            'id_sub_kategori' => $subKategori->id_sub_kategori,
                            'id_tahun' => $meta['id_tahun'],
                            'id_periode' => $meta['id_periode'],
                            'id_wilayah' => $wilayahId,
                            'tipe_pdrb' => $tipe,
                            'nilai' => $numericValue,
                            'updated_at' => now(),
                            'tahap_data' => 'awal'
                        ];
                        $valueCount++;
                    }
                }
            }

            // Bulk insert jika mencapai batch size
            if (count($bulkKategori) >= $batchSize) {
                $this->bulkInsert('kategori', $bulkKategori, $stats, $tipe);
                $bulkKategori = [];
            }

            if (count($bulkSubKategori) >= $batchSize) {
                $this->bulkInsert('subkategori', $bulkSubKategori, $stats, $tipe);
                $bulkSubKategori = [];
            }
        }

        // Insert sisa data
        if (!empty($bulkKategori)) {
            $this->bulkInsert('kategori', $bulkKategori, $stats, $tipe);
        }

        if (!empty($bulkSubKategori)) {
            $this->bulkInsert('subkategori', $bulkSubKategori, $stats, $tipe);
        }

        if ($valueCount === 0 && $totalValueCount > 0) {
            $errors[] = "Sheet '" . $sheet->getTitle() . "': nilai hanya terisi di kolom TOTAL, kolom TW kosong. Import mengabaikan kolom TOTAL.";
        }

        return [
            'errors' => $errors,
            'stats' => $stats,
            'rows' => $rowCount,
            'values' => $valueCount,
            'debug' => [
                'mapping_count' => count($columnMapping),
                'total_columns_count' => count($totalColumns),
                'total_value_count' => $totalValueCount
            ]
        ];
    }

    /**
     * Parse headers dari data array
     */
    private function parseHeaders($data)
    {
        if (count($data) < 3) {
            return ['mapping' => [], 'total_columns' => []];
        }

        $yearRow = $data[1] ?? []; // Row 2 (0-based index)
        $periodRow = $data[2] ?? []; // Row 3 (0-based index)

        $mapping = [];
        $totalColumns = [];
        $currentYear = null;
        [$minYear, $maxYear] = $this->getTemplateYearBounds();

        // Mulai dari kolom C (index 2) untuk format template baru
        for ($col = 2; $col < count($yearRow); $col++) {
            $yearValue = $this->cleanCellValue($yearRow[$col] ?? '');

            // Cek jika ini adalah tahun baru
            if (is_numeric($yearValue) && $yearValue >= $minYear && $yearValue <= $maxYear) {
                $currentYear = (int) $yearValue;
            }

            // Jika kita punya tahun valid, parse periode
            if ($currentYear && isset($periodRow[$col])) {
                $periodValue = $this->cleanCellValue($periodRow[$col]);
                $triwulanNum = $this->extractTriwulanNumber($periodValue);

                if ($triwulanNum && isset($this->tahunCache[$currentYear])) {
                    $periodeName = $this->getPeriodeName($triwulanNum);
                    $periode = $this->periodeCache[$periodeName] ?? null;

                    if ($periode) {
                        $mapping[$col] = [
                            'id_tahun' => $this->tahunCache[$currentYear]->id_tahun,
                            'id_periode' => $periode->id_periode,
                            'tahun' => $currentYear,
                            'triwulan' => $triwulanNum
                        ];
                    }
                } elseif (str_contains(strtoupper($periodValue), 'TOTAL')) {
                    $totalColumns[] = $col;
                    continue;
                }
            }
        }

        // Jika mapping masih kosong, coba alternatif parsing
        if (empty($mapping)) {
            $alt = $this->parseHeadersAlternative($data);
            $mapping = $alt['mapping'];
            $totalColumns = $alt['total_columns'];
        }

        return [
            'mapping' => $mapping,
            'total_columns' => array_values(array_unique($totalColumns))
        ];
    }

    /**
     * Alternatif parsing headers
     */
    private function parseHeadersAlternative($data)
    {
        $mapping = [];
        $totalColumns = [];

        if (count($data) < 3) {
            return ['mapping' => $mapping, 'total_columns' => $totalColumns];
        }

        [$minYear, $maxYear] = $this->getTemplateYearBounds();
        // Coba cari pola tahun di row mana saja
        for ($rowIndex = 0; $rowIndex < min(5, count($data)); $rowIndex++) {
            $row = $data[$rowIndex];

            for ($col = 0; $col < count($row); $col++) {
                $cellValue = $this->cleanCellValue($row[$col] ?? '');

                if (is_numeric($cellValue) && $cellValue >= $minYear && $cellValue <= $maxYear) {
                    $currentYear = (int) $cellValue;

                    // Cari triwulan di row berikutnya
                    if (isset($data[$rowIndex + 1])) {
                        $nextRow = $data[$rowIndex + 1];

                        // Cek 6 kolom berikutnya (4 TW + TOTAL)
                        for ($i = 0; $i < 6; $i++) {
                            $triwulanCol = $col + $i;
                            if (isset($nextRow[$triwulanCol])) {
                                $triwulanValue = $this->cleanCellValue($nextRow[$triwulanCol]);

                                if (str_contains(strtoupper($triwulanValue), 'TOTAL')) {
                                    $totalColumns[] = $triwulanCol;
                                    continue;
                                }

                                $triwulanNum = $this->extractTriwulanNumber($triwulanValue);

                                if ($triwulanNum && isset($this->tahunCache[$currentYear])) {
                                    $periodeName = $this->getPeriodeName($triwulanNum);
                                    $periode = $this->periodeCache[$periodeName] ?? null;

                                    if ($periode) {
                                        $mapping[$triwulanCol] = [
                                            'id_tahun' => $this->tahunCache[$currentYear]->id_tahun,
                                            'id_periode' => $periode->id_periode,
                                            'tahun' => $currentYear,
                                            'triwulan' => $triwulanNum
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return [
            'mapping' => $mapping,
            'total_columns' => array_values(array_unique($totalColumns))
        ];
    }

    /**
     * Find category ID dengan caching
     */
    private function findCategoryId($id, $name = '')
    {
        $cleanId = $this->cleanCellValue($id);
        $cleanName = $this->cleanCellValue($name);

        // Cari berdasarkan ID
        if (!empty($cleanId) && isset($this->allKategoris[$cleanId])) {
            return $this->allKategoris[$cleanId]->id_kategori;
        }

        // Cari berdasarkan nama
        if (!empty($cleanName)) {
            foreach ($this->allKategoris as $kategori) {
                if (strcasecmp(trim($kategori->nama_kategori), $cleanName) === 0) {
                    return $kategori->id_kategori;
                }
            }
        }

        return null;
    }

    /**
     * Find subcategory dengan caching
     */
    private function findSubCategory($categoryId, $id, $name = '')
    {
        $cleanId = $this->cleanCellValue($id);
        $cleanName = $this->cleanCellValue($name);

        $subKategoris = $this->allSubKategoris[$categoryId] ?? collect();

        if (empty($subKategoris)) {
            return null;
        }

        // Cari berdasarkan ID
        if (!empty($cleanId)) {
            $subKategori = $subKategoris->firstWhere('id_sub_kategori', $cleanId);
            if ($subKategori) {
                return $subKategori;
            }
        }

        // Cari berdasarkan nama
        if (!empty($cleanName)) {
            foreach ($subKategoris as $sub) {
                if (strcasecmp(trim($sub->nama_sub_kategori), $cleanName) === 0) {
                    return $sub;
                }
            }
        }

        return null;
    }

    /**
     * Bump cache version agar halaman hasil langsung update
     */
    private function bumpHasilCacheVersion($wilayahId)
    {
        $key = "pdrb_cache_version_{$wilayahId}";
        $current = Cache::get($key, 1);
        Cache::forever($key, $current + 1);
    }

    /**
     * Delete existing values only for keys present in the import file
     */
    private function deleteCompositeKeys($type, array $keys, $wilayahId, $tipe)
    {
        if (empty($keys)) {
            return;
        }

        $chunks = array_chunk($keys, 200);

        foreach ($chunks as $chunk) {
            $query = $type === 'kategori' ? NilaiKategori::query() : NilaiSubKategori::query();

            $query->where('id_wilayah', $wilayahId)
                ->where('tipe_pdrb', $tipe)
                ->where('tahap_data', 'awal')
                ->where(function ($q) use ($chunk, $type) {
                    foreach ($chunk as $key) {
                        $q->orWhere(function ($qq) use ($key, $type) {
                            if ($type === 'kategori') {
                                $qq->where('id_kategori', $key['id_kategori']);
                            } else {
                                $qq->where('id_sub_kategori', $key['id_sub_kategori']);
                            }
                            $qq->where('id_tahun', $key['id_tahun'])
                                ->where('id_periode', $key['id_periode']);
                        });
                    }
                })
                ->delete();
        }
    }

    /**
     * Bulk insert dengan chunking
     */
    private function bulkInsert($type, &$data, &$stats, $tipe)
    {
        if (empty($data)) {
            return;
        }

        $chunks = array_chunk($data, 100);

        foreach ($chunks as $chunk) {
            try {
                if ($type === 'kategori') {
                    NilaiKategori::upsert(
                        $chunk,
                        ['id_kategori', 'id_tahun', 'id_periode', 'id_wilayah', 'tipe_pdrb', 'tahap_data'],
                        ['nilai', 'updated_at']
                    );
                    $stats['kategori'] += count($chunk);
                } else {
                    NilaiSubKategori::upsert(
                        $chunk,
                        ['id_sub_kategori', 'id_tahun', 'id_periode', 'id_wilayah', 'tipe_pdrb', 'tahap_data'],
                        ['nilai', 'updated_at']
                    );
                    $stats['subkategori'] += count($chunk);
                }
            } catch (\Exception $e) {
                \Log::warning("Bulk insert $type error: " . $e->getMessage());

                // Fallback ke updateOrCreate
                foreach ($chunk as $item) {
                    try {
                        if ($type === 'kategori') {
                            NilaiKategori::updateOrCreate([
                                'id_kategori' => $item['id_kategori'],
                                'id_tahun' => $item['id_tahun'],
                                'id_periode' => $item['id_periode'],
                                'id_wilayah' => $item['id_wilayah'],
                                'tipe_pdrb' => $tipe,
                                'tahap_data' => 'awal'
                            ], [
                                'nilai' => $item['nilai'],
                                'tahap_data' => 'awal'
                            ]);
                            $stats['kategori']++;
                        } else {
                            NilaiSubKategori::updateOrCreate([
                                'id_sub_kategori' => $item['id_sub_kategori'],
                                'id_tahun' => $item['id_tahun'],
                                'id_periode' => $item['id_periode'],
                                'id_wilayah' => $item['id_wilayah'],
                                'tipe_pdrb' => $tipe,
                                'tahap_data' => 'awal'
                            ], [
                                'nilai' => $item['nilai'],
                                'tahap_data' => 'awal'
                            ]);
                            $stats['subkategori']++;
                        }
                    } catch (\Exception $e2) {
                        \Log::error("Individual insert $type error: " . $e2->getMessage());
                    }
                }
            }
        }

        $data = [];
    }

    /**
     * Build success message
     */
    private function buildSuccessMessage($importStats, $successMessages, $errors)
    {
        $totalBerlaku = $importStats['berlaku']['kategori'] + $importStats['berlaku']['subkategori'];
        $totalKonstan = $importStats['konstan']['kategori'] + $importStats['konstan']['subkategori'];
        $total = $totalBerlaku + $totalKonstan;

        $errorCount = count($errors);

        $message = "<strong>Import selesai.</strong>\n";
        $message .= "Berlaku: $totalBerlaku | Konstan: $totalKonstan | Total: $total";

        if ($errorCount > 0) {
            $message .= "\nSebagian data tidak diproses ($errorCount).";
            $firstError = $errors[0] ?? null;
            if ($firstError) {
                $message .= "\nCatatan: " . $firstError;
            }
        }

        return $message;
    }

    /**
     * Clean cell value
     */
    private function cleanCellValue($value)
    {
        if ($value === null) {
            return '';
        }

        if (is_object($value)) {
            return trim((string) $value);
        }

        return trim((string) $value);
    }

    /**
     * Deteksi tipe baris pada template compact (single tahun).
     * - Kode dengan digit => sub kategori (contoh: 1, 2, A1A)
     * - Kode huruf tanpa digit => kategori (contoh: A, B, M,N, PDRB)
     */
    private function detectCompactRowType($idCode)
    {
        $code = strtoupper($this->cleanCellValue($idCode));
        if ($code === '') {
            return null;
        }

        if (preg_match('/\d/', $code)) {
            return 'sub';
        }

        if (preg_match('/[A-Z]/', $code)) {
            return 'kategori';
        }

        return null;
    }

    /**
     * Extract triwulan number
     */
    private function extractTriwulanNumber($header)
    {
        if (empty($header)) {
            return null;
        }

        $header = strtoupper(trim((string) $header));

        // Pattern for "TW I", "Triwulan I", etc.
        if (preg_match('/TW\s*([IV]+)/i', $header, $matches)) {
            $roman = $matches[1];
        } elseif (preg_match('/([IV]+)/i', $header, $matches)) {
            $roman = $matches[1];
        } elseif (preg_match('/TRIWULAN\s*([IV]+)/i', $header, $matches)) {
            $roman = $matches[1];
        } elseif (preg_match('/(\d)/', $header, $matches)) {
            return (int) $matches[1];
        } else {
            return null;
        }

        $romanNumerals = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4];
        return $romanNumerals[$roman] ?? null;
    }

    /**
     * Get periode name
     */
    private function getPeriodeName($triwulanNumber)
    {
        $periodeMap = [
            1 => 'Triwulan I',
            2 => 'Triwulan II',
            3 => 'Triwulan III',
            4 => 'Triwulan IV'
        ];
        return $periodeMap[$triwulanNumber] ?? null;
    }

    /**
     * Convert to numeric - optimized
     */
    private function convertToNumeric($value)
    {
        if ($value === null || $value === '')
            return null;

        $str = trim((string) $value);
        if ($str === '')
            return null;

        $str = str_replace(' ', '', $str);

        if (str_contains($str, ',') && str_contains($str, '.')) {
            if (strrpos($str, '.') > strrpos($str, ',')) {
                $str = str_replace(',', '', $str);
            } else {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
        } elseif (str_contains($str, ',')) {
            $str = str_replace(',', '.', $str);
        }

        return is_numeric($str) ? $str : null;
    }

    // =============================
    // VIEW DATA
    // =============================

    public function hasil(Request $request)
    {
        $user = auth()->user();
        $tahun = Tahun::orderBy('tahun')->get();
        $periode = Periode::orderBy('id_periode')->get()->filter(function ($p) {
            return strtolower($p->nama_periode) !== 'tahunan';
        })->values();
        $kategori = Kategori::all();
        $sub = SubKategori::all();

        $selectedTahun = $request->id_tahun;
        $selectedPeriode = $request->id_periode;
        $wilayah = $request->scope_wilayah ?? $user->id_wilayah;
        $typePdrb = $request->tipe_pdrb === 'konstan' ? 'konstan' : 'berlaku';


        $nilaiKategori = NilaiKategori::where('id_wilayah', $wilayah)
            ->when($selectedTahun, fn($q) => $q->where('id_tahun', $selectedTahun))
            ->when($selectedPeriode, fn($q) => $q->where('id_periode', $selectedPeriode))
            ->where('tipe_pdrb', $typePdrb)
            ->get();

        $nilaiSub = NilaiSubKategori::where('id_wilayah', $wilayah)
            ->when($selectedTahun, fn($q) => $q->where('id_tahun', $selectedTahun))
            ->when($selectedPeriode, fn($q) => $q->where('id_periode', $selectedPeriode))
            ->where('tipe_pdrb', $typePdrb)
            ->get();

        return view('pdrb.hasil', compact(
            'tahun',
            'periode',
            'kategori',
            'sub',
            'nilaiKategori',
            'nilaiSub',
            'selectedTahun',
            'selectedPeriode',
            'wilayah',
            'typePdrb'
        ));
    }

    public function hasilPerTahun(Request $request)
    {
        $user = auth()->user();
        $tahun = Tahun::all();
        $kategori = Kategori::all();
        $sub = SubKategori::all();
        $periode = Periode::orderBy('id_periode')->get()->filter(function ($p) {
            return strtolower($p->nama_periode) !== 'tahunan';
        })->values();

        $selectedTahun = $request->id_tahun;
        $wilayah = $request->scope_wilayah ?? $user->id_wilayah;
        $typePdrb = $request->tipe_pdrb ?? 'berlaku';

        $nilaiKategori = NilaiKategori::where('id_wilayah', $wilayah)
            ->when($selectedTahun, fn($q) => $q->where('id_tahun', $selectedTahun))
            ->where('tipe_pdrb', $typePdrb)
            ->get();

        $nilaiSub = NilaiSubKategori::where('id_wilayah', $wilayah)
            ->when($selectedTahun, fn($q) => $q->where('id_tahun', $selectedTahun))
            ->where('tipe_pdrb', $typePdrb)
            ->get();

        return view('pdrb.hasilpertahun', compact(
            'tahun',
            'kategori',
            'sub',
            'periode',
            'nilaiKategori',
            'nilaiSub',
            'selectedTahun',
            'wilayah',
            'typePdrb'
        ));
    }

    public function semuaTahun(Request $request)
    {
        $user = auth()->user();
        $wilayah = $request->scope_wilayah ?? $user->id_wilayah;
        $typePdrb = $request->tipe_pdrb ?? 'berlaku';

        $tahun = Tahun::select('id_tahun', 'tahun')->orderBy('tahun')->get();
        $kategori = Kategori::with([
            'subKategori' => function ($query) {
                $query->select('id_sub_kategori', 'id_kategori', 'nama_sub_kategori')
                    ->orderBy('id_sub_kategori');
            }
        ])
            ->select('id_kategori', 'nama_kategori')
            ->orderBy('id_kategori')
            ->get();

        $periode = Periode::select('id_periode', 'nama_periode')->orderBy('id_periode')->get()->filter(function ($p) {
            return strtolower($p->nama_periode) !== 'tahunan';
        })->values();

        $nilaiKategori = NilaiKategori::where('id_wilayah', $wilayah)
            ->where('tipe_pdrb', $typePdrb)
            ->select('id_tahun', 'id_kategori', 'id_periode', 'nilai')
            ->get()
            ->groupBy(['id_tahun', 'id_kategori', 'id_periode']);

        $nilaiSub = NilaiSubKategori::where('id_wilayah', $wilayah)
            ->where('tipe_pdrb', $typePdrb)
            ->select('id_tahun', 'id_sub_kategori', 'id_periode', 'nilai')
            ->get()
            ->groupBy(['id_tahun', 'id_sub_kategori', 'id_periode']);

        // Format data
        $nilaiKategoriFormatted = [];
        foreach ($nilaiKategori as $tahunId => $dataTahun) {
            foreach ($dataTahun as $kategoriId => $dataKategori) {
                foreach ($dataKategori as $periodeId => $items) {
                    if ($items->isNotEmpty()) {
                        $nilaiKategoriFormatted[$tahunId][$kategoriId][$periodeId] = $items->first()->nilai;
                    }
                }
            }
        }

        $nilaiSubFormatted = [];
        foreach ($nilaiSub as $tahunId => $dataTahun) {
            foreach ($dataTahun as $subId => $dataSub) {
                foreach ($dataSub as $periodeId => $items) {
                    if ($items->isNotEmpty()) {
                        $nilaiSubFormatted[$tahunId][$subId][$periodeId] = $items->first()->nilai;
                    }
                }
            }
        }

        // Calculate totals
        $totalPerKategori = [];
        foreach ($tahun as $t) {
            foreach ($kategori as $k) {
                $total = 0;
                foreach ($periode as $p) {
                    $nilai = $nilaiKategoriFormatted[$t->id_tahun][$k->id_kategori][$p->id_periode] ?? 0;
                    $total += $nilai;
                }
                $totalPerKategori[$t->id_tahun][$k->id_kategori] = $total;
            }
        }

        return view('pdrb.hasil-semua-tahun', compact(
            'wilayah',
            'typePdrb',
            'tahun',
            'kategori',
            'periode',
            'nilaiKategoriFormatted',
            'nilaiSubFormatted',
            'totalPerKategori'
        ));
    }

    // =============================
    // EDIT & DELETE
    // =============================

    public function edit(Request $request)
    {
        $user = auth()->user();
        $tahun = Tahun::orderBy('tahun')->get();
        $periode = Periode::orderBy('id_periode')->get();
        $kategori = Kategori::all();
        $sub = SubKategori::all();

        $selectedTahun = $request->id_tahun;
        $selectedPeriode = $request->id_periode;
        $wilayah = $request->scope_wilayah ?? $user->id_wilayah;

        $nilaiKategori = NilaiKategori::where('id_wilayah', $wilayah)
            ->when($selectedTahun, fn($q) => $q->where('id_tahun', $selectedTahun))
            ->when($selectedPeriode, fn($q) => $q->where('id_periode', $selectedPeriode))
            ->get();

        $nilaiSub = NilaiSubKategori::where('id_wilayah', $wilayah)
            ->when($selectedTahun, fn($q) => $q->where('id_tahun', $selectedTahun))
            ->when($selectedPeriode, fn($q) => $q->where('id_periode', $selectedPeriode))
            ->get();

        return view('pdrb.edit', compact(
            'tahun',
            'periode',
            'kategori',
            'sub',
            'nilaiKategori',
            'nilaiSub',
            'selectedTahun',
            'selectedPeriode',
            'wilayah'
        ));
    }

    public function hapus(Request $request)
    {
        $user = auth()->user();
        $wilayah = $request->scope_wilayah ?? $user->id_wilayah;
        $tahun = $request->id_tahun;
        $periode = $request->id_periode;

        NilaiSubKategori::where('id_wilayah', $wilayah)
            ->where('id_tahun', $tahun)
            ->where('id_periode', $periode)
            ->delete();

        NilaiKategori::where('id_wilayah', $wilayah)
            ->where('id_tahun', $tahun)
            ->where('id_periode', $periode)
            ->delete();

        return back()->with('success', 'Data PDRB berhasil dihapus.');
    }

    /**
     * Get lock status for PDRB import (AJAX)
     * Global: berlaku untuk SEMUA wilayah, tahun, dan periode
     */
    public function getLockStatus(Request $request)
    {
        try {
            $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
            $idWilayah = $request->get('id_wilayah', 0);

            $lock = PdrbLock::getLockRecord($pendekatan, $idWilayah);
            $isLocked = PdrbLock::isLocked($pendekatan, $idWilayah);

            return response()->json([
                'success' => true,
                'is_locked' => $isLocked,
                'is_manually_locked' => $lock ? $lock->is_locked : false,
                'lock_deadline' => $lock && $lock->lock_deadline ? $lock->lock_deadline->format('Y-m-d\TH:i') : null,
                'locked_at' => $lock && $lock->locked_at ? $lock->locked_at->format('d/m/Y H:i') : null,
                'id_wilayah' => (int)$idWilayah
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle lock for PDRB import (AJAX)
     * Global: berlaku untuk SEMUA wilayah, tahun, dan periode sekaligus
     */
    public function toggleLock(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user || $user->role !== 'provinsi') {
                return response()->json(['success' => false, 'message' => 'Hanya Provinsi yang dapat mengunci data'], 403);
            }

            $request->validate([
                'pendekatan' => 'required',
                'action' => 'required|in:lock,unlock',
                'id_wilayah' => 'nullable'
            ]);

            $idWilayah = $request->get('id_wilayah', 0);

            $lock = PdrbLock::updateOrCreate(
                [
                    'id_wilayah' => $idWilayah,
                    'pendekatan' => $request->pendekatan,
                ],
                [
                    'locked_by' => $user->id,
                    'locked_at' => now()
                ]
            );

            if ($request->action === 'lock') {
                if ($request->deadline) {
                    // Deadline mode: JANGAN kunci langsung.
                    // Import tetap terbuka sampai deadline lewat, lalu otomatis terkunci.
                    $lock->is_locked = false;
                    $lock->lock_deadline = \Carbon\Carbon::parse($request->deadline);
                } else {
                    // Manual lock: kunci LANGSUNG tanpa deadline
                    $lock->is_locked = true;
                    $lock->lock_deadline = null;
                }
            } elseif ($request->action === 'unlock') {
                $lock->is_locked = false;
                $lock->lock_deadline = null;
            }

            $lock->save();

            $isLocked = PdrbLock::isLocked($request->pendekatan, $idWilayah);
            $msg = '';
            $wilSuffix = ($idWilayah == 0) ? ' (Global)' : ' (Wilayah ini)';
            if ($request->action === 'lock') {
                if ($request->deadline) {
                    $msg = 'Deadline berhasil diatur. Import akan terkunci otomatis setelah ' . $lock->lock_deadline->format('d/m/Y H:i') . $wilSuffix;
                } else {
                    $msg = 'Import berhasil dikunci sekarang' . $wilSuffix;
                }
            } else {
                $msg = 'Import berhasil dibuka' . $wilSuffix;
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'is_locked' => $isLocked,
                'lock_deadline' => $lock->lock_deadline ? $lock->lock_deadline->format('d/m/Y H:i') : null
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
