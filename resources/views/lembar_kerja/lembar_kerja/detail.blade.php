@extends('layouts.main')

@php
    $template = \App\Helpers\PdrbHelper::getTemplate($subKategori->id_sub_kategori);
@endphp

@section('title', 'Lembar Kerja Detail')

@section('content')
    <style>
        /* Scrollbars: keep visible but subtle for UX */
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.6);
            border-radius: 999px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(226, 232, 240, 0.6);
            border-radius: 999px;
        }

        .custom-scrollbar {
            -ms-overflow-style: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.6) rgba(226, 232, 240, 0.6);
        }

        /* Allow normal page scrolling (avoid preventing user scroll) */
        body {
            overflow: auto;
            background-color: #f8fafc;
        }

        /* Layout helpers */
        main>div {
            min-height: 0;
        }

        .lk-page {
            min-height: 0;
        }

        .lk-card {
            min-height: 0;
        }

        /* Table scroll area with soft shadow and responsive horizontal overflow */
        .lk-table-scroll {
            overflow: auto;
            position: relative;
            flex: 1;
            min-height: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px -10px rgba(15, 23, 42, 0.08);
            /* Premium shadow */
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .lk-sticky-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .lk-sticky-table thead th {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(248, 250, 252, 0.95);
            border-bottom: 1px solid rgba(226, 232, 240, 1);
            backdrop-filter: blur(8px);
            color: #334155;
            font-weight: 700;
            letter-spacing: 0.025em;
        }

        .lk-sticky-table thead tr:first-child th {
            top: 0;
            z-index: 40;
        }

        .lk-sticky-table thead tr:nth-child(2) th {
            top: 44px;
            z-index: 35;
        }

        /* Spacing inside table cells for readability */
        .lk-sticky-table th,
        .lk-sticky-table td {
            padding: 10px 12px;
            font-size: 0.85rem;
            transition: background-color 0.2s ease;
        }

        /* Input styles */
        .lk-input {
            padding: 8px 10px !important;
            font-size: 0.85rem;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) inset;
        }

        .lk-input:focus {
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15), 0 1px 2px rgba(0, 0, 0, 0.02) inset;
            border-color: rgba(249, 115, 22, 0.8) !important;
            outline: none;
        }

        .lk-input:read-only {
            background: #f8fafc;
            border-color: #f1f5f9;
            font-weight: 600;
            color: #475569;
            box-shadow: none;
        }

        .lk-input:read-only:focus {
            box-shadow: none;
            border-color: #f1f5f9 !important;
        }

        /* Buttons: consistent variants */
        .btn-primary {
            background: #0f172a;
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            border: 1px solid rgba(15, 23, 42, 0.08);
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            filter: brightness(0.95);
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.15);
        }

        /* Zebra rows and hover for table body */
        .lk-sticky-table tbody tr:nth-child(even) {
            background: #fafaf9;
        }

        .lk-sticky-table tbody tr:hover {
            background: #fff7ed;
        }

        /* Footer totals emphasize */
        .lk-sticky-table tfoot td {
            font-weight: 800;
            font-size: 0.9rem;
            color: #1e293b;
            border-top: 2px solid #cbd5e1;
        }

        /* Make small text slightly larger for accessibility */
        .text-xs {
            font-size: 0.8rem;
        }

        /* Segmented Control for Tabs */
        .segmented-control {
            display: inline-flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 9999px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .segmented-btn {
            padding: 8px 24px;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s ease;
        }

        .segmented-btn.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .segmented-btn:not(.active):hover {
            color: #334155;
        }

        /* Header styling */
        .header-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            box-shadow: 0 4px 20px -5px rgba(15, 23, 42, 0.05);
            padding: 1.25rem 1.5rem;
        }
    </style>
    <div class="bg-slate-50 flex flex-col p-2 md:p-4 space-y-4 overflow-hidden lk-page min-h-0">
        <div class="header-card flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">
            <div class="flex flex-col gap-1.5">
                <p class="text-[11px] font-bold text-orange-500 uppercase tracking-widest flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Lembar Kerja
                </p>
                <h1 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight">
                    {{ $subKategori->nama_sub_kategori }}
                    <span class="font-normal text-slate-400 mx-1">|</span>
                    <span class="text-slate-600">{{ $jenisLabel }}</span>
                </h1>
                <p class="text-sm font-medium text-slate-500 leading-tight">
                    {{ $subKategori->kategori->nama_kategori ?? 'Kategori' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <form method="get"
                    class="flex flex-wrap items-center gap-2 bg-slate-100/50 p-1.5 rounded-2xl border border-slate-200 shadow-sm">
                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                    @if(!empty($isProvinsiRole) && $isProvinsiRole && !empty($wilayahList) && $wilayahList->count())
                        <select name="wilayah_id"
                            class="px-3 py-2 text-sm font-medium rounded-xl border-none outline-none ring-1 ring-inset ring-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-inset focus:ring-orange-400 cursor-pointer">
                            @foreach($wilayahList as $w)
                                <option value="{{ $w->id_wilayah }}" {{ (int) $selectedWilayahId === (int) $w->id_wilayah ? 'selected' : '' }}>
                                    {{ $w->nama_wilayah }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    <select name="id_tahun"
                        class="px-3 py-2 text-sm font-medium rounded-xl border-none outline-none ring-1 ring-inset ring-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-inset focus:ring-orange-400 cursor-pointer">
                        @foreach($tahunList as $t)
                            <option value="{{ $t->id_tahun }}" {{ (int) $tahunId === (int) $t->id_tahun ? 'selected' : '' }}>
                                {{ $t->tahun }}
                            </option>
                        @endforeach
                    </select>
                    <select name="id_periode"
                        class="px-3 py-2 text-sm font-medium rounded-xl border-none outline-none ring-1 ring-inset ring-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-inset focus:ring-orange-400 cursor-pointer">
                        @foreach($periodeList as $p)
                            <option value="{{ $p->id_periode }}" {{ (int) $periodeId === (int) $p->id_periode ? 'selected' : '' }}>
                                {{ $p->nama_periode }}
                            </option>
                        @endforeach
                    </select>
                    <button
                        class="px-4 py-2 text-sm font-bold rounded-xl bg-slate-800 text-white shadow-sm hover:bg-slate-700 transition-colors">
                        Terapkan
                    </button>
                </form>

                <a href="{{ url('/lembar-kerja?jenis=' . $jenis) }}"
                    class="px-4 py-2 rounded-xl text-sm font-bold bg-white text-slate-600 border border-slate-200 shadow-sm hover:bg-slate-50 transition-colors flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>

                <a href="{{ route('rekon_lk', [
        'jenis' => $jenis,
        'wilayah_id' => $selectedWilayahId ?? null,
        'id_sub_kategori' => $subKategori->id_sub_kategori ?? null,
        'id_kategori' => $subKategori->kategori_id ?? null
    ]) }}" target="_blank" rel="noopener"
                    class="px-4 py-2 rounded-xl text-sm font-bold bg-white text-orange-600 border border-orange-200 shadow-sm hover:bg-orange-50 transition-colors flex items-center gap-1.5 ring-1 ring-inset ring-orange-500/10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Rekap LK
                </a>
            </div>
        </div>

        <div class="flex flex-col gap-4 min-h-0">
            <div class="flex justify-center md:justify-start">
                <div class="segmented-control">
                    <button id="tab-berlaku" type="button" class="segmented-btn active">Berlaku</button>
                    <button id="tab-konstan" type="button" class="segmented-btn">Konstan</button>
                </div>
            </div>

            {{-- TABEL BERLAKU (Visible by default) --}}
            <div id="card-berlaku"
                class="bg-white border border-slate-200/70 rounded-2xl p-2 md:p-3 flex flex-col lk-card min-h-0">
                <form method="post" action="{{ route('lembar_kerja.detail.save', $subKategori->id_sub_kategori) }}"
                    class="mt-1 flex flex-col min-h-0">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                    @if($lembarKerja)
                        <input type="hidden" name="lembar_kerja_id" value="{{ $lembarKerja->id }}">
                    @endif
                    <input type="hidden" id="tipe_pdrb_input_berlaku" name="tipe_pdrb" value="berlaku">
                    @if(!empty($isProvinsiRole) && $isProvinsiRole)
                        <input type="hidden" name="wilayah_id" value="{{ $selectedWilayahId }}">
                    @endif

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-1">
                        <div class="flex items-center gap-2">
                            <input type="number" id="lk-row-count-berlaku" min="1" value="1"
                                class="w-16 px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-400 text-center shadow-sm">
                            <button type="button" id="lk-add-rows-berlaku"
                                class="px-4 py-2 text-sm font-bold rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-orange-300 hover:bg-orange-50 hover:text-orange-700 transition-colors shadow-sm flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Baris
                            </button>
                            @if($lembarKerja)
                                <button
                                    class="px-5 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-500 hover:to-orange-400 text-white shadow-md hover:shadow-lg transform transition-all hover:-translate-y-0.5 flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Simpan Data
                                </button>
                            @endif
                        </div>
                    </div>

                    @php
                        $sectorView = 'lembar_kerja.sectors.' . $subKategori->id_sub_kategori;
                        $useSectorView = view()->exists($sectorView);
                        $schemaPath = config_path('lk_templates/' . $template . '.json');
                        $useDynamic = file_exists($schemaPath);
                    @endphp
                    @if($useSectorView)
                        @include($sectorView, ['tipePdrb' => 'berlaku', 'subKategori' => $subKategori])
                    @elseif($useDynamic)
                        @include('lembar_kerja.partials.tabel_dynamic', ['tipePdrb' => 'berlaku', 'template' => $template])
                    @else
                        @include('lembar_kerja.partials.tabel_' . $template, ['tipePdrb' => 'berlaku'])
                    @endif
                </form>
            </div>

            {{-- TABEL KONSTAN (Hidden by default) --}}
            <div id="card-konstan"
                class="hidden bg-white border border-slate-200/70 rounded-2xl p-2 md:p-3 flex flex-col lk-card min-h-0">
                <form method="post" action="{{ route('lembar_kerja.detail.save', $subKategori->id_sub_kategori) }}"
                    class="mt-1 flex flex-col min-h-0">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                    @if($lembarKerja)
                        <input type="hidden" name="lembar_kerja_id" value="{{ $lembarKerja->id }}">
                    @endif
                    <input type="hidden" id="tipe_pdrb_input_konstan" name="tipe_pdrb" value="konstan">
                    @if(!empty($isProvinsiRole) && $isProvinsiRole)
                        <input type="hidden" name="wilayah_id" value="{{ $selectedWilayahId }}">
                    @endif

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-1">
                        <div class="flex items-center gap-2">
                            <input type="number" id="lk-row-count-konstan" min="1" value="1"
                                class="w-16 px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-400 text-center shadow-sm">
                            <button type="button" id="lk-add-rows-konstan"
                                class="px-4 py-2 text-sm font-bold rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-orange-300 hover:bg-orange-50 hover:text-orange-700 transition-colors shadow-sm flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Baris
                            </button>
                            @if($lembarKerja)
                                <button
                                    class="px-5 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-500 hover:to-orange-400 text-white shadow-md hover:shadow-lg transform transition-all hover:-translate-y-0.5 flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Simpan Data
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($useSectorView)
                        @include($sectorView, ['tipePdrb' => 'konstan', 'subKategori' => $subKategori])
                    @elseif($useDynamic)
                        @include('lembar_kerja.partials.tabel_dynamic', ['tipePdrb' => 'konstan', 'template' => $template])
                    @else
                        @include('lembar_kerja.partials.tabel_' . $template, ['tipePdrb' => 'konstan'])
                    @endif
                </form>
            </div>
        </div>

        <form id="delete-item-form" method="post"
            action="{{ route('lembar_kerja.detail.delete_item', $subKategori->id_sub_kategori) }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="jenis" value="{{ $jenis }}">
            @if($lembarKerja)
                <input type="hidden" name="lembar_kerja_id" value="{{ $lembarKerja->id }}">
            @endif
            <input type="hidden" name="tipe_pdrb" value="{{ $tipePdrb ?? 'berlaku' }}">
            @if(!empty($isProvinsiRole) && $isProvinsiRole)
                <input type="hidden" name="wilayah_id" value="{{ $selectedWilayahId }}">
            @endif
            <input type="hidden" name="komoditas_id" id="delete-komoditas-id" value="">
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Helper functions
                function formatNumber(val, decimals = 6) {
                    if (val === null || val === undefined || val === '') return '';

                    let num = typeof val === 'number' ? val : getRawValue(val);
                    if (isNaN(num)) return val;

                    if (Math.abs(num) > 9e14) num = (num > 0 ? 9e14 : -9e14);

                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: decimals
                    }).format(num);
                }

                function formatNumberFixed(val, decimals = 2) {
                    if (val === null || val === undefined || val === '') return '';

                    let num = typeof val === 'number' ? val : getRawValue(val);
                    if (isNaN(num)) return val;

                    if (Math.abs(num) > 9e14) num = (num > 0 ? 9e14 : -9e14);

                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: decimals
                    }).format(num);
                }

                function getRawValue(val) {
                    if (val === null || val === undefined || val === '') return 0;
                    let str = String(val).trim();

                    // Remove all spaces and leading/trailing junk
                    str = str.replace(/\s/g, '');

                    if (/[eE]/.test(str)) {
                        let num = Number(str);
                        if (!Number.isFinite(num)) return 0;
                        return num;
                    }

                    const hasComma = str.includes(',');
                    const hasDot = str.includes('.');

                    if (hasComma && hasDot) {
                        // Standard Indo: 1.234.567,89
                        // Remove decimals (comma) to a standard dot, and remove thousand dots
                        str = str.replace(/\./g, '').replace(',', '.');
                    } else if (hasComma) {
                        // Only comma: 1234,56
                        str = str.replace(',', '.');
                    } else if (hasDot) {
                        // Only dot: could be 1.234 (thousand) or 1.23 (decimal)
                        // In PDRB context, users rarely type decimals without comma
                        // But if there are multiple dots, they are definitely thousands
                        if ((str.match(/\./g) || []).length > 1) {
                            str = str.replace(/\./g, '');
                        } else {
                            // Single dot. This is the hardest case.
                            // If it's followed by exactly 3 digits, its almost certainly a thousand separator in this app
                            const parts = str.split('.');
                            if (parts[1].length === 3) {
                                str = str.replace(/\./g, '');
                            }
                            // Otherwise treat as decimal
                        }
                    }

                    let num = parseFloat(str) || 0;
                    return num;
                }

                function normalizeNumberForDb(val) {
                    if (val === undefined || val === null || val === '') return '0';
                    let s = String(val);
                    // If it's already a clean float string (e.g. "123.45"), return it
                    if (/^-?\d+(\.\d+)?$/.test(s)) return s;
                    // Otherwise, convert from display format
                    return s.replace(/\./g, '').replace(/,/g, '.');
                }

                function getInputRawValue(input) {
                    if (!input) return 0;
                    const rawStr = input.dataset.rawValue ?? input.value;
                    return getRawValue(rawStr);
                }

                function normalizeDecimalDisplay(val) {
                    if (val === null || val === undefined) return '';
                    let str = String(val).trim();
                    if (str === '') return '';
                    if (str.includes(',')) return str;
                    return str.replace('.', ',');
                }

                function normalizeNumberForDb(val) {
                    if (val === null || val === undefined) return '0';
                    let str = String(val).trim();
                    if (str === '') return '0';

                    if (/[eE]/.test(str)) {
                        const num = Number(str);
                        if (!Number.isFinite(num)) return '0';
                        return String(num);
                    }

                    const hasComma = str.indexOf(',') !== -1;
                    const hasDot = str.indexOf('.') !== -1;

                    if (hasComma && hasDot) {
                        if (str.lastIndexOf(',') > str.lastIndexOf('.')) {
                            str = str.replace(/\./g, '').replace(',', '.');
                        } else {
                            str = str.replace(/,/g, '');
                        }
                    } else if (hasComma) {
                        str = str.replace(/\./g, '').replace(',', '.');
                    } else if (hasDot) {
                        if ((str.match(/\./g) || []).length > 1) {
                            const parts = str.split('.');
                            str = parts.slice(0, -1).join('') + '.' + parts[parts.length - 1];
                        }
                    }

                    str = str.replace(/[^\d.-]/g, '');
                    if (str === '' || str === '-' || str === '.') return '0';
                    return str;
                }

                function setShortDisplay(input) {
                    const rawStr = input.dataset.rawValue ?? input.value;
                    const rawNum = getRawValue(rawStr);
                    const name = input?.name || '';

                    if (rawNum === 0 && !input.readOnly) {
                        input.value = '';
                        return;
                    }

                    if (name.includes('[kuantum]')) {
                        input.value = new Intl.NumberFormat('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }).format(rawNum);
                        return;
                    }

                    if (name.includes('[harga_produsen]')) {
                        input.value = formatNumberFixed(rawNum, 2);
                        return;
                    }

                    let decimals = name.includes('rasio') ? 4 : 2;
                    let div = 1;
                    // Always divide by 1M for display fields or million-unit fields
                    if (input.dataset.unit === 'million' || (input.dataset.calc && input.dataset.calc.endsWith('_display'))) {
                        div = 1000000;
                    }
                    input.value = formatNumber(rawNum / div, decimals);
                }

                // Setup table functionality for a specific table
                function setupTable(tableId, rowsId, addBtnId, countInputId, tipePdrb) {
                    const tableBody = document.getElementById(rowsId);
                    const addBtn = document.getElementById(addBtnId);
                    const countInput = document.getElementById(countInputId);

                    if (!tableBody) return;

                    // Bind paste events
                    function bindPaste() {
                        tableBody.querySelectorAll('.lk-input').forEach((input) => {
                            if (input.dataset.pasteBound) return;
                            input.dataset.pasteBound = '1';
                            input.addEventListener('paste', function (event) {
                                const text = event.clipboardData.getData('text');
                                if (!text) return;

                                event.preventDefault();

                                const startCell = this.closest('td');
                                const startRow = this.closest('tr');
                                const startRowIndex = Array.from(tableBody.children).indexOf(startRow);
                                const startColIndex = Array.from(startRow.children).indexOf(startCell);

                                const rows = text.split(/\r?\n/).filter(r => r.length > 0);

                                rows.forEach((rowText, rIndex) => {
                                    const cols = rowText.split('\t');
                                    cols.forEach((cellText, cIndex) => {
                                        const targetRow = tableBody.children[startRowIndex + rIndex];
                                        if (!targetRow) return;

                                        const targetCell = targetRow.children[startColIndex + cIndex];
                                        if (!targetCell) return;

                                        const input = targetCell.querySelector('input:not([type="hidden"])');
                                        if (!input) return;

                                        input.value = cellText.trim();
                                        input.dispatchEvent(new Event('input', { bubbles: true }));

                                        if (input.classList.contains('number-format')) {
                                            setShortDisplay(input);
                                        }
                                    });
                                });
                            });
                        });
                    }

                    // Focus event
                    tableBody.addEventListener('focus', function (e) {
                        if (e.target.classList.contains('number-format')) {
                            if (e.target.readOnly) return;
                            const rawStr = e.target.dataset.rawValue ?? e.target.value;
                            const num = getRawValue(rawStr);
                            if (num === 0) {
                                e.target.value = '';
                            } else {
                                e.target.value = normalizeDecimalDisplay(rawStr);
                                setTimeout(() => e.target.select(), 10);
                            }
                        }
                    }, true);

                    // Blur event
                    tableBody.addEventListener('blur', function (e) {
                        if (e.target.classList.contains('number-format')) {
                            setShortDisplay(e.target);
                            calculateRow(e.target.closest('tr'), tipePdrb);
                        }
                    }, true);

                    // Input event
                    tableBody.addEventListener('input', function (e) {
                        if (e.target.classList.contains('number-format')) {
                            // Default behavior: Store raw value in its natural unit
                            let rawValue = getRawValue(e.target.value);

                            // Specific for Million-Unit display fields:
                            // We store the FULL Rupiah value in the dataset.rawValue for calculation consistency
                            if (e.target.dataset.unit === 'million' || (e.target.dataset.calc && e.target.dataset.calc.endsWith('_display'))) {
                                const fullRupiah = rawValue * 1000000;
                                e.target.dataset.rawValue = String(fullRupiah);

                                const hidden = e.target.parentElement.querySelector('input[type="hidden"]');
                                if (hidden && (hidden.name.includes('[nilai_output_utama]') || hidden.name.includes('[nilai_produksi]'))) {
                                    hidden.value = String(fullRupiah);
                                }
                            } else {
                                e.target.dataset.rawValue = String(rawValue);
                            }

                            calculateRow(e.target.closest('tr'), tipePdrb);
                        }
                    });

                    // Blur event to ensure formatting
                    tableBody.addEventListener('blur', function (e) {
                        if (e.target.classList.contains('number-format')) {
                            setShortDisplay(e.target);
                        }
                    }, true);

                    // Initialize number formats AND trigger initial calculation to correct stale data
                    tableBody.querySelectorAll('tr[data-template]').forEach((row) => {
                        row.querySelectorAll('.number-format').forEach((input) => {
                            const rawSource = input.dataset.rawValue ?? input.value;
                            // For display fields, the initial value from Blade is likely already in Million unit or Rupiah string
                            // We trust getRawValue to parse it correctly, but we must ensure display fields get the Full rawValue
                            if (input.dataset.unit === 'million' || (input.dataset.calc && input.dataset.calc.endsWith('_display'))) {
                                // If the value looks like it was already in million (small number but meant to be Juta),
                                // or just parse what's there and let calculateRow override it.
                            }
                            input.dataset.rawValue = getRawValue(rawSource);
                        });

                        // Mandatory recalculation on load to override any capped/stale data from previous sessions
                        calculateRow(row, tipePdrb);

                        // Re-format after calculation
                        row.querySelectorAll('.number-format').forEach((input) => {
                            setShortDisplay(input);
                        });
                    });

                    // Add rows functionality
                    if (addBtn && countInput) {
                        addBtn.addEventListener('click', () => {
                            const current = parseInt(tableBody.dataset.nextRow || '0', 10);
                            const addCount = Math.max(1, parseInt(countInput?.value || '1', 10));

                            const templateId = 'template-row-{{ $template ?? "default" }}';
                            const template = document.getElementById(templateId);

                            if (!template) {
                                console.error('Template not found:', templateId);
                                return;
                            }

                            for (let i = 0; i < addCount; i++) {
                                const rowIndex = current + i;
                                const clone = template.content.cloneNode(true);

                                // Replace INDEX and RANK_INDEX in attributes and text
                                const inputs = clone.querySelectorAll('[name*="INDEX"]');
                                inputs.forEach(input => {
                                    input.name = input.name.replace(/INDEX/g, rowIndex);
                                });

                                const rankCell = clone.querySelector('.row-index');
                                if (rankCell) {
                                    rankCell.textContent = rowIndex + 1;
                                }

                                // Enable / Disable fields based on isBaseYear
                                const isBaseYear = {{ ($isBaseYear ?? true) ? 'true' : 'false' }};
                                if (!isBaseYear) {
                                    // Specific fields that should be disabled if not base year might be manually defined or by schema
                                    // For dynamic templates, we could add data attributes, but for now we expect the schema to handle it if possible.
                                }

                                tableBody.appendChild(clone);
                            }

                            tableBody.dataset.nextRow = String(current + addCount);
                            bindPaste();
                            updateTotals(tipePdrb);
                        });
                    }

                    bindPaste();
                }

                function calculateRow(row, tipePdrb) {
                    if (!row) return;

                    // Most modern templates use lk_dynamic_calculator.js (lk-dynamic-input)
                    // If the row contains lk-dynamic-input, we don't need manual logic here.
                    if (row.querySelector('.lk-dynamic-input')) {
                        return;
                    }

                    if (row.dataset.template === 'peternakan') {


                        calculatePeternakanRow(row, tipePdrb);
                        return;
                    }

                    if (row.dataset.template === 'jasa_pertanian') {
                        calculateJasaPertanianRow(row, tipePdrb);
                        return;
                    }

                    if (row.dataset.template === 'kehutanan') {
                        calculateKehutananRow(row, tipePdrb);
                        return;
                    }

                    if (row.dataset.template === 'perikanan') {
                        calculatePerikananRow(row, tipePdrb);
                        return;
                    }

                    const kuantum = getInputRawValue(row.querySelector('[name*="[kuantum]"]'));
                    const harga = getInputRawValue(row.querySelector('[name*="[harga_produsen]"]'));
                    const rasioIkut = getInputRawValue(row.querySelector('[name*="[rasio_output_ikut]"]'));
                    const deflator = getInputRawValue(row.querySelector('[name*="[deflator]"]'));
                    const rasioKonsumsi = getInputRawValue(row.querySelector('[name*="[rasio_konsumsi_antara]"]'));

                    let wipBerlaku = 0;
                    let nilaiWip = 0;

                    if (tipePdrb === 'berlaku') {
                        const biayaRawat = getInputRawValue(row.querySelector('[name*="[biaya_perawatan]"]'));
                        const biayaSblm = getInputRawValue(row.querySelector('[name*="[biaya_sebelumnya]"]'));
                        wipBerlaku = biayaRawat - biayaSblm;

                        // Update WIP Berlaku field
                        const wipBerlakuEl = row.querySelector('[name*="[wip_berlaku]"]');
                        if (wipBerlakuEl) {
                            wipBerlakuEl.dataset.rawValue = String(wipBerlaku / 1000000);
                            wipBerlakuEl.value = formatNumber(wipBerlaku / 1000000, 2);
                        }
                        nilaiWip = wipBerlaku;
                    } else {
                        // For konstan, wip_berlaku is readonly, just read it
                        const wipBerlakuEl = row.querySelector('[name*="[wip_berlaku]"]');
                        if (wipBerlakuEl) {
                            // value is stored in million, let's keep it consistent
                            wipBerlaku = getInputRawValue(wipBerlakuEl) * 1000000;
                        }
                        nilaiWip = deflator !== 0 ? (wipBerlaku / deflator) * 100 : 0;

                        // Isi Nilai WIP (nilai konstan)
                        const wipEl = row.querySelector('[name*="[wip]"]');
                        if (wipEl) {
                            wipEl.dataset.rawValue = String(nilaiWip / 1000000);
                            wipEl.value = formatNumber(nilaiWip / 1000000, 2);
                        }
                    }

                    const setComputed = (selector, rawNumJuta) => {
                        const el = row.querySelector(selector);
                        if (!el) return;
                        el.dataset.rawValue = String(rawNumJuta);
                        el.value = formatNumber(rawNumJuta, 2);
                    };

                    // Nilai Output Utama = kuantum * harga
                    const nilaiUtama = kuantum * harga;
                    setComputed('[name*="[nilai_output_utama]"]', nilaiUtama / 1000000);

                    // Nilai Output Ikut = nilaiUtama * rasioIkut
                    const nilaiIkut = nilaiUtama * rasioIkut;
                    setComputed('[name*="[nilai_output_ikut]"]', nilaiIkut / 1000000);

                    // Output ADH = nilaiUtama + nilaiIkut + nilaiWip
                    const outputAdh = nilaiUtama + nilaiIkut + nilaiWip;
                    setComputed('[name*="[output_adh]"]', outputAdh / 1000000);

                    // Nilai Konsumsi Antara = outputAdh * rasioKonsumsi
                    const nilaiKonsumsi = outputAdh * rasioKonsumsi;
                    setComputed('[name*="[konsumsi_antara]"]', nilaiKonsumsi / 1000000);

                    // NTB = outputAdh - nilaiKonsumsi
                    const ntb = outputAdh - nilaiKonsumsi;
                    setComputed('[name*="[nilai_ntb]"]', ntb / 1000000);

                    updateTotals(tipePdrb);
                }

                function calculatePeternakanRow(row, tipePdrb) {
                    const popAwal = getInputRawValue(row.querySelector('[data-key="pop_awal"]'));

                    const popAkhir = getInputRawValue(row.querySelector('[data-key="pop_akhir"]'));
                    const impor = getInputRawValue(row.querySelector('[data-key="impor"]'));
                    const ekspor = getInputRawValue(row.querySelector('[data-key="ekspor"]'));

                    const jumlahStokCalc = popAkhir - popAwal + ekspor - impor;
                    const jumlahStokEl = row.querySelector('[data-calc="jumlah_stok"]');
                    let jumlahStok;

                    if (jumlahStokEl && document.activeElement === jumlahStokEl) {
                        jumlahStok = getInputRawValue(jumlahStokEl);
                    } else {
                        // Automatically calculate if any base population data exists
                        if (popAwal !== 0 || popAkhir !== 0 || ekspor !== 0 || impor !== 0) {
                            jumlahStok = jumlahStokCalc;
                            if (jumlahStokEl) {
                                jumlahStokEl.dataset.rawValue = String(jumlahStok);
                                setShortDisplay(jumlahStokEl);
                            }
                        } else {
                            jumlahStok = jumlahStokEl ? getInputRawValue(jumlahStokEl) : 0;
                        }
                    }

                    const beratDaging = getInputRawValue(row.querySelector('[data-key="berat_daging"]'));
                    const konvKarkas = getInputRawValue(row.querySelector('[data-key="konversi_karkas"]'));
                    const pemotonganCalc = konvKarkas !== 0 ? beratDaging / konvKarkas : 0;
                    const pemotonganEl = row.querySelector('[data-calc="pemotongan"]');
                    let pemotongan;

                    if (pemotonganEl && document.activeElement === pemotonganEl) {
                        pemotongan = getInputRawValue(pemotonganEl);
                    } else {
                        if (beratDaging !== 0 || konvKarkas !== 0) {
                            pemotongan = pemotonganCalc;
                            if (pemotonganEl) {
                                pemotonganEl.dataset.rawValue = String(pemotongan);
                                setShortDisplay(pemotonganEl);
                            }
                        } else {
                            pemotongan = pemotonganEl ? getInputRawValue(pemotonganEl) : 0;
                        }
                    }

                    const kuantumTotalCalc = jumlahStok + pemotongan;
                    const kuantumEl = row.querySelector('[data-calc="kuantum_total"]');
                    let kuantumTotal;

                    if (kuantumEl && document.activeElement === kuantumEl) {
                        kuantumTotal = getInputRawValue(kuantumEl);
                    } else {
                        if (jumlahStok !== 0 || pemotongan !== 0) {
                            kuantumTotal = kuantumTotalCalc;
                            if (kuantumEl) {
                                kuantumEl.dataset.rawValue = String(kuantumTotal);
                                setShortDisplay(kuantumEl);
                            }
                        } else {
                            kuantumTotal = kuantumEl ? getInputRawValue(kuantumEl) : 0;
                        }
                    }

                    const harga = getInputRawValue(row.querySelector('[data-key="harga"]'));
                    const nilaiProduksiCalc = kuantumTotal * harga;

                    const hiddenUtamaEl = row.querySelector('[name*="[nilai_output_utama]"]');
                    const displayUtamaEl = row.querySelector('[data-calc="nilai_output_utama_display"]');
                    let nilaiProduksi;

                    if (displayUtamaEl && document.activeElement === displayUtamaEl) {
                        // Being edited manually. Convert visible to raw-Rupiah.
                        nilaiProduksi = getRawValue(displayUtamaEl.value) * 1000000;
                        if (hiddenUtamaEl) hiddenUtamaEl.value = String(nilaiProduksi);
                        displayUtamaEl.dataset.rawValue = String(nilaiProduksi);
                    } else {
                        // NOT focused: Automatic Calculation logic
                        if (kuantumTotal !== 0 || harga !== 0) {
                            // Overwrite any existing value with fresh calculation results
                            nilaiProduksi = kuantumTotal * harga;
                            if (hiddenUtamaEl) hiddenUtamaEl.value = String(nilaiProduksi);
                            if (displayUtamaEl) {
                                displayUtamaEl.dataset.rawValue = String(nilaiProduksi);
                                setShortDisplay(displayUtamaEl);
                            }
                        } else {
                            // No inputs: Fallback to Hidden field source of truth
                            if (hiddenUtamaEl) {
                                nilaiProduksi = getRawValue(hiddenUtamaEl.value);
                                if (displayUtamaEl) {
                                    displayUtamaEl.dataset.rawValue = String(nilaiProduksi);
                                    setShortDisplay(displayUtamaEl);
                                }
                            } else {
                                nilaiProduksi = 0;
                            }
                        }
                    }

                    const rasioIkut = getInputRawValue(row.querySelector('[data-key="rasio_ikut"]'));
                    const nilaiIkut = nilaiProduksi * rasioIkut;
                    const nilaiIkutDisp = row.querySelector('[data-calc="nilai_ikut"]');
                    if (nilaiIkutDisp) nilaiIkutDisp.textContent = formatNumber(nilaiIkut / 1000000, 2);

                    const outputTotal = nilaiProduksi + nilaiIkut;
                    const outputTotalDisp = row.querySelector('[data-calc="output_total"]');
                    if (outputTotalDisp) outputTotalDisp.textContent = formatNumber(outputTotal / 1000000, 2);

                    const rasioKa = getInputRawValue(row.querySelector('[data-key="rasio_ka"]'));
                    const nilaiKa = outputTotal * rasioKa;
                    const nilaiKaDisp = row.querySelector('[data-calc="nilai_ka"]');
                    if (nilaiKaDisp) nilaiKaDisp.textContent = formatNumber(nilaiKa / 1000000, 2);

                    const ntb = outputTotal - nilaiKa;
                    const ntbDisp = row.querySelector('[data-calc="nilai_ntb"]');
                    if (ntbDisp) ntbDisp.textContent = formatNumber(ntb / 1000000, 2);

                    // Hidden fields for submission
                    const hiddenKuantum = row.querySelector('[name*="[kuantum]"]');
                    const hiddenUtama = row.querySelector('[name*="[nilai_output_utama]"]');
                    const hiddenIkut = row.querySelector('[name*="[nilai_output_ikut]"]');
                    const hiddenADH = row.querySelector('[name*="[output_adh]"]');
                    const hiddenNTB = row.querySelector('[name*="[nilai_ntb]"]');
                    const hiddenKA = row.querySelector('[name*="[konsumsi_antara]"]');

                    if (hiddenKuantum && document.activeElement !== kuantumEl) hiddenKuantum.value = normalizeNumberForDb(kuantumTotal);
                    if (hiddenUtama) hiddenUtama.value = normalizeNumberForDb(nilaiProduksi);
                    if (hiddenIkut) hiddenIkut.value = normalizeNumberForDb(nilaiIkut);
                    if (hiddenADH) hiddenADH.value = normalizeNumberForDb(outputTotal);
                    if (hiddenNTB) hiddenNTB.value = normalizeNumberForDb(ntb);
                    if (hiddenKA) hiddenKA.value = normalizeNumberForDb(nilaiKa);

                    updateTotals(tipePdrb);
                }

                function calculateJasaPertanianRow(row, tipePdrb) {
                    const outputUtama = getInputRawValue(row.querySelector('[data-key="output_utama"]'));
                    const rasioJasa = getInputRawValue(row.querySelector('[data-key="rasio_jasa"]'));
                    const rasioKa = getInputRawValue(row.querySelector('[data-key="rasio_ka"]'));

                    const nilaiJasa = outputUtama * rasioJasa;
                    const nilaiKa = rasioKa * nilaiJasa;
                    const ntb = nilaiJasa - nilaiKa;

                    const dispJasa = row.querySelector('[data-calc="nilai_jasa"]');
                    const dispKa = row.querySelector('[data-calc="nilai_ka"]');
                    const dispNtb = row.querySelector('[data-calc="nilai_ntb"]');

                    if (dispJasa) dispJasa.textContent = formatNumber(nilaiJasa, 2);
                    if (dispKa) dispKa.textContent = formatNumber(nilaiKa, 2);
                    if (dispNtb) dispNtb.textContent = formatNumber(ntb, 2);

                    const hiddenADH = row.querySelector('[name*="[output_adh]"]');
                    const hiddenNTB = row.querySelector('[name*="[nilai_ntb]"]');
                    const hiddenKA = row.querySelector('[name*="[konsumsi_antara]"]');

                    if (hiddenADH) {
                        hiddenADH.dataset.rawValue = String(nilaiJasa);
                        hiddenADH.value = formatNumber(nilaiJasa, 2);
                    }
                    if (hiddenNTB) {
                        hiddenNTB.dataset.rawValue = String(ntb);
                        hiddenNTB.value = formatNumber(ntb, 2);
                    }
                    if (hiddenKA) {
                        hiddenKA.dataset.rawValue = String(nilaiKa);
                        hiddenKA.value = formatNumber(nilaiKa, 2);
                    }

                    updateTotals(tipePdrb);
                }

                function calculateKehutananRow(row, tipePdrb) {
                    const kuantum = getInputRawValue(row.querySelector('[data-key="kuantum"]'));
                    const harga = getInputRawValue(row.querySelector('[data-key="harga"]'));
                    const rasioIkut = getInputRawValue(row.querySelector('[data-key="rasio_ikut"]'));
                    const biayaN = getInputRawValue(row.querySelector('[data-key="biaya_n"]'));
                    const biayaNMin1 = getInputRawValue(row.querySelector('[data-key="biaya_n_min_1"]'));
                    const rasioKa = getInputRawValue(row.querySelector('[data-key="rasio_ka"]'));

                    const nilaiUtama = kuantum * harga;
                    const nilaiIkut = nilaiUtama * rasioIkut;
                    const nilaiWip = biayaN - biayaNMin1;
                    const outputADH = nilaiUtama + nilaiIkut + nilaiWip;
                    const nilaiKA = (outputADH - nilaiWip) * rasioKa;
                    const ntb = outputADH - nilaiKA;

                    const dispUtama = row.querySelector('[data-calc="nilai_utama"]');
                    const dispIkut = row.querySelector('[data-calc="nilai_ikut"]');
                    const dispWip = row.querySelector('[data-calc="nilai_wip"]');
                    const dispADH = row.querySelector('[data-calc="output_adh"]');
                    const dispKA = row.querySelector('[data-calc="nilai_ka"]');
                    const dispNTB = row.querySelector('[data-calc="nilai_ntb"]');

                    if (dispUtama) dispUtama.textContent = formatNumber(nilaiUtama / 1000000, 2);
                    if (dispIkut) dispIkut.textContent = formatNumber(nilaiIkut / 1000000, 2);
                    if (dispWip) dispWip.textContent = formatNumber(nilaiWip / 1000000, 2);
                    if (dispADH) dispADH.textContent = formatNumber(outputADH / 1000000, 2);
                    if (dispKA) dispKA.textContent = formatNumber(nilaiKA / 1000000, 2);
                    if (dispNTB) dispNTB.textContent = formatNumber(ntb / 1000000, 2);
                    // Hidden fields for submission
                    const hiddenUtama = row.querySelector('[name*="[nilai_output_utama]"]');
                    const hiddenIkut = row.querySelector('[name*="[nilai_output_ikut]"]');
                    const hiddenADH = row.querySelector('[name*="[output_adh]"]');
                    const hiddenNTB = row.querySelector('[name*="[nilai_ntb]"]');
                    const hiddenKA = row.querySelector('[name*="[konsumsi_antara]"]');

                    if (hiddenUtama) hiddenUtama.value = normalizeNumberForDb(nilaiUtama);
                    if (hiddenIkut) hiddenIkut.value = normalizeNumberForDb(nilaiIkut);
                    if (hiddenADH) hiddenADH.value = normalizeNumberForDb(outputADH);
                    if (hiddenNTB) hiddenNTB.value = normalizeNumberForDb(ntb);
                    if (hiddenKA) hiddenKA.value = normalizeNumberForDb(nilaiKA);

                    updateTotals(tipePdrb);
                }

                function calculatePerikananRow(row, tipePdrb) {
                    calculateKehutananRow(row, tipePdrb);
                }

                function updateTotals(tipePdrb) {
                    const rowsId = tipePdrb === 'berlaku' ? '#lk-rows-berlaku tr' : '#lk-rows-konstan tr';
                    const totalPrefix = tipePdrb === 'berlaku' ? '' : '-konstan';

                    let totalKuantum = 0;
                    let totalNilaiUtama = 0;
                    let totalNilaiIkut = 0;
                    let totalOutputAdh = 0;
                    let totalKonsumsi = 0;
                    let totalNtb = 0;
                    let totalJumlahStok = 0;
                    let totalPemotongan = 0;

                    document.querySelectorAll(rowsId).forEach((row) => {
                        const kuantumInput = row.querySelector('[name*="[kuantum]"]');
                        const nilaiUtamaInput = row.querySelector('[name*="[nilai_output_utama]"]');
                        const nilaiIkutInput = row.querySelector('[name*="[nilai_output_ikut]"]');
                        const outputAdhInput = row.querySelector('[name*="[output_adh]"]');
                        const konsumsiInput = row.querySelector('[name*="[konsumsi_antara]"]');
                        const ntbInput = row.querySelector('[name*="[nilai_ntb]"]');

                        if (kuantumInput) totalKuantum += getInputRawValue(kuantumInput);
                        if (nilaiUtamaInput) {
                            totalNilaiUtama += getInputRawValue(nilaiUtamaInput);
                        }
                        if (nilaiIkutInput) totalNilaiIkut += getInputRawValue(nilaiIkutInput);
                        if (outputAdhInput) totalOutputAdh += getInputRawValue(outputAdhInput);
                        if (konsumsiInput) totalKonsumsi += getInputRawValue(konsumsiInput);
                        if (ntbInput) totalNtb += getInputRawValue(ntbInput);

                        const jStok = row.querySelector('[data-calc="jumlah_stok"]');
                        if (jStok) totalJumlahStok += getInputRawValue(jStok);
                        const pMotongan = row.querySelector('[data-calc="pemotongan"]');
                        if (pMotongan) totalPemotongan += getInputRawValue(pMotongan);
                    });

                    const setTotal = (selector, value, shouldDivide = true) => {
                        const cells = document.querySelectorAll(selector);
                        cells.forEach(cell => {
                            cell.textContent = formatNumber(shouldDivide ? value / 1000000 : value, 2);
                        });
                    };

                    setTotal(`[data-total${totalPrefix}="kuantum"]`, totalKuantum, false);
                    setTotal(`[data-total${totalPrefix}="nilai_output_utama"]`, totalNilaiUtama);
                    setTotal(`[data-total${totalPrefix}="nilai_output_ikut"]`, totalNilaiIkut);
                    setTotal(`[data-total${totalPrefix}="output_adh"]`, totalOutputAdh);
                    setTotal(`[data-total${totalPrefix}="konsumsi_antara"]`, totalKonsumsi);
                    setTotal(`[data-total${totalPrefix}="nilai_ntb"]`, totalNtb);
                    setTotal(`[data-total${totalPrefix}="jumlah_stok"]`, totalJumlahStok, false);
                    setTotal(`[data-total${totalPrefix}="pemotongan"]`, totalPemotongan, false);

                    // Update Effective Ratios
                    const rasioIkut = totalNilaiUtama > 0 ? totalNilaiIkut / totalNilaiUtama : 0;
                    const rasioKonsumsi = totalOutputAdh > 0 ? totalKonsumsi / totalOutputAdh : 0;

                    const setTotalRasio = (selector, value) => {
                        const cell = document.querySelector(selector);
                        if (!cell) return;
                        cell.textContent = value.toLocaleString('id-ID', {
                            minimumFractionDigits: 4,
                            maximumFractionDigits: 15
                        });
                    };

                    setTotalRasio(`[data-total${totalPrefix}="rasio_output_ikut"]`, rasioIkut);
                    setTotalRasio(`[data-total${totalPrefix}="rasio_konsumsi_antara"]`, rasioKonsumsi);
                }

                // Setup both tables
                setupTable('card-berlaku', 'lk-rows-berlaku', 'lk-add-rows-berlaku', 'lk-row-count-berlaku', 'berlaku');
                setupTable('card-konstan', 'lk-rows-konstan', 'lk-add-rows-konstan', 'lk-row-count-konstan', 'konstan');

                // Delete functionality
                const deleteForm = document.getElementById('delete-item-form');
                const deleteInput = document.getElementById('delete-komoditas-id');
                document.querySelectorAll('[data-delete-komoditas]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const id = btn.getAttribute('data-delete-komoditas');
                        if (!id || !deleteForm || !deleteInput) return;
                        if (!confirm('Hapus baris komoditas ini?')) return;
                        deleteInput.value = id;
                        deleteForm.submit();
                    });
                });

                // Tabs functionality
                const tabBerlakuBtn = document.getElementById('tab-berlaku');
                const tabKonstanBtn = document.getElementById('tab-konstan');
                const cardBerlaku = document.getElementById('card-berlaku');
                const cardKonstan = document.getElementById('card-konstan');

                function activateTab(tab) {
                    if (!tabBerlakuBtn || !tabKonstanBtn) return;

                    if (tab === 'konstan') {
                        cardKonstan.classList.remove('hidden');
                        cardBerlaku.classList.add('hidden');
                        tabKonstanBtn.classList.add('active');
                        tabBerlakuBtn.classList.remove('active');
                        updateTotals('konstan');
                    } else {
                        cardBerlaku.classList.remove('hidden');
                        cardKonstan.classList.add('hidden');
                        tabBerlakuBtn.classList.add('active');
                        tabKonstanBtn.classList.remove('active');
                        updateTotals('berlaku');
                    }
                }

                // When clicking a tab, reload page with tipe_pdrb set so server provides correct data
                tabBerlakuBtn?.addEventListener('click', () => {
                    try {
                        const url = new URL(window.location.href);
                        url.searchParams.set('tipe_pdrb', 'berlaku');
                        window.location.href = url.toString();
                    } catch (e) {
                        activateTab('berlaku');
                    }
                });

                tabKonstanBtn?.addEventListener('click', () => {
                    try {
                        const url = new URL(window.location.href);
                        url.searchParams.set('tipe_pdrb', 'konstan');
                        window.location.href = url.toString();
                    } catch (e) {
                        activateTab('konstan');
                    }
                });

                // Initialize based on server value
                const initialTipe = '{{ $tipePdrb ?? 'berlaku' }}';
                activateTab(initialTipe);

                // Ensure form submits with correct tipe_pdrb
                document.getElementById('card-berlaku')?.querySelector('form')?.addEventListener('submit', function () {
                    this.querySelectorAll('.number-format').forEach((input) => {
                        const rawStr = input.dataset.rawValue ?? input.value;
                        if (rawStr !== undefined && rawStr !== null) {
                            input.value = normalizeNumberForDb(rawStr);
                        }
                    });
                });

                document.getElementById('card-konstan')?.querySelector('form')?.addEventListener('submit', function () {
                    this.querySelectorAll('.number-format').forEach((input) => {
                        const rawStr = input.dataset.rawValue ?? input.value;
                        if (rawStr !== undefined && rawStr !== null) {
                            input.value = normalizeNumberForDb(rawStr);
                        }
                    });
                });
            });
        </script>
    </div>
    <script src="{{ asset('js/lk_dynamic_calculator.js') }}"></script>
@endsection