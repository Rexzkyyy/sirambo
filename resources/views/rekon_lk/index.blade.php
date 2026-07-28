@extends('layouts.main')

@section('title', 'Rekap Lembar Kerja')

@section('content')
    <style>
        html,
        body {
            height: 100%;
        }

        main {
            overflow: hidden !important;
        }

        main>div {
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        main>div>div {
            height: 100%;
            min-height: 0 !important;
            display: flex;
            flex-direction: column;
        }

        .rk-wrap {
            overflow-x: auto;
        }

        .rk-table {
            border-collapse: collapse;
            font-size: 13px;
            min-width: 900px;
            width: 100%;
        }

        .rk-table th,
        .rk-table td {
            border: 1px solid #94a3b8;
            padding: 2px 4px;
            white-space: nowrap;
        }

        .rk-table thead th {
            background: #f1f5f9;
            text-align: center;
            font-weight: 700;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Uraian column */
        .rk-table td.uraian-cell,
        .rk-table th.uraian-cell {
            min-width: 110px;
            text-align: center;
            font-weight: 700;
            vertical-align: middle;
            position: sticky;
            left: 0;
            background: #f1f5f9;
            z-index: 20;
        }

        /* Fix overlapping sticky borders */
        .rk-table thead th.uraian-cell,
        .rk-table thead th.tipe-cell {
            z-index: 30;
        }

        /* Tipe PDRB column */
        .rk-table td.tipe-cell,
        .rk-table th.tipe-cell {
            min-width: 60px;
            text-align: center;
            font-weight: 600;
            position: sticky;
            left: 110px;
            background: #f1f5f9;
            z-index: 20;
        }

        /* Value cells */
        .rk-table td.val-cell {
            min-width: 120px;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* Warna baris RILIS */
        .row-rilis td {
            background: #fde8e8;
        }

        .row-rilis td:not(.uraian-cell):not(.tipe-cell) {
            color: #dc2626;
            font-weight: 700;
        }

        .row-rilis .tipe-cell,
        .row-rilis .uraian-cell {
            color: #dc2626;
            font-weight: 700;
            background: #fde8e8;
        }

        /* Warna baris ASLI */
        .row-asli td {
            background: #e8f0f8;
        }

        .row-asli .uraian-cell,
        .row-asli .tipe-cell {
            background: #e8f0f8;
        }

        /* Warna baris Adjusment */
        .row-adj td {
            background: #fff;
        }

        .row-adj td:not(.uraian-cell):not(.tipe-cell) {
            color: #dc2626;
        }

        .row-adj .tipe-cell,
        .row-adj .uraian-cell {
            color: #dc2626;
            font-weight: 700;
            background: #fff;
        }

        /* Warna baris ASLI+Adj */
        .row-asli-adj td {
            background: #fef08a;
            font-weight: 700;
        }

        .row-asli-adj .uraian-cell,
        .row-asli-adj .tipe-cell {
            background: #fef08a;
        }

        /* Warna baris Mark Up */
        .row-markup td {
            background: #f8fafc;
            font-style: italic;
        }

        .row-markup .uraian-cell,
        .row-markup .tipe-cell {
            background: #f8fafc;
        }

        /* Warna baris Growth */
        .row-growth td {
            background: #f1f5f9;
            font-weight: 600;
        }

        .row-growth .uraian-cell,
        .row-growth .tipe-cell {
            background: #f1f5f9;
        }

        /* Header tahun - warna peach */
        .th-tahun {
            background: #fcd9c0;
        }

        /* Total year column */
        .col-total {
            background: #fef9f5 !important;
            font-weight: 700;
        }

        /* Negative value */
        .val-neg {
            color: #dc2626;
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            margin-bottom: 0;
        }

        .filter-bar select,
        .filter-bar input {
            padding: 4px 8px;
            font-size: 11px;
            border: 1px solid #94a3b8;
            border-radius: 6px;
            background: #fff;
        }

        .filter-bar button {
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 6px;
            border: none;
            background: #1e293b;
            color: #fff;
            cursor: pointer;
        }

        .filter-bar a.back-btn {
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            text-decoration: none;
        }

        .btn-lock {
            background-color: #ef4444 !important;
        }

        .btn-unlock {
            background-color: #10b981 !important;
        }

        .btn-release {
            background-color: #3b82f6 !important;
        }

        .lock-indicator {
            font-size: 10px;
            color: #64748b;
            font-weight: normal;
            margin-top: 2px;
        }

        /* Toast Notification */
        #toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            background: #10b981;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            margin-top: 10px;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast svg {
            width: 20px;
            height: 20px;
        }

        /* Mini Calculator Styles */
        #mini-calc-container {
            position: fixed;
            z-index: 1000;
            display: none;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            padding: 10px;
            width: 200px;
            transition: all 0.2s ease;
        }

        #mini-calc-container input {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 14px;
            margin-bottom: 8px;
            outline: none;
            transition: border-color 0.2s;
        }

        #mini-calc-container input:focus {
            border-color: #3b82f6;
        }

        .calc-display {
            font-size: 12px;
            color: #64748b;
            text-align: right;
            margin-bottom: 8px;
            font-family: 'JetBrains Mono', monospace;
            min-height: 18px;
        }

        .calc-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
        }

        .calc-btn {
            padding: 6px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-apply {
            background: #3b82f6;
            color: #white;
            color: white;
        }

        .btn-apply:hover {
            background: #2563eb;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }
    </style>

    <div id="toast-container"></div>

    {{-- Mini Calculator UI --}}
    <div id="mini-calc-container">
        <div class="calc-display" id="calc-display-preview">0</div>
        <input type="text" id="calc-input" placeholder="Contoh: 5000 + 2500" autocomplete="off">
        <div class="calc-buttons">
            <div class="calc-btn btn-cancel" id="btn-calc-cancel">Batal</div>
            <div class="calc-btn btn-apply" id="btn-calc-apply">Terapkan</div>
        </div>
    </div>

    <div class="p-1 h-full flex flex-col">
        {{-- Header & Filter Bar in one row --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 mb-2">
            <div class="flex items-center gap-2">
                <h1 class="text-sm font-bold text-slate-900 tracking-tight leading-none whitespace-nowrap">
                    Rekonsiliasi Lembar Kerja {{ $jenis === 'lapangan_usaha' ? 'Lapangan Usaha' : 'Pengeluaran' }}
                    @if(count($items) === 1)
                        - {{ $items[0]['nama'] }}
                    @endif
                </h1>
                @if($wilayahId)
                    <span class="text-slate-300">|</span>
                    <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
                        <strong>{{ optional(collect($wilayahList)->firstWhere('id_wilayah', $wilayahId))->nama_wilayah ?? $wilayahId }}</strong>
                    </p>
                @endif
            </div>

            <a href="{{ url()->previous() }}" class="back-btn py-1.5 px-3 ml-auto">← Kembali</a>
        </div>

        {{-- Tabel Rekonsiliasi Terpadu Menyamping --}}

        <div class="rk-wrap rounded-lg border border-slate-200 shadow-sm bg-white flex-1 min-h-0 relative z-10"
            style="max-height: calc(100vh - 180px);">
            <table class="rk-table" id="rekon-matrix-table">
                <thead>
                    <tr>
                        <th class="uraian-cell" rowspan="2">Uraian</th>
                        <th class="tipe-cell" rowspan="2">PDRB</th>
                        @foreach($tablesData as $tableData)
                            <th class="th-tahun" colspan="{{ count($periodeCols) + 1 }}">
                                {{ $tableData['tahunLabel'] }}
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($tablesData as $tableData)
                            @foreach($periodeCols as $pid => $plabel)
                                <th>
                                    <div>{{ $plabel }}</div>                                    @php
                                        $firstItemKey = array_key_first($tableData['items']);
                                        $rekon = $tableData['items'][$firstItemKey]['rekon'][$pid] ?? null;
                                    @endphp
                                    @if($isProvinsiRole && $rekon)
                                        <div class="flex flex-col items-center mt-1 space-y-1">
                                            <button class="toggle-lock-btn px-2 py-0.5 text-[10px] text-white rounded {{ $rekon->locked_at ? 'btn-unlock' : 'btn-lock' }}"
                                                data-id="{{ $rekon->id }}">
                                                {{ $rekon->locked_at ? 'Buka Kunci' : 'Kunci' }}
                                            </button>
                                            <button class="toggle-release-btn px-2 py-0.5 text-[10px] text-white rounded btn-release"
                                                data-id="{{ $rekon->id }}">
                                                Rilis
                                            </button>
                                        </div>
                                    @endif
                                    <div class="lock-indicator" id="lock-info-{{ $tableData['tahunId'] }}-{{ $pid }}">
                                        @if($rekon && $rekon->locked_at)
                                            🔒 Locked by {{ $rekon->lockedBy->name ?? 'Admin' }}
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                            <th class="col-total">TOTAL</th>
                        @endforeach
                    </tr>
                </thead>
                    <tbody>
                        @php
                            // Row sequence
                            $rows = [
                                ['key' => 'rilis', 'label' => 'RILIS', 'class' => 'row-rilis'],
                                ['key' => 'asli', 'label' => 'ASLI', 'class' => 'row-asli'],
                                ['key' => 'adj', 'label' => 'ADJUSMENT', 'class' => 'row-adj'],
                                ['key' => 'asli_adj', 'label' => 'ASLI + Adj', 'class' => 'row-asli-adj'],
                                ['key' => 'markup', 'label' => 'Mark Up (%)', 'class' => 'row-markup'],
                                ['key' => 'yoy', 'label' => 'G: Y-on-Y', 'class' => 'row-growth'],
                                ['key' => 'qtoq', 'label' => 'G: Q-to-Q', 'class' => 'row-growth'],
                                ['key' => 'ctoc', 'label' => 'G: C-to-C', 'class' => 'row-growth'],
                                ['key' => 'implisit', 'label' => 'Indeks Implisit', 'class' => 'row-growth'],
                                ['key' => 'i_yoy', 'label' => 'I: Y-on-Y', 'class' => 'row-growth'],
                                ['key' => 'i_qtoq', 'label' => 'I: Q-to-Q', 'class' => 'row-growth'],
                            ];

                            $tipes = [
                                ['field' => 'adhb', 'label' => 'ADHB'],
                                ['field' => 'adhk', 'label' => 'ADHK'],
                            ];

                            $subRowsDefault = [
                                ['key' => 'rilis', 'label' => 'Rilis'],
                                ['key' => 'asli', 'label' => 'Asli'],
                            ];

                            $fmtVal = function ($val, $isResultRow = false, $adjVal = 0, $isPercent = false) {
                                if ($isResultRow && (float) $adjVal === 0.0 && $isPercent) {
                                    return '-';
                                }

                                if ($val < 0) {
                                    return '<span class="val-neg">(' . number_format(abs($val), 9, ',', '.') . ($isPercent ? '%' : '') . ')</span>';
                                }
                                return number_format($val, 9, ',', '.') . ($isPercent ? '%' : '');
                            };
                        @endphp

                        @foreach($items as $item)
                            @foreach($rows as $row)
                                @php
                                    $isMultiRow = in_array($row['key'], ['yoy', 'qtoq', 'ctoc', 'implisit', 'i_yoy', 'i_qtoq']);
                                @endphp

                                @if($isMultiRow)
                                    @foreach($subRowsDefault as $gsr)
                                        <tr class="{{ $row['class'] }}" data-item-key="{{ $item['key'] }}">
                                            @if($loop->first)
                                                <td class="uraian-cell" rowspan="2">{{ $row['label'] }}</td>
                                            @endif
                                            <td class="tipe-cell">{{ $gsr['label'] }}</td>

                                            @foreach($tablesData as $tableData)
                                                @php
                                                    $matrix = $tableData['items'][$item['key']];
                                                    $tahunId = $tableData['tahunId'];
                                                @endphp
                                                @foreach($periodeCols as $pid => $plabel)
                                                    @php
                                                        $val = 0;
                                                        $isPercent = true;
                                                        if (in_array($row['key'], ['yoy', 'qtoq', 'ctoc'])) {
                                                            $val = $matrix['growth'][$pid][$row['key']][$gsr['key']] ?? 0;
                                                        } elseif ($row['key'] === 'implisit') {
                                                            $val = $matrix['implisit'][$pid][$gsr['key']] ?? 0;
                                                            $isPercent = false;
                                                        } else {
                                                            $shortKey = str_replace('i_', '', $row['key']);
                                                            $val = $matrix['implisit_growth'][$pid][$shortKey][$gsr['key']] ?? 0;
                                                        }
                                                        $cellId = $item['key'] . "-" . $row['key'] . "-" . $gsr['key'] . "-" . $pid;
                                                    @endphp
                                                    <td class="val-cell" id="cell-{{ $tahunId }}-{{ $cellId }}">
                                                        {!! $fmtVal($val, false, 0, $isPercent) !!}
                                                    </td>
                                                @endforeach

                                                @php
                                                    $totVal = 0;
                                                    $isPercent = true;
                                                    if ($row['key'] === 'yoy' || $row['key'] === 'ctoc') {
                                                        $totVal = $matrix['total_growth'][$row['key']][$gsr['key']] ?? 0;
                                                    } elseif ($row['key'] === 'implisit') {
                                                        $totVal = $matrix['total']['implisit'][$gsr['key']] ?? 0;
                                                        $isPercent = false;
                                                    } elseif ($row['key'] === 'i_yoy') {
                                                        $totVal = $matrix['total_implisit_growth']['yoy'][$gsr['key']] ?? 0;
                                                    }
                                                @endphp
                                                <td class="val-cell col-total" id="total-{{ $tahunId }}-{{ $item['key'] }}-{{ $row['key'] }}-{{ $gsr['key'] }}">
                                                    {!! $fmtVal($totVal, false, 0, $isPercent) !!}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @else
                                    @foreach($tipes as $ti => $tipe)
                                        <tr class="{{ $row['class'] }}" data-item-key="{{ $item['key'] }}">
                                            @if($ti === 0)
                                                <td class="uraian-cell" rowspan="2">{{ $row['label'] }}</td>
                                            @endif
                                            <td class="tipe-cell">{{ $tipe['label'] }}</td>

                                            @foreach($tablesData as $tableData)
                                                @php
                                                    $matrix = $tableData['items'][$item['key']];
                                                    $tahunId = $tableData['tahunId'];
                                                    $tahunLabel = $tableData['tahunLabel'];
                                                @endphp
                                                @foreach($periodeCols as $pid => $plabel)
                                                    @php
                                                        $rekon = $matrix['rekon'][$pid] ?? null;
                                                        $asliVal = $matrix[$tipe['field']][$pid]['asli'] ?? 0;
                                                        $adjVal = $matrix[$tipe['field']][$pid]['adj'] ?? 0;
                                                        $rilisVal = $matrix[$tipe['field']][$pid]['rilis'] ?? ($asliVal + $adjVal);
                                                        $isPast = ($tahunLabel < $currentYearVal) || ($tahunLabel == $currentYearVal && $pid < $currentQuarterVal);

                                                        $val = 0;
                                                        $isResultRow = false;
                                                        $isPercent = false;

                                                        if ($row['key'] === 'asli') {
                                                            $val = $asliVal;
                                                        } elseif ($row['key'] === 'adj') {
                                                            $val = $adjVal;
                                                        } elseif ($row['key'] === 'rilis') {
                                                            $val = $rilisVal;
                                                            $isResultRow = true;
                                                        } elseif ($row['key'] === 'asli_adj') {
                                                            $val = $asliVal + $adjVal;
                                                            $isResultRow = true;
                                                        } elseif ($row['key'] === 'markup') {
                                                            $val = ($asliVal != 0) ? ($adjVal / $asliVal * 100) : 0;
                                                            $isResultRow = true;
                                                            $isPercent = true;
                                                        }

                                                        $cellId = $item['key'] . "-" . $row['key'] . "-" . $tipe['field'] . "-" . $pid;
                                                        $meta = 'data-item-key="' . $item['key'] . '"';
                                                        if ($row['key'] === 'asli' && $tipe['field'] === 'adhk') {
                                                            $prevAdhk = $matrix['prev_year']['adhk'][$pid] ?? 0;
                                                            $prevCumAdhk = $matrix['cum_adhk_prev'][$pid] ?? 0;
                                                            $prevImpl = $matrix['prev_year']['implisit'][$pid] ?? 0;
                                                            $meta .= ' data-prev-year="' . $prevAdhk . '" data-prev-cum="' . $prevCumAdhk . '" data-prev-implisit="' . $prevImpl . '"';
                                                        }
                                                        if ($row['key'] === 'asli' && $tipe['field'] === 'adhb') {
                                                            $prevAdhb = $matrix['prev_year']['adhb'][$pid] ?? 0;
                                                            $meta .= ' data-prev-year-adhb="' . $prevAdhb . '"';
                                                        }
                                                        $extraClass = '';
                                                        $extraData = '';
                                                        if ($row['key'] === 'asli_adj' && $rekon) {
                                                            $extraClass = 'cursor-pointer hover:bg-yellow-200 transition-colors duration-200 show-history';
                                                            $fieldToPass = $tipe['field'] === 'adhb' ? 'adj_berlaku' : 'adj_konstan';
                                                            $extraData = 'data-rekon-id="' . $rekon->id . '" data-field="' . $fieldToPass . '" title="Klik untuk lihat histori"';
                                                        }
                                                    @endphp
                                                    <td class="val-cell {{ $extraClass }}" id="cell-{{ $tahunId }}-{{ $cellId }}" {!! $meta !!} {!! $extraData !!} data-item-key="{{ $item['key'] }}" data-is-past="{{ $isPast ? 1 : 0 }}" data-original-rilis="{{ $rilisVal }}">
                                                        @if($row['key'] === 'adj' && $rekon)
                                                            <input type="text"
                                                                class="w-full px-2 py-0.5 text-right text-xs border rounded rekon-adj-input bg-white focus:outline-none focus:ring-1 focus:ring-orange-400 focus:border-orange-400 {{ $rekon->locked_at ? 'bg-slate-50 cursor-not-allowed' : '' }}"
                                                                data-id="{{ $rekon->id }}" data-item-key="{{ $item['key'] }}"
                                                                data-field="adj_{{ $tipe['field'] === 'adhb' ? 'berlaku' : 'konstan' }}"
                                                                data-pid="{{ $pid }}" data-tahun="{{ $tahunId }}"
                                                                data-tipe="{{ $tipe['field'] }}"
                                                                data-asli="{{ $asliVal }}"
                                                                data-is-past="{{ $isPast ? 1 : 0 }}"
                                                                data-raw-value="{{ $val != 0 ? rtrim(rtrim(number_format($val, 10, ',', '.'), '0'), ',') : '' }}"
                                                                value="{{ $val != 0 ? rtrim(rtrim(number_format($val, 10, ',', '.'), '0'), ',') : '' }}"
                                                                {{ $rekon->locked_at ? 'disabled' : '' }}
                                                                placeholder="0,00">
                                                        @else
                                                            {!! $fmtVal($val, $isResultRow, $adjVal, $isPercent) !!}
                                                        @endif
                                                    </td>
                                                @endforeach

                                                @php
                                                    $totAsli = $matrix['total'][$tipe['field']]['asli'] ?? 0;
                                                    $totAdj = $matrix['total'][$tipe['field']]['adj'] ?? 0;
                                                    $totRilis = $matrix['total'][$tipe['field']]['rilis'] ?? ($totAsli + $totAdj);

                                                    $totVal = 0;
                                                    $isResultRow = false;
                                                    $isPercent = false;

                                                    if ($row['key'] === 'asli') {
                                                        $totVal = $totAsli;
                                                    } elseif ($row['key'] === 'adj') {
                                                        $totVal = $totAdj;
                                                    } elseif ($row['key'] === 'rilis') {
                                                        $totVal = $totRilis;
                                                        $isResultRow = true;
                                                    } elseif ($row['key'] === 'asli_adj') {
                                                        $totVal = $totAsli + $totAdj;
                                                        $isResultRow = true;
                                                    } elseif ($row['key'] === 'markup') {
                                                        $totVal = ($totAsli != 0) ? ($totAdj / $totAsli * 100) : 0;
                                                        $isResultRow = true;
                                                        $isPercent = true;
                                                    }
                                                @endphp
                                                <td class="val-cell col-total" id="total-{{ $tahunId }}-{{ $item['key'] }}-{{ $row['key'] }}-{{ $tipe['field'] }}">
                                                    {!! $fmtVal($totVal, $isResultRow, $totAdj, $isPercent) !!}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        @endforeach

                    </tbody>
                </table>
            </div>


        </div>

        {{-- Modal Histori --}}
        <div id="historyModal" class="fixed inset-0 z-50 hidden bg-black border border-black bg-opacity-50 flex items-center justify-center p-4 transition-opacity duration-300">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl transform scale-95 opacity-0 transition-all duration-300" id="historyModalContent">
                <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Riwayat Perubahan Nilai</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Daftar historis penyesuaian (adjustment) nilai.</p>
                        </div>
                    </div>
                    <button type="button" class="text-slate-400 border hover:border-slate-300 hover:text-slate-500 hover:bg-slate-100 rounded-full p-2 transition-colors" onclick="closeHistoryModal()">
                        <span class="sr-only">Close</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left align-middle border-b border-slate-100">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-50 font-bold border-y border-slate-200">
                                <tr>
                                    <th scope="col" class="px-6 py-4">User Pengubah</th>
                                    <th scope="col" class="px-6 py-4">Waktu Ubah</th>
                                    <th scope="col" class="px-6 py-4 text-right">Nilai Lama</th>
                                    <th scope="col" class="px-6 py-4 text-right">Nilai Baru</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody" class="divide-y divide-slate-100">
                                <!-- Diisi oleh js -->
                            </tbody>
                        </table>
                    </div>
                    <div id="historyLoading" class="hidden py-12 flex flex-col items-center justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                        <p class="mt-3 text-sm text-slate-500 font-medium tracking-wide">Memuat riwayat...</p>
                    </div>
                </div>
                <div class="p-4 bg-slate-50 border-t border-slate-100 rounded-b-xl flex justify-end">
                    <button type="button" class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-sm transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-slate-200" onclick="closeHistoryModal()">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden data span for meta calculations in JS -->
        <div id="rekon-meta-data" style="display:none;">
            @foreach($tablesData as $tableData)
                @foreach($items as $item)
                    @php 
                        $matrix = $tableData['items'][$item['key']];
                        $tahunId = $tableData['tahunId']; 
                    @endphp
                    <span class="meta-item-tahun" data-item-key="{{ $item['key'] }}" data-tahun="{{ $tahunId }}" 
                        data-prev-q-adhk="{{ $matrix['prev_q']['adhk'] ?? 0 }}"
                        data-prev-q-implisit="{{ $matrix['prev_q']['implisit'] ?? 0 }}">
                    </span>
                @endforeach
            @endforeach
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function () {
                function formatNumber(val, isResultRow = false, adjVal = 0, isPercent = false) {
                    if (isResultRow && parseFloat(adjVal) === 0 && isPercent) return '-';

                    let num = parseFloat(val) || 0;
                    let neg = num < 0;
                    let str = Math.abs(num).toLocaleString('id-ID', { minimumFractionDigits: 9, maximumFractionDigits: 9 });
                    if (isPercent) str += '%';
                    if (neg) return `<span class="val-neg">(${str})</span>`;
                    return str;
                }

                function formatQuantity(val, shortDisplay = false) {
                    if (val === '' || val === null || val === undefined) return '';
                    // Ensure string
                    let str = val.toString().trim();
                    if (str === '') return '';

                    // Handle negative
                    let isNeg = str.startsWith('-');
                    if (isNeg) str = str.substring(1);

                    // Smart English Locale / Numpad Dot Detection
                    let lastDot = str.lastIndexOf('.');
                    let lastComma = str.lastIndexOf(',');
                    if (lastDot > lastComma) {
                        let afterDot = str.substring(lastDot + 1);
                        // If there's NOT exactly 3 digits after the dot, or there's a comma before it,
                        // it's highly likely to be a decimal point, not a thousands separator.
                        if (afterDot.length !== 3 || lastComma !== -1) {
                            str = str.replace(/,/g, ''); // Remove English thousands separators
                            str = str.replace(/\./g, ','); // Convert the true decimal point to comma
                        }
                    }

                    // Replace dots (thousands) and fix commas
                    str = str.replace(/\./g, '');

                    let parts = str.split(',');
                    let whole = parts[0].replace(/\D/g, '');
                    let decimal = parts.length > 1 ? parts[1].replace(/\D/g, '') : null;

                    // If asking for short display and there is a decimal, we round/truncate visually
                    if (shortDisplay) {
                         let rawFloat = parseFloat(whole + '.' + (decimal || '0'));
                         if (!isNaN(rawFloat)) {
                             let localized = new Intl.NumberFormat('id-ID', {
                                 minimumFractionDigits: 2,
                                 maximumFractionDigits: 2
                             }).format(rawFloat);
                             if (isNeg) {
                                return '-' + localized;
                             }
                             return localized;
                         }
                    }

                    // Add grouping dots
                    whole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

                    let result = (isNeg ? '-' : '') + whole;
                    if (decimal !== null) result += ',' + decimal;

                    return result;
                }

                // --- FOCUS / BLUR UX ---
                function setShortDisplay(input) {
                    if (!input) return;
                    const rawStr = input.dataset.rawValue ?? input.value;
                    if (rawStr === '') {
                        input.value = '';
                        return;
                    }
                    // Short display max 2 decimals visually
                    input.value = formatQuantity(rawStr, true);
                }

                // Bind focus/blur to all adjustment inputs
                $(document).on('focus', '.rekon-adj-input', function() {
                    if (this.readOnly || this.disabled) return;
                    const rawStr = this.dataset.rawValue ?? this.value;
                    if (rawStr) {
                        this.value = formatQuantity(rawStr, false);
                        setTimeout(() => $(this).select(), 10);
                    }
                });

                $(document).on('blur', '.rekon-adj-input', function() {
                    if (this.readOnly || this.disabled) return;
                    setShortDisplay(this);
                });

                // Initialize display recursively
                $('.rekon-adj-input').each(function() {
                    const rawSource = this.dataset.rawValue ?? this.value;
                    this.dataset.rawValue = rawSource;
                    setShortDisplay(this);
                });

                function unformatQuantity(val) {
                    if (!val) return 0;
                    let str = val.toString().replace(/\./g, '').replace(',', '.').replace(/[^-0-9.]/g, '');
                    // We return float for simple JS math, but for server we'll use the raw string
                    return parseFloat(str) || 0;
                }

                // High precision multiplication by 1,000,000 using string manipulation
                function multiplyByMillion(valStr) {
                    // valStr is potentially fully formatted (e.g. 32.926,44)
                    let str = valStr.toString().replace(/\./g, '').replace(/,/g, '.').replace(/[^-0-9.]/g, '');
                    if (!str.includes('.')) return str + "000000";

                    let parts = str.split('.');
                    let whole = parts[0];
                    let fraction = parts[1];

                    // Shift decimal 6 places
                    if (fraction.length <= 6) {
                        return whole + fraction.padEnd(6, '0');
                    } else {
                        return whole + fraction.substring(0, 6) + "." + fraction.substring(6);
                    }
                }

                $(document).on('input', '.rekon-adj-input', function() {
                    let val = $(this).val();
                    let selectionStart = this.selectionStart;
                    let oldLength = val.length;

                    let formatted = formatQuantity(val, false);
                    $(this).val(formatted);
                    // Also update pure precision raw-data value during typing loop
                    this.dataset.rawValue = formatted;

                    let newLength = formatted.length;
                    this.setSelectionRange(selectionStart + (newLength - oldLength), selectionStart + (newLength - oldLength));
                });

                // --- MINI CALCULATOR LOGIC ---
                let $activeInput = null;
                const $calcContainer = $('#mini-calc-container');
                const $calcInput = $('#calc-input');
                const $calcPreview = $('#calc-display-preview');

                function safeEval(str) {
                    try {
                        // Hanya izinkan angka, operator dasar, titik (ribuan sementara), dan koma
                        let cleanStr = str.replace(/[^-0-9+*/().,]/g, '');
                        if (!cleanStr) return 0;

                        // Bersihkan pemisah ribuan (titik) sebelum koma diubah jadi titik desimal!
                        cleanStr = cleanStr.replace(/\./g, '');
                        // Ubah koma ke titik untuk evaluasi JS
                        cleanStr = cleanStr.replace(/,/g, '.');

                        return Function(`'use strict'; return (${cleanStr})`)();
                    } catch (e) {
                        return null;
                    }
                }

                $(document).on('click', '.rekon-adj-input', function(e) {
                    if ($(this).prop('disabled')) return;

                    $activeInput = $(this);
                    const rect = this.getBoundingClientRect();

                    $calcContainer.css({
                        top: (rect.bottom + window.scrollY + 5) + 'px',
                        left: (rect.left + window.scrollX) + 'px',
                        display: 'block'
                    });

                    // Gunakan nilai saat ini sebagai awalan jika bukan nol
                    const currentVal = unformatQuantity($(this).val());
                    // Jika input kosong atau 0, biarkan kosong agar user bisa langsung ketik
                    $calcInput.val(currentVal !== 0 ? $(this).val() : '').focus();
                    updatePreview();
                    e.stopPropagation();
                });

                $calcInput.on('input', function() {
                    updatePreview();
                });

                function updatePreview() {
                    const expr = $calcInput.val();
                    if (!expr) {
                        $calcPreview.text('0');
                        return;
                    }
                    const result = safeEval(expr);
                    if (result !== null) {
                        $calcPreview.text(result.toLocaleString('id-ID', { 
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 15 
                        }));
                        $calcPreview.removeClass('text-red-500');
                    } else {
                        $calcPreview.text('Format salah');
                        $calcPreview.addClass('text-red-500');
                    }
                }

                $calcInput.on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        applyCalc();
                    } else if (e.key === 'Escape') {
                        hideCalc();
                    }
                });

                $('#btn-calc-apply').on('click', applyCalc);
                $('#btn-calc-cancel').on('click', hideCalc);

                function applyCalc() {
                    if (!$activeInput) return;
                    const result = safeEval($calcInput.val());
                    if (result !== null) {
                        // Convert JS float (which uses dot for decimal) back to Indonesian locale (comma)
                        let resultStr = result.toString().replace(/\./g, ',');
                        let fullVal = formatQuantity(resultStr, false);

                        // Update dataset.rawValue BEFORE triggering change
                        $activeInput[0].dataset.rawValue = fullVal;
                        $activeInput.val(fullVal).trigger('change');

                        // Trigger blur aesthetic
                        setShortDisplay($activeInput[0]);
                        hideCalc();
                    }
                }

                function hideCalc() {
                    $calcContainer.hide();
                    $activeInput = null;
                }

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#mini-calc-container').length && !$(e.target).hasClass('rekon-adj-input')) {
                        hideCalc();
                    }
                });

                $calcContainer.on('click', function(e) {
                    e.stopPropagation();
                });

                $('.rekon-adj-input').on('change', function () {
                    const $input = $(this);
                    const id = $input.data('id');
                    const field = $input.data('field');
                    // Use data-raw-value (which has full digits) instead of val()
                    // Use || instead of ?? so if rawValue is "" (empty string), it falls back to val()
                    const rawValue = ($input[0].dataset.rawValue || $input.val()) ?? '';

                    // Keep the input visually 2-decimals since it's blurred after pressing enter/clicking away
                    setShortDisplay(this);

                    // if empty string => pass "" so controller saves it as 0/null logic
                    const inputVal = rawValue.trim() === '' ? '' : rawValue; 

                    // Value that travels to server: 32926,44 -> 32926440000
                    const valueForServer = (inputVal === '') ? '' : multiplyByMillion(inputVal);
                    const pid = parseInt($input.data('pid'));
                    const tahunId = $input.data('tahun');
                    const tipe = $input.data('tipe');
                    const asli = parseFloat($input.data('asli')) || 0;

                    $input.addClass('bg-yellow-50');

                    $.ajax({
                        url: "{{ route('rekon_lk.update_adjustment') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id,
                            field: field,
                            value: valueForServer
                        },
                        success: function (res) {
                            $input.removeClass('bg-yellow-50').addClass('bg-green-50');
                            setTimeout(() => $input.removeClass('bg-green-50'), 1000);

                            if (res.success) {
                                const adjNum = unformatQuantity(rawValue); 
                                const isPast = $input.data('is-past') == 1;

                                // Use the highly precise total from server if provided
                                const serverFinal = tipe === 'adhb' ? res.final_berlaku : res.final_konstan;
                                const displayVal = serverFinal ? (parseFloat(serverFinal) / 1000000) : (asli + adjNum);
                                const itemKey = $input.data('item-key');

                                // Always update ASLI + Adj
                                const formattedAsliAdj = formatNumber(displayVal, true, adjNum);
                                $(`#cell-${tahunId}-${itemKey}-asli_adj-${tipe}-${pid}`).html(formattedAsliAdj);

                                // Only update Rilis if NOT past
                                if (!isPast) {
                                    $(`#cell-${tahunId}-${itemKey}-rilis-${tipe}-${pid}`).html(formattedAsliAdj);
                                }

                                let markupVal = (asli !== 0) ? (adjNum / asli * 100) : 0;
                                $(`#cell-${tahunId}-${itemKey}-markup-${tipe}-${pid}`).html(formatNumber(markupVal, true, adjNum, true));

                                showToast('Data berhasil disimpan');
                                updateTotals(tipe, tahunId, itemKey);
                                updateAnalyticalRows(tahunId, itemKey);
                            }
                        },
                        error: function (err) {
                            $input.removeClass('bg-yellow-50 saving').addClass('bg-red-50');
                            if (err.status === 403) {
                                alert(err.responseJSON.message);
                                location.reload(); // Refresh to ensure UI reflects lock
                            } else {
                                alert('Gagal menyimpan adjusment: ' + (err.responseJSON?.message || 'Error server. Silakan cek koneksi atau lapor admin.'));
                            }
                        }
                    });
                });

                function showToast(message) {
                    const id = 'toast-' + Date.now();
                    const toastHtml = `
                        <div id="${id}" class="toast">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>${message}</span>
                        </div>
                    `;
                    $('#toast-container').append(toastHtml);
                    const $toast = $(`#${id}`);
                    setTimeout(() => $toast.addClass('show'), 100);
                    setTimeout(() => {
                        $toast.removeClass('show').addClass('opacity-0');
                        setTimeout(() => $toast.remove(), 300);
                    }, 3000);
                }

                function updateTotals(tipe, tahunId, itemKey) {
                    let sumAsli = 0;
                    let sumAdj = 0;
                    let sumRilis = 0;

                    $(`.rekon-adj-input[data-tipe="${tipe}"][data-tahun="${tahunId}"][data-item-key="${itemKey}"]`).each(function() {
                        let pid = $(this).data('pid');
                        let a = parseFloat($(this).data('asli')) || 0;
                        let v = unformatQuantity($(this).val());
                        let isPast = $(this).data('is-past') == 1;

                        sumAsli += a;
                        sumAdj += v;

                        if (isPast) {
                            let origRilis = parseFloat($(`#cell-${tahunId}-${itemKey}-rilis-${tipe}-${pid}`).data('original-rilis')) || 0;
                            sumRilis += origRilis;
                        } else {
                            sumRilis += (a + v);
                        }
                    });

                    $(`#total-${tahunId}-${itemKey}-asli-${tipe}`).html(formatNumber(sumAsli));
                    $(`#total-${tahunId}-${itemKey}-adj-${tipe}`).html(formatNumber(sumAdj));

                    const formattedTot = formatNumber(sumRilis, true, sumAdj);
                    $(`#total-${tahunId}-${itemKey}-rilis-${tipe}`).html(formattedTot);
                    $(`#total-${tahunId}-${itemKey}-asli_adj-${tipe}`).html(formatNumber(sumAsli + sumAdj, true, sumAdj));

                    let totalMarkup = (sumAsli !== 0) ? (sumAdj / sumAsli * 100) : 0;
                    $(`#total-${tahunId}-${itemKey}-markup-${tipe}`).html(formatNumber(totalMarkup, true, sumAdj, true));
                }

                function updateAnalyticalRows(tahunId, itemKey) {
                    const $metaEl = $(`.meta-item-tahun[data-tahun="${tahunId}"][data-item-key="${itemKey}"]`);
                    const prevQ1Adhk = parseFloat($metaEl.data('prev-q-adhk')) || 0;
                    const prevQ1Implisit = parseFloat($metaEl.data('prev-q-implisit')) || 0;

                    let cumulativeRilis = 0;
                    let cumulativeAsli = 0;
                    let totalAdhbRilis = 0;
                    let totalAdhkRilis = 0;
                    let totalAdhbAsli = 0;
                    let totalAdhkAsli = 0;
                    let totalAdhbPrev = 0;
                    let totalAdhkPrev = 0;

                    for (let pid = 1; pid <= 4; pid++) {
                        const adjInputAdhb = $(`.rekon-adj-input[data-tipe="adhb"][data-pid="${pid}"][data-tahun="${tahunId}"][data-item-key="${itemKey}"]`);
                        const adjInputAdhk = $(`.rekon-adj-input[data-tipe="adhk"][data-pid="${pid}"][data-tahun="${tahunId}"][data-item-key="${itemKey}"]`);

                        let adhbRilis, adhkRilis;
                        let adhbAsli = parseFloat(adjInputAdhb.data('asli')) || 0;
                        let adhkAsli = parseFloat(adjInputAdhk.data('asli')) || 0;

                        if (adjInputAdhb.data('is-past') == 1) {
                            adhbRilis = parseFloat($(`#cell-${tahunId}-${itemKey}-rilis-adhb-${pid}`).data('original-rilis')) || 0;
                            adhkRilis = parseFloat($(`#cell-${tahunId}-${itemKey}-rilis-adhk-${pid}`).data('original-rilis')) || 0;
                        } else {
                            adhbRilis = adhbAsli + unformatQuantity(adjInputAdhb.val());
                            adhkRilis = adhkAsli + unformatQuantity(adjInputAdhk.val());
                        }

                        const metaCellAdhk = $(`#cell-${tahunId}-${itemKey}-asli-adhk-${pid}`);
                        const prevYearAdhk = parseFloat(metaCellAdhk.data('prev-year')) || 0;
                        const prevYearImplisit = parseFloat(metaCellAdhk.data('prev-implisit')) || 0;
                        const prevCumAdhk = parseFloat(metaCellAdhk.data('prev-cum')) || 0;

                        const metaCellAdhb = $(`#cell-${tahunId}-${itemKey}-asli-adhb-${pid}`);
                        const prevYearAdhb = parseFloat(metaCellAdhb.data('prev-year-adhb')) || 0;

                        totalAdhbRilis += adhbRilis;
                        totalAdhkRilis += adhkRilis;
                        totalAdhbAsli += adhbAsli;
                        totalAdhkAsli += adhkAsli;
                        totalAdhbPrev += prevYearAdhb;
                        totalAdhkPrev += prevYearAdhk;

                        // Growth Rilis
                        let yoyRilis = (prevYearAdhk !== 0) ? (adhkRilis / prevYearAdhk * 100 - 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-yoy-rilis-${pid}`).html(formatNumber(yoyRilis, false, 0, true));

                        // Growth Asli
                        let yoyAsli = (prevYearAdhk !== 0) ? (adhkAsli / prevYearAdhk * 100 - 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-yoy-asli-${pid}`).html(formatNumber(yoyAsli, false, 0, true));

                        let prevQRilis, prevQAsli;
                        if (pid === 1) {
                            prevQRilis = prevQAsli = prevQ1Adhk;
                        } else {
                            const prevInp = $(`.rekon-adj-input[data-tipe="adhk"][data-pid="${pid-1}"][data-tahun="${tahunId}"][data-item-key="${itemKey}"]`);
                            prevQAsli = parseFloat(prevInp.data('asli')) || 0;
                            prevQRilis = prevQAsli + (parseFloat(prevInp.val()) || 0);
                            // Case past
                            if (prevInp.data('is-past') == 1) {
                                prevQRilis = parseFloat($(`#cell-${tahunId}-${itemKey}-rilis-adhk-${pid-1}`).data('original-rilis')) || 0;
                            }
                        }

                        let qtoqRilis = (prevQRilis !== 0) ? (adhkRilis / prevQRilis * 100 - 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-qtoq-rilis-${pid}`).html(formatNumber(qtoqRilis, false, 0, true));

                        let qtoqAsli = (prevQAsli !== 0) ? (adhkAsli / prevQAsli * 100 - 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-qtoq-asli-${pid}`).html(formatNumber(qtoqAsli, false, 0, true));

                        cumulativeRilis += adhkRilis;
                        cumulativeAsli += adhkAsli;
                        let ctocRilis = (prevCumAdhk !== 0) ? (cumulativeRilis / prevCumAdhk * 100 - 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-ctoc-rilis-${pid}`).html(formatNumber(ctocRilis, false, 0, true));

                        let ctocAsli = (prevCumAdhk !== 0) ? (cumulativeAsli / prevCumAdhk * 100 - 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-ctoc-asli-${pid}`).html(formatNumber(ctocAsli, false, 0, true));

                        // Implisit
                        let implisitRilis = (adhkRilis !== 0) ? (adhbRilis / adhkRilis * 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-implisit-rilis-${pid}`).html(formatNumber(implisitRilis, false, 0, false));

                        let implisitAsli = (adhkAsli !== 0) ? (adhbAsli / adhkAsli * 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-implisit-asli-${pid}`).html(formatNumber(implisitAsli, false, 0, false));

                        // Implisit Growth
                        let iYoyRilis = (prevYearImplisit !== 0) ? (implisitRilis / prevYearImplisit * 100 - 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-i_yoy-rilis-${pid}`).html(formatNumber(iYoyRilis, false, 0, true));

                        let iYoyAsli = (prevYearImplisit !== 0) ? (implisitAsli / prevYearImplisit * 100 - 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-i_yoy-asli-${pid}`).html(formatNumber(iYoyAsli, false, 0, true));

                        let prevI_Rilis, prevI_Asli;
                        if (pid === 1) {
                            prevI_Rilis = prevI_Asli = prevQ1Implisit;
                        } else {
                            const prevInpB = $(`.rekon-adj-input[data-tipe="adhb"][data-pid="${pid-1}"][data-tahun="${tahunId}"][data-item-key="${itemKey}"]`);
                            const prevInpK = $(`.rekon-adj-input[data-tipe="adhk"][data-pid="${pid-1}"][data-tahun="${tahunId}"][data-item-key="${itemKey}"]`);
                            const aB = parseFloat(prevInpB.data('asli')) || 0;
                            const aK = parseFloat(prevInpK.data('asli')) || 0;
                            const pB = aB + (parseFloat(prevInpB.val()) || 0);
                            const pK = aK + (parseFloat(prevInpK.val()) || 0);

                            prevI_Asli = (aK !== 0) ? (aB / aK * 100) : 0;
                            prevI_Rilis = (pK !== 0) ? (pB / pK * 100) : 0;

                            if (prevInpB.data('is-past') == 1) {
                                const rB = parseFloat($(`#cell-${tahunId}-${itemKey}-rilis-adhb-${pid-1}`).data('original-rilis')) || 0;
                                const rK = parseFloat($(`#cell-${tahunId}-${itemKey}-rilis-adhk-${pid-1}`).data('original-rilis')) || 0;
                                prevI_Rilis = (rK !== 0) ? (rB / rK * 100) : 0;
                            }
                        }
                        let iQtoqRilis = (prevI_Rilis !== 0) ? (implisitRilis / prevI_Rilis * 100 - 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-i_qtoq-rilis-${pid}`).html(formatNumber(iQtoqRilis, false, 0, true));

                        let iQtoqAsli = (prevI_Asli !== 0) ? (implisitAsli / prevI_Asli * 100 - 100) : 0;
                        $(`#cell-${tahunId}-${itemKey}-i_qtoq-asli-${pid}`).html(formatNumber(iQtoqAsli, false, 0, true));
                    }

                    // Totals
                    let totImplisitRilis = (totalAdhkRilis !== 0) ? (totalAdhbRilis / totalAdhkRilis * 100) : 0;
                    $(`#total-${tahunId}-${itemKey}-implisit-rilis`).html(formatNumber(totImplisitRilis, false, 0, false));

                    let totImplisitAsli = (totalAdhkAsli !== 0) ? (totalAdhbAsli / totalAdhkAsli * 100) : 0;
                    $(`#total-${tahunId}-${itemKey}-implisit-asli`).html(formatNumber(totImplisitAsli, false, 0, false));

                    let totalYoyRilis = (totalAdhkPrev !== 0) ? (totalAdhkRilis / totalAdhkPrev * 100 - 100) : 0;
                    $(`#total-${tahunId}-${itemKey}-yoy-rilis`).html(formatNumber(totalYoyRilis, false, 0, true));
                    $(`#total-${tahunId}-${itemKey}-ctoc-rilis`).html(formatNumber(totalYoyRilis, false, 0, true));

                    let totalYoyAsli = (totalAdhkPrev !== 0) ? (totalAdhkAsli / totalAdhkPrev * 100 - 100) : 0;
                    $(`#total-${tahunId}-${itemKey}-yoy-asli`).html(formatNumber(totalYoyAsli, false, 0, true));
                    $(`#total-${tahunId}-${itemKey}-ctoc-asli`).html(formatNumber(totalYoyAsli, false, 0, true));

                    let totImplPrev = (totalAdhkPrev !== 0) ? (totalAdhbPrev / totalAdhkPrev * 100) : 0;
                    let totIYoyRilis = (totImplPrev !== 0) ? (totImplisitRilis / totImplPrev * 100 - 100) : 0;
                    $(`#total-${tahunId}-${itemKey}-i_yoy-rilis`).html(formatNumber(totIYoyRilis, false, 0, true));

                    let totIYoyAsli = (totImplPrev !== 0) ? (totImplisitAsli / totImplPrev * 100 - 100) : 0;
                    $(`#total-${tahunId}-${itemKey}-i_yoy-asli`).html(formatNumber(totIYoyAsli, false, 0, true));
                }

                $('.toggle-lock-btn').on('click', function () {
                    const $btn = $(this);
                    const id = $btn.data('id');

                    if (!confirm('Apakah Anda yakin ingin mengubah status kunci data ini?')) return;

                    $.ajax({
                        url: "{{ route('rekon_lk.lock') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id
                        },
                        success: function (res) {
                            if (res.success) {
                                location.reload();
                            }
                        },
                        error: function (err) {
                            alert(err.responseJSON.message || 'Gagal mengubah status kunci.');
                        }
                    });
                });

                $('.toggle-release-btn').on('click', function () {
                    const $btn = $(this);
                    const id = $btn.data('id');

                    if (!confirm('Apakah Anda yakin ingin merilis data ini ke database final?')) return;

                    $.ajax({
                        url: "{{ route('rekon_lk.release') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id
                        },
                        success: function (res) {
                            if (res.success) {
                                alert(res.message);
                            } else {
                                alert(res.message || 'Gagal merilis data.');
                            }
                        },
                        error: function (err) {
                            alert(err.responseJSON?.message || 'Terjadi kesalahan sistem saat merilis data.');
                        }
                    });
                });

                // History Modal Logic
                $('.show-history').on('click', function() {
                    const rekonId = $(this).data('rekon-id');
                    const field = $(this).data('field');

                    if(!rekonId) return;

                    $('#historyModal').removeClass('hidden');
                    setTimeout(() => {
                        $('#historyModalContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
                    }, 10);

                    $('#historyTableBody').empty();
                    $('#historyLoading').removeClass('hidden');

                    const url = `{{ url('rekon-lk/history') }}/${rekonId}?field=${field}`;

                    $.get(url, function(res) {
                        $('#historyLoading').addClass('hidden');

                        if(res.status === 'success') {
                            let html = '';
                            if(res.data.length === 0) {
                                html = `<tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500 bg-slate-50/50">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span>Belum ada riwayat perubahan adjustment</span>
                                        </div>
                                    </td>
                                </tr>`;
                            } else {
                                res.data.forEach(item => {
                                    html += `<tr class="hover:bg-slate-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                                                    ${item.user.charAt(0).toUpperCase()}
                                                </div>
                                                <span class="font-medium text-slate-900">${item.user}</span>
                                            </div>    
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-slate-500">${item.waktu}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-slate-600">${item.nilai_lama}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-blue-600">${item.nilai_baru}</td>
                                    </tr>`;
                                });
                            }
                            $('#historyTableBody').html(html);
                        } else {
                            $('#historyTableBody').html('<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Gagal memuat history.</td></tr>');
                        }
                    }).fail(function() {
                        $('#historyLoading').addClass('hidden');
                        $('#historyTableBody').html('<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Koneksi Error.</td></tr>');
                    });
                });

                // Close modal on escape key
                $(document).keydown(function(e) {
                    if (e.key === "Escape") closeHistoryModal();
                });

                // Close modal by clicking outside
                $('#historyModal').mousedown(function(e) {
                    if (e.target === this) closeHistoryModal();
                });

                // --- POLLING LOGIC ---
                let lastPollAt = Date.now();
                let pollTimer = null;
                let pollActive = true;
                let pollInFlight = false;
                function handleRekonLkUpdate(update) {
                    // update: { rekon_id, field_type, nilai_baru, id_tahun, id_periode }
                    const $input = $(`.rekon-adj-input[data-id="${update.rekon_id}"][data-field="${update.field_type}"]`);
                    if ($input.length === 0) return;

                    const newValFormatted = formatQuantity(update.nilai_baru / 1_000_000);
                    if ($input.is(':focus')) return;

                    $input.val(newValFormatted);
                    $input[0].dataset.rawValue = newValFormatted;
                    $input.addClass('bg-blue-50');
                    setTimeout(() => $input.removeClass('bg-blue-50'), 2000);

                    const pid = update.id_periode;
                    const tahunId = update.id_tahun;
                    const itemKey = $input.data('item-key');
                    const tipe = $input.data('tipe');
                    const asli = parseFloat($input.data('asli')) || 0;
                    const adjNum = parseFloat(update.nilai_baru / 1_000_000);
                    const isPast = $input.data('is-past') == 1;
                    const asliAdjVal = asli + adjNum;

                    const formattedAsliAdj = formatNumber(asliAdjVal, true, adjNum);
                    $(`#cell-${tahunId}-${itemKey}-asli_adj-${tipe}-${pid}`).html(formattedAsliAdj);

                    if (!isPast) {
                        $(`#cell-${tahunId}-${itemKey}-rilis-${tipe}-${pid}`).html(formatNumber(asliAdjVal, true, adjNum));
                    }

                    let markupVal = (asli !== 0) ? (adjNum / asli * 100) : 0;
                    $(`#cell-${tahunId}-${itemKey}-markup-${tipe}-${pid}`).html(formatNumber(markupVal, true, adjNum, true));

                    updateTotals(tipe, tahunId, itemKey);
                    updateAnalyticalRows(tahunId, itemKey);
                }
                  function handleAsliUpdate(item) {
                    // item: { id_tahun, id_periode, id_kategori, id_sub_kategori, asli_adhb, asli_adhk }
                    const itemKey = item.id_sub_kategori ? `sub-${item.id_sub_kategori}` : `cat-${item.id_kategori}`;
                    const tahunId = item.id_tahun;
                    const pid = item.id_periode;

                    // Update row ASLI ADHB
                    const adhbInp = $(`.rekon-adj-input[data-tipe="adhb"][data-pid="${pid}"][data-tahun="${tahunId}"][data-item-key="${itemKey}"]`);
                    if (adhbInp.length) {
                        const newVal = item.asli_adhb / 1_000_000;
                        adhbInp.data('asli', newVal);
                        $(`#cell-${tahunId}-${itemKey}-asli-adhb-${pid}`).html(formatNumber(newVal));

                        const adj = unformatQuantity(adhbInp.val());
                        $(`#cell-${tahunId}-${itemKey}-asli_adj-adhb-${pid}`).html(formatNumber(newVal + adj, true, adj));
                        updateTotals('adhb', tahunId, itemKey);
                    }

                    // Update row ASLI ADHK
                    const adhkInp = $(`.rekon-adj-input[data-tipe="adhk"][data-pid="${pid}"][data-tahun="${tahunId}"][data-item-key="${itemKey}"]`);
                    if (adhkInp.length) {
                        const newVal = item.asli_adhk / 1_000_000;
                        adhkInp.data('asli', newVal);
                        $(`#cell-${tahunId}-${itemKey}-asli-adhk-${pid}`).html(formatNumber(newVal));

                        const adj = unformatQuantity(adhkInp.val());
                        $(`#cell-${tahunId}-${itemKey}-asli_adj-adhk-${pid}`).html(formatNumber(newVal + adj, true, adj));
                        updateTotals('adhk', tahunId, itemKey);
                    }

                    updateAnalyticalRows(tahunId, itemKey);
                }

                async function pollRekonLkUpdates() {
                    if (pollInFlight || !pollActive) return;

                    pollInFlight = true;
                    try {
                        const res = await $.get("{{ route('rekon_lk.poll') }}", {
                            wilayah_id: "{{ $wilayahId }}",
                            jenis: "{{ $jenis }}",
                            since: lastPollAt
                        });

                        if (res.status === 'ok') {
                            if (res.updates && res.updates.length > 0) {
                                res.updates.forEach(handleRekonLkUpdate);
                            }
                            if (res.asli_updates && res.asli_updates.length > 0) {
                                res.asli_updates.forEach(handleAsliUpdate);
                            }
                            lastPollAt = res.server_time_ms;
                        }
                    } catch (e) {
                        console.error("Polling error", e);
                    } finally {
                        pollInFlight = false;
                    }
                }

                function initPolling() {
                    if (pollTimer) clearInterval(pollTimer);
                    // Polling setiap 5 detik
                    pollTimer = setInterval(pollRekonLkUpdates, 5000);
                }

                document.addEventListener('visibilitychange', function() {
                    pollActive = !document.hidden;
                    if (pollActive) {
                        lastPollAt = Date.now() - 5000;
                        pollRekonLkUpdates();
                    }
                });

                initPolling();

                @if(request('item_key'))
                setTimeout(() => {
                    const targetKey = "{{ request('item_key') }}";
                    const tipePdrb = "{{ request('tipe_pdrb') }}"; // berlaku/konstan
                    let $rows = $(`[data-item-key="${targetKey}"]`);
                    
                    if ($rows.length) {
                        let $targetRow = $rows.first();
                        
                        // If specific PDRB type requested, find the matching row
                        if (tipePdrb) {
                            const labelSearch = tipePdrb === 'konstan' ? 'ADHK' : 'ADHB';
                            const $specificRow = $rows.filter(function() {
                                return $(this).find('.tipe-cell').text().trim().includes(labelSearch);
                            }).first();
                            
                            if ($specificRow.length) {
                                $targetRow = $specificRow;
                                // Highlight the specific row more prominently
                                $rows = $specificRow; 
                            }
                        }

                        $targetRow[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        $rows.find('td').css('background-color', '#fef08a'); // yellow-200
                        setTimeout(() => {
                            $rows.find('td').css('transition', 'background-color 2s ease');
                            $rows.find('td').css('background-color', '');
                        }, 3000);
                    }
                }, 800);
                @endif
            });

            // Global close function
            window.closeHistoryModal = function() {
                $('#historyModalContent').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
                setTimeout(() => {
                    $('#historyModal').addClass('hidden');
                }, 300);
            }
        </script>
@endsection