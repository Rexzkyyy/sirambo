@extends('layouts.main')

@php
    $template = \App\Helpers\PdrbHelper::getTemplate($subKategori->id_sub_kategori);
@endphp

@section('title', 'Lembar Kerja Detail')

@section('content')
    <style>
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
            border-right: 1px solid rgba(226, 232, 240, 1);
            backdrop-filter: blur(8px);
            color: #334155;
            font-weight: 700;
            letter-spacing: 0.025em;
        }

        .lk-sticky-table thead th:last-child {
            border-right: none;
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
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .lk-sticky-table {
            table-layout: fixed;
            width: max-content;
            min-width: 100%;
        }

        .lk-sticky-table td:last-child {
            border-right: none;
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
    </style>
    <div class="bg-slate-50 flex flex-col p-2 md:p-4 space-y-4 overflow-hidden lk-page min-h-0">
        <div class="header-card flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">
            <div class="flex flex-col gap-1">
                <p class="text-[11px] font-bold text-orange-600 uppercase tracking-widest">
                    Lembar Kerja
                </p>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">
                    {{ $subKategori->nama_sub_kategori }} 
                    <span class="font-medium text-slate-400">|</span> 
                    <span class="text-slate-600">{{ $jenisLabel }}</span>
                </h1>
                <p class="text-sm font-semibold text-slate-500">
                    {{ $subKategori->kategori->nama_kategori ?? 'Kategori' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <form method="get" id="lk-filter-form"
                    class="flex flex-wrap items-center gap-2 bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                    <input type="hidden" name="tipe_pdrb" id="tipe_pdrb_filter" value="{{ $tipePdrb ?? 'berlaku' }}">
                    @if(!empty($isProvinsiRole) && $isProvinsiRole && !empty($wilayahList) && $wilayahList->count())
                        <input type="hidden" name="wilayah_id" value="{{ $selectedWilayahId }}">
                        <div class="px-4 py-2 text-sm font-bold text-slate-700 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center gap-2">
                            <span class="text-slate-400 font-medium">Wilayah:</span>
                            {{ optional($wilayahList->firstWhere('id_wilayah', $selectedWilayahId))->nama_wilayah ?? $selectedWilayahId }}
                        </div>
                    @endif
                    <select name="id_tahun" onchange="this.form.submit()"
                        class="px-3 py-2 text-sm font-medium rounded-xl border-none outline-none bg-white shadow-sm cursor-pointer">
                        @foreach($tahunList as $t)
                            <option value="{{ $t->id_tahun }}" {{ (int) $tahunId === (int) $t->id_tahun ? 'selected' : '' }}>
                                {{ $t->tahun }}
                            </option>
                        @endforeach
                    </select>
                    <select name="id_periode" onchange="this.form.submit()"
                        class="px-3 py-2 text-sm font-medium rounded-xl border-none outline-none bg-white shadow-sm cursor-pointer">
                        @foreach($periodeList as $p)
                            <option value="{{ $p->id_periode }}" {{ (int) $periodeId === (int) $p->id_periode ? 'selected' : '' }}>
                                {{ $p->nama_periode }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <div class="flex items-center gap-2">
                    <a href="{{ url('/lembar-kerja?jenis=' . $jenis) }}"
                        class="px-6 py-2 rounded-xl text-sm font-bold bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 transition-all">
                        Kembali
                    </a>

                    <a href="{{ route('rekon_lk', [
                        'jenis' => $jenis,
                        'wilayah_id' => $selectedWilayahId ?? null,
                        'item_key' => 'sub-' . ($subKategori->id_sub_kategori ?? ''),
                        'id_tahun' => $tahunId ?? null,
                        'id_periode' => $periodeId ?? null,
                        'tipe_pdrb' => $tipePdrb ?? 'berlaku'
                    ]) }}" target="_blank" rel="noopener" id="btn-rekap-lk"
                        class="px-6 py-2 rounded-xl text-sm font-bold bg-white text-orange-600 border border-orange-200 hover:bg-orange-50 transition-all">
                        Rekap LK
                    </a>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4 min-h-0">
            <div class="flex justify-center md:justify-start">
                <div class="segmented-control">
                    <button id="tab-berlaku" type="button" class="segmented-btn active">Berlaku</button>
                    <button id="tab-konstan" type="button" class="segmented-btn">Konstan</button>
                </div>
            </div>

            {{-- TABEL BERLAKU --}}
            <div id="card-berlaku"
                class="bg-white border border-slate-200 rounded-2xl p-3 flex flex-col lk-card min-h-0">
                <form method="post" action="{{ route('lembar_kerja.detail.save', $subKategori->id_sub_kategori) }}"
                    class="flex flex-col min-h-0">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                    @if($lembarKerja)
                        <input type="hidden" name="lembar_kerja_id" value="{{ $lembarKerja->id }}">
                    @endif
                    <input type="hidden" id="tipe_pdrb_input_berlaku" name="tipe_pdrb" value="berlaku">
                    @if(!empty($isProvinsiRole) && $isProvinsiRole)
                        <input type="hidden" name="wilayah_id" value="{{ $selectedWilayahId }}">
                    @endif

                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <input type="number" id="lk-row-count-berlaku" min="1" value="1"
                                    class="w-14 px-2 py-2 text-sm font-bold rounded-xl border border-slate-200 text-center">
                                <button type="button" id="lk-add-rows-berlaku"
                                    class="px-5 py-2 text-sm font-bold rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-all">
                                    Tambah Baris
                                </button>
                            </div>
                            @if($lembarKerja)
                                <button class="btn-primary py-2 px-8">
                                    Simpan Data
                                </button>
                            @endif
                        </div>

                        @if($lembarKerja)
                            <button type="button" 
                                onclick="if(confirm('PERHATIAN: Seluruh data pada lembar kerja ini akan dihapus. Lanjutkan?')) document.getElementById('reset-lk-form').submit()"
                                class="px-5 py-2 text-sm font-bold rounded-xl border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-all">
                                <i class="fas fa-trash-alt mr-2"></i> Hapus Seluruh Data
                            </button>
                        @endif
                    </div>

                    @php
                        $template = \App\Helpers\PdrbHelper::getTemplate($subKategori->id_sub_kategori);
                    @endphp
                    @include('lembar_kerja.partials.tabel_dynamic', ['tipePdrb' => 'berlaku', 'template' => $template])
                </form>
            </div>

            {{-- TABEL KONSTAN (Hidden by default) --}}
            <div id="card-konstan"
                class="hidden bg-white border border-slate-200 rounded-2xl p-3 flex flex-col lk-card min-h-0">
                <form method="post" action="{{ route('lembar_kerja.detail.save', $subKategori->id_sub_kategori) }}"
                    class="flex flex-col min-h-0">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                    @if($lembarKerja)
                        <input type="hidden" name="lembar_kerja_id" value="{{ $lembarKerja->id }}">
                    @endif
                    <input type="hidden" id="tipe_pdrb_input_konstan" name="tipe_pdrb" value="konstan">
                    @if(!empty($isProvinsiRole) && $isProvinsiRole)
                        <input type="hidden" name="wilayah_id" value="{{ $selectedWilayahId }}">
                    @endif

                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <input type="number" id="lk-row-count-konstan" min="1" value="1"
                                    class="w-14 px-2 py-2 text-sm font-bold rounded-xl border border-slate-200 text-center">
                                <button type="button" id="lk-add-rows-konstan"
                                    class="px-5 py-2 text-sm font-bold rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-all">
                                    Tambah Baris
                                </button>
                            </div>
                            @if($lembarKerja)
                                <button class="btn-primary py-2 px-8">
                                    Simpan Data
                                </button>
                            @endif
                        </div>

                        @if($lembarKerja)
                            <button type="button" 
                                onclick="if(confirm('PERHATIAN: Seluruh data pada lembar kerja ini akan dihapus. Lanjutkan?')) document.getElementById('reset-lk-form').submit()"
                                class="px-5 py-2 text-sm font-bold rounded-xl border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-all">
                                <i class="fas fa-trash-alt mr-2"></i> Hapus Seluruh Data
                            </button>
                        @endif
                    </div>

                    @include('lembar_kerja.partials.tabel_dynamic', ['tipePdrb' => 'konstan', 'template' => $template])
                </form>
            </div>
        </div>

        <form id="reset-lk-form" method="post"
            action="{{ route('lembar_kerja.detail.reset', $subKategori->id_sub_kategori) }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="jenis" value="{{ $jenis }}">
            @if($lembarKerja)
                <input type="hidden" name="lembar_kerja_id" value="{{ $lembarKerja->id }}">
            @endif
        </form>

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
                // Initialize Tabs functionality
                const tabBerlakuBtn = document.getElementById('tab-berlaku');
                const tabKonstanBtn = document.getElementById('tab-konstan');
                const cardBerlaku = document.getElementById('card-berlaku');
                const cardKonstan = document.getElementById('card-konstan');

                function activateTab(tab, updateUrl = true) {
                    if (!cardBerlaku || !cardKonstan) return;
                    
                    const rekapBtn = document.getElementById('btn-rekap-lk');
                    let rekapUrl = new URL(rekapBtn.href);
                    
                    if (tab === 'konstan') {
                        cardKonstan.classList.remove('hidden');
                        cardBerlaku.classList.add('hidden');
                        tabKonstanBtn?.classList.add('active');
                        tabBerlakuBtn?.classList.remove('active');
                        rekapUrl.searchParams.set('tipe_pdrb', 'konstan');
                        const filterInput = document.getElementById('tipe_pdrb_filter');
                        if (filterInput) filterInput.value = 'konstan';
                    } else {
                        cardBerlaku.classList.remove('hidden');
                        cardKonstan.classList.add('hidden');
                        tabBerlakuBtn?.classList.add('active');
                        tabKonstanBtn?.classList.remove('active');
                        rekapUrl.searchParams.set('tipe_pdrb', 'berlaku');
                        const filterInput = document.getElementById('tipe_pdrb_filter');
                        if (filterInput) filterInput.value = 'berlaku';
                    }
                    
                    rekapBtn.href = rekapUrl.toString();

                    if (updateUrl) {
                        const url = new URL(window.location);
                        url.searchParams.set('tipe_pdrb', tab);
                        window.history.replaceState({}, '', url);
                    }
                }

                // Initial activation based on URL or PHP variable
                const initialTab = new URLSearchParams(window.location.search).get('tipe_pdrb') || '{{ $tipePdrb ?? "berlaku" }}';
                activateTab(initialTab, false);

                if (tabBerlakuBtn && tabKonstanBtn) {
                    tabBerlakuBtn.addEventListener('click', () => activateTab('berlaku'));
                    tabKonstanBtn.addEventListener('click', () => activateTab('konstan'));
                }

                // Initialize Row Addition functionality
                function setupRowAddition(addBtnId, countInputId, rowsId, templateId) {
                    const addBtn = document.getElementById(addBtnId);
                    const countInput = document.getElementById(countInputId);
                    const tableBody = document.getElementById(rowsId);
                    const template = document.getElementById(templateId);

                    if (addBtn && tableBody && template) {
                        addBtn.addEventListener('click', () => {
                            const current = parseInt(tableBody.dataset.nextRow || '0', 10);
                            const addCount = Math.max(1, parseInt(countInput?.value || '1', 10));

                            for (let i = 0; i < addCount; i++) {
                                const rowIndex = current + i;
                                const clone = template.content.cloneNode(true);

                                clone.querySelectorAll('[name*="INDEX"]').forEach(input => {
                                    input.name = input.name.replace(/INDEX/g, rowIndex);
                                });

                                const rankCell = clone.querySelector('.row-index');
                                if (rankCell) rankCell.textContent = rowIndex + 1;

                                tableBody.appendChild(clone);
                            }
                            tableBody.dataset.nextRow = String(current + addCount);
                        });
                    }
                }

                setupRowAddition('lk-add-rows-berlaku', 'lk-row-count-berlaku', 'lk-rows-berlaku', 'template-row-{{ $template }}');
                setupRowAddition('lk-add-rows-konstan', 'lk-row-count-konstan', 'lk-rows-konstan', 'template-row-{{ $template }}');

                // Delete functionality
                const deleteForm = document.getElementById('delete-item-form');
                const deleteInput = document.getElementById('delete-komoditas-id');
                
                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-delete-komoditas]');
                    if (btn) {
                        const id = btn.getAttribute('data-delete-komoditas');
                        if (confirm('Hapus baris komoditas ini?')) {
                            deleteInput.value = id;
                            deleteForm.submit();
                        }
                    }
                });


                // Form submission normalization
                const forms = document.querySelectorAll('form[action*="save"]');
                forms.forEach(form => {
                    form.addEventListener('submit', (e) => {
                        form.querySelectorAll('.lk-dynamic-input').forEach(input => {
                            // Convert to DB format (dot as decimal separator, no thousand separator)
                            const raw = input.dataset.rawValue || input.value;
                            let str = String(raw).replace(/\./g, '').replace(',', '.');
                            input.value = str;
                        });
                    });
                });
            });
        </script>
        <script src="{{ asset('js/lk_dynamic_calculator.js') }}"></script>
    </div>
@endsection