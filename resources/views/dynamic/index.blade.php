@extends('layouts.main')

@section('title', 'Tabel Dinamis PDRB')

@section('content')
<style>
    /* Custom Scrollbar ala Sirambo */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
        border: 2px solid #f8fafc;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Memastikan tabel tidak terpotong dan bisa di-scroll ke samping */
    #dynamic-table-wrapper table {
        width: 100%;
        min-width: 800px; /* Minimal lebar agar scroll muncul di layar kecil */
        border-collapse: separate;
        border-spacing: 0;
    }

    /* Animasi saat hover baris tabel */
    #dynamic-table-wrapper tr:hover td {
        background-color: #f1f5f9;
    }

    /* Tambahan style untuk loading state */
    .loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    #dynamic-table-wrapper {
        position: relative;
        min-height: 400px;
    }
</style>

<div class="p-6 space-y-8 bg-slate-50 min-h-screen font-sans">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Tabel Dinamis PDRB</h1>
            <p class="text-sm text-slate-500 mt-1">Gunakan panel kontrol di bawah untuk memfilter data statistik secara real-time.</p>
        </div>
        <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-2xl shadow-sm border border-slate-200">
             <div class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
             </div>
             <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Sistem Aktif</span>
        </div>
    </div>

    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="bg-slate-50/50 border-b border-slate-200 px-6 py-4">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                Panel Konfigurasi
            </h2>
        </div>

        <div class="p-6">
            <div class="grid lg:grid-cols-12 gap-8">
                <div class="lg:col-span-4 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-7 h-7 bg-blue-600 text-white rounded-lg text-xs font-bold shadow-md shadow-blue-100">1</span>
                        <h3 class="font-bold text-slate-800">Pilih Indikator</h3>
                    </div>
                    
                    @php
                        $indicatorGroups = isset($indicatorOptions) && $indicatorOptions->isNotEmpty() 
                            ? $indicatorOptions->sortBy(fn($item) => sprintf('%03d-%03d', $item['group_order'] ?? 99, $item['item_order'] ?? 99))->groupBy('group')
                            : collect();
                    @endphp

                    <div id="indicator-list-scroll" class="border border-slate-200 rounded-xl p-0 max-h-80 overflow-y-auto bg-white shadow-inner custom-scrollbar">
                        @forelse($indicatorGroups as $groupLabel => $options)
                            <div class="border-b border-slate-100 last:border-0">
                                <div class="sticky top-0 bg-slate-50 px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 border-b border-slate-100 z-10">
                                    {{ $groupLabel }}
                                </div>
                                <div class="p-2 space-y-1">
                                    @foreach($options as $option)
                                        <label class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-blue-50 cursor-pointer transition-all group">
                                            <input type="radio" name="indicator" value="{{ $option['key'] }}"
                                                data-calc="{{ $option['calc'] ?? '' }}" 
                                                class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500 focus:ring-offset-0" 
                                                {{ isset($indicatorSelection) && $indicatorSelection === $option['key'] ? 'checked' : '' }}>
                                            <span class="text-sm font-semibold text-slate-600 group-hover:text-blue-700 transition-colors">
                                                {{ $option['label_short'] ?? $option['label'] }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-slate-400 text-sm">Tidak ada indikator tersedia</div>
                        @endforelse
                    </div>
                </div>

                <div class="lg:col-span-4 space-y-8">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-7 h-7 bg-blue-600 text-white rounded-lg text-xs font-bold shadow-md shadow-blue-100">2</span>
                                <h3 class="font-bold text-slate-800">Tahun</h3>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" id="selectAllYears" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" {{ isset($allYearsSelected) && $allYearsSelected ? 'checked' : '' }}>
                                <span class="text-[11px] font-bold text-slate-400 group-hover:text-blue-600 uppercase transition-colors">Semua</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2 border border-slate-200 rounded-xl p-3 bg-white max-h-40 overflow-y-auto shadow-inner custom-scrollbar">
                            @forelse($tahunList ?? [] as $tahun)
                                <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                                    <input type="checkbox" name="tahun_ids[]" value="{{ $tahun->id_tahun }}"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" 
                                        {{ isset($selectedYears) && in_array($tahun->id_tahun, $selectedYears, true) ? 'checked' : '' }}>
                                    <span class="text-sm text-slate-700 font-medium">{{ $tahun->tahun }}</span>
                                </label>
                            @empty
                                <div class="col-span-2 text-center text-slate-400 text-sm py-2">Tidak ada data tahun</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-7 h-7 bg-blue-600 text-white rounded-lg text-xs font-bold shadow-md shadow-blue-100">3</span>
                                <h3 class="font-bold text-slate-800">Triwulan</h3>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" id="selectAllPeriods" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-[11px] font-bold text-slate-400 group-hover:text-blue-600 uppercase transition-colors">Semua</span>
                            </label>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-4 shadow-inner">
                            <div class="grid grid-cols-2 gap-3">
                                @forelse($periodeList ?? [] as $periode)
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="periode_checks[]" value="{{ $periode->id_periode }}"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" 
                                            {{ isset($selectedPeriodIds) && in_array($periode->id_periode, $selectedPeriodIds, true) ? 'checked' : '' }}>
                                        <span class="text-sm text-slate-600 group-hover:text-blue-600 font-medium transition-colors">{{ $periode->nama_periode }}</span>
                                    </label>
                                @empty
                                    <div class="col-span-2 text-center text-slate-400 text-sm py-2">Tidak ada data periode</div>
                                @endforelse
                            </div>
                            <div class="pt-3 border-t border-slate-200">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="checkbox" name="include_total" value="1"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" 
                                        {{ isset($includeTotal) && $includeTotal ? 'checked' : '' }}>
                                    <span class="text-sm font-bold text-emerald-700 uppercase tracking-tight">Total Tahunan</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center justify-center w-7 h-7 bg-blue-600 text-white rounded-lg text-xs font-bold shadow-md shadow-blue-100">4</span>
                            <h3 class="font-bold text-slate-800">Judul Baris</h3>
                        </div>
                    </div>
                    <div class="border border-slate-200 rounded-xl bg-white shadow-inner overflow-hidden flex flex-col h-[350px]">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em]">Komponen Data</span>
                            <button type="button" id="selectAllComponents" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 transition-colors uppercase">Pilih Semua</button>
                        </div>
                        <div class="p-2 overflow-y-auto flex-grow custom-scrollbar">
                            @if(isset($rowMode) && $rowMode === 'wilayah')
                                @forelse($rowItems ?? [] as $wil)
                                    <label class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer group">
                                        <input type="checkbox" name="component_ids[]" value="{{ $wil->id_wilayah }}"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" 
                                            {{ empty($selectedComponentIds) || in_array($wil->id_wilayah, $selectedComponentIds, true) ? 'checked' : '' }}>
                                        <span class="text-sm text-slate-700 group-hover:text-slate-900">{{ $wil->nama_wilayah }}</span>
                                    </label>
                                @empty
                                    <div class="text-center text-slate-400 text-sm py-4">Tidak ada data wilayah</div>
                                @endforelse
                            @elseif(isset($rowMode) && $rowMode === 'sub')
                                @forelse($subFiltered ?? [] as $subRow)
                                    <label class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer group">
                                        <input type="checkbox" name="component_ids[]" value="{{ $subRow->id_sub_kategori }}"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" 
                                            {{ empty($selectedComponentIds) || in_array($subRow->id_sub_kategori, $selectedComponentIds, true) ? 'checked' : '' }}>
                                        <span class="text-sm text-slate-700 group-hover:text-slate-900">{{ $subRow->nama_sub_kategori }}</span>
                                    </label>
                                @empty
                                    <div class="text-center text-slate-400 text-sm py-4">Tidak ada data sub kategori</div>
                                @endforelse
                            @else
                                @php 
                                    $items = (isset($rowMode) && ($rowMode === 'sektor' || $rowMode === 'sektor_pengeluaran')) 
                                        ? ($rowItems ?? collect()) 
                                        : ($kategoriSelectable ?? collect());
                                @endphp
                                @forelse($items as $item)
                                    <label class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer group">
                                        <input type="checkbox" name="component_ids[]" value="{{ $item->id_sektor ?? $item->id_kategori }}"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" 
                                            {{ empty($selectedComponentIds) || in_array(($item->id_sektor ?? $item->id_kategori), $selectedComponentIds, true) ? 'checked' : '' }}>
                                        <span class="text-sm text-slate-700 leading-tight group-hover:text-slate-900">{{ $item->nama_sektor ?? $item->nama_kategori }}</span>
                                    </label>
                                @empty
                                    <div class="text-center text-slate-400 text-sm py-4">Tidak ada data komponen</div>
                                @endforelse
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-10 pt-6 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-start gap-3 max-w-lg">
                    <div class="mt-1 p-1.5 bg-blue-50 rounded-md">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p id="showTableHint" class="text-xs text-slate-500 leading-relaxed italic">Sistem akan mengolah data berdasarkan parameter yang Anda tentukan di atas. Hasil akan ditampilkan pada tabel di bawah ini.</p>
                </div>
                <button id="showTableBtn" class="w-full md:w-auto px-10 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-xl shadow-blue-200 transition-all active:scale-95 flex items-center justify-center gap-3 group">
                    <span>Tampilkan Tabel</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden min-h-[500px] flex flex-col">
        <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Output Data
            </h3>
        </div>

        <div id="dynamic-table-wrapper" class="w-full overflow-x-auto custom-scrollbar flex-grow bg-white">
            <div class="py-24 flex flex-col items-center justify-center text-center px-6">
                <div class="w-24 h-24 bg-slate-50 rounded-3xl flex items-center justify-center mb-6 border border-slate-100 shadow-sm">
                    <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h4 class="text-slate-800 font-bold text-lg">Siap Menampilkan Data</h4>
                <p class="text-slate-400 text-sm max-w-sm mx-auto mt-2">Pilih konfigurasi data pada panel di atas dan klik tombol tampilkan untuk memuat tabel statistik.</p>
            </div>
        </div>
    </section>
</div>

@include('dynamic.partials.scripts')


@endsection