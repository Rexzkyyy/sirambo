<section class="bg-white rounded-xl shadow border border-slate-200 p-4">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-900">Tabel Hasil</h3>
        <span class="text-xs text-slate-500">
            @if(($columnMode ?? 'time') === 'wilayah')
                Kolom: Tahun x Triwulan x Wilayah
            @else
                Kolom: Tahun x Triwulan
            @endif
        </span>
    </div>
    @php
        $columnMode = $columnMode ?? 'time';
        $columnItems = $columnItems ?? collect();
        $perkapitaScale = $perkapitaScale ?? 1;
        $showTotalColumn = $showTotalColumn ?? false;
        $pdrbData = $pdrbData ?? [];
    @endphp
    @if($rowItems->isEmpty() || $tahunTampil->isEmpty() || ($periodeTampil->isEmpty() && !$showTotalColumn) || ($columnMode === 'wilayah' && $columnItems->isEmpty()))
        <div class="text-sm text-slate-500">Pilih indikator, tahun, dan turunan agar tabel muncul.</div>
    @else
        <style>
            .dynamic-table-scroll {
                max-height: 70vh;
                overflow: auto;
                position: relative;
            }

            .dynamic-sticky-table {
                border-collapse: separate;
                border-spacing: 0;
            }

            .dynamic-sticky-table thead th {
                position: sticky;
                top: 0;
                z-index: 30;
                background: #f1f5f9;
            }

            .dynamic-sticky-table thead tr:first-child th {
                top: 0;
                z-index: 35;
            }

            .dynamic-sticky-table thead tr:nth-child(2) th {
                top: 36px;
                z-index: 30;
            }

            .dynamic-sticky-table thead th:first-child {
                left: 0;
                z-index: 60;
                background: #f1f5f9;
            }

            .dynamic-sticky-table tbody td:first-child {
                position: sticky;
                left: 0;
                z-index: 20;
                background: #ffffff;
            }

            .dynamic-sticky-table tbody tr.bg-slate-50 td:first-child {
                background: #f8fafc;
            }

            .dynamic-sticky-table tbody tr:hover td:first-child {
                background: #f8fafc;
            }
        </style>
        <div class="flex items-center justify-end mb-3">
            <a href="{{ route('pdrb.dynamic.export', request()->query()) }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-emerald-600 text-emerald-700 text-sm font-semibold hover:bg-emerald-200 text-emerald-900 font-semibold">
                Export Excel
            </a>
        </div>
        @php
            $periodeTotalList = $showTotalColumn ? $periodeList : $periodeTampil;
            $subByKategori = $subFiltered->groupBy('id_kategori');
            $periodSource = isset($periodeMaster) ? $periodeMaster : $periodeList;
            $periodOrder = $periodSource->pluck('id_periode')->values()->all();
            $periodIndexMap = array_flip($periodOrder);
            $tahunById = $tahunMaster->keyBy('id_tahun');
            $tahunByValue = $tahunMaster->keyBy(fn($t) => (int) $t->tahun);
            $kategoriDataBerlaku = $kategoriDataBerlaku ?? [];
            $kategoriDataKonstan = $kategoriDataKonstan ?? [];
            $subDataBerlaku = $subDataBerlaku ?? [];
            $subDataKonstan = $subDataKonstan ?? [];
            $kategoriWilayahData = $kategoriWilayahData ?? [];
            $subWilayahData = $subWilayahData ?? [];
            $pendudukData = $pendudukData ?? [];
            $pendudukWilayahData = $pendudukWilayahData ?? [];

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

            $formatValue = function ($nilai) use ($calcMode) {
                if ($nilai === null || $nilai === '') {
                    return '-';
                }
                if ($calcMode) {
                    if ($calcMode === 'perkapita') {
                        return number_format($nilai, 2, ',', '.');
                    }
                    return number_format($nilai, 2, ',', '.') . '%';
                }
                return number_format($nilai, 1, ',', '.');
            };

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
                    $prevYearId = $tahunByValue->get((int) $tahunValue - 1)?->id_tahun;
                    $prev = $prevYearId ? ($data[$prevYearId][$periodeId] ?? null) : null;
                    if ($prev === null || $prev == 0) {
                        return null;
                    }
                    return (($current / $prev) * 100) - 100;
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
                        $prevYearId = $tahunByValue->get((int) $tahunValue - 1)?->id_tahun;
                    }
                    if (!$prevPeriodId || !$prevYearId) {
                        return null;
                    }
                    $prev = $data[$prevYearId][$prevPeriodId] ?? null;
                    if ($prev === null || $prev == 0) {
                        return null;
                    }
                    return (($current / $prev) * 100) - 100;
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
                    return (($sumNow / $sumPrev) * 100) - 100;
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
                    $prevPeriodId = 4; // Triwulan IV
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
                return (($sumNow / $sumPrev) * 100) - 100;
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
        @endphp
        <div class="dynamic-table-scroll">
            <table class="min-w-full border text-sm dynamic-sticky-table">
                <thead class="bg-slate-100">
                    @if($columnMode === 'wilayah')
                        <tr>
                            <th class="border px-3 py-2 text-left" rowspan="2">Baris Data</th>
                            @foreach($tahunTampil as $tahun)
                                @foreach($periodeTampil as $periode)
                                    <th class="border px-3 py-2 text-center" colspan="{{ $columnItems->count() }}">
                                        {{ $tahun->tahun }} {{ $periode->nama_periode }}
                                    </th>
                                @endforeach
                                @if($showTotalColumn)
                                    <th class="border px-3 py-2 text-center" colspan="{{ $columnItems->count() }}">
                                        {{ $tahun->tahun }} Total
                                    </th>
                                @endif
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($tahunTampil as $tahun)
                                @foreach($periodeTampil as $periode)
                                    @foreach($columnItems as $colWil)
                                        <th class="border px-3 py-2 text-center text-xs">{{ $colWil->nama_wilayah }}</th>
                                    @endforeach
                                @endforeach
                                @if($showTotalColumn)
                                    @foreach($columnItems as $colWil)
                                        <th class="border px-3 py-2 text-center text-xs">{{ $colWil->nama_wilayah }}</th>
                                    @endforeach
                                @endif
                            @endforeach
                        </tr>
                    @else
                        <tr>
                            <th class="border px-3 py-2 text-left">Baris Data</th>
                            @foreach($tahunTampil as $tahun)
                                @foreach($periodeTampil as $periode)
                                    <th class="border px-3 py-2 text-center">{{ $tahun->tahun }} {{ $periode->nama_periode }}</th>
                                @endforeach
                                @if($showTotalColumn)
                                    <th class="border px-3 py-2 text-center">{{ $tahun->tahun }} Total</th>
                                @endif
                            @endforeach
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @if($rowMode === 'wilayah')
                        @foreach($rowItems as $wil)
                            @php
                                $wilId = $wil->id_wilayah;
                            @endphp
                            <tr class="border-t hover:bg-slate-50 transition">
                                <td class="border px-3 py-2 font-semibold">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-xs mr-2">W</span>
                                    {{ $wil->nama_wilayah }}
                                </td>
                                @foreach($tahunTampil as $tahun)
                                    @php $sum = 0;
                                    $count = 0; @endphp
                                    @foreach($periodeTampil as $periode)
                                        @php
                                            $nilai = $wilayahData[$wilId][$tahun->id_tahun][$periode->id_periode] ?? null;
                                            if ($nilai !== null) {
                                                $sum += $nilai;
                                                $count++;
                                            }
                                        @endphp
                                        <td class="border px-3 py-2 text-right">
                                            {{ $formatValue($nilai) }}
                                        </td>
                                    @endforeach
                                    @if($showTotalColumn)
                                        @php
                                            $sum = 0;
                                            $count = 0;
                                            foreach ($periodeTotalList as $periodeTotal) {
                                                $nilaiTotal = $wilayahData[$wilId][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                                if ($nilaiTotal !== null) {
                                                    $sum += $nilaiTotal;
                                                    $count++;
                                                }
                                            }
                                        @endphp
                                        <td class="border px-3 py-2 text-right bg-emerald-200 text-emerald-900 font-semibold">
                                            {{ $count > 0 ? number_format($sum, 1, ',', '.') : '-' }}
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    @elseif($columnMode === 'wilayah' && $rowMode === 'sub')
                        @php $subOnlyCounter = 0; @endphp
                        @foreach($rowItems as $subRow)
                            @php $subOnlyCounter++; @endphp
                            @php
                                $subId = $subRow->id_sub_kategori;
                            @endphp
                            <tr class="border-t hover:bg-slate-50 transition">
                                <td class="border px-3 py-2">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-xs mr-2">{{ $subOnlyCounter }}</span>
                                    {{ $subRow->nama_sub_kategori }}
                                </td>
                                @foreach($tahunTampil as $tahun)
                                    @foreach($periodeTampil as $periode)
                                        @foreach($columnItems as $colWil)
                                            @php
                                                $wilId = $colWil->id_wilayah;
                                                $nilai = $computeGrowth($subWilayahData[$subId][$wilId] ?? [], $tahun->id_tahun, $periode->id_periode, $wilId);
                                            @endphp
                                            <td class="border px-3 py-2 text-right">
                                                {{ $formatValue($nilai) }}
                                            </td>
                                        @endforeach
                                    @endforeach
                                    @if($showTotalColumn)
                                        @foreach($columnItems as $colWil)
                                            @php
                                                $wilId = $colWil->id_wilayah;
                                                $sum = 0;
                                                $count = 0;
                                                foreach ($periodeTotalList as $periodeTotal) {
                                                    $nilaiTotal = $subWilayahData[$subId][$wilId][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                                    if ($nilaiTotal !== null) {
                                                        $sum += $nilaiTotal;
                                                        $count++;
                                                    }
                                                }
                                            @endphp
                                            <td class="border px-3 py-2 text-right bg-emerald-200 text-emerald-900 font-semibold">
                                                {{ $count > 0 ? number_format($sum, 1, ',', '.') : '-' }}
                                            </td>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    @elseif($columnMode === 'wilayah')
                        @php $catCounter = 0; @endphp
                        @foreach($rowItems as $kategoriRow)
                            @if($calcMode === 'distribusi' && in_array($kategoriRow->id_kategori, $pdrbKategoriIds ?? [], true))
                                @continue
                            @endif
                            @php
                                $catCounter++;
                                $catCode = $getKategoriCode($kategoriRow->nama_kategori, $indexToCode($catCounter));
                                $catPrefix = $getKategoriPrefix($kategoriRow->nama_kategori);
                                $subIndex = 0;
                                $kategoriId = $kategoriRow->id_kategori;
                                $kategoriLabel = $kategoriRow->nama_kategori;
                            @endphp
                            <tr class="border-t bg-slate-50">
                                <td class="border px-3 py-2 font-semibold">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded bg-slate-800 text-white text-xs mr-2">{{ $catPrefix ?: $catCode }}</span>
                                    {{ $kategoriLabel }}
                                </td>
                                @foreach($tahunTampil as $tahun)
                                    @foreach($periodeTampil as $periode)
                                        @foreach($columnItems as $colWil)
                                            @php
                                                $wilId = $colWil->id_wilayah;
                                                $nilai = $computeGrowth($kategoriWilayahData[$kategoriId][$wilId] ?? [], $tahun->id_tahun, $periode->id_periode, $wilId);
                                            @endphp
                                            <td class="border px-3 py-2 text-right">
                                                {{ $formatValue($nilai) }}
                                            </td>
                                        @endforeach
                                    @endforeach
                                    @if($showTotalColumn)
                                        @foreach($columnItems as $colWil)
                                            @php
                                                $wilId = $colWil->id_wilayah;
                                                $sum = 0;
                                                $count = 0;
                                                foreach ($periodeTotalList as $periodeTotal) {
                                                    $nilaiTotal = $kategoriWilayahData[$kategoriId][$wilId][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                                    if ($nilaiTotal !== null) {
                                                        $sum += $nilaiTotal;
                                                        $count++;
                                                    }
                                                }
                                                $pendudukTotal = $pendudukWilayahData[$wilId][$tahun->id_tahun] ?? null;
                                            @endphp
                                            <td class="border px-3 py-2 text-right bg-emerald-200 text-emerald-900 font-semibold">
                                                @if($calcMode === 'perkapita')
                                                    {{ ($count > 0 && $pendudukTotal) ? $formatValue(($sum / $pendudukTotal) * $perkapitaScale) : '-' }}
                                                @else
                                                    {{ $count > 0 ? number_format($sum, 1, ',', '.') : '-' }}
                                                @endif
                                            </td>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tr>

                            @if(($showSubRows ?? true))
                                @foreach($subByKategori->get($kategoriId, collect()) as $subRow)
                                    @php
                                        $subIndex++;
                                        $subCode = $getSubCode($subRow->id_sub_kategori, $subIndex, $catCode);
                                        $subId = $subRow->id_sub_kategori;
                                    @endphp
                                    <tr class="border-t hover:bg-slate-50 transition">
                                        <td class="border px-3 py-2">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-xs mr-2">{{ $subCode }}</span>
                                            {{ $subRow->nama_sub_kategori }}
                                        </td>
                                        @foreach($tahunTampil as $tahun)
                                            @foreach($periodeTampil as $periode)
                                                @foreach($columnItems as $colWil)
                                                    @php
                                                        $wilId = $colWil->id_wilayah;
                                                        $nilai = $computeGrowth($subWilayahData[$subId][$wilId] ?? [], $tahun->id_tahun, $periode->id_periode, $wilId);
                                                    @endphp
                                                    <td class="border px-3 py-2 text-right">
                                                        {{ $formatValue($nilai) }}
                                                    </td>
                                                @endforeach
                                            @endforeach
                                            @if($showTotalColumn)
                                                @foreach($columnItems as $colWil)
                                                    @php
                                                        $wilId = $colWil->id_wilayah;
                                                        $sum = 0;
                                                        $count = 0;
                                                        foreach ($periodeTotalList as $periodeTotal) {
                                                            $nilaiTotal = $subWilayahData[$subId][$wilId][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                                            if ($nilaiTotal !== null) {
                                                                $sum += $nilaiTotal;
                                                                $count++;
                                                            }
                                                        }
                                                        $pendudukTotal = $pendudukWilayahData[$wilId][$tahun->id_tahun] ?? null;
                                                    @endphp
                                                    <td class="border px-3 py-2 text-right bg-emerald-200 text-emerald-900 font-semibold">
                                                        @if($calcMode === 'perkapita')
                                                            {{ ($count > 0 && $pendudukTotal) ? $formatValue(($sum / $pendudukTotal) * $perkapitaScale) : '-' }}
                                                        @else
                                                            {{ $count > 0 ? number_format($sum, 1, ',', '.') : '-' }}
                                                        @endif
                                                    </td>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    @elseif($rowMode === 'sub')
                        @php $subOnlyCounter = 0; @endphp
                        @foreach($rowItems as $subRow)
                            @php $subOnlyCounter++; @endphp
                            @php
                                $subId = $subRow->id_sub_kategori;
                            @endphp
                            <tr class="border-t hover:bg-slate-50 transition">
                                <td class="border px-3 py-2">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-xs mr-2">{{ $subOnlyCounter }}</span>
                                    {{ $subRow->nama_sub_kategori }}
                                </td>
                                @foreach($tahunTampil as $tahun)
                                    @php $sum = 0;
                                    $count = 0; @endphp
                                    @foreach($periodeTampil as $periode)
                                        @php
                                            $nilai = $computeGrowth($subData[$subId] ?? [], $tahun->id_tahun, $periode->id_periode);
                                            if ($nilai !== null && !$calcMode) {
                                                $sum += $nilai;
                                                $count++;
                                            }
                                        @endphp
                                        <td class="border px-3 py-2 text-right">
                                            {{ $formatValue($nilai) }}
                                        </td>
                                    @endforeach
                                    @if($showTotalColumn)
                                        @php
                                            if ($calcMode) {
                                                $nilaiTotal = $computeTotalGrowth($subData[$subId] ?? [], $tahun->id_tahun);
                                            } else {
                                                $sum = 0;
                                                $count = 0;
                                                foreach ($periodeTotalList as $periodeTotal) {
                                                    $nilaiTotal = $subData[$subId][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                                    if ($nilaiTotal !== null) {
                                                        $sum += $nilaiTotal;
                                                        $count++;
                                                    }
                                                }
                                            }
                                        @endphp
                                        <td class="border px-3 py-2 text-right bg-emerald-200 text-emerald-900 font-semibold">
                                            @if($calcMode)
                                                {{ $formatValue($nilaiTotal ?? null) }}
                                            @else
                                                {{ $count > 0 ? number_format($sum, 1, ',', '.') : '-' }}
                                            @endif
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    @elseif($rowMode === 'sektor' || $rowMode === 'sektor_pengeluaran')
                        @foreach($rowItems as $sektorRow)
                            @php
                                $sektorId = $sektorRow->id_sektor;
                                $sektorLabel = $sektorRow->nama_sektor;
                            @endphp
                            <tr class="border-t bg-slate-50">
                                <td class="border px-3 py-2 font-semibold">
                                     {{ $sektorLabel }}
                                </td>
                                @foreach($tahunTampil as $tahun)
                                    @php $sum = 0;
                                    $count = 0; @endphp
                                    @foreach($periodeTampil as $periode)
                                        @php
                                            if ($calcMode === 'indeks') {
                                                $nilai = $computeIndeks($sektorDataBerlaku[$sektorId] ?? [], $sektorDataKonstan[$sektorId] ?? [], $tahun->id_tahun, $periode->id_periode);
                                            } elseif ($calcMode === 'laju') {
                                                $nilai = $computeLaju($sektorDataBerlaku[$sektorId] ?? [], $sektorDataKonstan[$sektorId] ?? [], $tahun->id_tahun, $periode->id_periode);
                                            } else {
                                                $dataSrc = $tipePdrb === 'berlaku' ? ($sektorDataBerlaku[$sektorId] ?? []) : ($sektorDataKonstan[$sektorId] ?? []);
                                                $nilai = $computeGrowth($dataSrc, $tahun->id_tahun, $periode->id_periode);
                                            }
                                        @endphp
                                        <td class="border px-3 py-2 text-right">
                                            {{ $formatValue($nilai) }}
                                        </td>
                                    @endforeach
                                    @if($showTotalColumn)
                                        @php
                                            if ($calcMode === 'indeks') {
                                                $nilaiTotal = $computeIndeksTotal($sektorDataBerlaku[$sektorId] ?? [], $sektorDataKonstan[$sektorId] ?? [], $tahun->id_tahun);
                                            } elseif ($calcMode === 'laju') {
                                                $nilaiTotal = $computeLajuTotal($sektorDataBerlaku[$sektorId] ?? [], $sektorDataKonstan[$sektorId] ?? [], $tahun->id_tahun);
                                            } elseif ($calcMode) {
                                                $dataSrc = $tipePdrb === 'berlaku' ? ($sektorDataBerlaku[$sektorId] ?? []) : ($sektorDataKonstan[$sektorId] ?? []);
                                                $nilaiTotal = $computeTotalGrowth($dataSrc, $tahun->id_tahun);
                                            } else {
                                                $sum = 0;
                                                $count = 0;
                                                $dataSrc = $tipePdrb === 'berlaku' ? ($sektorDataBerlaku[$sektorId] ?? []) : ($sektorDataKonstan[$sektorId] ?? []);
                                                foreach ($periodeTotalList as $periodeTotal) {
                                                    $val = $dataSrc[$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                                    if ($val !== null) {
                                                        $sum += $val;
                                                        $count++;
                                                    }
                                                }
                                                $nilaiTotal = $count > 0 ? $sum : null;
                                            }
                                        @endphp
                                        <td class="border px-3 py-2 text-right bg-emerald-200 text-emerald-900 font-semibold">
                                            @if($calcMode)
                                                {{ $formatValue($nilaiTotal) }}
                                            @else
                                                {{ $nilaiTotal !== null ? number_format($nilaiTotal, 1, ',', '.') : '-' }}
                                            @endif
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach

                        {{-- TOTAL ROW --}}
                        <tr class="border-t bg-slate-200 font-bold">
                            <td class="border px-3 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-800 text-white text-xs mr-2">T</span>
                                TOTAL
                            </td>
                            @foreach($tahunTampil as $tahun)
                                @php
                                    $allSektorBerlaku = [];
                                    $allSektorKonstan = [];
                                    // Pre-calculate totals for this year and context
                                    foreach ($rowItems as $sRow) {
                                        $sId = $sRow->id_sektor;
                                        foreach ($periodSource as $p) {
                                            $allSektorBerlaku[$sId][$tahun->id_tahun][$p->id_periode] = $sektorDataBerlaku[$sId][$tahun->id_tahun][$p->id_periode] ?? 0;
                                            $allSektorKonstan[$sId][$tahun->id_tahun][$p->id_periode] = $sektorDataKonstan[$sId][$tahun->id_tahun][$p->id_periode] ?? 0;
                                        }
                                        // Also need previous year for growth calc
                                        if ($calcMode) {
                                            $tahunValue = $tahunById->get($tahun->id_tahun)?->tahun;
                                            $prevYearId = $tahunByValue->get($tahunValue - 1)?->id_tahun;
                                            if ($prevYearId) {
                                                foreach ($periodSource as $p) {
                                                    $allSektorBerlaku[$sId][$prevYearId][$p->id_periode] = $sektorDataBerlaku[$sId][$prevYearId][$p->id_periode] ?? 0;
                                                    $allSektorKonstan[$sId][$prevYearId][$p->id_periode] = $sektorDataKonstan[$sId][$prevYearId][$p->id_periode] ?? 0;
                                                }
                                            }
                                        }
                                    }

                                    $totalDataBerlaku = [];
                                    $totalDataKonstan = [];
                                    $yearsToSum = [$tahun->id_tahun];
                                    if ($calcMode) {
                                        $tahunValue = $tahunById->get($tahun->id_tahun)?->tahun;
                                        $prevYearId = $tahunByValue->get($tahunValue - 1)?->id_tahun;
                                        if ($prevYearId)
                                            $yearsToSum[] = $prevYearId;
                                    }

                                    foreach ($yearsToSum as $tId) {
                                        foreach ($periodSource as $p) {
                                            $sumB = 0;
                                            $sumK = 0;
                                            foreach ($rowItems as $sRow) {
                                                $sumB += ($allSektorBerlaku[$sRow->id_sektor][$tId][$p->id_periode] ?? 0);
                                                $sumK += ($allSektorKonstan[$sRow->id_sektor][$tId][$p->id_periode] ?? 0);
                                            }
                                            $totalDataBerlaku[$tId][$p->id_periode] = $sumB;
                                            $totalDataKonstan[$tId][$p->id_periode] = $sumK;
                                        }
                                    }

                                    $dataSrcTotal = $tipePdrb === 'berlaku' ? $totalDataBerlaku : $totalDataKonstan;
                                @endphp

                                @foreach($periodeTampil as $periode)
                                    @php
                                        if ($calcMode === 'indeks') {
                                            $nilai = $computeIndeks($totalDataBerlaku, $totalDataKonstan, $tahun->id_tahun, $periode->id_periode);
                                        } elseif ($calcMode === 'laju') {
                                            $nilai = $computeLaju($totalDataBerlaku, $totalDataKonstan, $tahun->id_tahun, $periode->id_periode);
                                        } else {
                                            $nilai = $computeGrowth($dataSrcTotal, $tahun->id_tahun, $periode->id_periode);
                                        }
                                    @endphp
                                    <td class="border px-3 py-2 text-right">
                                        {{ $formatValue($nilai) }}
                                    </td>
                                @endforeach

                                @if($showTotalColumn)
                                    @php
                                        if ($calcMode === 'indeks') {
                                            $nilaiTotal = $computeIndeksTotal($totalDataBerlaku, $totalDataKonstan, $tahun->id_tahun);
                                        } elseif ($calcMode === 'laju') {
                                            $nilaiTotal = $computeLajuTotal($totalDataBerlaku, $totalDataKonstan, $tahun->id_tahun);
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
                                    @endphp
                                    <td class="border px-3 py-2 text-right bg-emerald-100 text-emerald-900 font-bold">
                                        @if($calcMode)
                                            {{ $formatValue($nilaiTotal) }}
                                        @else
                                            {{ $nilaiTotal !== null ? number_format($nilaiTotal, 1, ',', '.') : '-' }}
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @else
                        @php $catCounter = 0; @endphp
                        @foreach($rowItems as $kategoriRow)
                            @if($calcMode === 'distribusi' && in_array($kategoriRow->id_kategori, $pdrbKategoriIds ?? [], true))
                                @continue
                            @endif
                            @php
                                $catCounter++;
                                $catCode = $getKategoriCode($kategoriRow->nama_kategori, $indexToCode($catCounter));
                                $catPrefix = $getKategoriPrefix($kategoriRow->nama_kategori);
                                $subIndex = 0;
                                $kategoriId = $kategoriRow->id_kategori;
                                $kategoriLabel = $kategoriRow->nama_kategori;
                            @endphp
                            <tr class="border-t bg-slate-50">
                                <td class="border px-3 py-2 font-semibold">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded bg-slate-800 text-white text-xs mr-2">{{ $catPrefix ?: $catCode }}</span>
                                    {{ $kategoriLabel }}
                                </td>
                                @foreach($tahunTampil as $tahun)
                                    @php $sum = 0;
                                    $count = 0; @endphp
                                    @foreach($periodeTampil as $periode)
                                        @php
                                            if ($calcMode === 'indeks') {
                                                $nilai = $computeIndeks($kategoriDataBerlaku[$kategoriId] ?? [], $kategoriDataKonstan[$kategoriId] ?? [], $tahun->id_tahun, $periode->id_periode);
                                            } elseif ($calcMode === 'laju') {
                                                $nilai = $computeLaju($kategoriDataBerlaku[$kategoriId] ?? [], $kategoriDataKonstan[$kategoriId] ?? [], $tahun->id_tahun, $periode->id_periode);
                                            } else {
                                                $nilai = $computeGrowth($kategoriData[$kategoriId] ?? [], $tahun->id_tahun, $periode->id_periode);
                                            }
                                            if ($nilai !== null && !$calcMode) {
                                                $sum += $nilai;
                                                $count++;
                                            }
                                        @endphp
                                        <td class="border px-3 py-2 text-right">
                                            {{ $formatValue($nilai) }}
                                        </td>
                                    @endforeach
                                    @if($showTotalColumn)
                                        @php
                                            if ($calcMode === 'indeks') {
                                                $nilaiTotal = $computeIndeksTotal($kategoriDataBerlaku[$kategoriId] ?? [], $kategoriDataKonstan[$kategoriId] ?? [], $tahun->id_tahun);
                                            } elseif ($calcMode === 'laju') {
                                                $nilaiTotal = $computeLajuTotal($kategoriDataBerlaku[$kategoriId] ?? [], $kategoriDataKonstan[$kategoriId] ?? [], $tahun->id_tahun);
                                            } elseif ($calcMode) {
                                                $nilaiTotal = $computeTotalGrowth($kategoriData[$kategoriId] ?? [], $tahun->id_tahun);
                                            } else {
                                                $sum = 0;
                                                $count = 0;
                                                foreach ($periodeTotalList as $periodeTotal) {
                                                    $nilaiTotal = $kategoriData[$kategoriId][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                                    if ($nilaiTotal !== null) {
                                                        $sum += $nilaiTotal;
                                                        $count++;
                                                    }
                                                }
                                            }
                                        @endphp
                                        <td class="border px-3 py-2 text-right bg-emerald-200 text-emerald-900 font-semibold">
                                            @if($calcMode)
                                                {{ $formatValue($nilaiTotal ?? null) }}
                                            @else
                                                {{ $count > 0 ? number_format($sum, 1, ',', '.') : '-' }}
                                            @endif
                                        </td>
                                    @endif
                                @endforeach
                            </tr>

                            @if(($showSubRows ?? true))
                                @foreach($subByKategori->get($kategoriId, collect()) as $subRow)
                                    @php
                                        $subIndex++;
                                        $subCode = $getSubCode($subRow->id_sub_kategori, $subIndex, $catCode);
                                        $subId = $subRow->id_sub_kategori;
                                    @endphp
                                    <tr class="border-t hover:bg-slate-50 transition">
                                        <td class="border px-3 py-2">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-xs mr-2">{{ $subCode }}</span>
                                            {{ $subRow->nama_sub_kategori }}
                                        </td>
                                        @foreach($tahunTampil as $tahun)
                                            @php $sum = 0;
                                            $count = 0; @endphp
                                            @foreach($periodeTampil as $periode)
                                                @php
                                                    if ($calcMode === 'indeks') {
                                                        $nilai = $computeIndeks($subDataBerlaku[$subId] ?? [], $subDataKonstan[$subId] ?? [], $tahun->id_tahun, $periode->id_periode);
                                                    } elseif ($calcMode === 'laju') {
                                                        $nilai = $computeLaju($subDataBerlaku[$subId] ?? [], $subDataKonstan[$subId] ?? [], $tahun->id_tahun, $periode->id_periode);
                                                    } else {
                                                        $nilai = $computeGrowth($subData[$subId] ?? [], $tahun->id_tahun, $periode->id_periode);
                                                    }
                                                    if ($nilai !== null && !$calcMode) {
                                                        $sum += $nilai;
                                                        $count++;
                                                    }
                                                @endphp
                                                <td class="border px-3 py-2 text-right">
                                                    {{ $formatValue($nilai) }}
                                                </td>
                                            @endforeach
                                            @if($showTotalColumn)
                                                @php
                                                    if ($calcMode === 'indeks') {
                                                        $nilaiTotal = $computeIndeksTotal($subDataBerlaku[$subId] ?? [], $subDataKonstan[$subId] ?? [], $tahun->id_tahun);
                                                    } elseif ($calcMode === 'laju') {
                                                        $nilaiTotal = $computeLajuTotal($subDataBerlaku[$subId] ?? [], $subDataKonstan[$subId] ?? [], $tahun->id_tahun);
                                                    } elseif ($calcMode) {
                                                        $nilaiTotal = $computeTotalGrowth($subData[$subId] ?? [], $tahun->id_tahun);
                                                    } else {
                                                        $sum = 0;
                                                        $count = 0;
                                                        foreach ($periodeTotalList as $periodeTotal) {
                                                            $nilaiTotal = $subData[$subId][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                                            if ($nilaiTotal !== null) {
                                                                $sum += $nilaiTotal;
                                                                $count++;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                <td class="border px-3 py-2 text-right bg-emerald-200 text-emerald-900 font-semibold">
                                                    @if($calcMode)
                                                        {{ $formatValue($nilaiTotal ?? null) }}
                                                    @else
                                                        {{ $count > 0 ? number_format($sum, 1, ',', '.') : '-' }}
                                                    @endif
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    @endif

                    @if($calcMode === 'distribusi' && !empty($pdrbKategoriIds))
                        @php
                            $allKategori = $kategori ?? collect();
                            $pdrbRows = collect($pdrbKategoriIds)->map(function ($id) use ($rowItems, $allKategori) {
                                return $rowItems->firstWhere('id_kategori', $id) ?? $allKategori->firstWhere('id_kategori', $id);
                            })->filter();
                        @endphp

                        @if($pdrbRows->isNotEmpty())
                            @foreach($pdrbRows as $pdrbRow)
                                @php
                                    $pdrbId = $pdrbRow->id_kategori;
                                @endphp
                                <tr class="border-t bg-blue-50 font-semibold">
                                    <td class="border px-3 py-2">
                                        {{ $pdrbRow->nama_kategori }}
                                    </td>

                                    @if($columnMode === 'wilayah')
                                        @foreach($tahunTampil as $tahun)
                                            @foreach($periodeTampil as $periode)
                                                @foreach($columnItems as $colWil)
                                                    @php
                                                        $wilId = $colWil->id_wilayah;
                                                        $nilai = $computeGrowth($kategoriWilayahData[$pdrbId][$wilId] ?? [], $tahun->id_tahun, $periode->id_periode, $wilId);
                                                    @endphp
                                                    <td class="border px-3 py-2 text-right">
                                                        {{ $formatValue($nilai) }}
                                                    </td>
                                                @endforeach
                                            @endforeach
                                            @if($showTotalColumn)
                                                @foreach($columnItems as $colWil)
                                                    @php
                                                        $wilId = $colWil->id_wilayah;
                                                        $sum = 0;
                                                        $count = 0;
                                                        foreach ($periodeTotalList as $periodeTotal) {
                                                            $nilaiTotal = $kategoriWilayahData[$pdrbId][$wilId][$tahun->id_tahun][$periodeTotal->id_periode] ?? null;
                                                            if ($nilaiTotal !== null) {
                                                                $sum += $nilaiTotal;
                                                                $count++;
                                                            }
                                                        }
                                                    @endphp
                                                    <td class="border px-3 py-2 text-right bg-emerald-200 text-emerald-900 font-semibold">
                                                        @if($count > 0)
                                                            {{ $formatValue($computeTotalGrowth($kategoriWilayahData[$pdrbId][$wilId] ?? [], $tahun->id_tahun)) }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @else
                                        @foreach($tahunTampil as $tahun)
                                            @foreach($periodeTampil as $periode)
                                                @php
                                                    $nilai = $computeGrowth($kategoriData[$pdrbId] ?? [], $tahun->id_tahun, $periode->id_periode);
                                                @endphp
                                                <td class="border px-3 py-2 text-right">
                                                    {{ $formatValue($nilai) }}
                                                </td>
                                            @endforeach
                                            @if($showTotalColumn)
                                                @php
                                                    $nilaiTotal = $computeTotalGrowth($kategoriData[$pdrbId] ?? [], $tahun->id_tahun);
                                                @endphp
                                                <td class="border px-3 py-2 text-right bg-emerald-200 text-emerald-900 font-semibold">
                                                    {{ $formatValue($nilaiTotal ?? null) }}
                                                </td>
                                            @endif
                                        @endforeach
                                    @endif
                                </tr>
                            @endforeach
                        @endif
                    @endif
                </tbody>
            </table>
        </div>
    @endif
</section>