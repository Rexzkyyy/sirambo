<?php

namespace App\Http\Controllers;

use App\Models\SubKategori;
use App\Models\NilaiSubKategori;
use App\Models\NilaiKategori;
use App\Models\Tahun;
use App\Models\Periode;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Kategori;
use App\Events\RekonP1Updated;

class RekonP1Controller extends Controller
{
    private function rekonDebugEnabled()
    {
        return (bool) config('app.debug') && (bool) env('REKON_P1_DEBUG', false);
    }

    private function rekonDebugLog($level, $message, array $context = [])
    {
        if (!$this->rekonDebugEnabled()) {
            return;
        }

        \Log::log($level, $message, $context);
    }
    public function index(Request $request)
    {
        try {
            $pendekatan = $request->get('jenis', 'lapangan_usaha');
            if (!in_array($pendekatan, ['lapangan_usaha', 'pengeluaran'])) {
                $pendekatan = 'lapangan_usaha';
            }

            $kategoriList = Kategori::where('pendekatan', $pendekatan)->get();
            $subKategoriList = SubKategori::with('kategori')
                ->whereHas('kategori', function ($q) use ($pendekatan) {
                    $q->where('pendekatan', $pendekatan);
                })
                ->get();

            if ($pendekatan === 'lapangan_usaha') {
                $filteredSubKategori = $subKategoriList->filter(function ($item, $index) {
                    return $index > 0;
                })->values();
            } else {
                $filteredSubKategori = $subKategoriList->values();
            }

            if ($pendekatan === 'lapangan_usaha') {
                $filteredKategori = $kategoriList->filter(function ($item, $index) {
                    $indeksYangDitampilkan = [4, 5, 9, 10, 11, 12, 13, 14, 15, 16];
                    return in_array($index, $indeksYangDitampilkan);
                })->values();
            } else {
                $filteredKategori = $kategoriList->filter(function ($item) {
                    return !in_array($item->id_kategori, [31, 32, 23, 26]);
                })->values();
            }

            $gabunganData = collect();

            foreach ($filteredSubKategori as $subKategori) {
                $gabunganData->push([
                    'type' => 'subkategori',
                    'data' => $subKategori,
                    'nama' => $subKategori->nama_sub_kategori,
                    'kategori_nama' => $subKategori->kategori ? $subKategori->kategori->nama_kategori : null,
                    'id' => $subKategori->id_sub_kategori,
                    'kategori_id' => $subKategori->kategori_id,
                    'route' => route('rekon_p1.detail', $subKategori->id_sub_kategori) . '?jenis=' . $pendekatan
                ]);
            }

            foreach ($filteredKategori as $kategori) {
                $subKategoriCount = $subKategoriList->where('kategori_id', $kategori->id_kategori)->count();

                $gabunganData->push([
                    'type' => 'kategori',
                    'data' => $kategori,
                    'nama' => $kategori->nama_kategori,
                    'kategori_nama' => null,
                    'id' => $kategori->id_kategori,
                    'kategori_id' => null,
                    'subkategori_count' => $subKategoriCount,
                    'route' => route('rekon_p1.detail.kategori', $kategori->id_kategori) . '?jenis=' . $pendekatan
                ]);
            }

            return view('rekon_p1.index', [
                'gabunganData' => $gabunganData,
                'totalItems' => $gabunganData->count(),
                'pendekatan' => $pendekatan
            ]);

        } catch (\Exception $e) {
            return view('rekon_p1.index', [
                'gabunganData' => collect(),
                'totalItems' => 0,
                'error' => $e->getMessage(),
                'pendekatan' => $request->get('jenis', 'lapangan_usaha')
            ]);
        }
    }




    public function detail($id, Request $request)
    {
        $isKategori = request()->routeIs('rekon_p1.detail.kategori');
        $pendekatan = $request->get('jenis', 'lapangan_usaha');
        if (!in_array($pendekatan, ['lapangan_usaha', 'pengeluaran'])) {
            $pendekatan = 'lapangan_usaha';
        }
        $subKategoriIds = collect();
        $subKategori = null;
        $hasNoData = false;
        $useKategoriTable = $isKategori;

        if ($isKategori) {
            $kategori = Kategori::with('subKategori')->findOrFail($id);
            $subKategoriIds = $kategori->subKategori->pluck('id_sub_kategori')->values();

            $subKategori = (object) [
                'id_sub_kategori' => null,
                'nama_sub_kategori' => $kategori->nama_kategori,
                'kode_sub_kategori' => $kategori->kode_kategori,
                'kategori_id' => $kategori->id_kategori,
                'is_kategori' => true
            ];
        } else {
            $subKategori = SubKategori::findOrFail($id);
            $subKategoriIds = collect([$subKategori->id_sub_kategori]);
        }

        $hasAwalData = $useKategoriTable
            ? NilaiKategori::where('id_kategori', $id)
                ->where('tahap_data', 'awal')
                ->exists()
            : NilaiSubKategori::whereIn('id_sub_kategori', $subKategoriIds->toArray())
                ->where('tahap_data', 'awal')
                ->exists();

        if (!$hasAwalData) {
            if ($isKategori) {
                $hasNoData = true;
            } else {
                return redirect()->route('rekon_p1.index', ['jenis' => $pendekatan])
                    ->with('error', 'Data awal untuk sub kategori ini belum tersedia.');
            }
        }

        $currentKategoriId = $isKategori
            ? (int) $id
            : (int) ($subKategori->id_kategori ?? $subKategori->kategori_id ?? 0);
        $yoyAlertTargetKategoriIds = [23, 24, 25, 26];
        $kabGrowthAlertKategoriIds = [23, 24, 25];

        $subKategoriInKabAlert = false;
        if (!$isKategori && $pendekatan === 'pengeluaran') {
            $subKategoriInKabAlert = SubKategori::where('id_sub_kategori', $id)
                ->whereIn('id_kategori', $kabGrowthAlertKategoriIds)
                ->exists();
        }

        $isYoyTotalKabHighlightEnabled = $pendekatan === 'pengeluaran'
            && (in_array($currentKategoriId, $yoyAlertTargetKategoriIds, true) || $subKategoriInKabAlert);
        $isKabGrowthAlertEnabled = $pendekatan === 'pengeluaran'
            && (in_array($currentKategoriId, $kabGrowthAlertKategoriIds, true) || $subKategoriInKabAlert);

        // Ambil 4 tahun berbasis waktu sekarang (tahun berjalan + 3 tahun sebelumnya).
        $currentYear = Carbon::now()->year;
        $targetYearsDesc = collect(range($currentYear - 3, $currentYear))
            ->sortDesc()
            ->values();

        $tahunRows = Tahun::whereIn('tahun', $targetYearsDesc->all())
            ->orderBy('tahun', 'desc')
            ->get(['id_tahun', 'tahun']);

        // Jika ada gap di tabel master tahun, lengkapi dari tahun yang lebih lama.
        if ($tahunRows->count() < 4) {
            $missing = 4 - $tahunRows->count();
            $minTarget = $targetYearsDesc->min();
            $extraRows = Tahun::where('tahun', '<', $minTarget)
                ->orderBy('tahun', 'desc')
                ->limit($missing)
                ->get(['id_tahun', 'tahun']);
            $tahunRows = $tahunRows->merge($extraRows);
        }

        $tahunRows = $tahunRows
            ->sortByDesc('tahun')
            ->take(4)
            ->values();
        $tahunIdsFull = $tahunRows->pluck('id_tahun')->values();

        if ($tahunIdsFull->isEmpty()) {
            if ($isKategori) {
                $hasNoData = true;
                $tahunIdsFull = Tahun::orderBy('tahun', 'desc')
                    ->limit(4)
                    ->pluck('id_tahun')
                    ->values();
            } else {
                return redirect()->route('rekon_p1.index', ['jenis' => $pendekatan])
                    ->with('error', 'Data awal untuk sub kategori ini belum tersedia.');
            }
        }

        $quarterNow = (int) ceil(Carbon::now()->month / 3);

        // Tampilan tahun berbasis triwulan berjalan:
        // - Jika Triwulan I - III: Hanya tampilkan tahun berjalan (1 tahun).
        // - Jika Triwulan IV: Tampilkan tahun berjalan + 2 tahun sebelumnya (3 tahun).
        if ($quarterNow < 4) {
            $tahunIdsDisplay = $tahunIdsFull->take(1)->values();
        } else {
            $tahunIdsDisplay = $tahunIdsFull->take(3)->values();
        }

        $tahunIdsDisplay = $tahunIdsDisplay->reverse()->values();
        $tahunIdsCalc = $tahunIdsFull->reverse()->values();

        $tahunMap = Tahun::whereIn('id_tahun', $tahunIdsDisplay)->pluck('tahun', 'id_tahun');

        $quarterNames = [
            'Triwulan I',
            'Triwulan II',
            'Triwulan III',
            'Triwulan IV',
        ];

        // Periode penuh (Q1-Q4) dipakai untuk kalkulasi.
        $periodeTriwulan = Periode::whereIn('nama_periode', $quarterNames)
            ->orderByRaw("FIELD(nama_periode, 'Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV')")
            ->get();

        if ($periodeTriwulan->isEmpty()) {
            $periodeTriwulan = Periode::whereIn('nama_periode', ['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV'])
                ->orderByRaw("FIELD(nama_periode, 'Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV')")
                ->get();
        }
        $periodeIds = $periodeTriwulan->pluck('id_periode')->values();

        // Periode tampilan:
        // - 2 tahun sebelumnya: tampilkan semua triwulan (Q1-Q4)
        // - Tahun berjalan: tampilkan sampai triwulan berjalan saat ini
        $currentYearQuarterNames = array_slice($quarterNames, 0, max(1, min(4, $quarterNow)));
        $periodeByName = $periodeTriwulan->keyBy('nama_periode');
        $periodeByTahunDisplay = [];
        foreach ($tahunIdsDisplay as $idTahun) {
            $yearValue = (int) ($tahunMap[$idTahun] ?? 0);
            if ($yearValue === $currentYear) {
                $subset = collect($currentYearQuarterNames)
                    ->map(fn($name) => $periodeByName->get($name))
                    ->filter()
                    ->values();
                $periodeByTahunDisplay[$idTahun] = $subset->isNotEmpty()
                    ? $subset
                    : $periodeTriwulan->values();
            } else {
                $periodeByTahunDisplay[$idTahun] = $periodeTriwulan->values();
            }
        }

        $aggregate = !$useKategoriTable && $isKategori && $subKategoriIds->isNotEmpty();
        $kategoriId = $useKategoriTable ? $id : null;

        $isNetExport = $isKategori
            && $pendekatan === 'pengeluaran'
            && (int) $id === 28;

        $lockType = $isKategori ? 'kategori' : 'subkategori';
        $lockEntityId = $isKategori
            ? (int) ($subKategori->id_kategori ?? $subKategori->kategori_id ?? $id)
            : (int) ($subKategori->id_sub_kategori ?? $id);
        $isLocked = $this->getRekonP1LockStatus($lockType, $lockEntityId, $pendekatan);

        if ($isNetExport) {
            $sourceKategoriIds = [29, 30];
            $dataAwalFull = $this->getAggregatedKategoriData($sourceKategoriIds, $tahunIdsCalc, $periodeIds, 'awal');
            $dataAdjFull = $this->getAggregatedKategoriData($sourceKategoriIds, $tahunIdsCalc, $periodeIds, 'rekonsiliasi');
        } else {
            $dataAwalFull = $this->getData($subKategoriIds, $tahunIdsCalc, $periodeIds, 'awal', $aggregate, $kategoriId);
            $dataAdjFull = $this->getData($subKategoriIds, $tahunIdsCalc, $periodeIds, 'rekonsiliasi', $aggregate, $kategoriId);
        }

        // Tahun berjalan hanya gunakan data sampai triwulan berjalan.
        $currentYearId = $tahunRows->firstWhere('tahun', $currentYear)?->id_tahun;
        $allowedCurrentPeriodeIds = collect($periodeByTahunDisplay[$currentYearId] ?? collect())
            ->pluck('id_periode')
            ->map(fn($v) => (int) $v)
            ->values();
        if ($currentYearId && $allowedCurrentPeriodeIds->isNotEmpty()) {
            $allowedCurrentPeriodeIdsArr = $allowedCurrentPeriodeIds->all();
            $dataAwalFull = $dataAwalFull
                ->filter(function ($row) use ($currentYearId, $allowedCurrentPeriodeIdsArr) {
                    if ((int) ($row->id_tahun ?? 0) !== (int) $currentYearId) {
                        return true;
                    }
                    return in_array((int) ($row->id_periode ?? 0), $allowedCurrentPeriodeIdsArr, true);
                })
                ->values();
            $dataAdjFull = $dataAdjFull
                ->filter(function ($row) use ($currentYearId, $allowedCurrentPeriodeIdsArr) {
                    if ((int) ($row->id_tahun ?? 0) !== (int) $currentYearId) {
                        return true;
                    }
                    return in_array((int) ($row->id_periode ?? 0), $allowedCurrentPeriodeIdsArr, true);
                })
                ->values();
        }

        $rowsFull = $this->strukturData($dataAwalFull, $dataAdjFull, $periodeTriwulan);
        $rowsDisplay = $this->trimRowsByYears($rowsFull, $tahunIdsDisplay);

        $provinsiFull = $rowsFull->firstWhere('jenis', 'provinsi');
        $kabkotaFull = $rowsFull
            ->where('jenis', '!=', 'provinsi')
            ->sortBy(fn($row) => (int) ($row['id_wilayah'] ?? 0))
            ->values();

        $provinsiDisplay = $rowsDisplay->firstWhere('jenis', 'provinsi');
        $kabkotaDisplay = $rowsDisplay
            ->where('jenis', '!=', 'provinsi')
            ->sortBy(fn($row) => (int) ($row['id_wilayah'] ?? 0))
            ->values();

        $totalKabKotaFull = $this->hitungTotalKabKota($kabkotaFull);
        $totalKabKotaDisplay = $this->hitungTotalKabKota($kabkotaDisplay);

        $this->rekonDebugLog('info', 'Debug - Total KabKota Display exists:', [
            'exists' => !empty($totalKabKotaDisplay),
            'has_data' => isset($totalKabKotaDisplay['data']) && !empty($totalKabKotaDisplay['data'])
        ]);

        $calculations = $this->hitungSemuaPerhitungan(
            $provinsiFull,
            $totalKabKotaFull,
            $kabkotaFull,
            $periodeTriwulan,
            $tahunIdsCalc,
            $tahunIdsDisplay
        );

        // 🔥 PREPARE DATA FOR DISPLAY (OFFLOAD FROM BLADE)
        $calculations = $this->prepareDisplayData(
            $calculations,
            $pendekatan,
            $tahunIdsDisplay,
            $periodeByTahunDisplay,
            $periodeTriwulan,
            $tahunMap
        );

        $provinsi = $provinsiDisplay;
        $totalKabKota = $totalKabKotaDisplay;
        $kabkota = $kabkotaDisplay;

        $this->rekonDebugLog('info', 'Debug - Final data for view:', [
            'provinsi_exists' => !empty($provinsi),
            'totalKabKota_exists' => !empty($totalKabKota),
            'kabkota_count' => $kabkota->count(),
            'calculations_keys' => array_keys($calculations),
            'has_total_kabkota_calc' => isset($calculations['total_kabkota']) && !empty($calculations['total_kabkota'])
        ]);

        return view('rekon_p1.detail', compact(
            'subKategori',
            'tahunIdsDisplay',
            'tahunMap',
            'periodeTriwulan',
            'periodeByTahunDisplay',
            'currentYearId',
            'quarterNow',
            'isYoyTotalKabHighlightEnabled',
            'isKabGrowthAlertEnabled',
            'provinsi',
            'totalKabKota',
            'kabkota',
            'calculations',
            'isKategori',
            'hasNoData',
            'pendekatan',
            'isLocked',
            'isNetExport'
        ));
    }

    private function prepareDisplayData($calculations, $pendekatan, $tahunIdsDisplay, $periodeByTahunDisplay, $periodeTriwulan, $tahunMap)
    {
        $diffThreshold = $pendekatan === 'pengeluaran' ? 4 : 5;
        $isTotalKabThreshold = 1.0; // Per user request/logic check

        $formatNumber = function ($val, $decimals = 2) {
            if ($val === null || $val === '')
                return '-';
            if ($val == 0)
                return '0';
            return number_format((float) $val, $decimals, ',', '.');
        };

        $formatAdj = function ($val) {
            if ($val === null || $val === '' || $val == 0 || $val == '0.00')
                return '';
            return number_format((float) $val, 2, ',', '.');
        };

        $getDiffCategory = function ($base, $adj) use ($diffThreshold) {
            $base = floatval($base ?? 0);
            $adj = floatval($adj ?? 0);
            if ($base == 0 || $adj == 0)
                return '';
            $diff = abs($adj - $base);
            $bedaArah = (($base > 0 && $adj < 0) || ($base < 0 && $adj > 0));
            if ($diff >= $diffThreshold && $bedaArah)
                return 'extreme_beda_arah';
            if ($diff >= $diffThreshold)
                return 'extreme';
            if ($bedaArah)
                return 'beda_arah';
            return '';
        };

        $getBgClass = function ($category) {
            if (!$category)
                return '';
            if (strpos($category, 'extreme') !== false)
                return 'bg-red-strong';
            if (strpos($category, 'beda_arah') !== false)
                return 'bg-yellow-strong';
            return '';
        };

        $getStatusLabel = function ($category) {
            if ($category === 'extreme_beda_arah')
                return 'Extreme Beda Arah';
            if ($category === 'extreme')
                return 'Extreme';
            if ($category === 'beda_arah')
                return 'Beda Arah';
            return '';
        };

        $renderStatusPopup = function ($status) {
            return '';
        };

        // 1. Process Provinsi
        if (isset($calculations['provinsi'])) {
            foreach ($tahunIdsDisplay as $idTahun) {
                foreach ($periodeTriwulan as $periode) {
                    $p = $periode->nama_periode;
                    if (isset($calculations['provinsi'][$idTahun][$p])) {
                        $this->processItemDisplay($calculations['provinsi'][$idTahun][$p], $getDiffCategory, $getBgClass, $getStatusLabel, $renderStatusPopup, $formatNumber, $formatAdj);
                    }
                }
                // Provinsi Total
                if (isset($calculations['provinsi_total'][$idTahun])) {
                    $this->processItemDisplay($calculations['provinsi_total'][$idTahun], $getDiffCategory, $getBgClass, $getStatusLabel, $renderStatusPopup, $formatNumber, $formatAdj);
                } elseif (isset($calculations['provinsi'][$idTahun]['TOTAL'])) {
                    $this->processItemDisplay($calculations['provinsi'][$idTahun]['TOTAL'], $getDiffCategory, $getBgClass, $getStatusLabel, $renderStatusPopup, $formatNumber, $formatAdj);
                }
            }
        }

        // 2. Process KabKota
        if (isset($calculations['kabkota'])) {
            foreach ($calculations['kabkota'] as $idWilayah => &$wilayahData) {
                foreach ($tahunIdsDisplay as $idTahun) {
                    foreach ($periodeTriwulan as $periode) {
                        $p = $periode->nama_periode;
                        if (isset($wilayahData[$idTahun][$p])) {
                            $this->processItemDisplay($wilayahData[$idTahun][$p], $getDiffCategory, $getBgClass, $getStatusLabel, $renderStatusPopup, $formatNumber, $formatAdj);
                        }
                    }
                    if (isset($wilayahData[$idTahun]['TOTAL'])) {
                        $this->processItemDisplay($wilayahData[$idTahun]['TOTAL'], $getDiffCategory, $getBgClass, $getStatusLabel, $renderStatusPopup, $formatNumber, $formatAdj);
                    }
                }
            }
        }

        // 3. Process Total KabKota
        if (isset($calculations['total_kabkota'])) {
            foreach ($tahunIdsDisplay as $idTahun) {
                foreach ($periodeTriwulan as $periode) {
                    $p = $periode->nama_periode;
                    if (isset($calculations['total_kabkota'][$idTahun][$p])) {
                        $item = &$calculations['total_kabkota'][$idTahun][$p];
                        $this->processItemDisplay($item, $getDiffCategory, $getBgClass, $getStatusLabel, $renderStatusPopup, $formatNumber, $formatAdj);

                        // Special Logic for Total KabKota Extreme vs Provinsi
                        $provData = $calculations['provinsi'][$idTahun][$p] ?? [];
                        $metrics = ['qoq' => 'qoq_konstan_plus_adj', 'yoy' => 'yoy_konstan_plus_adj', 'ctoc' => 'ctoc_konstan_plus_adj', 'laju' => 'laju_berlaku_plus_adj'];
                        foreach ($metrics as $mKey => $mField) {
                            $val = floatval($item[$mField] ?? 0);
                            $provVal = floatval($provData[$mField] ?? 0);
                            $absDiff = abs($val - $provVal);
                            if ($absDiff > $isTotalKabThreshold && $absDiff > 0.001) {
                                $item['display'][$mKey . '_bg'] = 'bg-red-strong text-white';
                                $item['display'][$mKey . '_status'] = 'Extreme';
                                $item['display'][$mKey . '_popup'] = $renderStatusPopup('Extreme');
                            }
                        }
                    }
                }
                // Total KabKota TOTAL
                if (isset($calculations['total_kabkota'][$idTahun]['TOTAL'])) {
                    $item = &$calculations['total_kabkota'][$idTahun]['TOTAL'];
                    $this->processItemDisplay($item, $getDiffCategory, $getBgClass, $getStatusLabel, $renderStatusPopup, $formatNumber, $formatAdj);
                }
            }
        }

        // 4. Process Diskrepansi
        if (isset($calculations['diskrepansi'])) {
            // Already simple enough but can format if needed
        }

        return $calculations;
    }

    private function processItemDisplay(&$item, $getDiffCategory, $getBgClass, $getStatusLabel, $renderStatusPopup, $formatNumber, $formatAdj)
    {
        $item['display'] = [
            'berlaku' => $formatNumber($item['berlaku'] ?? 0),
            'adj_berlaku' => $formatAdj($item['adj_berlaku'] ?? 0),
            'berlaku_plus_adj' => $formatNumber($item['berlaku_plus_adj'] ?? 0),
            'konstan' => $formatNumber($item['konstan'] ?? 0),
            'adj_konstan' => $formatAdj($item['adj_konstan'] ?? 0),
            'konstan_plus_adj' => $formatNumber($item['konstan_plus_adj'] ?? 0),

            'qoq_konstan' => $formatNumber($item['qoq_konstan'] ?? 0) . '%',
            'qoq_konstan_plus_adj' => $formatNumber($item['qoq_konstan_plus_adj'] ?? 0) . '%',

            'yoy_konstan' => $formatNumber($item['yoy_konstan'] ?? 0) . '%',
            'yoy_konstan_plus_adj' => $formatNumber($item['yoy_konstan_plus_adj'] ?? 0) . '%',

            'ctoc_konstan' => $formatNumber($item['ctoc_konstan'] ?? 0) . '%',
            'ctoc_konstan_plus_adj' => $formatNumber($item['ctoc_konstan_plus_adj'] ?? 0) . '%',

            'indeks_berlaku' => $formatNumber($item['indeks_berlaku'] ?? 0),
            'indeks_berlaku_plus_adj' => $formatNumber($item['indeks_berlaku_plus_adj'] ?? 0),

            'laju_berlaku' => $formatNumber($item['laju_berlaku'] ?? 0) . '%',
            'laju_berlaku_plus_adj' => $formatNumber($item['laju_berlaku_plus_adj'] ?? 0) . '%',
        ];

        // Categories & Popups
        $metrics = ['qoq', 'yoy', 'ctoc', 'indeks', 'laju'];
        foreach ($metrics as $m) {
            $baseField = ($m === 'indeks' || $m === 'laju') ? $m . '_berlaku' : $m . '_konstan';
            $adjField = $baseField . '_plus_adj';

            $cat = $getDiffCategory($item[$baseField] ?? 0, $item[$adjField] ?? 0);
            $item['display'][$m . '_bg'] = $getBgClass($cat);
            $item['display'][$m . '_status'] = $getStatusLabel($cat);
            $item['display'][$m . '_popup'] = $renderStatusPopup($item['display'][$m . '_status']);
        }
    }

    private function getData($subKategoriIds, $tahunIds, $periodeIds, $tahap, $aggregate = false, $kategoriId = null, $idWilayah = null)
    {
        $ids = collect($subKategoriIds)->filter()->values();

        if ($kategoriId) {
            return $this->getKategoriData($kategoriId, $tahunIds, $periodeIds, $tahap, $idWilayah);
        }

        $query = NilaiSubKategori::query()
            ->select([
                'id_wilayah',
                'id_tahun',
                'id_periode',
                'tipe_pdrb',
                'nilai',
                'id_nilai_sub_kategori'
            ])
            ->with([
                'wilayah' => function ($q) {
                    $q->withNama();
                },
                'periode:id_periode,nama_periode'
            ])
            ->whereIn('id_sub_kategori', $ids->toArray())
            ->whereIn('id_tahun', $tahunIds)
            ->whereIn('tipe_pdrb', ['berlaku', 'konstan'])
            ->whereIn('id_periode', $periodeIds);

        if ($tahap === 'rekonsiliasi') {
            $query->where('tahap_data', 'rekonsiliasi');
        } else {
            $query->where('tahap_data', $tahap);
        }

        if ($idWilayah) {
            $query->where('id_wilayah', $idWilayah);
        }

        if ($aggregate) {
            $query->select([
                'id_wilayah',
                'id_tahun',
                'id_periode',
                'tipe_pdrb',
                DB::raw('SUM(nilai) as nilai'),
                DB::raw('NULL as id_nilai_sub_kategori')
            ])->groupBy('id_wilayah', 'id_tahun', 'id_periode', 'tipe_pdrb');
        }

        $data = $query
            ->orderBy('id_wilayah')
            ->orderBy('id_tahun')
            ->orderBy('id_periode')
            ->get();

        return $data;
    }

    private function getKategoriData($id_kategori, $tahunIds, $periodeIds, $tahap, $idWilayah = null)
    {
        $query = NilaiKategori::query()
            ->select([
                'id_wilayah',
                'id_tahun',
                'id_periode',
                'tipe_pdrb',
                'nilai',
                'id_nilai_kategori'
            ])
            ->with([
                'wilayah' => function ($q) {
                    $q->withNama();
                },
                'periode:id_periode,nama_periode'
            ])
            ->where('id_kategori', $id_kategori)
            ->whereIn('id_tahun', $tahunIds)
            ->whereIn('tipe_pdrb', ['berlaku', 'konstan'])
            ->whereIn('id_periode', $periodeIds);

        if ($tahap === 'rekonsiliasi') {
            $query->where('tahap_data', 'rekonsiliasi');
        } else {
            $query->where('tahap_data', $tahap);
        }

        if ($idWilayah) {
            $query->where('id_wilayah', $idWilayah);
        }

        return $query->orderBy('id_wilayah')
            ->orderBy('id_tahun')
            ->orderBy('id_periode')
            ->get();
    }

    private function getAggregatedKategoriData(array $kategoriIds, $tahunIds, $periodeIds, $tahap)
    {
        if (empty($kategoriIds)) {
            return collect();
        }

        // Logic check untuk Net Ekspor: Ekspor (29) - Impor (30)
        $isNetExportCalc = count($kategoriIds) == 2 && in_array(29, $kategoriIds) && in_array(30, $kategoriIds);

        $sumExpression = $isNetExportCalc
            ? 'SUM(CASE WHEN id_kategori = 29 THEN nilai WHEN id_kategori = 30 THEN -nilai ELSE 0 END)'
            : 'SUM(nilai)';

        return NilaiKategori::query()
            ->select([
                'id_wilayah',
                'id_tahun',
                'id_periode',
                'tipe_pdrb',
                DB::raw("{$sumExpression} as nilai"),
                DB::raw('NULL as id_nilai_kategori')
            ])
            ->with([
                'wilayah' => function ($q) {
                    $q->withNama();
                },
                'periode:id_periode,nama_periode'
            ])
            ->whereIn('id_kategori', $kategoriIds)
            ->whereIn('id_tahun', $tahunIds)
            ->where('tahap_data', $tahap)
            ->whereIn('tipe_pdrb', ['berlaku', 'konstan'])
            ->whereIn('id_periode', $periodeIds)
            ->groupBy('id_wilayah', 'id_tahun', 'id_periode', 'tipe_pdrb')
            ->orderBy('id_wilayah')
            ->orderBy('id_tahun')
            ->orderBy('id_periode')
            ->get();
    }

    private function trimRowsByYears($rows, $tahunIdsDisplay)
    {
        $years = collect($tahunIdsDisplay)->values()->all();
        $yearKeys = array_flip($years);

        return $rows->map(function ($row) use ($yearKeys) {
            if (!isset($row['data']) || !is_array($row['data'])) {
                return $row;
            }

            $row['data'] = array_intersect_key($row['data'], $yearKeys);
            return $row;
        });
    }


    private function strukturData($dataAwal, $dataAdj, $periodeTriwulan)
    {
        $rows = [];

        foreach ($dataAwal as $item) {
            if (!$item->wilayah)
                continue;

            // 🔥 SKIP TOTAL
            if (strtoupper($item->wilayah->nama_wilayah) === 'TOTAL')
                continue;

            $this->initRow($rows, $item);

            $rows[$item->id_wilayah]['data'][$item->id_tahun][$item->periode->nama_periode][$item->tipe_pdrb]
                = $item->nilai;

            $rows[$item->id_wilayah]['data'][$item->id_tahun][$item->periode->nama_periode]['id_nilai_sub_kategori_' . $item->tipe_pdrb]
                = $item->id_nilai_sub_kategori ?? $item->id_nilai_kategori;
        }

        foreach ($dataAdj as $item) {
            if (!$item->wilayah)
                continue;

            // 🔥 SKIP TOTAL
            if (strtoupper($item->wilayah->nama_wilayah) === 'TOTAL')
                continue;
            if (strtolower($item->wilayah->tipe ?? '') === 'provinsi')
                continue;

            $this->initRow($rows, $item);

            $tipe = $item->tipe_pdrb;
            $periodeNama = $item->periode->nama_periode;
            $base = $rows[$item->id_wilayah]['data'][$item->id_tahun][$periodeNama][$tipe] ?? 0;
            $rows[$item->id_wilayah]['data'][$item->id_tahun][$periodeNama]['adj_' . $tipe]
                = $item->nilai - $base;

            $rows[$item->id_wilayah]['data'][$item->id_tahun][$item->periode->nama_periode]['id_nilai_sub_kategori_adj_' . $item->tipe_pdrb]
                = $item->id_nilai_sub_kategori ?? $item->id_nilai_kategori;
        }

        return collect($rows);
    }


    private function initRow(&$rows, $item)
    {
        $idWilayah = $item->id_wilayah;
        $idTahun = $item->id_tahun;
        $periodeNama = $item->periode->nama_periode;

        if (!isset($rows[$idWilayah])) {
            $rows[$idWilayah] = [
                'id_wilayah' => $idWilayah,
                'wilayah' => $item->wilayah->nama_wilayah ?? '-',
                'jenis' => $item->wilayah->tipe ?? '-',
                'data' => []
            ];
        }

        if (!isset($rows[$idWilayah]['data'][$idTahun][$periodeNama])) {
            $rows[$idWilayah]['data'][$idTahun][$periodeNama] = [
                'berlaku' => 0,
                'konstan' => 0,
                'adj_berlaku' => 0,
                'adj_konstan' => 0,
                'id_periode' => $item->periode->id_periode,
                'periode_obj' => $item->periode
            ];
        }
    }

    private function hitungTotalKabKota($kabkota)
    {
        $this->rekonDebugLog('info', 'Debug - hitungTotalKabKota: Kabkota count', ['count' => $kabkota->count()]);

        $total = ['wilayah' => 'TOTAL KABUPATEN/KOTA', 'data' => []];

        if ($kabkota->isEmpty()) {
            $this->rekonDebugLog('warning', 'Debug - hitungTotalKabKota: Kabkota kosong');
            return $total;
        }

        $countData = 0;
        foreach ($kabkota as $row) {
            if (!isset($row['data']) || empty($row['data'])) {
                $this->rekonDebugLog('info', 'Debug - Row tidak memiliki data:', ['wilayah' => $row['wilayah'] ?? '-']);
                continue;
            }

            $countData++;
            $this->rekonDebugLog('info', 'Debug - Processing row data:', [
                'wilayah' => $row['wilayah'] ?? '-',
                'tahun_count' => count($row['data'])
            ]);

            foreach ($row['data'] as $idTahun => $periodeData) {
                foreach ($periodeData as $periodeNama => $nilai) {
                    if (!isset($total['data'][$idTahun][$periodeNama])) {
                        $total['data'][$idTahun][$periodeNama] = [
                            'berlaku' => 0,
                            'konstan' => 0,
                            'adj_berlaku' => 0,
                            'adj_konstan' => 0,
                            'id_periode' => $nilai['id_periode'],
                            'periode_obj' => $nilai['periode_obj']
                        ];
                    }

                    $total['data'][$idTahun][$periodeNama]['berlaku'] += $nilai['berlaku'] ?? 0;
                    $total['data'][$idTahun][$periodeNama]['konstan'] += $nilai['konstan'] ?? 0;
                    $total['data'][$idTahun][$periodeNama]['adj_berlaku'] += $nilai['adj_berlaku'] ?? 0;
                    $total['data'][$idTahun][$periodeNama]['adj_konstan'] += $nilai['adj_konstan'] ?? 0;
                }
            }
        }

        $this->rekonDebugLog('info', 'Debug - hitungTotalKabKota result:', [
            'rows_with_data' => $countData,
            'total_rows' => $kabkota->count(),
            'tahun_count' => count($total['data']),
            'tahun_keys' => array_keys($total['data'])
        ]);

        foreach ($total['data'] as $tahun => $periodeData) {
            $this->rekonDebugLog('info', 'Debug - Tahun ' . $tahun . ' periods:', array_keys($periodeData));
        }

        return $total;
    }

    private function hitungSemuaPerhitungan($provinsi, $totalKabKota, $kabkota, $periodeTriwulan, $tahunIdsCalc, $tahunIdsDisplay)
    {
        $calculations = [
            'provinsi' => [],
            'total_kabkota' => [],
            'kabkota' => [],
            'diskrepansi' => [],
            'diskrepansi_persen' => []
        ];

        $periodeMap = [];
        foreach ($periodeTriwulan as $index => $periode) {
            $periodeMap[$periode->nama_periode] = [
                'index' => $index,
                'obj' => $periode
            ];
        }

        // Data provinsi
        if ($provinsi && !empty($provinsi['data'])) {
            $dataProvinsiFull = $this->getFullDataForCalculations($provinsi, $tahunIdsCalc);
            $this->rekonDebugLog('info', 'Debug - Data Provinsi Full keys:', array_keys($dataProvinsiFull));

            $calculations['provinsi'] = $this->hitungPerWilayah(
                $dataProvinsiFull,
                $periodeTriwulan,
                $tahunIdsCalc,
                $tahunIdsDisplay,
                $periodeMap,
                'provinsi'
            );

            $this->rekonDebugLog('info', 'Debug - Provinsi Calculations tahun:', array_keys($calculations['provinsi']));
        }

        // Data total kabupaten/kota
        $this->rekonDebugLog('info', 'Debug - Checking totalKabKota:', [
            'totalKabKota_exists' => !is_null($totalKabKota),
            'has_data' => isset($totalKabKota['data']) && !empty($totalKabKota['data'])
        ]);

        if ($totalKabKota && !empty($totalKabKota['data'])) {
            $dataTotalKabKotaFull = $this->getFullDataForCalculations($totalKabKota, $tahunIdsCalc);
            $this->rekonDebugLog('info', 'Debug - Data Total KabKota Full:', [
                'tahun_keys' => array_keys($dataTotalKabKotaFull),
                'is_empty' => empty($dataTotalKabKotaFull)
            ]);

            if (!empty($dataTotalKabKotaFull)) {
                $calculations['total_kabkota'] = $this->hitungPerWilayah(
                    $dataTotalKabKotaFull,
                    $periodeTriwulan,
                    $tahunIdsCalc,
                    $tahunIdsDisplay,
                    $periodeMap,
                    'total_kabkota'
                );

                $this->rekonDebugLog('info', 'Debug - Total KabKota Calculations Result:', [
                    'tahun_keys' => array_keys($calculations['total_kabkota']),
                    'has_data' => !empty($calculations['total_kabkota'])
                ]);
            } else {
                $this->rekonDebugLog('warning', 'Debug - Data Total KabKota Full kosong');
            }
        } else {
            $this->rekonDebugLog('warning', 'Debug - TotalKabKota tidak ada atau data kosong', [
                'totalKabKota_exists' => !is_null($totalKabKota),
                'totalKabKota_data_exists' => isset($totalKabKota['data']),
                'totalKabKota_data_count' => isset($totalKabKota['data']) ? count($totalKabKota['data']) : 0
            ]);
        }

        // Data per kabupaten/kota
        $kabkotaCalculationsCount = 0;
        foreach ($kabkota as $row) {
            $dataWilayahFull = $this->getFullDataForCalculations($row, $tahunIdsCalc);
            if (!empty($dataWilayahFull)) {
                $kabkotaCalculationsCount++;
            }
            $calculations['kabkota'][$row['id_wilayah']] = $this->hitungPerWilayah(
                $dataWilayahFull,
                $periodeTriwulan,
                $tahunIdsCalc,
                $tahunIdsDisplay,
                $periodeMap,
                'kabkota'
            );
        }

        $this->rekonDebugLog('info', 'Debug - Kabkota calculations count:', ['count' => $kabkotaCalculationsCount]);

        // Hitung diskrepansi
        if ($provinsi && $totalKabKota && isset($calculations['provinsi']) && isset($calculations['total_kabkota'])) {
            $calculations['diskrepansi'] = $this->hitungDiskrepansi(
                $provinsi,
                $totalKabKota,
                $tahunIdsDisplay
            );

            $calculations['diskrepansi_persen'] = $this->hitungDiskrepansiPersen(
                $provinsi,
                $totalKabKota,
                $periodeTriwulan,
                $tahunIdsDisplay
            );

            $this->rekonDebugLog('info', 'Debug - Diskrepansi calculated:', [
                'has_diskrepansi' => !empty($calculations['diskrepansi']),
                'has_diskrepansi_persen' => !empty($calculations['diskrepansi_persen'])
            ]);
        } else {
            $this->rekonDebugLog('warning', 'Debug - Tidak bisa hitung diskrepansi', [
                'provinsi_exists' => !empty($provinsi),
                'totalKabKota_exists' => !empty($totalKabKota),
                'has_provinsi_calc' => isset($calculations['provinsi']),
                'has_total_kabkota_calc' => isset($calculations['total_kabkota'])
            ]);
        }

        $this->rekonDebugLog('info', 'Debug - Final Calculations Keys:', array_keys($calculations));
        $this->rekonDebugLog('info', 'Debug - Has total_kabkota?', [
            'has_total_kabkota' => isset($calculations['total_kabkota']) && !empty($calculations['total_kabkota'])
        ]);

        return $calculations;
    }

    private function getFullDataForCalculations($wilayahData, $tahunIdsCalc)
    {
        if (!$wilayahData || empty($wilayahData['data'])) {
            $this->rekonDebugLog('warning', 'Debug - getFullDataForCalculations: Data kosong', [
                'wilayahData_exists' => !is_null($wilayahData),
                'data_exists' => isset($wilayahData['data']),
                'data_count' => isset($wilayahData['data']) ? count($wilayahData['data']) : 0
            ]);
            return [];
        }

        $fullData = [];
        foreach ($tahunIdsCalc as $idTahun) {
            if (isset($wilayahData['data'][$idTahun])) {
                $fullData[$idTahun] = $wilayahData['data'][$idTahun];
            } else {
                $fullData[$idTahun] = [];
            }
        }

        $this->rekonDebugLog('info', 'Debug - getFullDataForCalculations result:', [
            'tahunIdsCalc' => $tahunIdsCalc->toArray(),
            'fullData_keys' => array_keys($fullData),
            'fullData_counts' => array_map('count', $fullData)
        ]);

        return $fullData;
    }

    private function hitungPerWilayah($dataWilayahFull, $periodeTriwulan, $tahunIdsCalc, $tahunIdsDisplay, $periodeMap, $tipe = 'kabkota')
    {
        $this->rekonDebugLog('info', 'Debug - hitungPerWilayah ' . $tipe . ': Data masuk', [
            'data_keys' => array_keys($dataWilayahFull),
            'tahunIdsCalc' => $tahunIdsCalc->toArray(),
            'tahunIdsDisplay' => $tahunIdsDisplay->toArray()
        ]);

        $hasil = [];
        $tahunIdsCalcArray = $tahunIdsCalc->toArray();

        // STEP 1: HITUNG SEMUA DATA (VERSI LENGKAP) UNTUK SEMUA 4 TAHUN
        foreach ($tahunIdsCalcArray as $i => $idTahun) {
            $hasil[$idTahun] = [];
            $currentIndex = $i;

            $this->rekonDebugLog('info', 'Debug - Processing tahun ' . $tipe . ':', [
                'id_tahun' => $idTahun,
                'currentIndex' => $currentIndex,
                'has_data_for_year' => isset($dataWilayahFull[$idTahun]) && !empty($dataWilayahFull[$idTahun])
            ]);

            // Hitung data per periode triwulan
            foreach ($periodeTriwulan as $periode) {
                $p = $periode->nama_periode;
                if ($p === 'TOTAL')
                    continue;

                $data = $dataWilayahFull[$idTahun][$p] ?? null;

                if (!$data) {
                    $hasil[$idTahun][$p] = $this->getDefaultPerhitungan();
                    continue;
                }

                $b = $data['berlaku'] ?? 0;
                $k = $data['konstan'] ?? 0;
                $ab = $data['adj_berlaku'] ?? 0;
                $ak = $data['adj_konstan'] ?? 0;

                $bp = $b + $ab;
                $kp = $k + $ak;

                $qoq = $this->hitungQoQData($dataWilayahFull, $idTahun, $p, $periodeTriwulan, $currentIndex, $tahunIdsCalcArray);
                $yoy = $this->hitungYoYData($dataWilayahFull, $idTahun, $p, $tahunIdsCalcArray, $currentIndex);
                $ctoc = $this->hitungCToCData($dataWilayahFull, $idTahun, $p, $tahunIdsCalcArray, $currentIndex, $periodeMap);

                $hasil[$idTahun][$p] = [
                    'berlaku' => $b,
                    'konstan' => $k,
                    'adj_berlaku' => $ab,
                    'adj_konstan' => $ak,
                    'berlaku_plus_adj' => $bp,
                    'konstan_plus_adj' => $kp,
                    'qoq_konstan' => $qoq['qoq_konstan'],
                    'qoq_konstan_plus_adj' => $qoq['qoq_konstan_plus_adj'],
                    'yoy_konstan' => $yoy['yoy_konstan'],
                    'yoy_konstan_plus_adj' => $yoy['yoy_konstan_plus_adj'],
                    'ctoc_konstan' => $ctoc['ctoc_konstan'],
                    'ctoc_konstan_plus_adj' => $ctoc['ctoc_konstan_plus_adj'],
                    'indeks_berlaku' => $this->hitungIndeksImplisit($b, $k),
                    'indeks_berlaku_plus_adj' => $this->hitungIndeksImplisit($bp, $kp),
                    'laju_berlaku' => $qoq['laju_berlaku'],
                    'laju_berlaku_plus_adj' => $qoq['laju_berlaku_plus_adj'],
                    'id_periode' => $data['id_periode'] ?? null,
                    'id_nilai_sub_kategori_adj_berlaku' => $data['id_nilai_sub_kategori_adj_berlaku'] ?? null,
                    'id_nilai_sub_kategori_adj_konstan' => $data['id_nilai_sub_kategori_adj_konstan'] ?? null
                ];
            }

            // HITUNG TOTAL TAHUNAN (SUMMATION OF ALL PERIODS)
            $total = ['berlaku' => 0, 'konstan' => 0, 'adj_berlaku' => 0, 'adj_konstan' => 0];

            foreach ($hasil[$idTahun] as $periodeName => $d) {
                if ($periodeName !== 'TOTAL') {
                    $total['berlaku'] += $d['berlaku'];
                    $total['konstan'] += $d['konstan'];
                    $total['adj_berlaku'] += $d['adj_berlaku'];
                    $total['adj_konstan'] += $d['adj_konstan'];
                }
            }

            $bp = $total['berlaku'] + $total['adj_berlaku'];
            $kp = $total['konstan'] + $total['adj_konstan'];

            $hasil[$idTahun]['TOTAL'] = [
                'berlaku' => $total['berlaku'],
                'konstan' => $total['konstan'],
                'adj_berlaku' => $total['adj_berlaku'],
                'adj_konstan' => $total['adj_konstan'],
                'berlaku_plus_adj' => $bp,
                'konstan_plus_adj' => $kp,
                'indeks_berlaku' => $this->hitungIndeksImplisit($total['berlaku'], $total['konstan']),
                'indeks_berlaku_plus_adj' => $this->hitungIndeksImplisit($bp, $kp),
                'id_periode' => null, // TOTAL bukan periode
                'id_nilai_sub_kategori_adj_berlaku' => null,
                'id_nilai_sub_kategori_adj_konstan' => null
            ];
        }

        // ========== PERBAIKAN UTAMA: HITUNG PERTUMBUHAN UNTUK TOTAL TAHUNAN ==========
        // Khusus untuk TOTAL tahunan, kita hitung pertumbuhan dari tahun sebelumnya
        foreach ($tahunIdsCalcArray as $i => $idTahun) {
            if ($i > 0) { // Ada tahun sebelumnya untuk dibandingkan
                $prevYear = $tahunIdsCalcArray[$i - 1];

                if (isset($hasil[$idTahun]['TOTAL']) && isset($hasil[$prevYear]['TOTAL'])) {
                    $current = $hasil[$idTahun]['TOTAL'];
                    $previous = $hasil[$prevYear]['TOTAL'];

                    // Fungsi untuk menghitung pertumbuhan
                    $growth = function ($currentValue, $previousValue) {
                        if ($previousValue == 0)
                            return 0;
                        return (($currentValue - $previousValue) / $previousValue) * 100;
                    };

                    // Hitung semua pertumbuhan untuk TOTAL tahunan
                    $hasil[$idTahun]['TOTAL']['qoq_konstan'] = $growth($current['konstan'], $previous['konstan']);
                    $hasil[$idTahun]['TOTAL']['qoq_konstan_plus_adj'] = $growth($current['konstan_plus_adj'], $previous['konstan_plus_adj']);
                    $hasil[$idTahun]['TOTAL']['yoy_konstan'] = $growth($current['konstan'], $previous['konstan']);
                    $hasil[$idTahun]['TOTAL']['yoy_konstan_plus_adj'] = $growth($current['konstan_plus_adj'], $previous['konstan_plus_adj']);
                    $hasil[$idTahun]['TOTAL']['ctoc_konstan'] = $growth($current['konstan'], $previous['konstan']);
                    $hasil[$idTahun]['TOTAL']['ctoc_konstan_plus_adj'] = $growth($current['konstan_plus_adj'], $previous['konstan_plus_adj']);

                    // Hitung laju implisit untuk TOTAL
                    $hasil[$idTahun]['TOTAL']['laju_berlaku'] = $growth($current['indeks_berlaku'], $previous['indeks_berlaku']);
                    $hasil[$idTahun]['TOTAL']['laju_berlaku_plus_adj'] = $growth($current['indeks_berlaku_plus_adj'], $previous['indeks_berlaku_plus_adj']);

                    $this->rekonDebugLog('info', 'DEBUG TOTAL GROWTH ' . $tipe, [
                        'tahun' => $idTahun,
                        'prev_tahun' => $prevYear,
                        'qoq_konstan' => $hasil[$idTahun]['TOTAL']['qoq_konstan'],
                        'yoy_konstan' => $hasil[$idTahun]['TOTAL']['yoy_konstan'],
                        'ctoc_konstan' => $hasil[$idTahun]['TOTAL']['ctoc_konstan'],
                        'laju_berlaku' => $hasil[$idTahun]['TOTAL']['laju_berlaku']
                    ]);
                }
            } else {
                // Tahun pertama: set nilai pertumbuhan ke 0
                if (isset($hasil[$idTahun]['TOTAL'])) {
                    $hasil[$idTahun]['TOTAL']['qoq_konstan'] = 0;
                    $hasil[$idTahun]['TOTAL']['qoq_konstan_plus_adj'] = 0;
                    $hasil[$idTahun]['TOTAL']['yoy_konstan'] = 0;
                    $hasil[$idTahun]['TOTAL']['yoy_konstan_plus_adj'] = 0;
                    $hasil[$idTahun]['TOTAL']['ctoc_konstan'] = 0;
                    $hasil[$idTahun]['TOTAL']['ctoc_konstan_plus_adj'] = 0;
                    $hasil[$idTahun]['TOTAL']['laju_berlaku'] = 0;
                    $hasil[$idTahun]['TOTAL']['laju_berlaku_plus_adj'] = 0;
                }
            }
        }

        // Filter hanya tahun yang ditampilkan (3 tahun terakhir)
        $filteredResult = [];
        foreach ($tahunIdsDisplay->toArray() as $displayYear) {
            if (isset($hasil[$displayYear])) {
                $filteredResult[$displayYear] = $hasil[$displayYear];
            }
        }

        $this->rekonDebugLog('info', 'Debug - hitungPerWilayah ' . $tipe . ' result:', [
            'original_tahun_keys' => array_keys($hasil),
            'filtered_tahun_keys' => array_keys($filteredResult),
            'has_TOTAL_in_first_year' => isset($filteredResult[$tahunIdsDisplay->first()]['TOTAL']),
            'TOTAL_values' => isset($filteredResult[$tahunIdsDisplay->first()]['TOTAL'])
                ? [
                    'berlaku' => $filteredResult[$tahunIdsDisplay->first()]['TOTAL']['berlaku'],
                    'konstan' => $filteredResult[$tahunIdsDisplay->first()]['TOTAL']['konstan'],
                    'qoq_konstan' => $filteredResult[$tahunIdsDisplay->first()]['TOTAL']['qoq_konstan'],
                    'yoy_konstan' => $filteredResult[$tahunIdsDisplay->first()]['TOTAL']['yoy_konstan'],
                    'ctoc_konstan' => $filteredResult[$tahunIdsDisplay->first()]['TOTAL']['ctoc_konstan']
                ]
                : []
        ]);

        return $filteredResult;
    }

    private function hitungQoQData($dataWilayahFull, $idTahun, $currentPeriode, $periodeTriwulan, $currentIndex, $tahunIdsCalc)
    {
        $prevPeriode = $this->getPreviousPeriode($currentPeriode, $periodeTriwulan);

        $currentData = $dataWilayahFull[$idTahun][$currentPeriode] ?? null;

        if ($currentPeriode === 'Triwulan I' && $currentIndex > 0) {
            $prevYearId = $tahunIdsCalc[$currentIndex - 1] ?? null;
            if ($prevYearId) {
                $prevData = $dataWilayahFull[$prevYearId]['Triwulan IV'] ?? null;
            } else {
                $prevData = null;
            }
        } else {
            $prevData = $prevPeriode ? ($dataWilayahFull[$idTahun][$prevPeriode] ?? null) : null;
        }

        if (!$currentData || !$prevData) {
            return [
                'qoq_berlaku' => 0,
                'qoq_konstan' => 0,
                'qoq_berlaku_plus_adj' => 0,
                'qoq_konstan_plus_adj' => 0,
                'laju_berlaku' => 0,
                'laju_berlaku_plus_adj' => 0
            ];
        }

        $berlaku = $currentData['berlaku'] ?? 0;
        $konstan = $currentData['konstan'] ?? 0;
        $adjBerlaku = $currentData['adj_berlaku'] ?? 0;
        $adjKonstan = $currentData['adj_konstan'] ?? 0;
        $berlakuPlusAdj = $berlaku + $adjBerlaku;
        $konstanPlusAdj = $konstan + $adjKonstan;

        $prevBerlaku = $prevData['berlaku'] ?? 0;
        $prevKonstan = $prevData['konstan'] ?? 0;
        $prevAdjBerlaku = $prevData['adj_berlaku'] ?? 0;
        $prevAdjKonstan = $prevData['adj_konstan'] ?? 0;
        $prevBerlakuPlusAdj = $prevBerlaku + $prevAdjBerlaku;
        $prevKonstanPlusAdj = $prevKonstan + $prevAdjKonstan;

        $indeksBerlaku = $this->hitungIndeksImplisit($berlaku, $konstan);
        $indeksBerlakuPlusAdj = $this->hitungIndeksImplisit($berlakuPlusAdj, $konstanPlusAdj);
        $prevIndeksBerlaku = $this->hitungIndeksImplisit($prevBerlaku, $prevKonstan);
        $prevIndeksBerlakuPlusAdj = $this->hitungIndeksImplisit($prevBerlakuPlusAdj, $prevKonstanPlusAdj);

        return [
            'qoq_berlaku' => $this->hitungQoQ($berlaku, $prevBerlaku),
            'qoq_konstan' => $this->hitungQoQ($konstan, $prevKonstan),
            'qoq_berlaku_plus_adj' => $this->hitungQoQ($berlakuPlusAdj, $prevBerlakuPlusAdj),
            'qoq_konstan_plus_adj' => $this->hitungQoQ($konstanPlusAdj, $prevKonstanPlusAdj),
            'laju_berlaku' => $this->hitungLajuImplisit($indeksBerlaku, $prevIndeksBerlaku),
            'laju_berlaku_plus_adj' => $this->hitungLajuImplisit($indeksBerlakuPlusAdj, $prevIndeksBerlakuPlusAdj)
        ];
    }

    private function hitungYoYData($dataWilayahFull, $idTahun, $currentPeriode, $tahunIdsCalc, $currentIndex)
    {
        if ($currentIndex === 0) {
            return [
                'yoy_berlaku' => 0,
                'yoy_konstan' => 0,
                'yoy_berlaku_plus_adj' => 0,
                'yoy_konstan_plus_adj' => 0
            ];
        }

        $prevYearId = $tahunIdsCalc[$currentIndex - 1] ?? null;

        if (!$prevYearId) {
            return [
                'yoy_berlaku' => 0,
                'yoy_konstan' => 0,
                'yoy_berlaku_plus_adj' => 0,
                'yoy_konstan_plus_adj' => 0
            ];
        }

        $currentData = $dataWilayahFull[$idTahun][$currentPeriode] ?? null;
        $prevYearData = $dataWilayahFull[$prevYearId][$currentPeriode] ?? null;

        if (!$currentData || !$prevYearData) {
            return [
                'yoy_berlaku' => 0,
                'yoy_konstan' => 0,
                'yoy_berlaku_plus_adj' => 0,
                'yoy_konstan_plus_adj' => 0
            ];
        }

        $berlaku = $currentData['berlaku'] ?? 0;
        $konstan = $currentData['konstan'] ?? 0;
        $adjBerlaku = $currentData['adj_berlaku'] ?? 0;
        $adjKonstan = $currentData['adj_konstan'] ?? 0;
        $berlakuPlusAdj = $berlaku + $adjBerlaku;
        $konstanPlusAdj = $konstan + $adjKonstan;

        $prevBerlaku = $prevYearData['berlaku'] ?? 0;
        $prevKonstan = $prevYearData['konstan'] ?? 0;
        $prevAdjBerlaku = $prevYearData['adj_berlaku'] ?? 0;
        $prevAdjKonstan = $prevYearData['adj_konstan'] ?? 0;
        $prevBerlakuPlusAdj = $prevBerlaku + $prevAdjBerlaku;
        $prevKonstanPlusAdj = $prevKonstan + $prevAdjKonstan;

        return [
            'yoy_berlaku' => $this->hitungYoY($berlaku, $prevBerlaku),
            'yoy_konstan' => $this->hitungYoY($konstan, $prevKonstan),
            'yoy_berlaku_plus_adj' => $this->hitungYoY($berlakuPlusAdj, $prevBerlakuPlusAdj),
            'yoy_konstan_plus_adj' => $this->hitungYoY($konstanPlusAdj, $prevKonstanPlusAdj)
        ];
    }

    private function hitungCToCData($dataWilayahFull, $idTahun, $currentPeriode, $tahunIdsCalc, $currentIndex, $periodeMap)
    {
        if ($currentIndex === 0) {
            return [
                'ctoc_berlaku' => 0,
                'ctoc_konstan' => 0,
                'ctoc_berlaku_plus_adj' => 0,
                'ctoc_konstan_plus_adj' => 0
            ];
        }

        $prevYearId = $tahunIdsCalc[$currentIndex - 1] ?? null;

        if (!$prevYearId) {
            return [
                'ctoc_berlaku' => 0,
                'ctoc_konstan' => 0,
                'ctoc_berlaku_plus_adj' => 0,
                'ctoc_konstan_plus_adj' => 0
            ];
        }

        $currentCumulative = $this->hitungAkumulasiTriwulan($dataWilayahFull, $idTahun, $currentPeriode, $periodeMap);
        $prevCumulative = $this->hitungAkumulasiTriwulan($dataWilayahFull, $prevYearId, $currentPeriode, $periodeMap);

        return [
            'ctoc_berlaku' => $this->hitungCToC($currentCumulative['berlaku'], $prevCumulative['berlaku']),
            'ctoc_konstan' => $this->hitungCToC($currentCumulative['konstan'], $prevCumulative['konstan']),
            'ctoc_berlaku_plus_adj' => $this->hitungCToC($currentCumulative['berlaku_plus_adj'], $prevCumulative['berlaku_plus_adj']),
            'ctoc_konstan_plus_adj' => $this->hitungCToC($currentCumulative['konstan_plus_adj'], $prevCumulative['konstan_plus_adj'])
        ];
    }

    private function hitungAkumulasiTriwulan($dataWilayahFull, $idTahun, $currentPeriode, $periodeMap)
    {
        $currentIndex = $periodeMap[$currentPeriode]['index'] ?? -1;
        if ($currentIndex === -1) {
            return [
                'berlaku' => 0,
                'konstan' => 0,
                'berlaku_plus_adj' => 0,
                'konstan_plus_adj' => 0
            ];
        }

        $totalBerlaku = 0;
        $totalKonstan = 0;
        $totalBerlakuPlusAdj = 0;
        $totalKonstanPlusAdj = 0;

        foreach ($periodeMap as $periodeNama => $info) {
            if ($info['index'] <= $currentIndex) {
                $data = $dataWilayahFull[$idTahun][$periodeNama] ?? null;
                if ($data) {
                    $totalBerlaku += $data['berlaku'] ?? 0;
                    $totalKonstan += $data['konstan'] ?? 0;
                    $totalBerlakuPlusAdj += ($data['berlaku'] ?? 0) + ($data['adj_berlaku'] ?? 0);
                    $totalKonstanPlusAdj += ($data['konstan'] ?? 0) + ($data['adj_konstan'] ?? 0);
                }
            }
        }

        return [
            'berlaku' => $totalBerlaku,
            'konstan' => $totalKonstan,
            'berlaku_plus_adj' => $totalBerlakuPlusAdj,
            'konstan_plus_adj' => $totalKonstanPlusAdj
        ];
    }

    private function getDefaultPerhitungan()
    {
        return [
            'berlaku' => 0,
            'konstan' => 0,
            'adj_berlaku' => 0,
            'adj_konstan' => 0,
            'berlaku_plus_adj' => 0,
            'konstan_plus_adj' => 0,
            'qoq_berlaku' => 0,
            'qoq_konstan' => 0,
            'qoq_berlaku_plus_adj' => 0,
            'qoq_konstan_plus_adj' => 0,
            'yoy_berlaku' => 0,
            'yoy_konstan' => 0,
            'yoy_berlaku_plus_adj' => 0,
            'yoy_konstan_plus_adj' => 0,
            'ctoc_berlaku' => 0,
            'ctoc_konstan' => 0,
            'ctoc_berlaku_plus_adj' => 0,
            'ctoc_konstan_plus_adj' => 0,
            'indeks_berlaku' => 0,
            'indeks_berlaku_plus_adj' => 0,
            'laju_berlaku' => 0,
            'laju_berlaku_plus_adj' => 0,
            'id_periode' => null,
            'id_nilai_sub_kategori_adj_berlaku' => null,
            'id_nilai_sub_kategori_adj_konstan' => null
        ];
    }

    private function getPreviousPeriode($currentPeriode, $periodeTriwulan)
    {
        $periodNames = $periodeTriwulan->pluck('nama_periode')->toArray();
        $currentIndex = array_search($currentPeriode, $periodNames);

        if ($currentIndex > 0) {
            return $periodNames[$currentIndex - 1];
        }

        return null;
    }

    private function hitungDiskrepansi($provinsi, $totalKabKota, $tahunIdsDisplay)
    {
        $hasil = [];

        if (!$provinsi || empty($provinsi['data']))
            return $hasil;

        foreach ($tahunIdsDisplay as $idTahun) {
            if (!isset($provinsi['data'][$idTahun]))
                continue;

            foreach ($provinsi['data'][$idTahun] as $periodeNama => $prov) {
                $total = $totalKabKota['data'][$idTahun][$periodeNama] ?? null;

                $provPdrbBerlaku = $prov['berlaku'] ?? 0;
                $totPdrbBerlaku = $total['berlaku'] ?? 0;
                $diskrepansiPdrbBerlaku = $totPdrbBerlaku - $provPdrbBerlaku;

                $provPdrbKonstan = $prov['konstan'] ?? 0;
                $totPdrbKonstan = $total['konstan'] ?? 0;
                $diskrepansiPdrbKonstan = $totPdrbKonstan - $provPdrbKonstan;

                $provBerlakuPlusAdj = ($prov['berlaku'] ?? 0) + ($prov['adj_berlaku'] ?? 0);
                $totBerlakuPlusAdj = ($total['berlaku'] ?? 0) + ($total['adj_berlaku'] ?? 0);
                $diskrepansiBerlakuPlusAdj = $totBerlakuPlusAdj - $provBerlakuPlusAdj;

                $provKonstanPlusAdj = ($prov['konstan'] ?? 0) + ($prov['adj_konstan'] ?? 0);
                $totKonstanPlusAdj = ($total['konstan'] ?? 0) + ($total['adj_konstan'] ?? 0);
                $diskrepansiKonstanPlusAdj = $totKonstanPlusAdj - $provKonstanPlusAdj;

                $hasil[$idTahun][$periodeNama] = [
                    'pdrb_berlaku' => round($diskrepansiPdrbBerlaku, 9),
                    'pdrb_konstan' => round($diskrepansiPdrbKonstan, 9),
                    'pdrb_adj_berlaku' => round($diskrepansiBerlakuPlusAdj, 9),
                    'pdrb_adj_konstan' => round($diskrepansiKonstanPlusAdj, 9),
                    'prov_pdrb_berlaku' => $provPdrbBerlaku,
                    'total_pdrb_berlaku' => $totPdrbBerlaku,
                    'prov_berlaku_plus_adj' => $provBerlakuPlusAdj,
                    'total_berlaku_plus_adj' => $totBerlakuPlusAdj,
                ];
            }
        }

        return $hasil;
    }

    private function hitungDiskrepansiPersen($provinsi, $totalKabKota, $periodeTriwulan, $tahunIdsDisplay)
    {
        $hasil = [];

        if (!$provinsi || !$totalKabKota)
            return $hasil;

        foreach ($tahunIdsDisplay as $idTahun) {
            foreach ($periodeTriwulan as $periode) {
                $periodeNama = $periode->nama_periode;

                $dataProv = $provinsi['data'][$idTahun][$periodeNama] ?? null;
                $dataTotal = $totalKabKota['data'][$idTahun][$periodeNama] ?? null;

                if (!$dataProv || !$dataTotal)
                    continue;

                $pdrbProvBerlaku = $dataProv['berlaku'] ?? 0;
                $pdrbTotalBerlaku = $dataTotal['berlaku'] ?? 0;
                $diskrepansiPersenPdrbBerlaku = $pdrbProvBerlaku != 0 ?
                    (($pdrbTotalBerlaku - $pdrbProvBerlaku) / $pdrbProvBerlaku) * 100 : 0;

                $adjProvBerlaku = $dataProv['adj_berlaku'] ?? 0;
                $adjTotalBerlaku = $dataTotal['adj_berlaku'] ?? 0;
                $pdrbPlusAdjProvBerlaku = $pdrbProvBerlaku + $adjProvBerlaku;
                $pdrbPlusAdjTotalBerlaku = $pdrbTotalBerlaku + $adjTotalBerlaku;
                $diskrepansiPersenPdrbAdjBerlaku = $pdrbPlusAdjProvBerlaku != 0 ?
                    (($pdrbPlusAdjTotalBerlaku - $pdrbPlusAdjProvBerlaku) / $pdrbPlusAdjProvBerlaku) * 100 : 0;

                $pdrbProvKonstan = $dataProv['konstan'] ?? 0;
                $pdrbTotalKonstan = $dataTotal['konstan'] ?? 0;
                $diskrepansiPersenPdrbKonstan = $pdrbProvKonstan != 0 ?
                    (($pdrbTotalKonstan - $pdrbProvKonstan) / $pdrbProvKonstan) * 100 : 0;

                $adjProvKonstan = $dataProv['adj_konstan'] ?? 0;
                $adjTotalKonstan = $dataTotal['adj_konstan'] ?? 0;
                $pdrbPlusAdjProvKonstan = $pdrbProvKonstan + $adjProvKonstan;
                $pdrbPlusAdjTotalKonstan = $pdrbTotalKonstan + $adjTotalKonstan;
                $diskrepansiPersenPdrbAdjKonstan = $pdrbPlusAdjProvKonstan != 0 ?
                    (($pdrbPlusAdjTotalKonstan - $pdrbPlusAdjProvKonstan) / $pdrbPlusAdjProvKonstan) * 100 : 0;

                $hasil[$idTahun][$periodeNama] = [
                    'pdrb_berlaku' => $diskrepansiPersenPdrbBerlaku,
                    'pdrb_adj_berlaku' => $diskrepansiPersenPdrbAdjBerlaku,
                    'pdrb_konstan' => $diskrepansiPersenPdrbKonstan,
                    'pdrb_adj_konstan' => $diskrepansiPersenPdrbAdjKonstan,
                    'prov_pdrb_berlaku' => $pdrbProvBerlaku,
                    'total_pdrb_berlaku' => $pdrbTotalBerlaku,
                    'prov_berlaku_plus_adj' => $pdrbPlusAdjProvBerlaku,
                    'total_berlaku_plus_adj' => $pdrbPlusAdjTotalBerlaku,
                ];
            }
        }

        return $hasil;
    }

    private function hitungQoQ($current, $prev)
    {
        if ($prev == 0)
            return 0;
        return (($current - $prev) / $prev) * 100;
    }

    private function hitungYoY($current, $lastYear)
    {
        if ($lastYear == 0)
            return 0;
        return (($current - $lastYear) / $lastYear) * 100;
    }

    private function hitungCToC($current, $lastYear)
    {
        if ($lastYear == 0)
            return 0;
        return (($current - $lastYear) / $lastYear) * 100;
    }

    private function hitungIndeksImplisit($berlaku, $konstan)
    {
        if ($konstan == 0)
            return 0;
        return ($berlaku / $konstan) * 100;
    }

    private function hitungLajuImplisit($currentIndex, $prevIndex)
    {
        if ($prevIndex == 0)
            return 0;
        return (($currentIndex - $prevIndex) / $prevIndex) * 100;
    }

    public function updateAdj(Request $request)
    {
        $request->validate([
            'id_wilayah' => 'required',
            'nilai' => 'nullable|numeric',
            'id_sub_kategori' => 'required_without:id_kategori',
            'id_kategori' => 'required_without:id_sub_kategori'
        ]);

        if ($request->filled('id_kategori') && (int) $request->id_kategori === 28) {
            $pendekatanKategori = $this->resolvePendekatan($request->id_kategori, $request->id_sub_kategori);
            if ($pendekatanKategori === 'pengeluaran') {
                return response()->json(['error' => 'LOCK'], 403);
            }
        }

        $lockType = $request->filled('id_kategori') ? 'kategori' : 'subkategori';
        $lockEntityId = (int) ($request->id_kategori ?? $request->id_sub_kategori);
        $lockPendekatan = $this->resolvePendekatan($request->id_kategori, $request->id_sub_kategori);
        if ($this->getRekonP1LockStatus($lockType, $lockEntityId, $lockPendekatan) && in_array(auth()->user()->role, ['kabupaten', 'kota'])) {
            return response()->json(['error' => 'LOCKED'], 423);
        }

        $user = auth()->user();

        if ($user->role === 'kabupaten') {
            if ($request->id_wilayah != $user->id_wilayah) {
                return response()->json(['error' => 'LOCK'], 403);
            }
        }

        if ($request->id_periode === 'total') {
            return response()->json(['error' => 'TOTAL tidak bisa diubah'], 403);
        }

        $adj = $request->nilai ?? 0;
        $adj = is_numeric($adj) ? (float) $adj : 0.0;

        // Simpan nilai rekonsiliasi sebagai PDRB+Adj (total), bukan nilai adj saja.
        $base = 0.0;
        if ($request->filled('id_kategori')) {
            $base = (float) (NilaiKategori::where([
                'id_kategori' => $request->id_kategori,
                'id_wilayah' => $request->id_wilayah,
                'id_tahun' => $request->id_tahun,
                'id_periode' => $request->id_periode,
                'tipe_pdrb' => $request->tipe,
                'tahap_data' => 'awal',
            ])->value('nilai') ?? 0);
        } else {
            $base = (float) (NilaiSubKategori::where([
                'id_sub_kategori' => $request->id_sub_kategori,
                'id_wilayah' => $request->id_wilayah,
                'id_tahun' => $request->id_tahun,
                'id_periode' => $request->id_periode,
                'tipe_pdrb' => $request->tipe,
                'tahap_data' => 'awal',
            ])->value('nilai') ?? 0);
        }

        $nilai = $base + $adj;

        if ($request->filled('id_kategori')) {
            $old = NilaiKategori::where([
                'id_kategori' => $request->id_kategori,
                'id_wilayah' => $request->id_wilayah,
                'id_tahun' => $request->id_tahun,
                'id_periode' => $request->id_periode,
                'tipe_pdrb' => $request->tipe,
                'tahap_data' => 'rekonsiliasi',
            ])->first();
        } else {
            $old = NilaiSubKategori::where([
                'id_sub_kategori' => $request->id_sub_kategori,
                'id_wilayah' => $request->id_wilayah,
                'id_tahun' => $request->id_tahun,
                'id_periode' => $request->id_periode,
                'tipe_pdrb' => $request->tipe,
                'tahap_data' => 'rekonsiliasi',
            ])->first();
        }

        if (!$old) {
            if ($request->filled('id_kategori')) {
                $old = NilaiKategori::create([
                    'id_kategori' => $request->id_kategori,
                    'id_wilayah' => $request->id_wilayah,
                    'id_tahun' => $request->id_tahun,
                    'id_periode' => $request->id_periode,
                    'tipe_pdrb' => $request->tipe,
                    'tahap_data' => 'rekonsiliasi',
                    'nilai' => $nilai,
                ]);

                DB::table('nilai_kategori_log')->insert([
                    'id_nilai_kategori' => $old->id_nilai_kategori,
                    'nilai_lama' => $base,
                    'nilai_baru' => $nilai,
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                ]);
            } else {
                $old = NilaiSubKategori::create([
                    'id_sub_kategori' => $request->id_sub_kategori,
                    'id_wilayah' => $request->id_wilayah,
                    'id_tahun' => $request->id_tahun,
                    'id_periode' => $request->id_periode,
                    'tipe_pdrb' => $request->tipe,
                    'tahap_data' => 'rekonsiliasi',
                    'nilai' => $nilai,
                    'updated_by' => auth()->id(),
                ]);

                DB::table('nilai_sub_kategori_log')->insert([
                    'id_nilai_sub_kategori' => $old->id_nilai_sub_kategori,
                    'nilai_lama' => $base,
                    'nilai_baru' => $nilai,
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                ]);
            }

            $historyId = ($old instanceof NilaiKategori)
                ? $old->id_nilai_kategori
                : $old->id_nilai_sub_kategori;

            $type = $request->filled('id_kategori') ? 'kategori' : 'subkategori';
            $entityId = (int) ($request->id_kategori ?? $request->id_sub_kategori);
            $pendekatan = $this->resolvePendekatan($request->id_kategori, $request->id_sub_kategori);

            if (!$request->filled('id_kategori')) {
                $this->recalcPdrbTotalsFromSubChange(
                    $pendekatan,
                    (int) $request->id_wilayah,
                    (int) $request->id_tahun,
                    (int) $request->id_periode,
                    (string) $request->tipe,
                    auth()->id()
                );
            }

            try {
                broadcast(new RekonP1Updated(
                    $type,
                    $entityId,
                    (int) $request->id_wilayah,
                    (int) $request->id_tahun,
                    (int) $request->id_periode,
                    (string) $request->tipe,
                    (float) $adj,
                    $historyId,
                    $pendekatan,
                    auth()->id()
                ))->toOthers();
            } catch (\Throwable $e) {
                \Log::warning('RekonP1 broadcast failed (create)', [
                    'error' => $e->getMessage(),
                    'id_wilayah' => $request->id_wilayah,
                    'id_tahun' => $request->id_tahun,
                    'id_periode' => $request->id_periode,
                    'tipe' => $request->tipe,
                ]);
            }

            return response()->json([
                'status' => 'created',
                'id' => $historyId,
                'type' => ($old instanceof NilaiKategori) ? 'kategori' : 'subkategori'
            ]);
        }

        if ($old->nilai != $nilai) {
            if ($old instanceof NilaiSubKategori) {
                DB::table('nilai_sub_kategori_log')->insert([
                    'id_nilai_sub_kategori' => $old->id_nilai_sub_kategori,
                    'nilai_lama' => $old->nilai,
                    'nilai_baru' => $nilai,
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                ]);
            } elseif ($old instanceof NilaiKategori) {
                DB::table('nilai_kategori_log')->insert([
                    'id_nilai_kategori' => $old->id_nilai_kategori,
                    'nilai_lama' => $old->nilai,
                    'nilai_baru' => $nilai,
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                ]);
            }
        }

        $old->nilai = $nilai;
        if ($old instanceof NilaiSubKategori) {
            $old->updated_by = auth()->id();
        }
        $old->save();

        $historyId = ($old instanceof NilaiKategori)
            ? $old->id_nilai_kategori
            : $old->id_nilai_sub_kategori;

        $type = $request->filled('id_kategori') ? 'kategori' : 'subkategori';
        $entityId = (int) ($request->id_kategori ?? $request->id_sub_kategori);
        $pendekatan = $this->resolvePendekatan($request->id_kategori, $request->id_sub_kategori);

        if (!$request->filled('id_kategori')) {
            $this->recalcPdrbTotalsFromSubChange(
                $pendekatan,
                (int) $request->id_wilayah,
                (int) $request->id_tahun,
                (int) $request->id_periode,
                (string) $request->tipe,
                auth()->id()
            );
        }

        try {
            broadcast(new RekonP1Updated(
                $type,
                $entityId,
                (int) $request->id_wilayah,
                (int) $request->id_tahun,
                (int) $request->id_periode,
                (string) $request->tipe,
                (float) $adj,
                $historyId,
                $pendekatan,
                auth()->id()
            ))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('RekonP1 broadcast failed (update)', [
                'error' => $e->getMessage(),
                'id_wilayah' => $request->id_wilayah,
                'id_tahun' => $request->id_tahun,
                'id_periode' => $request->id_periode,
                'tipe' => $request->tipe,
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'id' => $historyId,
            'type' => ($old instanceof NilaiKategori) ? 'kategori' : 'subkategori'
        ]);
    }

    private function recalcPdrbTotalsFromSubChange(
        string $pendekatan,
        int $wilayahId,
        int $tahunId,
        int $periodeId,
        string $tipePdrb,
        ?int $userId
    ): void {
        $targetIds = $this->getPdrbTotalKategoriIds($pendekatan);
        if (empty($targetIds)) {
            return;
        }

        $validTargetIds = Kategori::whereIn('id_kategori', $targetIds)
            ->where('pendekatan', $pendekatan)
            ->pluck('id_kategori')
            ->toArray();

        if (empty($validTargetIds)) {
            return;
        }

        $subByKategori = SubKategori::whereIn('id_kategori', $validTargetIds)
            ->get(['id_sub_kategori', 'id_kategori'])
            ->groupBy('id_kategori');

        $fallbackTotal = null;
        foreach ($validTargetIds as $kategoriId) {
            $subIds = $subByKategori[$kategoriId] ?? collect();
            if ($subIds->isNotEmpty()) {
                $total = $this->sumEffectiveSubKategoriByIds(
                    $subIds->pluck('id_sub_kategori')->toArray(),
                    $wilayahId,
                    $tahunId,
                    $periodeId,
                    $tipePdrb
                );
            } else {
                if ($fallbackTotal === null) {
                    $fallbackTotal = $this->sumEffectiveSubKategoriByPendekatan(
                        $pendekatan,
                        $validTargetIds,
                        $wilayahId,
                        $tahunId,
                        $periodeId,
                        $tipePdrb
                    );
                }
                $total = $fallbackTotal;
            }

            $this->upsertKategoriRekonsiliasiTotal(
                $kategoriId,
                $wilayahId,
                $tahunId,
                $periodeId,
                $tipePdrb,
                $total,
                $userId
            );
        }
    }

    private function getPdrbTotalKategoriIds(string $pendekatan): array
    {
        if ($pendekatan === 'lapangan_usaha') {
            return [21, 22];
        }
        if ($pendekatan === 'pengeluaran') {
            return [31, 32];
        }
        return [];
    }

    private function sumEffectiveSubKategoriByIds(
        array $subIds,
        int $wilayahId,
        int $tahunId,
        int $periodeId,
        string $tipePdrb
    ): float {
        if (empty($subIds)) {
            return 0.0;
        }

        $total = DB::table('sub_kategori as s')
            ->leftJoin('nilai_sub_kategori as nr', function ($join) use ($wilayahId, $tahunId, $periodeId, $tipePdrb) {
                $join->on('nr.id_sub_kategori', '=', 's.id_sub_kategori')
                    ->where('nr.tahap_data', 'rekonsiliasi')
                    ->where('nr.id_wilayah', $wilayahId)
                    ->where('nr.id_tahun', $tahunId)
                    ->where('nr.id_periode', $periodeId)
                    ->where('nr.tipe_pdrb', $tipePdrb);
            })
            ->leftJoin('nilai_sub_kategori as na', function ($join) use ($wilayahId, $tahunId, $periodeId, $tipePdrb) {
                $join->on('na.id_sub_kategori', '=', 's.id_sub_kategori')
                    ->where('na.tahap_data', 'awal')
                    ->where('na.id_wilayah', $wilayahId)
                    ->where('na.id_tahun', $tahunId)
                    ->where('na.id_periode', $periodeId)
                    ->where('na.tipe_pdrb', $tipePdrb);
            })
            ->whereIn('s.id_sub_kategori', $subIds)
            ->selectRaw('SUM(COALESCE(nr.nilai, na.nilai, 0)) as total')
            ->value('total');

        return (float) ($total ?? 0);
    }

    private function sumEffectiveSubKategoriByPendekatan(
        string $pendekatan,
        array $excludeKategoriIds,
        int $wilayahId,
        int $tahunId,
        int $periodeId,
        string $tipePdrb
    ): float {
        $query = DB::table('sub_kategori as s')
            ->join('kategori as k', 'k.id_kategori', '=', 's.id_kategori')
            ->leftJoin('nilai_sub_kategori as nr', function ($join) use ($wilayahId, $tahunId, $periodeId, $tipePdrb) {
                $join->on('nr.id_sub_kategori', '=', 's.id_sub_kategori')
                    ->where('nr.tahap_data', 'rekonsiliasi')
                    ->where('nr.id_wilayah', $wilayahId)
                    ->where('nr.id_tahun', $tahunId)
                    ->where('nr.id_periode', $periodeId)
                    ->where('nr.tipe_pdrb', $tipePdrb);
            })
            ->leftJoin('nilai_sub_kategori as na', function ($join) use ($wilayahId, $tahunId, $periodeId, $tipePdrb) {
                $join->on('na.id_sub_kategori', '=', 's.id_sub_kategori')
                    ->where('na.tahap_data', 'awal')
                    ->where('na.id_wilayah', $wilayahId)
                    ->where('na.id_tahun', $tahunId)
                    ->where('na.id_periode', $periodeId)
                    ->where('na.tipe_pdrb', $tipePdrb);
            })
            ->where('k.pendekatan', $pendekatan);

        if (!empty($excludeKategoriIds)) {
            $query->whereNotIn('k.id_kategori', $excludeKategoriIds);
        }

        $total = $query
            ->selectRaw('SUM(COALESCE(nr.nilai, na.nilai, 0)) as total')
            ->value('total');

        return (float) ($total ?? 0);
    }

    private function upsertKategoriRekonsiliasiTotal(
        int $kategoriId,
        int $wilayahId,
        int $tahunId,
        int $periodeId,
        string $tipePdrb,
        float $nilai,
        ?int $userId
    ): void {
        $now = now();
        $existing = NilaiKategori::where([
            'id_kategori' => $kategoriId,
            'id_wilayah' => $wilayahId,
            'id_tahun' => $tahunId,
            'id_periode' => $periodeId,
            'tipe_pdrb' => $tipePdrb,
            'tahap_data' => 'rekonsiliasi',
        ])->first();

        if (!$existing) {
            $existing = NilaiKategori::create([
                'id_kategori' => $kategoriId,
                'id_wilayah' => $wilayahId,
                'id_tahun' => $tahunId,
                'id_periode' => $periodeId,
                'tipe_pdrb' => $tipePdrb,
                'tahap_data' => 'rekonsiliasi',
                'nilai' => $nilai,
                'updated_at' => $now,
            ]);

            DB::table('nilai_kategori_log')->insert([
                'id_nilai_kategori' => $existing->id_nilai_kategori,
                'nilai_lama' => 0,
                'nilai_baru' => $nilai,
                'updated_by' => $userId,
                'created_at' => $now,
            ]);
            return;
        }

        if ((float) $existing->nilai !== (float) $nilai) {
            DB::table('nilai_kategori_log')->insert([
                'id_nilai_kategori' => $existing->id_nilai_kategori,
                'nilai_lama' => $existing->nilai,
                'nilai_baru' => $nilai,
                'updated_by' => $userId,
                'created_at' => $now,
            ]);
        }

        $existing->nilai = $nilai;
        $existing->updated_at = $now;
        $existing->save();
    }

    private function resolvePendekatan($idKategori, $idSubKategori): string
    {
        if ($idKategori) {
            return Kategori::where('id_kategori', $idKategori)->value('pendekatan') ?? 'lapangan_usaha';
        }

        $sub = SubKategori::with('kategori')->find($idSubKategori);
        return $sub?->kategori?->pendekatan ?? 'lapangan_usaha';
    }

    public function history($id)
    {
        $type = request()->query('type', 'subkategori');

        if ($type === 'kategori') {
            $recon = DB::table('nilai_kategori')->where('id_nilai_kategori', $id)->first();
            if (!$recon) {
                return response()->json([]);
            }

            $baseVal = DB::table('nilai_kategori')
                ->where([
                    'id_kategori' => $recon->id_kategori,
                    'id_wilayah' => $recon->id_wilayah,
                    'id_tahun' => $recon->id_tahun,
                    'id_periode' => $recon->id_periode,
                    'tipe_pdrb' => $recon->tipe_pdrb,
                    'tahap_data' => 'awal',
                ])->value('nilai') ?? 0.0;

            return DB::table('nilai_kategori_log as l')
                ->leftJoin('users as u', 'u.id', '=', 'l.updated_by')
                ->where('id_nilai_kategori', $id)
                ->orderBy('created_at', 'desc')
                ->select('l.*', 'u.name')
                ->get()
                ->map(function ($log) use ($baseVal) {
                    $log->nilai_adj = ((float)$log->nilai_baru) - ((float)$baseVal);
                    return $log;
                });
        }

        $recon = DB::table('nilai_sub_kategori')->where('id_nilai_sub_kategori', $id)->first();
        if (!$recon) {
            return response()->json([]);
        }

        $baseVal = DB::table('nilai_sub_kategori')
            ->where([
                'id_sub_kategori' => $recon->id_sub_kategori,
                'id_wilayah' => $recon->id_wilayah,
                'id_tahun' => $recon->id_tahun,
                'id_periode' => $recon->id_periode,
                'tipe_pdrb' => $recon->tipe_pdrb,
                'tahap_data' => 'awal',
            ])->value('nilai') ?? 0.0;

        return DB::table('nilai_sub_kategori_log as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.updated_by')
            ->where('id_nilai_sub_kategori', $id)
            ->orderBy('created_at', 'desc')
            ->select('l.*', 'u.name')
            ->get()
            ->map(function ($log) use ($baseVal) {
                $log->nilai_adj = ((float)$log->nilai_baru) - ((float)$baseVal);
                return $log;
            });
    }

    public function pollUpdates(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'type' => 'required|in:subkategori,kategori',
            'since' => 'nullable',
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        $type = $request->query('type', 'subkategori');
        $entityId = (int) $request->query('id');
        $limit = (int) $request->query('limit', 200);
        if ($limit < 1) {
            $limit = 200;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        $sinceAt = $this->parsePollingSince($request->query('since'));
        $pendekatan = $request->query('pendekatan', 'lapangan_usaha');
        if (!in_array($pendekatan, ['lapangan_usaha', 'pengeluaran'])) {
            $pendekatan = 'lapangan_usaha';
        }

        if ($type === 'kategori' && $entityId === 28 && $pendekatan === 'pengeluaran') {
            return $this->pollNetExportUpdates($sinceAt, $limit, $pendekatan);
        }

        if ($type === 'kategori') {
            $logs = DB::table('nilai_kategori_log as l')
                ->join('nilai_kategori as n', 'n.id_nilai_kategori', '=', 'l.id_nilai_kategori')
                ->where('n.id_kategori', $entityId)
                ->where('n.tahap_data', 'rekonsiliasi')
                ->where('l.created_at', '>', $sinceAt)
                ->orderBy('l.created_at', 'asc')
                ->limit($limit)
                ->get([
                    'n.id_nilai_kategori',
                    'n.id_wilayah',
                    'n.id_tahun',
                    'n.id_periode',
                    'n.tipe_pdrb',
                    'n.nilai',
                    'l.updated_by',
                    'l.created_at',
                ]);
        } else {
            $logs = DB::table('nilai_sub_kategori_log as l')
                ->join('nilai_sub_kategori as n', 'n.id_nilai_sub_kategori', '=', 'l.id_nilai_sub_kategori')
                ->where('n.id_sub_kategori', $entityId)
                ->where('n.tahap_data', 'rekonsiliasi')
                ->where('l.created_at', '>', $sinceAt)
                ->orderBy('l.created_at', 'asc')
                ->limit($limit)
                ->get([
                    'n.id_nilai_sub_kategori',
                    'n.id_wilayah',
                    'n.id_tahun',
                    'n.id_periode',
                    'n.tipe_pdrb',
                    'n.nilai',
                    'l.updated_by',
                    'l.created_at',
                ]);
        }

        if ($logs->isEmpty()) {
            return response()->json([
                'status' => 'ok',
                'server_time_ms' => now()->valueOf(),
                'locked' => $this->getRekonP1LockStatus($type, $entityId, $pendekatan),
                'updates' => [],
            ]);
        }

        $baseMap = $this->buildPollingBaseMap($type, $entityId, $logs);

        $updates = $logs->map(function ($row) use ($type, $entityId, $baseMap) {
            $key = $row->id_wilayah . '|' . $row->id_tahun . '|' . $row->id_periode . '|' . $row->tipe_pdrb;
            $base = $baseMap[$key] ?? 0;
            $adj = ((float) $row->nilai) - $base;

            return [
                'type' => $type,
                'entity_id' => $entityId,
                'id_wilayah' => (int) $row->id_wilayah,
                'id_tahun' => (int) $row->id_tahun,
                'id_periode' => (int) $row->id_periode,
                'tipe' => (string) $row->tipe_pdrb,
                'adj' => (float) $adj,
                'history_id' => $type === 'kategori' ? $row->id_nilai_kategori : $row->id_nilai_sub_kategori,
                'updated_by' => $row->updated_by,
                'created_at' => $row->created_at,
            ];
        })->values();

        return response()->json([
            'status' => 'ok',
            'server_time_ms' => now()->valueOf(),
            'locked' => $this->getRekonP1LockStatus($type, $entityId, $pendekatan),
            'updates' => $updates,
        ]);
    }

    private function parsePollingSince($since): Carbon
    {
        if (!$since) {
            return now()->subMinutes(5);
        }

        if (is_numeric($since)) {
            $value = (int) $since;
            if ($value > 1000000000000) {
                return Carbon::createFromTimestampMs($value);
            }
            return Carbon::createFromTimestamp($value);
        }

        try {
            return Carbon::parse($since);
        } catch (\Throwable $e) {
            return now()->subMinutes(5);
        }
    }

    private function buildPollingBaseMap(string $type, int $entityId, $logs): array
    {
        if ($logs->isEmpty()) {
            return [];
        }

        $wilayahIds = $logs->pluck('id_wilayah')->unique()->values();
        $tahunIds = $logs->pluck('id_tahun')->unique()->values();
        $periodeIds = $logs->pluck('id_periode')->unique()->values();
        $tipeList = $logs->pluck('tipe_pdrb')->unique()->values();

        if ($type === 'kategori') {
            $baseRows = NilaiKategori::where('id_kategori', $entityId)
                ->where('tahap_data', 'awal')
                ->whereIn('id_wilayah', $wilayahIds)
                ->whereIn('id_tahun', $tahunIds)
                ->whereIn('id_periode', $periodeIds)
                ->whereIn('tipe_pdrb', $tipeList)
                ->get(['id_wilayah', 'id_tahun', 'id_periode', 'tipe_pdrb', 'nilai']);
        } else {
            $baseRows = NilaiSubKategori::where('id_sub_kategori', $entityId)
                ->where('tahap_data', 'awal')
                ->whereIn('id_wilayah', $wilayahIds)
                ->whereIn('id_tahun', $tahunIds)
                ->whereIn('id_periode', $periodeIds)
                ->whereIn('tipe_pdrb', $tipeList)
                ->get(['id_wilayah', 'id_tahun', 'id_periode', 'tipe_pdrb', 'nilai']);
        }

        $map = [];
        foreach ($baseRows as $row) {
            $key = $row->id_wilayah . '|' . $row->id_tahun . '|' . $row->id_periode . '|' . $row->tipe_pdrb;
            $map[$key] = (float) $row->nilai;
        }

        return $map;
    }

    private function pollNetExportUpdates(Carbon $sinceAt, int $limit, string $pendekatan)
    {
        $sourceKategoriIds = [29, 30];

        $logs = DB::table('nilai_kategori_log as l')
            ->join('nilai_kategori as n', 'n.id_nilai_kategori', '=', 'l.id_nilai_kategori')
            ->whereIn('n.id_kategori', $sourceKategoriIds)
            ->where('n.tahap_data', 'rekonsiliasi')
            ->where('l.created_at', '>', $sinceAt)
            ->orderBy('l.created_at', 'asc')
            ->limit($limit)
            ->get([
                'n.id_wilayah',
                'n.id_tahun',
                'n.id_periode',
                'n.tipe_pdrb',
                'l.updated_by',
                'l.created_at',
            ]);

        if ($logs->isEmpty()) {
            return response()->json([
                'status' => 'ok',
                'server_time_ms' => now()->valueOf(),
                'locked' => $this->getRekonP1LockStatus('kategori', 28, $pendekatan),
                'updates' => [],
            ]);
        }

        $comboMap = [];
        foreach ($logs as $row) {
            $key = $row->id_wilayah . '|' . $row->id_tahun . '|' . $row->id_periode . '|' . $row->tipe_pdrb;
            $comboMap[$key] = [
                'id_wilayah' => (int) $row->id_wilayah,
                'id_tahun' => (int) $row->id_tahun,
                'id_periode' => (int) $row->id_periode,
                'tipe_pdrb' => (string) $row->tipe_pdrb,
            ];
        }

        $wilayahIds = collect($comboMap)->pluck('id_wilayah')->unique()->values();
        $tahunIds = collect($comboMap)->pluck('id_tahun')->unique()->values();
        $periodeIds = collect($comboMap)->pluck('id_periode')->unique()->values();
        $tipeList = collect($comboMap)->pluck('tipe_pdrb')->unique()->values();

        // Ambil semua data awal dan rekonsiliasi untuk kategori 29 & 30
        $allRows = NilaiKategori::whereIn('id_kategori', [29, 30])
            ->whereIn('id_wilayah', $wilayahIds)
            ->whereIn('id_tahun', $tahunIds)
            ->whereIn('id_periode', $periodeIds)
            ->whereIn('tipe_pdrb', $tipeList)
            ->get();

        $dataMap = [];
        foreach ($allRows as $row) {
            $key = $row->id_wilayah . '|' . $row->id_tahun . '|' . $row->id_periode . '|' . $row->tipe_pdrb;
            $dataMap[$key][$row->tahap_data][$row->id_kategori] = (float) $row->nilai;
        }

        $updates = [];
        foreach ($comboMap as $key => $combo) {
            $baseEkspor = $dataMap[$key]['awal'][29] ?? 0;
            $baseImpor = $dataMap[$key]['awal'][30] ?? 0;
            
            // Fix: Jika data rekonsiliasi belum ada, gunakan data awal (supaya Adj = 0)
            $reconEkspor = isset($dataMap[$key]['rekonsiliasi'][29]) ? $dataMap[$key]['rekonsiliasi'][29] : $baseEkspor;
            $reconImpor = isset($dataMap[$key]['rekonsiliasi'][30]) ? $dataMap[$key]['rekonsiliasi'][30] : $baseImpor;

            $adjEkspor = $reconEkspor - $baseEkspor;
            $adjImpor = $reconImpor - $baseImpor;
            
            // Jika Anda ingin -101 di Impor menghasilkan -101 di Net Export, 
            // maka secara teknis rumusnya adalah Penjumlahan dalam konteks "Total Adjustment"
            $finalAdj = $adjEkspor + $adjImpor;

            // DEBUG LOGGING
            \Log::info("NetExport Debug [{$key}]:", [
                'baseEkspor' => $baseEkspor,
                'reconEkspor' => $reconEkspor,
                'baseImpor' => $baseImpor,
                'reconImpor' => $reconImpor,
                'adjEkspor' => $adjEkspor,
                'adjImpor' => $adjImpor,
                'finalAdj' => $finalAdj
            ]);

            $updates[] = [
                'type' => 'kategori',
                'entity_id' => 28,
                'id_wilayah' => $combo['id_wilayah'],
                'id_tahun' => $combo['id_tahun'],
                'id_periode' => $combo['id_periode'],
                'tipe' => $combo['tipe_pdrb'],
                'adj' => $finalAdj,
                'history_id' => null,
                'updated_by' => null,
            ];
        }

        return response()->json([
            'status' => 'ok',
            'server_time_ms' => now()->valueOf(),
            'locked' => $this->getRekonP1LockStatus('kategori', 28, $pendekatan),
            'updates' => $updates,
        ]);
    }

    public function toggleLock(Request $request)
    {
        $request->validate([
            'type' => 'required|in:kategori,subkategori',
            'id' => 'required|integer',
            'pendekatan' => 'required|in:lapangan_usaha,pengeluaran',
            'locked' => 'nullable',
        ]);

        $user = auth()->user();
        if (!$user || !in_array($user->role, ['provinsi', 'provinsi_supervisor'], true)) {
            return response()->json(['error' => 'FORBIDDEN'], 403);
        }

        $type = $request->input('type');
        $entityId = (int) $request->input('id');
        $pendekatan = $request->input('pendekatan');

        $current = $this->getRekonP1LockStatus($type, $entityId, $pendekatan);
        if ($request->has('locked')) {
            $locked = filter_var($request->input('locked'), FILTER_VALIDATE_BOOLEAN);
        } else {
            $locked = !$current;
        }

        $this->setRekonP1LockStatus($type, $entityId, $pendekatan, $locked, $user->id);

        try {
            broadcast(new \App\Events\RekonP1LockUpdated(
                $type,
                $entityId,
                $pendekatan,
                $locked,
                $user->id
            ))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('RekonP1 lock broadcast failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'entity_id' => $entityId,
                'locked' => $locked,
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'locked' => $locked,
        ]);
    }

    private function getRekonP1LockStatus(string $type, int $entityId, string $pendekatan): bool
    {
        $row = DB::table('rekon_p1_locks')
            ->where('type', $type)
            ->where('entity_id', $entityId)
            ->where('pendekatan', $pendekatan)
            ->orderByDesc('updated_at')
            ->first();

        return (bool) ($row->is_locked ?? false);
    }

    private function setRekonP1LockStatus(string $type, int $entityId, string $pendekatan, bool $locked, ?int $userId): void
    {
        $now = now();
        $payload = [
            'is_locked' => $locked,
            'locked_by' => $locked ? $userId : null,
            'locked_at' => $locked ? $now : null,
            'updated_at' => $now,
        ];

        DB::table('rekon_p1_locks')->updateOrInsert(
            [
                'type' => $type,
                'entity_id' => $entityId,
                'pendekatan' => $pendekatan,
            ],
            array_merge($payload, [
                'created_at' => $now,
            ])
        );
    }

    public function recalculate(Request $request)
    {
        $request->validate([
            'id_wilayah' => 'required',
            'id_tahun' => 'required',
            'id_periode' => 'required',
            'id_sub_kategori' => 'required_without:id_kategori',
            'id_kategori' => 'required_without:id_sub_kategori',
        ]);

        try {
            $useKategori = $request->filled('id_kategori');
            $tahunIdsCalc = Tahun::whereIn('id_tahun', function ($q) use ($request, $useKategori) {
                $q->select('id_tahun')
                    ->from($useKategori ? 'nilai_kategori' : 'nilai_sub_kategori')
                    ->when(
                        $useKategori,
                        fn($qb) => $qb->where('id_kategori', $request->id_kategori),
                        fn($qb) => $qb->where('id_sub_kategori', $request->id_sub_kategori)
                    )
                    ->where('tahap_data', 'awal');
            })
                ->orderBy('tahun', 'desc')
                ->limit(4)
                ->pluck('id_tahun')
                ->reverse()
                ->values();

            $periodeTriwulan = Periode::whereIn(
                'nama_periode',
                ['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV']
            )->orderByRaw("FIELD(nama_periode, 'Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV')")
                ->get();
            $periodeIds = $periodeTriwulan->pluck('id_periode')->values();

            if ($useKategori) {
                $dataAwal = $this->getData(collect(), $tahunIdsCalc, $periodeIds, 'awal', false, $request->id_kategori, $request->id_wilayah);
                $dataAdj = $this->getData(collect(), $tahunIdsCalc, $periodeIds, 'rekonsiliasi', false, $request->id_kategori, $request->id_wilayah);
            } else {
                $dataAwal = $this->getData($request->id_sub_kategori, $tahunIdsCalc, $periodeIds, 'awal', true, null, $request->id_wilayah);
                $dataAdj = $this->getData($request->id_sub_kategori, $tahunIdsCalc, $periodeIds, 'rekonsiliasi', true, null, $request->id_wilayah);
            }

            $rows = $this->strukturData($dataAwal, $dataAdj, $periodeTriwulan);
            $wilayah = $rows->firstWhere('id_wilayah', $request->id_wilayah);

            if (!$wilayah) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data wilayah tidak ditemukan'
                ], 404);
            }

            $periodeMap = [];
            foreach ($periodeTriwulan as $index => $periode) {
                $periodeMap[$periode->nama_periode] = [
                    'index' => $index,
                    'obj' => $periode
                ];
            }

            $periodeObj = $periodeTriwulan->firstWhere('id_periode', $request->id_periode);
            $periodeNama = $periodeObj ? $periodeObj->nama_periode : null;

            if (!$periodeNama) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Periode tidak ditemukan'
                ], 404);
            }

            $dataWilayahFull = $this->getFullDataForCalculations($wilayah, $tahunIdsCalc);
            $tahunIdsDisplay = collect([$request->id_tahun]);

            $calculations = $this->hitungPerWilayah(
                $dataWilayahFull,
                $periodeTriwulan,
                $tahunIdsCalc,
                $tahunIdsDisplay,
                $periodeMap
            );

            $result = $calculations[$request->id_tahun][$periodeNama] ?? null;

            if (!$result) {
                // Jangan lempar error 500, karena wajar jika periode depan belum ada data
                return response()->json([
                    'status' => 'success',
                    'data' => null,
                    'message' => 'Data tidak tersedia untuk dikalkulasi'
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
                'periode_nama' => $periodeNama,
                'id_tahun' => $request->id_tahun,
                'id_periode' => $request->id_periode,
                'id_wilayah' => $request->id_wilayah
            ]);

        } catch (\Exception $e) {
            \Log::error('Recalculate Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function recalculateAll(Request $request)
    {
        $request->validate([
            'id_sub_kategori' => 'required',
            'id_tahun' => 'required',
            'id_periode' => 'required',
        ]);

        try {
            $wilayahIds = NilaiSubKategori::where('id_sub_kategori', $request->id_sub_kategori)
                ->where('id_tahun', $request->id_tahun)
                ->where('id_periode', $request->id_periode)
                ->distinct()
                ->pluck('id_wilayah');

            $results = [];

            foreach ($wilayahIds as $idWilayah) {
                $result = $this->recalculateSingle(
                    $request->id_sub_kategori,
                    $idWilayah,
                    $request->id_tahun,
                    $request->id_periode
                );

                if ($result) {
                    $results[$idWilayah] = $result;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $results,
                'count' => count($results)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan dalam perhitungan semua wilayah'
            ], 500);
        }
    }

    private function recalculateSingle($idSubKategori, $idWilayah, $idTahun, $idPeriode)
    {
        try {
            $tahunIdsCalc = Tahun::whereIn('id_tahun', function ($q) use ($idSubKategori) {
                $q->select('id_tahun')
                    ->from('nilai_sub_kategori')
                    ->where('id_sub_kategori', $idSubKategori)
                    ->where('tahap_data', 'awal');
            })
                ->orderBy('tahun', 'desc')
                ->limit(4)
                ->pluck('id_tahun')
                ->reverse()
                ->values();

            $periodeTriwulan = Periode::whereIn(
                'nama_periode',
                ['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV']
            )->orderByRaw("FIELD(nama_periode, 'Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV')")
                ->get();
            $periodeIds = $periodeTriwulan->pluck('id_periode')->values();

            $dataAwal = $this->getData($idSubKategori, $tahunIdsCalc, $periodeIds, 'awal');
            $dataAdj = $this->getData($idSubKategori, $tahunIdsCalc, $periodeIds, 'rekonsiliasi');

            $rows = $this->strukturData($dataAwal, $dataAdj, $periodeTriwulan);
            $wilayah = $rows->firstWhere('id_wilayah', $idWilayah);

            if (!$wilayah)
                return null;

            $periodeMap = [];
            foreach ($periodeTriwulan as $index => $periode) {
                $periodeMap[$periode->nama_periode] = [
                    'index' => $index,
                    'obj' => $periode
                ];
            }

            $periodeObj = $periodeTriwulan->firstWhere('id_periode', $idPeriode);
            $periodeNama = $periodeObj ? $periodeObj->nama_periode : null;

            if (!$periodeNama)
                return null;

            $dataWilayahFull = $this->getFullDataForCalculations($wilayah, $tahunIdsCalc);
            $tahunIdsDisplay = collect([$idTahun]);

            $calculations = $this->hitungPerWilayah(
                $dataWilayahFull,
                $periodeTriwulan,
                $tahunIdsCalc,
                $tahunIdsDisplay,
                $periodeMap
            );

            return $calculations[$idTahun][$periodeNama] ?? null;

        } catch (\Exception $e) {
            return null;
        }
    }
}







