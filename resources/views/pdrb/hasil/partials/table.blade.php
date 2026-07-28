{{-- TAMPILKAN TABEL JIKA ADA FILTER YANG DIPILIH --}}
@if(request('id_tahun') || request('id_periode') || request('scope_wilayah') || request('rentang_tahun'))
    <div class="bg-white shadow rounded-xl overflow-hidden flex flex-col min-h-0 h-full">
        @if($selectedWilayahs->count() > 0 && $tampilTahun->count() > 0)
            <div class="p-4 flex-1 min-h-0">
                @php
                    $wilayah = $selectedWilayahs->first();

                    // Hitung jumlah tahun
                    $jumlahTahun = $tampilTahun->count();
                    $jumlahPeriode = $periode->count();

                    // Tentukan tipe
                    $isPercentageType = in_array($typePdrb, ['y-on-y', 'q-to-q', 'c-to-c', 'laju', 'indeks', 'distribusi']);
                    $isDistribusiType = ($typePdrb == 'distribusi');
                    $isIndeksType = ($typePdrb == 'indeks');
                    $isLajuType = ($typePdrb == 'laju');
                    $isADHBType = ($typePdrb == 'berlaku');
                    $isADHKType = ($typePdrb == 'konstan');
                    $isQtoQType = ($typePdrb == 'q-to-q');
                    $isYonYType = ($typePdrb == 'y-on-y');
                    $isCtoCType = ($typePdrb == 'c-to-c');
                    $isComparativeType = $isQtoQType || $isYonYType || $isCtoCType;

                    // TAMPILKAN KOLOM TOTAL hanya jika semua periode ditampilkan
                    $showTotalColumn = $showAllPeriode;
                    $periodIds = $periode->pluck('id_periode')->toArray();

                    // Force min width for Q-to-Q to ensure horizontal scroll
                    $yearCount = $showAllTahun ? $tampilTahun->count() : 1;
                    $periodCount = $showAllPeriode ? $periode->count() : 1;
                    $totalColumnCount = ($yearCount * $periodCount) + ($showTotalColumn ? $yearCount : 0);
                    $qtoqMinWidth = $isQtoQType ? max(1200, 260 + ($totalColumnCount * 110)) : null;

                    // Kelompokkan data
                    $groupedData = [];

                    // Data kategori
                    foreach ($nilaiKategori as $nk) {
                        $groupedData['kategori'][$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = [
                            'nilai' => $nk->nilai,
                            'tipe_perhitungan' => $nk->tipe_perhitungan ?? null,
                            'data_valid' => $nk->data_valid ?? true
                        ];
                    }

                    // Data sub kategori
                    foreach ($nilaiSub as $ns) {
                        $groupedData['sub'][$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = [
                            'nilai' => $ns->nilai,
                            'kategori_id' => $ns->kategori_id ?? null,
                            'tipe_perhitungan' => $ns->tipe_perhitungan ?? null,
                            'data_valid' => $ns->data_valid ?? true
                        ];
                    }

                    // Mapping total tahunan (untuk indeks & laju)
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

                    // Ambil data ADHK untuk perhitungan TOTAL Q-to-Q dan Y-on-Y
                    $dataADHKKategori = [];
                    $dataADHKSub = [];
                    $totalADHKTahunanKategori = [];
                    $totalADHKTahunanSub = [];

                    if ($isComparativeType) {
                        $selectedYearIds = $tampilTahun->pluck('id_tahun')->toArray();

                        foreach ($dataADHKKategoriRaw as $nk) {
                            $dataADHKKategori[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;

                            if (!isset($totalADHKTahunanKategori[$nk->id_kategori][$nk->id_tahun])) {
                                $totalADHKTahunanKategori[$nk->id_kategori][$nk->id_tahun] = 0;
                            }
                            $totalADHKTahunanKategori[$nk->id_kategori][$nk->id_tahun] += $nk->nilai;
                        }

                        foreach ($dataADHKSubRaw as $ns) {
                            $dataADHKSub[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;

                            if (!isset($totalADHKTahunanSub[$ns->id_sub_kategori][$ns->id_tahun])) {
                                $totalADHKTahunanSub[$ns->id_sub_kategori][$ns->id_tahun] = 0;
                            }
                            $totalADHKTahunanSub[$ns->id_sub_kategori][$ns->id_tahun] += $ns->nilai;
                        }

                        $prevYears = [];
                        foreach ($tampilTahun as $t) {
                            $prevYear = $t->tahun - 1;
                            $prevTahun = \App\Models\Tahun::where('tahun', $prevYear)->first();
                            if ($prevTahun) {
                                $prevYears[] = $prevTahun->id_tahun;
                            }
                        }

                        if (count($prevYears) > 0) {
                            $prevADHKKategoriRaw = \App\Models\NilaiKategori::whereIn('id_tahun', $prevYears)
                                ->where('tipe_pdrb', 'konstan')
                                ->where('id_wilayah', $wilayah->id_wilayah)
                                ->get();

                            $prevADHKSubRaw = \App\Models\NilaiSubKategori::whereIn('id_tahun', $prevYears)
                                ->where('tipe_pdrb', 'konstan')
                                ->where('id_wilayah', $wilayah->id_wilayah)
                                ->get();

                            foreach ($prevADHKKategoriRaw as $nk) {
                                if (!in_array($nk->id_tahun, $selectedYearIds, true)) {
                                    if (!isset($totalADHKTahunanKategori[$nk->id_kategori][$nk->id_tahun])) {
                                        $totalADHKTahunanKategori[$nk->id_kategori][$nk->id_tahun] = 0;
                                    }
                                    $totalADHKTahunanKategori[$nk->id_kategori][$nk->id_tahun] += $nk->nilai;
                                }
                                $dataADHKKategori[$nk->id_kategori][$nk->id_tahun][$nk->id_periode] = $nk->nilai;
                            }

                            foreach ($prevADHKSubRaw as $ns) {
                                if (!in_array($ns->id_tahun, $selectedYearIds, true)) {
                                    if (!isset($totalADHKTahunanSub[$ns->id_sub_kategori][$ns->id_tahun])) {
                                        $totalADHKTahunanSub[$ns->id_sub_kategori][$ns->id_tahun] = 0;
                                    }
                                    $totalADHKTahunanSub[$ns->id_sub_kategori][$ns->id_tahun] += $ns->nilai;
                                }
                                $dataADHKSub[$ns->id_sub_kategori][$ns->id_tahun][$ns->id_periode] = $ns->nilai;
                            }
                        }
                    }

                    function formatAngka($nilai, $isPercentage)
                    {
                        if ($nilai === null || $nilai === '')
                            return '-';
                        return $isPercentage
                            ? number_format($nilai, 2, ',', '.')
                            : number_format($nilai, 1, ',', '.');
                    }

                    function hitungQtoQTotalTahunan($idKategori, $tahunId, $totalADHKTahunanData, $tampilTahunModel, $isSub = false)
                    {
                        $tahunSekarang = $tampilTahunModel->where('id_tahun', $tahunId)->first();
                        if (!$tahunSekarang)
                            return null;

                        $tahunSebelum = $tahunSekarang->tahun - 1;
                        $tahunSebelumModel = \App\Models\Tahun::where('tahun', $tahunSebelum)->first();

                        if (!$tahunSebelumModel)
                            return null;

                        $totalSekarang = $totalADHKTahunanData[$idKategori][$tahunId] ?? 0;
                        $totalSebelum = $totalADHKTahunanData[$idKategori][$tahunSebelumModel->id_tahun] ?? 0;

                        if ($totalSebelum == 0)
                            return null;

                        return (($totalSekarang / $totalSebelum) - 1) * 100;
                    }

                    function hitungQtoQTriwulan($nilaiSekarang, $nilaiSebelum)
                    {
                        if ($nilaiSekarang === null || $nilaiSebelum === null || $nilaiSebelum == 0) {
                            return null;
                        }
                        return (($nilaiSekarang / $nilaiSebelum) - 1) * 100;
                    }

                    function getLastPeriodNilai($dataByPeriod, $periodIds)
                    {
                        foreach (array_reverse($periodIds) as $pid) {
                            if (isset($dataByPeriod[$pid]) && $dataByPeriod[$pid]['nilai'] !== null) {
                                return $dataByPeriod[$pid]['nilai'];
                            }
                        }
                        return null;
                    }

                    function indexToCode($num)
                    {
                        $code = '';
                        while ($num > 0) {
                            $num--;
                            $code = chr(65 + ($num % 26)) . $code;
                            $num = intdiv($num, 26);
                        }
                        return $code;
                    }

                    function getKategoriPrefix($nama)
                    {
                        $map = [
                            'Jasa Perusahaan' => 'M,N',
                            'Jasa lainnya' => 'R,S,T,U',
                            'Produk Domestik Regional Bruto' => 'PDRB',
                            'Produk Domestik Regional Bruto Non Migas' => 'NON MIGAS',
                            'PRODUK DOMESTIK REGIONAL BRUTO LAPUS' => 'PDRB LAPUS',
                        ];

                        foreach ($map as $key => $prefix) {
                            if (strcasecmp($nama, $key) === 0) {
                                return $prefix;
                            }
                        }

                        return '';
                    }

                    function getKategoriCode($nama, $defaultCode)
                    {
                        $specialMap = [
                            'Pertanian, Peternakan, Perburuan dan Jasa Pertanian' => 'A1',
                            'Kehutanan dan Penebangan Kayu' => '2',
                            'Perikanan' => '3',
                        ];

                        return $specialMap[$nama] ?? $defaultCode;
                    }

                    function getSubCode($subId, $subIndex, $catCode)
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
                @endphp



                {{-- Tabel --}}
                <div class="sticky-table-container h-full">
                    <table
                        class="w-full text-[12px] text-slate-800 sticky-table hasil-table {{ $isQtoQType ? 'force-scroll-x' : '' }}"
                        @if($qtoqMinWidth) style="min-width: {{ $qtoqMinWidth }}px;" @endif>
                        <thead>
                            <tr>
                                <th rowspan="{{ $showAllPeriode ? 2 : 1 }}" class="th-kategori px-3 py-2.5 text-left sticky-col-1">
                                    Kategori</th>

                                @if($showAllTahun)
                                    @foreach($tampilTahun as $t)
                                        @if($showAllPeriode)
                                            <th colspan="{{ $periode->count() }}" class="th-year px-3 py-2.5 text-center">
                                                {{ $t->tahun }}
                                            </th>
                                            @if($showTotalColumn)
                                                <th rowspan="2" class="th-total px-3 py-2.5 text-center">
                                                    Total
                                                </th>
                                            @endif
                                        @else
                                            <th class="th-year px-3 py-2.5 text-center">
                                                {{ $t->tahun }}
                                            </th>
                                        @endif
                                    @endforeach
                                @else
                                    @if($showAllPeriode)
                                        <th colspan="{{ $periode->count() }}" class="th-year px-3 py-2.5 text-center">
                                            {{ $tampilTahun->first()->tahun ?? '' }}
                                        </th>
                                        @if($showTotalColumn)
                                            <th rowspan="2" class="th-total px-3 py-2.5 text-center">
                                                Total
                                            </th>
                                        @endif
                                    @else
                                        <th class="th-year px-3 py-2.5 text-center">
                                            {{ $tampilTahun->first()->tahun ?? '' }}
                                        </th>
                                    @endif
                                @endif
                            </tr>

                            @if($showAllPeriode)
                                <tr>
                                    @if($showAllTahun)
                                        @foreach($tampilTahun as $t)
                                            @foreach($periode as $p)
                                                <th class="th-period px-3 py-2 text-center">{{ $p->nama_periode }}</th>
                                            @endforeach
                                        @endforeach
                                    @else
                                        @foreach($periode as $p)
                                            <th class="th-period px-3 py-2 text-center">{{ $p->nama_periode }}</th>
                                        @endforeach
                                    @endif
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @php $catCounter = 0; @endphp
                            @foreach($kategori as $kat)


                                @php
                                    $catCounter++;
                                    $catCode = getKategoriCode($kat->nama_kategori, indexToCode($catCounter));
                                    $subIndex = 0;
                                    $catPrefix = getKategoriPrefix($kat->nama_kategori);
                                @endphp

                                {{-- BARIS KATEGORI UTAMA --}}
                                <tr class="kategori-row">
                                    <td class="td-kategori px-3 py-2.5 sticky-col-1">
                                        <div class="flex items-center gap-2">
                                            <span class="kategori-badge">{{ $catPrefix ?: $catCode }}</span>
                                            {{ $kat->nama_kategori }}
                                        </div>
                                    </td>

                                    @if($showAllTahun)
                                        @foreach($tampilTahun as $t)
                                            @if($showAllPeriode)
                                                @php
                                                    $arrayNilaiPeriode = [];
                                                @endphp
                                                @foreach($periode as $p)
                                                    @php
                                                        $dataKategori = $groupedData['kategori'][$kat->id_kategori][$t->id_tahun][$p->id_periode] ?? null;
                                                        $nilai = $dataKategori['nilai'] ?? null;
                                                        $arrayNilaiPeriode[] = $nilai;
                                                    @endphp
                                                    <td class="td-data px-3 py-2 text-right">
                                                        {{ formatAngka($nilai, $isPercentageType) }}
                                                    </td>
                                                @endforeach
                                                @if($showTotalColumn)
                                                    <td class="td-total-cell px-3 py-2 text-right">
                                                        @php
                                                            if ($isQtoQType || $isYonYType || $isCtoCType) {
                                                                $totalTahunan = $totalTahunanKategoriMap[$kat->id_kategori][$t->id_tahun] ?? null;
                                                                if ($totalTahunan === null) {
                                                                    $periodData = $groupedData['kategori'][$kat->id_kategori][$t->id_tahun] ?? [];
                                                                    $totalTahunan = getLastPeriodNilai($periodData, $periodIds);
                                                                }
                                                                echo formatAngka($totalTahunan, true);
                                                            } elseif ($isIndeksType || $isLajuType) {
                                                                $totalTahunan = $totalTahunanKategoriMap[$kat->id_kategori][$t->id_tahun] ?? null;
                                                                echo formatAngka($totalTahunan, true);
                                                            } else {
                                                                $nilaiTampil = 0;
                                                                $countValidData = 0;

                                                                foreach ($arrayNilaiPeriode as $nilaiPeriode) {
                                                                    if ($nilaiPeriode !== null) {
                                                                        if ($isPercentageType) {
                                                                            $nilaiTampil += $nilaiPeriode;
                                                                            $countValidData++;
                                                                        } else {
                                                                            $nilaiTampil += $nilaiPeriode;
                                                                            $countValidData++;
                                                                        }
                                                                    }
                                                                }

                                                                if ($isPercentageType && $countValidData > 0) {
                                                                    $nilaiTampil = $nilaiTampil / $countValidData;
                                                                }

                                                                if ($countValidData > 0) {
                                                                    echo formatAngka($nilaiTampil, $isPercentageType);
                                                                } else {
                                                                    echo '-';
                                                                }
                                                            }
                                                        @endphp
                                                    </td>
                                                @endif
                                            @else
                                                @php
                                                    $dataKategori = $groupedData['kategori'][$kat->id_kategori][$t->id_tahun][$selectedPeriode] ?? null;
                                                    $nilai = $dataKategori['nilai'] ?? null;
                                                @endphp
                                                <td class="td-data px-3 py-2 text-right">
                                                    {{ formatAngka($nilai, $isPercentageType) }}
                                                </td>
                                            @endif
                                        @endforeach
                                    @else
                                        @php
                                            $tahunId = $tampilTahun->first()->id_tahun ?? null;
                                        @endphp
                                        @if($showAllPeriode)
                                            @php
                                                $arrayNilaiPeriode = [];
                                            @endphp
                                            @foreach($periode as $p)
                                                @php
                                                    $dataKategori = $groupedData['kategori'][$kat->id_kategori][$tahunId][$p->id_periode] ?? null;
                                                    $nilai = $dataKategori['nilai'] ?? null;
                                                    $arrayNilaiPeriode[] = $nilai;
                                                @endphp
                                                <td class="td-data px-3 py-2 text-right">
                                                    {{ formatAngka($nilai, $isPercentageType) }}
                                                </td>
                                            @endforeach
                                            @if($showTotalColumn)
                                                <td class="td-total-cell px-3 py-2 text-right">
                                                    @php
                                                        if ($isQtoQType || $isYonYType || $isCtoCType) {
                                                            $totalTahunan = $totalTahunanKategoriMap[$kat->id_kategori][$tahunId] ?? null;
                                                            if ($totalTahunan === null) {
                                                                $periodData = $groupedData['kategori'][$kat->id_kategori][$tahunId] ?? [];
                                                                $totalTahunan = getLastPeriodNilai($periodData, $periodIds);
                                                            }
                                                            echo formatAngka($totalTahunan, true);
                                                        } elseif ($isIndeksType || $isLajuType) {
                                                            $totalTahunan = $totalTahunanKategoriMap[$kat->id_kategori][$tahunId] ?? null;
                                                            echo formatAngka($totalTahunan, true);
                                                        } else {
                                                            $nilaiTampil = 0;
                                                            $countValidData = 0;

                                                            foreach ($arrayNilaiPeriode as $nilaiPeriode) {
                                                                if ($nilaiPeriode !== null) {
                                                                    if ($isPercentageType) {
                                                                        $nilaiTampil += $nilaiPeriode;
                                                                        $countValidData++;
                                                                    } else {
                                                                        $nilaiTampil += $nilaiPeriode;
                                                                        $countValidData++;
                                                                    }
                                                                }
                                                            }

                                                            if ($isPercentageType && $countValidData > 0) {
                                                                $nilaiTampil = $nilaiTampil / $countValidData;
                                                            }

                                                            if ($countValidData > 0) {
                                                                echo formatAngka($nilaiTampil, $isPercentageType);
                                                            } else {
                                                                echo '-';
                                                            }
                                                        }
                                                    @endphp
                                                </td>
                                            @endif
                                        @else
                                            @php
                                                $dataKategori = $groupedData['kategori'][$kat->id_kategori][$tahunId][$selectedPeriode] ?? null;
                                                $nilai = $dataKategori['nilai'] ?? null;
                                            @endphp
                                            <td class="td-data px-3 py-2 text-right">
                                                {{ formatAngka($nilai, $isPercentageType) }}
                                            </td>
                                        @endif
                                    @endif
                                </tr>

                                {{-- BARIS SUB KATEGORI DALAM KATEGORI YANG SAMA --}}
                                @foreach($sub->where('id_kategori', $kat->id_kategori) as $s)
                                    @php $subIndex++; @endphp
                                    <tr class="sub-row">
                                        <td class="td-sub px-3 py-2 sticky-col-1">
                                            <div class="flex items-center gap-2 pl-6">
                                                <span class="sub-badge">{{ getSubCode($s->id_sub_kategori, $subIndex, $catCode) }}</span>
                                                {{ $s->nama_sub_kategori }}
                                            </div>
                                        </td>

                                        @if($showAllTahun)
                                            @foreach($tampilTahun as $t)
                                                @if($showAllPeriode)
                                                    @php
                                                        $arrayNilaiSubPeriode = [];
                                                    @endphp
                                                    @foreach($periode as $p)
                                                        @php
                                                            $dataSub = $groupedData['sub'][$s->id_sub_kategori][$t->id_tahun][$p->id_periode] ?? null;
                                                            $subNilai = $dataSub['nilai'] ?? null;
                                                            $arrayNilaiSubPeriode[] = $subNilai;
                                                        @endphp
                                                        <td class="td-data px-3 py-2 text-right">
                                                            {{ formatAngka($subNilai, $isPercentageType) }}
                                                        </td>
                                                    @endforeach
                                                    @if($showTotalColumn)
                                                        <td class="td-total-cell px-3 py-2 text-right">
                                                            @php
                                                                if ($isQtoQType || $isYonYType || $isCtoCType) {
                                                                    $totalTahunan = $totalTahunanSubMap[$s->id_sub_kategori][$t->id_tahun] ?? null;
                                                                    if ($totalTahunan === null) {
                                                                        $periodData = $groupedData['sub'][$s->id_sub_kategori][$t->id_tahun] ?? [];
                                                                        $totalTahunan = getLastPeriodNilai($periodData, $periodIds);
                                                                    }
                                                                    echo formatAngka($totalTahunan, true);
                                                                } elseif ($isIndeksType || $isLajuType) {
                                                                    $totalTahunan = $totalTahunanSubMap[$s->id_sub_kategori][$t->id_tahun] ?? null;
                                                                    echo formatAngka($totalTahunan, true);
                                                                } else {
                                                                    $nilaiSubTampil = 0;
                                                                    $countValidSubData = 0;

                                                                    foreach ($arrayNilaiSubPeriode as $nilaiSubPeriode) {
                                                                        if ($nilaiSubPeriode !== null) {
                                                                            if ($isPercentageType) {
                                                                                $nilaiSubTampil += $nilaiSubPeriode;
                                                                                $countValidSubData++;
                                                                            } else {
                                                                                $nilaiSubTampil += $nilaiSubPeriode;
                                                                                $countValidSubData++;
                                                                            }
                                                                        }
                                                                    }

                                                                    if ($isPercentageType && $countValidSubData > 0) {
                                                                        $nilaiSubTampil = $nilaiSubTampil / $countValidSubData;
                                                                    }

                                                                    if ($countValidSubData > 0) {
                                                                        echo formatAngka($nilaiSubTampil, $isPercentageType);
                                                                    } else {
                                                                        echo '-';
                                                                    }
                                                                }
                                                            @endphp
                                                        </td>
                                                    @endif
                                                @else
                                                    @php
                                                        $dataSub = $groupedData['sub'][$s->id_sub_kategori][$t->id_tahun][$selectedPeriode] ?? null;
                                                        $subNilai = $dataSub['nilai'] ?? null;
                                                    @endphp
                                                    <td class="td-data px-3 py-2 text-right">
                                                        {{ formatAngka($subNilai, $isPercentageType) }}
                                                    </td>
                                                @endif
                                            @endforeach
                                        @else
                                            @php
                                                $tahunId = $tampilTahun->first()->id_tahun ?? null;
                                            @endphp
                                            @if($showAllPeriode)
                                                @php
                                                    $arrayNilaiSubPeriode = [];
                                                @endphp
                                                @foreach($periode as $p)
                                                    @php
                                                        $dataSub = $groupedData['sub'][$s->id_sub_kategori][$tahunId][$p->id_periode] ?? null;
                                                        $subNilai = $dataSub['nilai'] ?? null;
                                                        $arrayNilaiSubPeriode[] = $subNilai;
                                                    @endphp
                                                    <td class="td-data px-3 py-2 text-right">
                                                        {{ formatAngka($subNilai, $isPercentageType) }}
                                                    </td>
                                                @endforeach
                                                @if($showTotalColumn)
                                                    <td class="td-total-cell px-3 py-2 text-right">
                                                        @php
                                                            if ($isQtoQType || $isYonYType || $isCtoCType) {
                                                                $totalTahunan = $totalTahunanSubMap[$s->id_sub_kategori][$tahunId] ?? null;
                                                                if ($totalTahunan === null) {
                                                                    $periodData = $groupedData['sub'][$s->id_sub_kategori][$tahunId] ?? [];
                                                                    $totalTahunan = getLastPeriodNilai($periodData, $periodIds);
                                                                }
                                                                echo formatAngka($totalTahunan, true);
                                                            } elseif ($isIndeksType || $isLajuType) {
                                                                $totalTahunan = $totalTahunanSubMap[$s->id_sub_kategori][$tahunId] ?? null;
                                                                echo formatAngka($totalTahunan, true);
                                                            } else {
                                                                $nilaiSubTampil = 0;
                                                                $countValidSubData = 0;

                                                                foreach ($arrayNilaiSubPeriode as $nilaiSubPeriode) {
                                                                    if ($nilaiSubPeriode !== null) {
                                                                        if ($isPercentageType) {
                                                                            $nilaiSubTampil += $nilaiSubPeriode;
                                                                            $countValidSubData++;
                                                                        } else {
                                                                            $nilaiSubTampil += $nilaiSubPeriode;
                                                                            $countValidSubData++;
                                                                        }
                                                                    }
                                                                }

                                                                if ($isPercentageType && $countValidSubData > 0) {
                                                                    $nilaiSubTampil = $nilaiSubTampil / $countValidSubData;
                                                                }

                                                                if ($countValidSubData > 0) {
                                                                    echo formatAngka($nilaiSubTampil, $isPercentageType);
                                                                } else {
                                                                    echo '-';
                                                                }
                                                            }
                                                        @endphp
                                                    </td>
                                                @endif
                                            @else
                                                @php
                                                    $dataSub = $groupedData['sub'][$s->id_sub_kategori][$tahunId][$selectedPeriode] ?? null;
                                                    $subNilai = $dataSub['nilai'] ?? null;
                                                @endphp
                                                <td class="td-data px-3 py-2 text-right">
                                                    {{ formatAngka($subNilai, $isPercentageType) }}
                                                </td>
                                            @endif
                                        @endif
                                    </tr>
                                @endforeach

                                {{-- SPASI ANTAR KATEGORI --}}
                                <tr>
                                    @php
                                        $colspan = 1;
                                        if ($showAllTahun) {
                                            if ($showAllPeriode) {
                                                $colspan += ($jumlahTahun * $jumlahPeriode) +
                                                    ($showTotalColumn ? $jumlahTahun : 0);
                                            } else {
                                                $colspan += $jumlahTahun;
                                            }
                                        } else {
                                            if ($showAllPeriode) {
                                                $colspan += $jumlahPeriode + ($showTotalColumn ? 1 : 0);
                                            } else {
                                                $colspan += 1;
                                            }
                                        }
                                    @endphp
                                    <td colspan="{{ $colspan }}" class="py-2 bg-gray-50"></td>
                                </tr>
                            @endforeach



                        </tbody>
                    </table>
                </div>

                @if($isQtoQType)
                    <div class="mt-4 p-3 bg-gray-100 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-2">Keterangan Perhitungan Q-to-Q:</h4>
                        <div class="text-sm text-gray-700 space-y-1">
                            <p><strong>Q-to-Q Triwulan:</strong> (Nilai ADHK kumulatif s/d triwulan sekarang / kumulatif s/d
                                triwulan sebelumnya - 1) x 100</p>
                            <p><strong>Q-to-Q Total Tahun:</strong> (Total ADHK Tahun Sekarang / Total ADHK Tahun Sebelumnya - 1) x
                                100</p>
                            <p class="text-xs text-gray-600">*Total ADHK Tahun = Σ(Q1 + Q2 + Q3 + Q4) dari data ADHK (Konstan)</p>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="p-8 text-center bg-gray-50">
                @if($selectedWilayahs->count() == 0)
                    <p class="text-gray-500 italic">
                        Tidak ada data untuk wilayah yang dipilih. Silakan pilih filter yang berbeda.
                    </p>
                @elseif($tampilTahun->count() == 0)
                    <p class="text-gray-500 italic">
                        Tidak ada data untuk tahun yang dipilih dalam rentang ini.
                    </p>
                @endif
            </div>
        @endif
    </div>
@else
    <div class="bg-white shadow rounded-xl p-8 text-center">
        <p class="text-gray-500 italic">
            Pilih Tahun, Periode, dan Wilayah untuk menampilkan hasil.
        </p>
    </div>
@endif

<style>
    /* =============================================
       HASIL PDRB TABLE - PREMIUM DESIGN SYSTEM
    ============================================= */

    /* Base Table */
    .hasil-table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(30,41,59,0.07);
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
    }

    /* ---- HEADER ---- */
    /* Row 1: Year group headers */
    .hasil-table thead tr:first-child th {
        background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
        color: #ffffff;
        font-weight: 700;
        font-size: 11.5px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        border-bottom: 2px solid #1d4ed8;
        border-right: 1px solid rgba(255,255,255,0.18);
    }
    .hasil-table thead tr:first-child th:last-child {
        border-right: none;
    }

    /* Specific header variants */
    .th-kategori {
        background: linear-gradient(135deg, #0f2942 0%, #1e40af 100%) !important;
        color: #fff !important;
        min-width: 220px;
        position: sticky;
        left: 0;
        z-index: 20;
    }
    .th-year {
        background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%) !important;
        color: #fff !important;
    }
    .th-total {
        background: linear-gradient(135deg, #065f46 0%, #059669 100%) !important;
        color: #fff !important;
    }

    /* Row 2: Period sub-headers */
    .hasil-table thead tr:nth-child(2) th {
        background: linear-gradient(180deg, #dbeafe 0%, #eff6ff 100%);
        color: #1e40af;
        font-weight: 600;
        font-size: 11px;
        border-bottom: 2px solid #bfdbfe;
        border-right: 1px solid #bfdbfe;
    }
    .th-period {
        background: linear-gradient(180deg, #dbeafe 0%, #eff6ff 100%) !important;
        color: #1d4ed8 !important;
    }

    /* ---- BODY CELLS ---- */
    .td-data, .td-kategori, .td-sub, .td-total-cell {
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e8edf5;
        transition: background-color 0.15s ease;
    }
    .td-data:last-child, .td-total-cell:last-child {
        border-right: none;
    }

    /* ---- KATEGORI ROW ---- */
    .kategori-row {
        background-color: #f0f5ff;
    }
    .kategori-row:hover {
        background-color: #dbeafe !important;
    }
    .td-kategori {
        background-color: #eef3ff;
        font-weight: 700;
        font-size: 12px;
        color: #1e3a5f;
        position: sticky;
        left: 0;
        z-index: 10;
        border-right: 2px solid #bfdbfe !important;
        border-bottom: 1px solid #c7d7fa !important;
    }
    .kategori-row .td-data {
        font-weight: 600;
        color: #1e3a5f;
        background-color: #f5f8ff;
    }
    .kategori-row:hover .td-data,
    .kategori-row:hover .td-kategori {
        background-color: #dbeafe;
    }

    /* ---- SUB-KATEGORI ROW ---- */
    .sub-row {
        background-color: #ffffff;
    }
    .sub-row:hover {
        background-color: #f8faff !important;
    }
    .sub-row:hover .td-data,
    .sub-row:hover .td-sub {
        background-color: #f0f5ff;
    }
    .td-sub {
        background-color: #ffffff;
        font-size: 11.5px;
        color: #374151;
        position: sticky;
        left: 0;
        z-index: 10;
        border-right: 2px solid #e2e8f0 !important;
    }
    .sub-row .td-data {
        color: #374151;
        font-size: 11.5px;
    }

    /* ---- TOTAL COLUMN ---- */
    .td-total-cell {
        background-color: #ecfdf5 !important;
        color: #065f46 !important;
        font-weight: 600;
        border-left: 2px solid #a7f3d0 !important;
        border-right: none;
    }
    .kategori-row .td-total-cell {
        background-color: #d1fae5 !important;
        color: #047857 !important;
        font-weight: 700;
    }

    /* ---- SPACER ROW ---- */
    .hasil-table tbody tr:has(td[colspan]) td {
        background-color: #f8fafc;
        height: 6px;
        border-bottom: 2px solid #e2e8f0;
    }

    /* ---- BADGES ---- */
    .kategori-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        padding: 2px 5px;
        border-radius: 4px;
        min-width: 22px;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        flex-shrink: 0;
    }
    .sub-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #e0e7ff;
        color: #3730a3;
        font-size: 9px;
        font-weight: 700;
        padding: 1px 4px;
        border-radius: 3px;
        min-width: 18px;
        flex-shrink: 0;
    }

    /* ---- STICKY FIRST COLUMN ---- */
    .sticky-col-1 {
        position: sticky;
        left: 0;
        z-index: 10;
        box-shadow: 2px 0 8px rgba(30,58,138,0.08);
    }
    .hasil-table thead .sticky-col-1 {
        z-index: 21;
    }
</style>