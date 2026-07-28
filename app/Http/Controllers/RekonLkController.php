<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RekonLembarKerja;

class RekonLkController extends Controller
{
    public function index(Request $request)
    {
        $jenis = $request->get('jenis', 'lapangan_usaha');
        $wilayahId = $request->get('wilayah_id');

        // ── Daftar wilayah (provinsi role) ────────────────────────────
        $user = auth()->user();
        $isProvinsiRole = in_array($user->role ?? '', ['provinsi', 'provinsi_supervisor']);
        $wilayahList = collect();
        if ($isProvinsiRole) {
            $wilayahList = \App\Models\Wilayah::withNama()->orderBy('nama_wilayah')->get();
            if (!$wilayahId && $wilayahList->isNotEmpty()) {
                $wilayahId = $wilayahList->first()->id_wilayah;
            }
        } else {
            $wilayahId = $user->wilayah_id ?? $user->id_wilayah ?? null;
        }

        // ── Daftar tahun untuk ditampilkan ──────────────────────────────
        $tahunList = DB::table('tahun')->orderBy('tahun', 'asc')->get();

        // ── Periode Triwulan I-IV ─────────────────────────────────────
        $periodeList = DB::table('periode')
            ->whereIn('id_periode', [1, 2, 3, 4])
            ->orderBy('id_periode')
            ->get();

        $periodeCols = [];
        foreach ($periodeList as $p) {
            $periodeCols[$p->id_periode] = 'Triw.' . $p->id_periode;
        }

        // ── Daftar Kategori & Sub Kategori ───────────────────────────
        $pendekatan = ($jenis === 'pengeluaran') ? 'pengeluaran' : 'lapangan_usaha';
        // Mirror logic from LembarKerjaController to show the same items
        $categoriesList = DB::table('kategori')
            ->where('pendekatan', $pendekatan)
            ->orderBy('id_kategori')
            ->get();

        $subKategoriList = DB::table('sub_kategori')
            ->join('kategori', 'kategori.id_kategori', '=', 'sub_kategori.id_kategori')
            ->where('kategori.pendekatan', $pendekatan)
            ->orderBy('kategori.id_kategori')
            ->orderBy('sub_kategori.id_sub_kategori')
            ->select('sub_kategori.*')
            ->get();

        $filteredSubKategori = $pendekatan === 'lapangan_usaha'
            ? $subKategoriList->filter(function ($item, $index) {
                return $index > 0;
            })->values()
            : $subKategoriList->values();

        if ($pendekatan === 'lapangan_usaha') {
            $indeksYangDitampilkan = [9, 10, 11, 12, 13, 14, 15, 16];
            $filteredKategori = $categoriesList->filter(function ($item, $index) use ($indeksYangDitampilkan) {
                return in_array($index, $indeksYangDitampilkan, true);
            })->values();
        } else {
            $filteredKategori = $categoriesList->filter(function ($item) {
                return !in_array($item->id_kategori, [31, 32, 23, 26], true);
            })->values();
        }

        $items = [];
        foreach ($filteredSubKategori as $sc) {
            $items[] = [
                'type' => 'subcategory',
                'id' => $sc->id_sub_kategori,
                'kode' => '',
                'nama' => $sc->nama_sub_kategori,
                'key' => 'sub-' . $sc->id_sub_kategori
            ];
        }
        foreach ($filteredKategori as $cat) {
            $items[] = [
                'type' => 'category',
                'id' => $cat->id_kategori,
                'kode' => $cat->kode_kategori,
                'nama' => $cat->nama_kategori,
                'key' => 'cat-' . $cat->id_kategori
            ];
        }
        $allItems = $items;

        $idSubKategori = $request->get('id_sub_kategori');
        $idKategori = $request->get('id_kategori');
        $itemKey = $request->get('item_key');

        if ($itemKey) {
            if (\str_starts_with($itemKey, 'sub-')) {
                $idSubKategori = (int) \str_replace('sub-', '', $itemKey);
            } elseif (\str_starts_with($itemKey, 'cat-')) {
                $idKategori = (int) \str_replace('cat-', '', $itemKey);
            }
        } elseif ($idSubKategori) {
            $itemKey = 'sub-' . $idSubKategori;
        } elseif ($idKategori) {
            $itemKey = 'cat-' . $idKategori;
        }

        if ($itemKey) {
            $items = array_filter($allItems, function ($item) use ($itemKey) {
                return $item['key'] === $itemKey;
            });
            // If no match found by itemKey, try separate IDs just in case
            if (empty($items)) {
                if ($idSubKategori) {
                    $items = array_filter($allItems, function ($item) use ($idSubKategori) {
                        return $item['type'] === 'subcategory' && (int) $item['id'] === (int) $idSubKategori;
                    });
                } elseif ($idKategori) {
                    $items = array_filter($allItems, function ($item) use ($idKategori) {
                        return $item['type'] === 'category' && (int) $item['id'] === (int) $idKategori;
                    });
                }
            }
        }

        if (empty($items)) {
            // Default to only showing the first item if none selected ("one by one" view)
            $items = count($allItems) > 0 ? [$allItems[0]] : [];
        }
        $items = array_values($items);

        // Update itemKey from what we actually found
        if (!empty($items)) {
            $itemKey = $items[0]['key'];
        }

        // Keep allItems for the dropdown selector
        $itemsForSelector = $allItems;

        $tablesData = [];
        $currentYearVal = (int) date('Y');
        $currentQuarterVal = (int) ceil((int) date('n') / 3);

        // --- BULK FETCHING TO PREVENT N+1 ---
        $tahunIdsList = $tahunList->pluck('id_tahun')->toArray();
        $allTahunMap = $tahunList->keyBy('id_tahun');
        $minTahunVal = $tahunList->min('tahun');

        // Include year-1 for growth calculations
        $growthYears = DB::table('tahun')->where('tahun', '>=', $minTahunVal - 1)->get();
        $growthYearIds = $growthYears->pluck('id_tahun')->toArray();
        $growthYearMap = $growthYears->keyBy('tahun');

        // 1. Bulk Lembar Kerja
        $fullLK = DB::table('lembar_kerja')
            ->where('wilayah_id', $wilayahId)
            ->where('jenis', $jenis)
            ->whereIn('id_tahun', array_unique(array_merge($tahunIdsList, $growthYearIds)))
            ->get();

        // --- BULK INITIALIZATION (NO WRITES IN LOOP) ---
        $requiredLks = [];
        // Index fullLK by specific item key for O(1) existence check
        $existingLkMap = [];
        foreach ($fullLK as $l) {
            $ik = $l->id_sub_kategori ? "sub-{$l->id_sub_kategori}" : "cat-{$l->id_kategori}";
            $existingLkMap["{$l->id_tahun}-{$l->id_periode}-{$ik}"] = $l;
        }

        foreach ($tahunList as $tObj) {
            foreach ($periodeList as $pObj) {
                foreach ($items as $item) {
                    $ik = $item['key']; // e.g., 'cat-1' or 'sub-1'
                    $lkKey = "{$tObj->id_tahun}-{$pObj->id_periode}-{$ik}";

                    if (!isset($existingLkMap[$lkKey])) {
                        $requiredLks[] = [
                            'wilayah_id' => $wilayahId,
                            'id_tahun' => $tObj->id_tahun,
                            'id_periode' => $pObj->id_periode,
                            'jenis' => $jenis,
                            'id_kategori' => $item['type'] === 'category' ? $item['id'] : null,
                            'id_sub_kategori' => $item['type'] === 'subcategory' ? $item['id'] : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        if (!empty($requiredLks)) {
            foreach (array_chunk($requiredLks, 500) as $chunk) {
                DB::table('lembar_kerja')->insert($chunk);
            }
            // Refresh fullLK after insert
            $fullLK = DB::table('lembar_kerja')
                ->where('wilayah_id', $wilayahId)
                ->where('jenis', $jenis)
                ->whereIn('id_tahun', $growthYearIds)
                ->get();
        }

        $lkLookup = [];
        $fullLKById = [];
        foreach ($fullLK as $lk) {
            $ik = $lk->id_sub_kategori ? "sub-{$lk->id_sub_kategori}" : "cat-{$lk->id_kategori}";
            $lkLookup["{$lk->id_tahun}-{$lk->id_periode}-{$ik}"] = $lk;
            $fullLKById[$lk->id] = $lk;
        }
        $fullLkIds = $fullLK->pluck('id')->toArray();

        // 3. Bulk LK Item sums
        $asliSums = DB::table('lembar_kerja_item')
            ->select('lembar_kerja_id', 'tipe_pdrb', DB::raw('SUM(nilai_ntb) as total_ntb'))
            ->whereIn('lembar_kerja_id', $fullLkIds)
            ->groupBy('lembar_kerja_id', 'tipe_pdrb')
            ->get();

        $asliLookup = [];
        foreach ($asliSums as $s) {
            $asliLookup["{$s->lembar_kerja_id}-{$s->tipe_pdrb}"] = (string) $s->total_ntb;
        }

        // 4. Bulk Official Values
        $catIds = $categoriesList->pluck('id_kategori')->toArray();
        $subcatIds = collect($items)->where('type', 'subcategory')->pluck('id')->toArray();

        $offKategori = DB::table('nilai_kategori')
            ->whereIn('id_kategori', $catIds)
            ->where('id_wilayah', $wilayahId)
            ->where('tahap_data', 'awal')
            ->whereIn('id_tahun', $tahunIdsList)
            ->get();

        $offSub = DB::table('nilai_sub_kategori')
            ->whereIn('id_sub_kategori', $subcatIds)
            ->where('id_wilayah', $wilayahId)
            ->where('tahap_data', 'awal')
            ->whereIn('id_tahun', $tahunIdsList)
            ->get();

        $offLookup = [];
        foreach ($offKategori as $v) {
            $offLookup["cat-{$v->id_kategori}-{$v->id_tahun}-{$v->id_periode}-{$v->tipe_pdrb}"] = (float) $v->nilai;
        }
        foreach ($offSub as $v) {
            $offLookup["sub-{$v->id_sub_kategori}-{$v->id_tahun}-{$v->id_periode}-{$v->tipe_pdrb}"] = (float) $v->nilai;
        }

        // 2. Bulk Rekon records
        $fullRekon = RekonLembarKerja::with('lockedBy')
            ->whereIn('lembar_kerja_id', $fullLkIds)
            ->get()
            ->keyBy('lembar_kerja_id');

        // Identify missing Rekon records
        $missingRekons = [];
        $defaultAdminId = DB::table('users')->where('role', 'provinsi')->orderBy('id')->value('id');

        foreach ($fullLK as $lk) {
            if (!isset($fullRekon[$lk->id])) {
                $ik = $lk->id_sub_kategori ? "sub-{$lk->id_sub_kategori}" : "cat-{$lk->id_kategori}";
                $asliB = $asliLookup["{$lk->id}-berlaku"] ?? 0;
                $asliK = $asliLookup["{$lk->id}-konstan"] ?? 0;
                $offB = $offLookup["{$ik}-{$lk->id_tahun}-{$lk->id_periode}-berlaku"] ?? 0;
                $offK = $offLookup["{$ik}-{$lk->id_tahun}-{$lk->id_periode}-konstan"] ?? 0;

                $tB = $asliB ?: ($offB * 1_000_000);
                $tK = $asliK ?: ($offK * 1_000_000);

                $missingRekons[] = [
                    'lembar_kerja_id' => $lk->id,
                    'total_berlaku' => $asliB,
                    'total_konstan' => $asliK,
                    'adj_berlaku' => '0',
                    'adj_konstan' => '0',
                    'final_berlaku' => number_format((float) $tB, 15, '.', ''),
                    'final_konstan' => number_format((float) $tK, 15, '.', ''),
                    'locked_at' => null,
                    'locked_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($missingRekons)) {
            foreach (array_chunk($missingRekons, 500) as $chunk) {
                DB::table('rekon_lembar_kerja')->insert($chunk);
            }
            // Refresh fullRekon
            $fullRekon = RekonLembarKerja::with('lockedBy')
                ->whereIn('lembar_kerja_id', $fullLkIds)
                ->get()
                ->keyBy('lembar_kerja_id');
        }

        // Re-key rekon lookup: {tahun}-{periode}-{itemKey}
        $rekonMatrixLookup = [];
        foreach ($fullRekon as $lkId => $r) {
            $lkRecord = $fullLKById[$lkId] ?? null;
            if ($lkRecord) {
                $ik = $lkRecord->id_sub_kategori ? "sub-{$lkRecord->id_sub_kategori}" : "cat-{$lkRecord->id_kategori}";
                $rekonMatrixLookup["{$lkRecord->id_tahun}-{$lkRecord->id_periode}-{$ik}"] = $r;
            }
        }

        // --- BATCH SYNC TOTALS (ONLY FOR OUT-OF-SYNC) ---
        $syncUpdates = [];
        foreach ($fullRekon as $lkId => $rekon) {
            $asliB = $asliLookup["{$lkId}-berlaku"] ?? 0;
            $asliK = $asliLookup["{$lkId}-konstan"] ?? 0;

            // Only sync if Asli values changed or if it was partially initialized
            if ((float) $rekon->total_berlaku !== (float) $asliB || (float) $rekon->total_konstan !== (float) $asliK) {
                $lkData = $fullLKById[$lkId] ?? null;
                if (!$lkData)
                    continue;
                $iK = $lkData->id_sub_kategori ? "sub-{$lkData->id_sub_kategori}" : "cat-{$lkData->id_kategori}";
                $offB = $offLookup["{$iK}-{$lkData->id_tahun}-{$lkData->id_periode}-berlaku"] ?? 0;
                $offK = $offLookup["{$iK}-{$lkData->id_tahun}-{$lkData->id_periode}-konstan"] ?? 0;

                $tB = $asliB ?: ($offB * 1000000);
                $tK = $asliK ?: ($offK * 1000000);

                if (function_exists('bcadd')) {
                    $sTB = number_format((float) $tB, 15, '.', '');
                    $sTK = number_format((float) $tK, 15, '.', '');
                    $sAdjB = number_format((float) ($rekon->adj_berlaku ?? 0), 15, '.', '');
                    $sAdjK = number_format((float) ($rekon->adj_konstan ?? 0), 15, '.', '');

                    $finalB = bcadd($sTB, $sAdjB, 15);
                    $finalK = bcadd($sTK, $sAdjK, 15);
                } else {
                    $finalB = (float) $tB + (float) ($rekon->adj_berlaku ?? 0);
                    $finalK = (float) $tK + (float) ($rekon->adj_konstan ?? 0);
                }

                $syncUpdates[] = [
                    'id' => $rekon->id,
                    'total_berlaku' => $this->capDecimal($asliB),
                    'total_konstan' => $this->capDecimal($asliK),
                    'final_berlaku' => $this->capDecimal($finalB),
                    'final_konstan' => $this->capDecimal($finalK),
                    'updated_at' => now()
                ];
            }
        }

        if (!empty($syncUpdates)) {
            foreach ($syncUpdates as $up) {
                DB::table('rekon_lembar_kerja')->where('id', $up['id'])->update($up);
            }
            // Refresh one last time if sync happened
            $fullRekon = RekonLembarKerja::with('lockedBy')->whereIn('lembar_kerja_id', $fullLkIds)->get()->keyBy('lembar_kerja_id');
            $rekonMatrixLookup = [];
            foreach ($fullRekon as $lkId => $r) {
                $lkRef = $fullLKById[$lkId] ?? null;
                if ($lkRef) {
                    $ik = $lkRef->id_sub_kategori ? "sub-{$lkRef->id_sub_kategori}" : "cat-{$lkRef->id_kategori}";
                    $rekonMatrixLookup["{$lkRef->id_tahun}-{$lkRef->id_periode}-{$ik}"] = $r;
                }
            }
        }

        // --- RENDERING LOOPS (READ ONLY) ---
        foreach ($tahunList as $tObj) {
            $tahunId = $tObj->id_tahun;
            $tahunLabel = $tObj->tahun;
            $prevYearTObj = $growthYearMap[$tObj->tahun - 1] ?? null;
            $prevYearId = $prevYearTObj ? $prevYearTObj->id_tahun : null;

            $itemizedData = [];

            foreach ($items as $item) {
                $itemKey = $item['key'];

                $matrix = [
                    'adhb' => [],
                    'adhk' => [],
                    'rekon' => [],
                    'prev_year' => ['adhb' => [], 'adhk' => [], 'implisit' => []],
                    'prev_q' => ['adhb' => 0, 'adhk' => 0, 'implisit' => 0],
                ];

                $runningSumAdhk = 0;
                $runningSumAdhkPrev = 0;

                foreach ($periodeList as $pObj) {
                    $pid = $pObj->id_periode;

                    // Bulk Lookup (Read-Only)
                    $lk = $lkLookup["{$tahunId}-{$pid}-{$itemKey}"] ?? null;
                    if (!$lk)
                        continue;
                    $rekon = $fullRekon[$lk->id] ?? null;

                    $asliBValue = $asliLookup["{$lk->id}-berlaku"] ?? 0;
                    $asliKValue = $asliLookup["{$lk->id}-konstan"] ?? 0;
                    $offBValue = $offLookup["{$itemKey}-{$tahunId}-{$pid}-berlaku"] ?? 0;
                    $offKValue = $offLookup["{$itemKey}-{$tahunId}-{$pid}-konstan"] ?? 0;

                    $finalAsliB = (float) ($asliBValue ?? 0);
                    $finalAsliK = (float) ($asliKValue ?? 0);

                    // Fallback untuk Tahun Dasar 2010: ADHB dan ADHK harus sama
                    if ((int)$tahunLabel === 2010) {
                        if ($finalAsliB == 0 && $finalAsliK != 0) $finalAsliB = $finalAsliK;
                        elseif ($finalAsliK == 0 && $finalAsliB != 0) $finalAsliK = $finalAsliB;
                    }

                    $matrix['rekon'][$pid] = $rekon;
                    $matrix['adhb'][$pid] = [
                        'asli' => $finalAsliB / 1_000_000,
                        'adj' => (float) ($rekon->adj_berlaku ?? 0) / 1_000_000,
                        'rilis' => (float) ($offBValue ?? 0),
                    ];
                    $matrix['adhk'][$pid] = [
                        'asli' => $finalAsliK / 1_000_000,
                        'adj' => (float) ($rekon->adj_konstan ?? 0) / 1_000_000,
                        'rilis' => (float) ($offKValue ?? 0),
                    ];

                    // Baseline for growth
                    $pRekonRecord = $prevYearId ? ($rekonMatrixLookup["{$prevYearId}-{$pid}-{$itemKey}"] ?? null) : null;
                    $matrix['prev_year']['adhb'][$pid] = $pRekonRecord ? (float) $pRekonRecord->final_berlaku : 0;
                    $matrix['prev_year']['adhk'][$pid] = $pRekonRecord ? (float) $pRekonRecord->final_konstan : 0;
                    $matrix['prev_year']['implisit'][$pid] = ($matrix['prev_year']['adhk'][$pid] != 0)
                        ? ($matrix['prev_year']['adhb'][$pid] / $matrix['prev_year']['adhk'][$pid] * 100)
                        : 0;

                    if ($pid === 1) {
                        $pqRekonRecord = $prevYearId ? ($rekonMatrixLookup["{$prevYearId}-4-{$itemKey}"] ?? null) : null;
                        $matrix['prev_q']['adhb'] = $pqRekonRecord ? (float) $pqRekonRecord->final_berlaku : 0;
                        $matrix['prev_q']['adhk'] = $pqRekonRecord ? (float) $pqRekonRecord->final_konstan : 0;
                        $matrix['prev_q']['implisit'] = ($matrix['prev_q']['adhk'] != 0)
                            ? ($matrix['prev_q']['adhb'] / $matrix['prev_q']['adhk'] * 100)
                            : 0;
                    }

                    $implAsli = ($asliKValue != 0) ? ($asliBValue / $asliKValue * 100) : 0;
                    $implRilis = ($offKValue != 0) ? ($offBValue / $offKValue * 100) : 0;

                    $matrix['implisit'][$pid] = ['asli' => $implAsli, 'rilis' => $implRilis];

                    $runningSumAdhk += $offKValue;
                    $prevYearAdhk_pid_val = $matrix['prev_year']['adhk'][$pid] / 1_000_000;
                    $runningSumAdhkPrev += $prevYearAdhk_pid_val;

                    $matrix['growth'][$pid] = [
                        'yoy' => [
                            'asli' => ($prevYearAdhk_pid_val != 0) ? (($asliKValue / 1_000_000) / $prevYearAdhk_pid_val * 100 - 100) : 0,
                            'rilis' => ($prevYearAdhk_pid_val != 0) ? ($offKValue / $prevYearAdhk_pid_val * 100 - 100) : 0,
                        ],
                        'qtoq' => ['asli' => 0, 'rilis' => 0],
                        'ctoc' => [
                            'asli' => ($runningSumAdhkPrev != 0) ? (array_sum(array_map(fn($v) => $v['asli'], array_slice($matrix['adhk'], 0, $pid, true))) / $runningSumAdhkPrev * 100 - 100) : 0,
                            'rilis' => ($runningSumAdhkPrev != 0) ? ($runningSumAdhk / $runningSumAdhkPrev * 100 - 100) : 0,
                        ],
                    ];

                    $prImplPrev = $matrix['prev_year']['implisit'][$pid];
                    $matrix['implisit_growth'][$pid] = [
                        'yoy' => [
                            'asli' => ($prImplPrev != 0) ? ($implAsli / $prImplPrev * 100 - 100) : 0,
                            'rilis' => ($prImplPrev != 0) ? ($implRilis / $prImplPrev * 100 - 100) : 0,
                        ],
                        'qtoq' => ['asli' => 0, 'rilis' => 0],
                    ];

                    $pQAsliVal = 0;
                    $pQRilisVal = 0;
                    $pQImplAsliVal = 0;
                    $pQImplRilisVal = 0;

                    if ($pid === 1) {
                        $pQAsliVal = $pQRilisVal = $matrix['prev_q']['adhk'] / 1_000_000;
                        $pQImplAsliVal = $pQImplRilisVal = $matrix['prev_q']['implisit'];
                    } else {
                        $pQAsliVal = $matrix['adhk'][$pid - 1]['asli'] ?? 0;
                        $pQRilisVal = $matrix['adhk'][$pid - 1]['rilis'] ?? 0;
                        $pQImplAsliVal = $matrix['implisit'][$pid - 1]['asli'] ?? 0;
                        $pQImplRilisVal = $matrix['implisit'][$pid - 1]['rilis'] ?? 0;
                    }

                    $matrix['growth'][$pid]['qtoq']['asli'] = ($pQAsliVal != 0) ? (($asliKValue / 1_000_000) / $pQAsliVal * 100 - 100) : 0;
                    $matrix['growth'][$pid]['qtoq']['rilis'] = ($pQRilisVal != 0) ? ($offKValue / $pQRilisVal * 100 - 100) : 0;

                    $matrix['implisit_growth'][$pid]['qtoq']['asli'] = ($pQImplAsliVal != 0) ? ($implAsli / $pQImplAsliVal * 100 - 100) : 0;
                    $matrix['implisit_growth'][$pid]['qtoq']['rilis'] = ($pQImplRilisVal != 0) ? ($implRilis / $pQImplRilisVal * 100 - 100) : 0;
                }

                foreach (['adhb', 'adhk'] as $tipe) {
                    $matrix['total'][$tipe] = [
                        'asli' => array_sum(array_column($matrix[$tipe], 'asli')),
                        'adj' => array_sum(array_column($matrix[$tipe], 'adj')),
                        'rilis' => array_sum(array_column($matrix[$tipe], 'rilis')),
                    ];
                }

                $totImplAsli = ($matrix['total']['adhk']['asli'] != 0) ? ($matrix['total']['adhb']['asli'] / $matrix['total']['adhk']['asli'] * 100) : 0;
                $totImplRilis = ($matrix['total']['adhk']['rilis'] != 0) ? ($matrix['total']['adhb']['rilis'] / $matrix['total']['adhk']['rilis'] * 100) : 0;
                $matrix['total']['implisit'] = ['asli' => $totImplAsli, 'rilis' => $totImplRilis];

                $totAdhkPrVal = array_sum($matrix['prev_year']['adhk']);
                $totAdhbPrVal = array_sum($matrix['prev_year']['adhb']);
                $totAdhkPrDisplay = $totAdhkPrVal / 1_000_000;
                $totImplPr = ($totAdhkPrVal != 0) ? ($totAdhbPrVal / $totAdhkPrVal * 100) : 0;

                $matrix['total_growth'] = [
                    'yoy' => [
                        'asli' => ($totAdhkPrDisplay != 0) ? ($matrix['total']['adhk']['asli'] / $totAdhkPrDisplay * 100 - 100) : 0,
                        'rilis' => ($totAdhkPrDisplay != 0) ? ($matrix['total']['adhk']['rilis'] / $totAdhkPrDisplay * 100 - 100) : 0,
                    ],
                    'ctoc' => [
                        'asli' => ($totAdhkPrDisplay != 0) ? ($matrix['total']['adhk']['asli'] / $totAdhkPrDisplay * 100 - 100) : 0,
                        'rilis' => ($totAdhkPrDisplay != 0) ? ($matrix['total']['adhk']['rilis'] / $totAdhkPrDisplay * 100 - 100) : 0,
                    ],
                ];

                $matrix['total_implisit_growth'] = [
                    'yoy' => [
                        'asli' => ($totImplPr != 0) ? ($totImplAsli / $totImplPr * 100 - 100) : 0,
                        'rilis' => ($totImplPr != 0) ? ($totImplRilis / $totImplPr * 100 - 100) : 0,
                    ],
                ];

                $itemizedData[$itemKey] = $matrix;
            }

            $tablesData[] = [
                'tahunId' => $tahunId,
                'tahunLabel' => $tahunLabel,
                'items' => $itemizedData,
            ];
        }

        return view('rekon_lk.index', compact(
            'tablesData',
            'periodeCols',
            'tahunList',
            'wilayahList',
            'wilayahId',
            'isProvinsiRole',
            'jenis',
            'currentYearVal',
            'currentQuarterVal',
            'items',
            'itemsForSelector',
            'itemKey'
        ));
    }

    public function updateAdjustment(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('updateAdjustment Payload:', $request->all());

        $request->validate([
            'id' => 'required|exists:rekon_lembar_kerja,id',
            'field' => 'required|in:adj_berlaku,adj_konstan',
            'value' => 'nullable|string', // Allow string to preserve precision from frontend
        ]);

        $rekon = RekonLembarKerja::findOrFail($request->id);

        if ($rekon->locked_at) {
            return response()->json([
                'success' => false,
                'message' => 'Data sudah dikunci oleh ' . ($rekon->lockedBy->name ?? 'Admin') . '. Silakan hubungi admin provinsi untuk membuka kunci.'
            ], 403);
        }

        $field = $request->field;
        $value = trim($request->value ?? '');

        // Coerce empty strings to '0' to prevent MySQL decimal type errors
        if ($value === '') {
            $value = '0';
        }

        try {
            // Tentukan nilai lama untuk dicatat di log
            $nilaiLama = $field === 'adj_berlaku' ? $rekon->adj_berlaku : $rekon->adj_konstan;
            $nilaiLama = trim((string) $nilaiLama);
            if ($nilaiLama === '')
                $nilaiLama = '0'; // Ensure string for bcmath

            \Illuminate\Support\Facades\Log::info('Type/Value BEFORE rekon assignment:', [gettype($value), $value]);
            $rekon->$field = $value;
            \Illuminate\Support\Facades\Log::info('Type/Value AFTER rekon assignment:', [gettype($rekon->$field), $rekon->$field]);

            if ($field === 'adj_berlaku') {
                $total = trim((string) ($rekon->total_berlaku ?? '0'));
                if ($total === '')
                    $total = '0';
                if (function_exists('bcadd')) {
                    $sTotal = number_format((float) $total, 15, '.', '');
                    $sValue = number_format((float) $value, 15, '.', '');
                    $rekon->final_berlaku = bcadd($sTotal, $sValue, 15);
                } else {
                    $rekon->final_berlaku = (string) ((float) $total + (float) $value);
                }
            } else {
                $total = trim((string) ($rekon->total_konstan ?? '0'));
                if ($total === '')
                    $total = '0';
                if (function_exists('bcadd')) {
                    $sTotal = number_format((float) $total, 15, '.', '');
                    $sValue = number_format((float) $value, 15, '.', '');
                    $rekon->final_konstan = bcadd($sTotal, $sValue, 15);
                } else {
                    $rekon->final_konstan = (string) ((float) $total + (float) $value);
                }
            }

            // Catat ke log hanya jika ada perubahan adjustment
            // Use bccomp if available for precision comparison
            $hasChanged = function_exists('bccomp')
                ? bccomp($nilaiLama, $value, 15) !== 0
                : (float) $nilaiLama !== (float) $value;

            if ($hasChanged) {
                DB::table('rekon_lembar_kerja_log')->insert([
                    'rekon_lembar_kerja_id' => $rekon->id,
                    'field_type' => $field,
                    'nilai_lama' => $nilaiLama,
                    'nilai_baru' => $value,
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $rekon->save();
            \Illuminate\Support\Facades\Log::info('Model saved successfully. Reloading from DB...');

            $rekonRefresh = RekonLembarKerja::find($rekon->id);
            \Illuminate\Support\Facades\Log::info('Values AFTER reload:', [
                'adj_berlaku' => $rekonRefresh->adj_berlaku,
                'adj_konstan' => $rekonRefresh->adj_konstan,
                'final_berlaku' => $rekonRefresh->final_berlaku,
                'final_konstan' => $rekonRefresh->final_konstan,
            ]);

            return response()->json([
                'success' => true,
                'final_berlaku' => $rekonRefresh->final_berlaku,
                'final_konstan' => $rekonRefresh->final_konstan,
                'fmt_final_berlaku' => number_format((float) ($rekonRefresh->final_berlaku / 1_000_000), 9, ',', '.'),
                'fmt_final_konstan' => number_format((float) ($rekonRefresh->final_konstan / 1_000_000), 9, ',', '.'),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Save Adjustment Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan ke database: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleLock(Request $request)
    {
        $user = auth()->user();
        $isProvinsiRole = in_array($user->role ?? '', ['provinsi', 'provinsi_supervisor']);

        if (!$isProvinsiRole) {
            return response()->json(['success' => false, 'message' => 'Hanya admin provinsi yang dapat mengunci data.'], 403);
        }

        $request->validate(['id' => 'required|exists:rekon_lembar_kerja,id']);
        $rekon = RekonLembarKerja::findOrFail($request->id);

        if ($rekon->locked_at) {
            $rekon->locked_at = null;
            $rekon->locked_by = null;
        } else {
            $rekon->locked_at = now();
            $rekon->locked_by = $user->id;
        }

        $rekon->save();
        $rekon->load('lockedBy');

        return response()->json([
            'success' => true,
            'locked' => $rekon->locked_at !== null,
            'locked_at' => $rekon->locked_at ? $rekon->locked_at->format('Y-m-d H:i:s') : null,
            'locked_by' => $rekon->lockedBy->name ?? null
        ]);
    }

    public function releaseData(Request $request)
    {
        $user = auth()->user();
        $isProvinsiRole = in_array($user->role ?? '', ['provinsi', 'provinsi_supervisor']);

        if (!$isProvinsiRole) {
            return response()->json(['success' => false, 'message' => 'Hanya admin provinsi yang dapat merilis data.'], 403);
        }

        $request->validate(['id' => 'required|exists:rekon_lembar_kerja,id']);
        $rekon = RekonLembarKerja::findOrFail($request->id);

        // Cari data lembar_kerja untuk dapat parent kategori & detail wilayah
        $lk = DB::table('lembar_kerja')->where('id', $rekon->lembar_kerja_id)->first();
        if (!$lk) {
            return response()->json(['success' => false, 'message' => 'Lembar kerja tidak ditemukan.'], 404);
        }

        $now = now();
        $tahapData = 'LK';

        if (!empty($lk->id_sub_kategori)) {
            // Target: nilai_sub_kategori
            // 1. Berlaku
            DB::table('nilai_sub_kategori')->updateOrInsert(
                [
                    'id_sub_kategori' => $lk->id_sub_kategori,
                    'id_wilayah' => $lk->wilayah_id,
                    'id_periode' => $lk->id_periode,
                    'id_tahun' => $lk->id_tahun,
                    'tahap_data' => $tahapData,
                    'tipe_pdrb' => 'berlaku',
                ],
                [
                    'nilai' => (float) ($rekon->final_berlaku ?? 0) / 1000000,
                    'updated_by' => $user->id,
                    'updated_at' => $now,
                ]
            );

            // 2. Konstan
            DB::table('nilai_sub_kategori')->updateOrInsert(
                [
                    'id_sub_kategori' => $lk->id_sub_kategori,
                    'id_wilayah' => $lk->wilayah_id,
                    'id_periode' => $lk->id_periode,
                    'id_tahun' => $lk->id_tahun,
                    'tahap_data' => $tahapData,
                    'tipe_pdrb' => 'konstan',
                ],
                [
                    'nilai' => (float) ($rekon->final_konstan ?? 0) / 1000000,
                    'updated_by' => $user->id,
                    'updated_at' => $now,
                ]
            );
        } elseif (!empty($lk->id_kategori)) {
            // Target: nilai_kategori
            // 1. Berlaku
            DB::table('nilai_kategori')->updateOrInsert(
                [
                    'id_kategori' => $lk->id_kategori,
                    'id_wilayah' => $lk->wilayah_id,
                    'id_periode' => $lk->id_periode,
                    'id_tahun' => $lk->id_tahun,
                    'tahap_data' => $tahapData,
                    'tipe_pdrb' => 'berlaku',
                ],
                [
                    'nilai' => (float) ($rekon->final_berlaku ?? 0) / 1000000,
                    'updated_by' => $user->id,
                    'updated_at' => $now,
                ]
            );

            // 2. Konstan
            DB::table('nilai_kategori')->updateOrInsert(
                [
                    'id_kategori' => $lk->id_kategori,
                    'id_wilayah' => $lk->wilayah_id,
                    'id_periode' => $lk->id_periode,
                    'id_tahun' => $lk->id_tahun,
                    'tahap_data' => $tahapData,
                    'tipe_pdrb' => 'konstan',
                ],
                [
                    'nilai' => (float) ($rekon->final_konstan ?? 0) / 1000000,
                    'updated_by' => $user->id,
                    'updated_at' => $now,
                ]
            );
        } else {
            return response()->json(['success' => false, 'message' => 'Data Lembar Kerja tidak memiliki kategori (id_kategori dan id_sub_kategori kosong).'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dirilis dan dipindahkan ke ' . (!empty($lk->id_sub_kategori) ? 'Nilai Sub Kategori' : 'Nilai Kategori') . '.',
        ]);
    }

    public function history($id)
    {
        $field = request('field', 'adj_berlaku');

        $logs = DB::table('rekon_lembar_kerja_log as log')
            ->join('users as u', 'u.id', '=', 'log.updated_by')
            ->where('log.rekon_lembar_kerja_id', $id)
            ->where('log.field_type', $field)
            ->select(
                'log.nilai_lama',
                'log.nilai_baru',
                'log.created_at',
                'u.name as user_name'
            )
            ->orderBy('log.created_at', 'desc')
            ->limit(10)
            ->get();

        if ($logs->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => []
            ]);
        }

        $formattedLogs = $logs->map(function ($log) {
            return [
                'user' => $log->user_name,
                'waktu' => \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s'),
                // Tampilkan dalam Juta format indonesia
                'nilai_lama' => number_format((float) ($log->nilai_lama ?? 0) / 1_000_000, 9, ',', '.'),
                'nilai_baru' => number_format((float) ($log->nilai_baru ?? 0) / 1_000_000, 9, ',', '.')
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedLogs
        ]);
    }

    public function pollUpdates(Request $request)
    {
        $since = (int) $request->query('since', 0);
        if ($since <= 0) {
            $since = (time() - 30) * 1000;
        }
        $sinceDt = date('Y-m-d H:i:s', floor($since / 1000));

        $wilayahId = $request->query('wilayah_id');
        $jenis = $request->query('jenis', 'lapangan_usaha');

        // 1. Ambil log adjustment (rekon_lembar_kerja)
        $adjUpdates = DB::table('rekon_lembar_kerja_log as log')
            ->join('rekon_lembar_kerja as r', 'r.id', '=', 'log.rekon_lembar_kerja_id')
            ->join('lembar_kerja as l', 'l.id', '=', 'r.lembar_kerja_id')
            ->where('l.jenis', $jenis)
            ->when($wilayahId, fn($q) => $q->where('l.wilayah_id', $wilayahId))
            ->where('log.created_at', '>=', $sinceDt)
            ->select(
                'l.id_tahun',
                'l.id_periode',
                'l.id_kategori',
                'l.id_sub_kategori',
                'r.id as rekon_id',
                'log.field_type',
                'log.nilai_baru'
            )
            ->orderBy('log.created_at', 'asc')
            ->get();

        // 2. Ambil perubahan data ASLI (lembar_kerja_item)
        $affectedLk = DB::table('lembar_kerja as l')
            ->leftJoin('lembar_kerja_item as i', 'l.id', '=', 'i.lembar_kerja_id')
            ->where('l.jenis', $jenis)
            ->when($wilayahId, fn($q) => $q->where('l.wilayah_id', $wilayahId))
            ->where(function ($q) use ($sinceDt) {
                $q->where('l.updated_at', '>=', $sinceDt)
                    ->orWhere('i.updated_at', '>=', $sinceDt);
            })
            ->distinct()
            ->pluck('l.id');

        $asliUpdates = [];
        foreach ($affectedLk as $lkId) {
            $lk = DB::table('lembar_kerja')->where('id', $lkId)->first();

            $asliAdhb = DB::table('lembar_kerja_item')
                ->where('lembar_kerja_id', $lkId)
                ->where('tipe_pdrb', 'berlaku')
                ->sum('nilai_ntb');

            $asliAdhk = DB::table('lembar_kerja_item')
                ->where('lembar_kerja_id', $lkId)
                ->where('tipe_pdrb', 'konstan')
                ->sum('nilai_ntb');

            $asliUpdates[] = [
                'id_tahun' => $lk->id_tahun,
                'id_periode' => $lk->id_periode,
                'id_kategori' => $lk->id_kategori,
                'id_sub_kategori' => $lk->id_sub_kategori,
                'asli_adhb' => (float) $asliAdhb,
                'asli_adhk' => (float) $asliAdhk
            ];
        }

        return response()->json([
            'status' => 'ok',
            'server_time_ms' => round(microtime(true) * 1000),
            'updates' => $adjUpdates,
            'asli_updates' => $asliUpdates
        ]);
    }
    /**
     * Caps a numeric value (string, float, or int) to fit within DECIMAL(30,15)
     * Maximum 15 digits before decimal point.
     */
    private function capDecimal($value)
    {
        if ($value === null || $value === '')
            return '0';
        $num = (float) $value;
        $max = 999999999999999.0; // 15 digits of 9
        if ($num > $max)
            return number_format($max, 15, '.', '');
        if ($num < -$max)
            return number_format(-$max, 15, '.', '');

        // Return formatted value to 15 decimals to be safe
        return number_format($num, 15, '.', '');
    }
}
